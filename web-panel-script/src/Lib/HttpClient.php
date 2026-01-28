<?php
declare(strict_types=1);

namespace App\Lib;

final class HttpClient
{
    public static function postForm(string $url, array $data, array $headers = []): array
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('cURL extension is required.');
        }

        $ch = curl_init($url);
        $payload = http_build_query($data);

        $defaultHeaders = [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array_merge($defaultHeaders, $headers),
        ]);

        $response = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('HTTP request failed: ' . $error);
        }

        return ['status' => $status, 'body' => $response];
    }

    public static function get(string $url, array $headers = []): array
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('cURL extension is required.');
        }

        $ch = curl_init($url);

        $defaultHeaders = [
            'Accept: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array_merge($defaultHeaders, $headers),
        ]);

        $response = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('HTTP request failed: ' . $error);
        }

        return ['status' => $status, 'body' => $response];
    }
}
