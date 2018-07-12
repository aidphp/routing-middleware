<?php

declare(strict_types=1);

namespace Aidphp\Routing\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class PathMiddleware implements MiddlewareInterface
{
    protected $paths = [];

    public function __construct(array $paths = [])
    {
        foreach ($paths as $path => $middleware)
        {
            $this->register($path, $middleware);
        }
    }

    public function register(string $path, MiddlewareInterface $middleware): self
    {
        $this->paths[$path] = $middleware;
        return $this;
    }

    public function process(ServerRequestInterface $req, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $req->getUri()->getPath();

        if (isset($this->paths[$path]))
        {
            return ($this->paths[$path])->process($req, $handler);
        }

        return $handler->handle($req);
    }
}