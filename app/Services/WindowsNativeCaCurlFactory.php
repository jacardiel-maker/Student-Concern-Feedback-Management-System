<?php

namespace App\Services;

use GuzzleHttp\Handler\CurlFactoryInterface;
use GuzzleHttp\Handler\EasyHandle;
use Psr\Http\Message\RequestInterface;

final class WindowsNativeCaCurlFactory implements CurlFactoryInterface
{
    public function __construct(private CurlFactoryInterface $factory) {}

    public function create(RequestInterface $request, array $options): EasyHandle
    {
        $easyHandle = $this->factory->create($request, $options);

        // XAMPP's bundled CA file cannot verify Google's current chain, while
        // Windows' native certificate store can. Guzzle's request option
        // allow-list omits CURLOPT_SSL_OPTIONS, so apply it to the handle.
        curl_setopt($easyHandle->handle, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);

        return $easyHandle;
    }

    public function release(EasyHandle $easyHandle): void
    {
        $this->factory->release($easyHandle);
    }
}
