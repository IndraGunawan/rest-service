<?php declare(strict_types=1);

/*
 * This file is part of indragunawan/rest-service package.
 *
 * (c) Indra Gunawan <hello@indra.my.id>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IndraGunawan\RestService\Exception;

class ValidatorException extends \RuntimeException
{
    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $errorMessage;

    /**
     * @param string          $field
     * @param string          $errorMessage
     * @param \Exception|null $prev
     */
    public function __construct($field, $errorMessage, \Exception $prev = null)
    {
        $this->field = $field;
        $this->errorMessage = $errorMessage;

        parent::__construct($field.' : '.$errorMessage, 0, $prev);
    }

    public function getField()
    {
        return $this->field;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}
