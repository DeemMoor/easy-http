<?php

namespace Custom\EasyHttp\Psr17;

use Custom\EasyHttp\Psr7\Stream;
use Psr\Http\Message\StreamInterface;
use Custom\EasyHttp\Psr7\LazyOpenStream;
use Psr\Http\Message\StreamFactoryInterface;

class StreamFactory implements StreamFactoryInterface
{

    /**
     * @inheritDoc
     */
    public function createStream(string $content = ''): StreamInterface
    {
        return new Stream($content);
    }

    /**
     * @inheritDoc
     */
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        return new LazyOpenStream($filename, $mode);
    }

    /**
     * @inheritDoc
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        return new Stream($resource);
    }
}