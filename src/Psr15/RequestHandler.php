<?php

namespace Custom\EasyHttp\Psr15;

use Custom\EasyHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


class RequestHandler implements RequestHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new Response();
    }
}