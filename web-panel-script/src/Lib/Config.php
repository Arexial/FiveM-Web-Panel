<?php
declare(strict_types=1);

namespace App\Lib;

final class Config
{
    private static array $data = [];

    public static function init(array $data): void
    {
        self::$data = $data;
    }

    public static function get(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $value = self::$data;

        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }
}
