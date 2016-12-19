<?php

namespace IndraGunawan\RestService\Validator;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration.
 */
class SpecificationConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('rest_service');

        $rootNode
            ->validate()
                ->always(function ($v) {
                    foreach ($v['operations'] as $name => $operation) {
                        if (!$operation['requestProtocol']) {
                            $v['operations'][$name]['requestProtocol'] = $v['protocol'];
                        }
                        if (!$operation['responseProtocol']) {
                            $v['operations'][$name]['responseProtocol'] = $v['protocol'];
                        }
                    }

                    return $v;
                })
            ->end()
            ->children()
                ->scalarNode('name')
                    ->cannotBeEmpty()
                    ->defaultNull()
                ->end() // name
                ->scalarNode('version')
                    ->cannotBeEmpty()
                    ->defaultNull()
                ->end() // version
                ->scalarNode('endpoint')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end() // endpoint
                ->scalarNode('description')
                    ->cannotBeEmpty()
                    ->defaultNull()
                ->end() // description
                ->scalarNode('documentationUrl')
                    ->cannotBeEmpty()
                    ->defaultNull()
                ->end() // documentationUrl
                ->scalarNode('protocol')
                    ->cannotBeEmpty()
                    ->defaultValue('rest_json')
                    ->validate()
                        ->ifNotInArray(['rest_json'])
                        ->thenInvalid('Invalid Protocol %s, Available protocols are "rest_json"')
                    ->end()
                ->end() // protocol
                ->append($this->getDefaultNode())
                ->append($this->getOperationNode())
                ->append($this->getShapeNode())
                ->append($this->getErrorShapeNode())
            ->end() // rest_service children
        ;

        return $treeBuilder;
    }

    private function getDefaultNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('defaults');

        $node
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->scalarNode('rule')
                        ->cannotBeEmpty()
                        ->defaultNull()
                    ->end() // rule
                    ->scalarNode('defaultValue')
                        ->cannotBeEmpty()
                        ->defaultValue('')
                    ->end() // defaultValue
                ->end() // default children
            ->end() // defaults prototype
        ;

        return $node;
    }

    private function getOperationNode()
    {
        $treeBuilder = new TreeBuilder();

        $availableHttpMethods = [
            'GET', 'POST', 'PUT', 'PATCH',
            'DELETE', 'HEAD', 'OPTIONS',
        ];

        $node = $treeBuilder
            ->root('operations')
            ->isRequired()
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('name')
        ;

        $operationNode = $node->prototype('array');

        $operationNode
            ->children()
                ->scalarNode('httpMethod')
                    ->isRequired()
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function ($v) {
                            return strtoupper($v);
                        })
                    ->end()
                    ->validate()
                        ->ifNotInArray($availableHttpMethods)
                        ->thenInvalid('Invalid HTTP Method %s, Available methods are "'.implode('", "', $availableHttpMethods).'"')
                    ->end() // validate
                ->end() // httpMethod
                ->scalarNode('requestUri')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->validate()
                        ->ifString()
                        ->then(function ($v) {
                            return '/'.ltrim($v, '/');
                        })
                    ->end()
                ->end()
                ->scalarNode('description')
                    ->cannotBeEmpty()
                    ->defaultNull()
                ->end() // description
                ->scalarNode('documentationUrl')
                    ->cannotBeEmpty()
                    ->defaultNull()
                ->end() // documentationUrl
                ->scalarNode('requestProtocol')
                    ->cannotBeEmpty()
                    ->defaultNull()
                    ->validate()
                        ->ifNotInArray(['rest_json', 'form_params'])
                        ->thenInvalid('Invalid Protocol %s, Available protocols are "rest_json", "form_params"')
                    ->end()
                ->end() // requestProtocol
                ->scalarNode('responseProtocol')
                    ->cannotBeEmpty()
                    ->defaultNull()
                    ->validate()
                        ->ifNotInArray(['rest_json', 'stream'])
                        ->thenInvalid('Invalid Protocol %s, Available protocols are "rest_json", "stream"')
                    ->end()
                ->end() // responseProtocol
                ->booleanNode('strictRequest')
                    ->defaultFalse()
                ->end() // strictRequest
                ->booleanNode('strictResponse')
                    ->defaultFalse()
                ->end() // strictResponse
                ->booleanNode('sentEmptyField')
                    ->defaultTrue()
                ->end()
        ;

        $requestNode = $operationNode
            ->children()
                ->arrayNode('request')
        ;
        $this->addShapeSection($requestNode);

        $responseNode = $operationNode
            ->children()
                ->arrayNode('response')
        ;
        $this->addShapeSection($responseNode);

        $errorsNode = $operationNode
            ->children()
                ->arrayNode('errors')
                    ->prototype('array')
        ;
        $this->addErrorShapeSection($errorsNode);

        return $node;
    }

    private function getShapeNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder
            ->root('shapes')
            ->useAttributeAsKey('name')
        ;

        $this->addShapeSection($node->prototype('array'), false);

        return $node;
    }

    private function getErrorShapeNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder
            ->root('errorShapes')
            ->useAttributeAsKey('name')
        ;

        $this->addErrorShapeSection($node->prototype('array'), false);

        return $node;
    }

    private function addShapeSection(ArrayNodeDefinition $rootNode, $fromOperation = true)
    {
        $availableLocations = ['header', 'uri', 'query', 'body'];
        $availableTypes = ['map', 'list'];
        $availableMemberTypes = ['map', 'list', 'string', 'integer', 'float', 'number', 'boolean', 'datetime'];
        // map = object
        // list = array

        $rootNode
            ->beforeNormalization()
                ->always(function ($v) use ($fromOperation) {
                    if (!$fromOperation) {
                        if (isset($v['shape'])) {
                            throw new InvalidConfigurationException('Unrecognized option "shape"');
                        }
                    }

                    if (isset($v['shape']) && (isset($v['type']) || isset($v['members']))) {
                        throw new InvalidConfigurationException('Cannot combine "shape" with other properties.');
                    }

                    return $v;
                })
            ->end()
            ->children()
                ->scalarNode('shape')
                    ->cannotBeEmpty()
                    ->defaultNull()
                ->end()
                ->scalarNode('extends')
                    ->cannotBeEmpty()
                    ->defaultNull()
                ->end()
                ->scalarNode('type')
                    ->cannotBeEmpty()
                    ->defaultValue('map')
                    ->validate()
                        ->ifNotInArray($availableTypes)
                        ->thenInvalid('Invalid type %s, Available types are "'.implode('", "', $availableTypes).'"')
                    ->end() // validate
                ->end() // type
                ->arrayNode('members')
                    ->beforeNormalization()
                        ->ifArray()
                        ->then(function ($v) {
                            foreach ($v as $name => $member) {
                                if (!isset($member['locationName'])) {
                                    $v[$name]['locationName'] = $name;
                                }

                                if (isset($member['shape'])
                                    && (isset($member['location'])
                                        || isset($member['defaultValue'])
                                        || isset($member['format'])
                                    )
                                ) {
                                    throw new InvalidConfigurationException(sprintf(
                                        'Member "%s". Cannot combine "shape" with other properties.',
                                        $name
                                    ));
                                }

                                if (isset($member['shape']) && isset($member['type'])) {
                                    if (!in_array($member['type'], ['map', 'list'])) {
                                        throw new InvalidConfigurationException('type for shape only "map", "list"');
                                    }
                                } elseif (isset($member['type'])
                                    && !in_array($member['type'], ['string', 'datetime'])
                                    && isset($member['format'])
                                ) {
                                    throw new InvalidConfigurationException('"format" only for "string" or "datetime"');
                                }
                            }

                            return $v;
                        })
                    ->end()
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('shape')
                                ->cannotBeEmpty()
                                ->defaultNull()
                            ->end() // shape
                            ->scalarNode('type')
                                ->cannotBeEmpty()
                                ->defaultValue('string')
                                ->validate()
                                    ->ifNotInArray($availableMemberTypes)
                                    ->thenInvalid('Invalid type %s, Available types are "'.implode('", "', $availableMemberTypes).'"')
                                ->end() // validate
                            ->end() // type
                            ->scalarNode('format')
                                ->cannotBeEmpty()
                                ->defaultNull()
                            ->end() // format
                            ->scalarNode('location')
                                ->defaultValue('body')
                                ->cannotBeEmpty()
                                ->validate()
                                    ->ifNotInArray($availableLocations)
                                    ->thenInvalid('Invalid shape location %s, Available methods are "'.implode('", "', $availableLocations).'"')
                                ->end() // validate
                            ->end() // location
                            ->scalarNode('locationName')
                                ->defaultNull()
                                ->cannotBeEmpty()
                            ->end() // locationName
                            ->scalarNode('rule')
                                ->defaultNull()
                                ->cannotBeEmpty()
                            ->end() // rule
                            ->scalarNode('defaultValue')
                                ->defaultValue('')
                                ->cannotBeEmpty()
                            ->end()
                        ->end() // member children
                    ->end() // members prototype
                ->end() // members
            ->end() // end
        ;
    }

    private function addErrorShapeSection(ArrayNodeDefinition $rootNode, $fromOperation = true)
    {
        $availableOperators = ['===', '!==', '==', '!=', '<', '<=', '>=', '>'];
        $rootNode
            ->beforeNormalization()
                ->always(function ($v) use ($fromOperation) {
                    if (!$fromOperation) {
                        if (isset($v['errorShape'])) {
                            throw new InvalidConfigurationException('Unrecognized option "errorShape"');
                        }
                    }

                    if (isset($v['errorShape'])
                        && (isset($v['type'])
                            || isset($v['codeField'])
                            || isset($v['ifCode'])
                            || isset($v['messageField'])
                            || isset($v['defaultMessage'])
                        )
                    ) {
                        throw new InvalidConfigurationException('Cannot combine "shape" with other properties.');
                    }

                    if (isset($v['type'])) {
                        if ('field' === $v['type'] && !isset($v['codeField'])) {
                            throw new InvalidConfigurationException('Error type "field", plesae provide "codeField" option.');
                        } elseif ('httpStatusCode' === $v['type'] && isset($v['codeField'])) {
                            throw new InvalidConfigurationException('Error type "httpStatusCode", Unrecognized option "codeField".');
                        }
                    }

                    return $v;
                })
            ->end()
            ->children()
                ->scalarNode('errorShape')
                    ->cannotBeEmpty()
                    ->defaultNull()
                ->end() // shape
                ->scalarNode('type')
                    ->cannotBeEmpty()
                    ->validate()
                        ->ifNotInArray(['httpStatusCode', 'field'])
                        ->thenInvalid('Invalid error type %s, Available error types are "httpStatusCode", "field"')
                    ->end() // validate
                ->end() // type
                ->scalarNode('codeField') // if type = field
                    ->cannotBeEmpty()
                    ->defaultNull()
                ->end() // codeField
                ->scalarNode('ifCode')
                    ->cannotBeEmpty()
                    ->defaultNull()
                ->end() // ifCode
                ->scalarNode('operator')
                    ->cannotBeEmpty()
                    ->defaultValue('==')
                    ->validate()
                        ->ifNotInArray($availableOperators)
                        ->thenInvalid('Invalid operator type %s, Available operators are "'.implode('", "', $availableOperators).'"')
                    ->end() // validate
                ->end() // operator
                ->scalarNode('messageField')
                    ->cannotBeEmpty()
                    ->defaultNull()
                ->end() // messageField
                ->scalarNode('defaultMessage')
                    ->cannotBeEmpty()
                    ->defaultNull()
                ->end() // defaultMessage
            ->end() // error children
        ;
    }
}
