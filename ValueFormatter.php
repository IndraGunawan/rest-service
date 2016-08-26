<?php

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
     * @param mixed      $value
     * @param mixed|null $format
     *
     * @return int
     */
    private function formatInteger($value, $format = null)
    {
        return (int) (string) $value;
    }

    /**
     * Format Float.
     *
     * @param mixed      $value
     * @param mixed|null $format
     *
     * @return float
     */
    private function formatFloat($value, $format = null)
    {
        return (float) (string) $value;
    }

    /**
     * Format String.
     *
     * @param mixed      $value
     * @param mixed|null $format
     *
     * @return string
     */
    private function formatString($value, $format = null)
    {
        return sprintf($format ?: '%s', (string) $value);
    }

    /**
     * Format Boolean.
     *
     * @param mixed      $value
     * @param mixed|null $format
     *
     * @return bool
     */
    private function formatBoolean($value, $format = null)
    {
        return ('false' === $value || false === $value || 0 === $value) ? false : true;
    }

    /**
     * Format Number.
     *
     * @param mixed      $value
     * @param mixed|null $format
     *
     * @return string
     */
    private function formatNumber($value, $format = null)
    {
        if ($format) {
            $format = explode('|', $format);
            $decimal = isset($format[0]) ? $format[0] : 0;
            $decimalPoint = isset($format[1]) ? $format[1] : '.';
            $thousandsSeparator = isset($format[2]) ? $format[2] : ',';

            return number_format((float) (string) $value, $decimal, $decimalPoint, $thousandsSeparator);
        }

        return (string) $value;
    }

    /**
     * Format Datetime.
     *
     * @param mixed      $value
     * @param mixed|null $format
     *
     * @return \DateTime|string
     */
    private function formatDatetime($value, $format = null)
    {
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
