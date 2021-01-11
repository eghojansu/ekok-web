<?php

namespace Ekok\Web;

class Common
{
    public static function stringify($data): string
    {
        if (null === $data || is_scalar($data)) {
            return is_string($data) ? $data :  var_export($data, true);
        }

        $str = '';

        foreach ($data as $key => $value) {
            if (!is_numeric($key)) {
                $str .= static::stringify($key) . ' => ';
            }

            $str .= static::stringify($value) . ', ';
        }

        return '[' . substr($str, 0, -2) . ']';
    }

    public static function castData($value)
    {
        if (preg_match('/^(?:0x[0-9a-f]+|0[0-7]+|0b[01]+)$/i', $value)) {
            return intval($value, 0);
        }

        if (is_numeric($value)) {
            return $value + 0;
        }

        $checked = trim($value);

        if (preg_match('/^\w+$/i', $checked) && defined($checked)) {
            return constant($checked);
        }

        return $value;
    }

    public static function &getDataRef(array &$var, string $field)
    {
        $parts = explode('.', $field);

        foreach ($parts as $part) {
            if (null === $var || is_scalar($var)) {
                $var = array();
            }

            $var = &$var[$part];
        }

        return $var;
    }

    public static function getDataValue(array $data, string $field)
    {
        if (false === strpos($field, '.')) {
            return $data[$field] ?? null;
        }

        $value = $data;
        $parts = explode('.', $field);

        foreach ($parts as $part) {
            if (is_array($value) && (isset($value[$part]) || array_key_exists($part, $value))) {
                $value = &$value[$part];
            } else {
                $value = null;
                break;
            }
        }

        return $value;
    }

    public static function parseExpression(string $expression): array
    {
        $parsed = array();
        $parts = explode('|', $expression);

        foreach ($parts as $part) {
            if ($part) {
                list($rule, $argumentLine) = explode(':', $part) + array(1 => null);

                $parsed[trim($rule)] = $argumentLine ? array_map('static::castData', array_map('trim', explode(',', $argumentLine))) : array();
            }
        }

        return $parsed;
    }

    public static function fixSlashes(string $str, bool $ensureSlash = false): string
    {
        return rtrim(strtr($str, '\\', '/'), '/') . ($ensureSlash ? '/' : '');
    }
}
