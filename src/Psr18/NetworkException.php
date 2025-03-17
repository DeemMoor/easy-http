<?php

namespace Custom\EasyHttp\Psr18;

use Psr\Http\Client\ClientExceptionInterface;

class NetworkException implements ClientExceptionInterface
{
    private $message;

    private $statusCode;

    public function __construct(string $message, int $statusCode)
    {
        $this->message = $message;
        $this->statusCode = $statusCode;
    }

    /**
     * @inheritDoc
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @inheritDoc
     */
    public function getCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @inheritDoc
     */
    public function getFile(): string
    {
        // TODO: Implement getFile() method.
    }

    /**
     * @inheritDoc
     */
    public function getLine(): int
    {
        // TODO: Implement getLine() method.
    }

    /**
     * @inheritDoc
     */
    public function getTrace(): array
    {
        // TODO: Implement getTrace() method.
    }

    /**
     * @inheritDoc
     */
    public function getTraceAsString(): string
    {
        // TODO: Implement getTraceAsString() method.
    }

    /**
     * @inheritDoc
     */
    public function getPrevious(): ?\Throwable
    {
        // TODO: Implement getPrevious() method.
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        // TODO: Implement __toString() method.
    }
}