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

class ValueFormatter
{
    /**
     * Evaluates if the given value is to be treated as empty.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function isValueEmpty($value)
    {
        return null === $value || '' === $value;
    }

    /**
     * Get $value if not empty, $defaultValue otherwise.
     *
     * @param mixed      $value
     * @param mixed|null $defaultValue
     *
     * @return mixed|null
     */
    public function getValue($value, $defaultValue = null)
    {
        if (!$this->isValueEmpty($value)) {
            return $value;
        }

        return $defaultValue;
    }

    /**
     * Get formatted value base on type and format.
     *
     * @param string     $type
     * @param string     $format
     * @param mixed      $value
     * @param mixed|null $defaultValue
     *
     * @return mixed
     */
    public function format($type, $format, $value, $defaultValue = null)
    {
        $value = $this->getValue($value, $defaultValue);

        if (method_exists($this, 'format'.ucfirst(strtolower($type)))) {
            return $this->{'format'.ucfirst(strtolower($type))}($value, $format);
        }

        return $value;
    }

    /**
     * Format Integer.
     *
     * @return int
     */
    private function formatInteger()
    {
        list($value) = func_get_args();

        return (int) (string) $value;
    }

    /**
     * Format Float.
     *
     * @return float
     */
    private function formatFloat()
    {
        list($value) = func_get_args();

        return (float) (string) $value;
    }

    /**
     * Format String.
     *
     * @return string
     */
    private function formatString()
    {
        list($value, $format) = func_get_args();

        return sprintf($format ?: '%s', (string) $value);
    }

    /**
     * Format Boolean.
     *
     * @return bool
     */
    private function formatBoolean()
    {
        list($value) = func_get_args();

        return ('false' === $value || false === $value || 0 === $value) ? false : true;
    }

    /**
     * Format Number.
     *
     * @return string
     */
    private function formatNumber()
    {
        list($value, $format) = func_get_args();

        if ($format) {
            $format = explode('|', $format);
            $decimal = isset($format[0]) ? $format[0] : 0;
            $decimalPoint = isset($format[1]) ? $format[1] : '.';
            $thousandsSeparator = isset($format[2]) ? $format[2] : ',';

            return number_format($this->formatFloat($value), $this->formatInteger($decimal), $decimalPoint, $thousandsSeparator);
        }

        return (string) $value;
    }

    /**
     * Format Datetime.
     *
     * @return \DateTime|string|bool
     */
    private function formatDatetime()
    {
        list($value, $format) = func_get_args();

        if ($this->isValueEmpty($value)) {
            return;
        }

        if ($value instanceof \DateTime) {
            return $value->format($format ?: 'Y-m-d\TH:i:s\Z');
        }

        if ($format) {
            return \DateTime::createFromFormat($format, $value);
        }

        return new \DateTime($value);
    }
}
