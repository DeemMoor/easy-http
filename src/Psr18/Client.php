<?php

namespace Custom\EasyHttp\Psr18;

use Custom\EasyHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Client implements ClientInterface
{
    private string $baseUrl = '';
    private int $lastResponseCode = 0;
    private array $lastResponseHeaders = [];


    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }
    /**
     * @inheritDoc
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
//        $ch = curl_init($request->getUri()->getScheme().'://'.$request->getUri()->getHost().$request->getUri()->getPath());
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, (string) $request->getUri());
//        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->getMethod());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'curl/7.88.1');
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            if ($name === 'Host') {
                continue;
            }
            foreach ($values as $value) {
                $headers[] = "$name: $value";
            }
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($request->getBody()->getSize() > 0) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, (string) $request->getBody());
        }

        $responseHeaders = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$responseHeaders) {
            $len = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) < 2) {
                return $len;
            }
            $responseHeaders[strtolower(trim($header[0]))] = trim($header[1]);
            return $len;
        });

        $responseBody = curl_exec($ch);

        if ($responseBody === false) {
            $errorMessage = curl_error($ch);
            $errorCode = curl_errno($ch);
            curl_close($ch);
            throw new \RuntimeException("cURL error ($errorCode): $errorMessage");
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return new Response($statusCode, $responseHeaders, $responseBody);

    }

    public function getLastResponseCode(): int
    {
        return $this->lastResponseCode;
    }

    public function getLastResponseHeaders(): array
    {
        return $this->lastResponseHeaders;
    }

}