<?php declare(strict_types=1);

/*
 * This file is part of indragunawan/rest-service package.
 *
 * (c) Indra Gunawan <hello@indra.my.id>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IndraGunawan\RestService;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Command\Command;
use GuzzleHttp\Command\CommandInterface;
use GuzzleHttp\Command\ServiceClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use IndraGunawan\RestService\Exception\BadRequestException;
use IndraGunawan\RestService\Exception\CommandException;
use IndraGunawan\RestService\Exception\ValidatorException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * The Guzzle ServiceClient serves as the foundation for creating web service
 * clients that interact with RPC-style APIs.
 */
class ServiceClient implements ServiceClientInterface
{
    /**
     * @var HttpClient HTTP client used to send requests
     */
    private $httpClient;

    /**
     * @var HandlerStack
     */
    private $handlerStack;

    /**
     * @var callable
     */
    private $commandToRequestTransformer;

    /**
     * @var callable
     */
    private $responseToResultTransformer;

    /**
     * @var callable
     */
    private $badResponseExceptionParser;

    /**
     * @param string $specificationFile
     * @param array  $config
     * @param string $cacheDir
     * @param bool   $debug
     *
     * @throws \IndraGunawan\RestService\Exception\InvalidSpecificationException
     */
    public function __construct($specificationFile, array $config = [], $cacheDir = null, $debug = false)
    {
        $this->httpClient = new HttpClient(isset($config['httpClient']) ? $config['httpClient'] : []);
        $this->handlerStack = new HandlerStack();
        $this->handlerStack->setHandler($this->createCommandHandler());

        $service = new Service(
            $specificationFile,
            (isset($config['defaults']) && is_array($config['defaults'])) ? $config['defaults'] : [],
            $cacheDir,
            $debug
        );

        $builder = new Builder($service);

        $this->commandToRequestTransformer = $builder->commandToRequestTransformer();
        $this->responseToResultTransformer = $builder->responseToResultTransformer();
        $this->badResponseExceptionParser = $builder->badResponseExceptionParser();
    }

    public function getHttpClient()
    {
        return $this->httpClient;
    }

    public function getHandlerStack()
    {
        return $this->handlerStack;
    }

    /**
     * {@inheritdoc}
     */
    public function getCommand($name, array $params = [])
    {
        return new Command($name, $params, clone $this->handlerStack);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(CommandInterface $command)
    {
        return $this->executeAsync($command)->wait();
    }

    /**
     * {@inheritdoc}
     */
    public function executeAsync(CommandInterface $command)
    {
        $stack = $command->getHandlerStack() ?: $this->handlerStack;
        $handler = $stack->resolve();

        return $handler($command);
    }

    /**
     * {@inheritdoc}
     */
    public function executeAll($commands, array $options = [])
    {
        // Modify provided callbacks to track results.
        $results = [];
        $options['fulfilled'] = function ($v, $k) use (&$results, $options) {
            if (isset($options['fulfilled'])) {
                $options['fulfilled']($v, $k);
            }
            $results[$k] = $v;
        };
        $options['rejected'] = function ($v, $k) use (&$results, $options) {
            if (isset($options['rejected'])) {
                $options['rejected']($v, $k);
            }
            $results[$k] = $v;
        };

        // Execute multiple commands synchronously, then sort and return the results.
        return $this->executeAllAsync($commands, $options)
            ->then(function () use (&$results) {
                ksort($results);

                return $results;
            })
            ->wait();
    }

    /**
     * {@inheritdoc}
     */
    public function executeAllAsync($commands, array $options = [])
    {
        // Apply default concurrency.
        if (!isset($options['concurrency'])) {
            $options['concurrency'] = 25;
        }

        // Convert the iterator of commands to a generator of promises.
        $commands = Promise\iter_for($commands);
        $promises = function () use ($commands) {
            foreach ($commands as $key => $command) {
                if (!$command instanceof CommandInterface) {
                    throw new \InvalidArgumentException('The iterator must '
                        .'yield instances of '.CommandInterface::class);
                }
                yield $key => $this->executeAsync($command);
            }
        };

        // Execute the commands using a pool.
        return (new Promise\EachPromise($promises(), $options))->promise();
    }

    /**
     * Creates and executes a command for an operation by name.
     *
     * @param string $name Name of the command to execute.
     * @param array  $args Arguments to pass to the getCommand method.
     *
     * @throws \IndraGunawan\RestService\Exception\BadRequestException
     * @throws \IndraGunawan\RestService\Exception\BadResponseException
     * @throws \IndraGunawan\RestService\Exception\CommandException
     *
     * @return ResultInterface|PromiseInterface
     *
     * @see \GuzzleHttp\Command\ServiceClientInterface::getCommand
     */
    public function __call($name, array $args)
    {
        $args = isset($args[0]) ? $args[0] : [];
        if (substr($name, -5) === 'Async') {
            $command = $this->getCommand(substr($name, 0, -5), $args);

            return $this->executeAsync($command);
        } else {
            return $this->execute($this->getCommand($name, $args));
        }
    }

    /**
     * Defines the main handler for commands that uses the HTTP client.
     *
     * @throws \IndraGunawan\RestService\Exception\BadRequestException
     * @throws \IndraGunawan\RestService\Exception\BadResponseException
     * @throws \IndraGunawan\RestService\Exception\CommandException
     *
     * @return callable
     */
    private function createCommandHandler()
    {
        return function (CommandInterface $command) {
            return Promise\coroutine(function () use ($command) {
                // Prepare the HTTP options.
                $opts = $command['@http'] ?: [];

                try {
                    // Prepare the request from the command and send it.
                    $request = $this->transformCommandToRequest($command);
                    $promise = $this->httpClient->sendAsync($request, $opts);

                    // Create a result from the response.
                    $response = (yield $promise);
                    yield $this->transformResponseToResult($response, $request, $command);
                } catch (ValidatorException $e) {
                    throw new BadRequestException($e->getField(), $e->getErrorMessage(), $e);
                } catch (BadResponseException $e) {
                    $this->parseBadResponseException($command, $e);
                } catch (\Exception $e) {
                    throw new CommandException($e->getMessage(), $command, $e);
                }
            });
        };
    }

    /**
     * Transforms a Command object into a Request object.
     *
     * @param CommandInterface $command
     *
     * @return RequestInterface
     */
    private function transformCommandToRequest(CommandInterface $command)
    {
        $transform = $this->commandToRequestTransformer;

        return $transform($command);
    }

    /**
     * Transforms a Response object, also using data from the Request object,
     * into a Result object.
     *
     * @param ResponseInterface $response
     * @param RequestInterface  $request
     *
     * @return ResultInterface
     */
    private function transformResponseToResult(
        ResponseInterface $response,
        RequestInterface $request,
        CommandInterface $command
    ) {
        $transform = $this->responseToResultTransformer;

        return $transform($response, $request, $command);
    }

    /**
     * Parse BadResponseException when retrive response.
     *
     * @param CommandInterface     $command
     * @param BadResponseException $e
     *
     * @throws \IndraGunawan\RestService\Exception\BadResponseException
     */
    private function parseBadResponseException(CommandInterface $command, BadResponseException $e)
    {
        $parser = $this->badResponseExceptionParser;

        $parser($command, $e);
    }
}
