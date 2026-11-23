<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlFactory;
use GuzzleHttp\Handler\CurlHandler;

final class GoogleHttpClientFactory
{
    public function make(): Client
    {
        $options = [
            'connect_timeout' => 5,
            'timeout' => 10,
        ];

        if (PHP_OS_FAMILY === 'Windows' && defined('CURLSSLOPT_NATIVE_CA')) {
            $options['handler'] = new CurlHandler([
                'handle_factory' => new WindowsNativeCaCurlFactory(new CurlFactory(3)),
            ]);
        }

        return new Client($options);
    }
}
