<?php

declare(strict_types=1);

namespace Aidphp\Routing\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class MethodMiddleware implements MiddlewareInterface
{
    protected $methods = [];

    public function __construct(array $methods = [])
    {
        foreach ($methods as $method => $middleware)
        {
            $this->register($method, $middleware);
        }
    }

    public function register(string $method, MiddlewareInterface $middleware): self
    {
        $this->methods[$method] = $middleware;
        return $this;
    }

    public function process(ServerRequestInterface $req, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $req->getMethod();

        if (isset($this->methods[$method]))
        {
            return ($this->methods[$method])->process($req, $handler);
        }

        $req = $req->withAttribute(self::class, array_keys($this->methods));

        return $handler->handle($req);
    }
}