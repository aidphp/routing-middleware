<?php

declare(strict_types=1);

namespace Aidphp\Routing\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class PatternMiddleware implements MiddlewareInterface
{
    protected $patterns = [];

    public function __construct(array $patterns = [])
    {
        foreach ($patterns as $pattern => $middleware)
        {
            $this->register($pattern, $middleware);
        }
    }

    public function register(string $pattern, MiddlewareInterface $middleware): self
    {
        $this->patterns[$pattern] = $middleware;
        return $this;
    }

    public function process(ServerRequestInterface $req, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $req->getUri()->getPath();

        foreach ($this->patterns as $pattern => $middleware)
        {
            $matches = [];
            if (preg_match('#^' . $pattern . '$#', $path, $matches))
            {
                foreach ($matches as $key => $value)
                {
                    if (is_string($key))
                    {
                        $req = $req->withAttribute($key, $value);
                    }
                }

                return $middleware->process($req, $handler);
            }
        }

        return $handler->handle($req);
    }
}