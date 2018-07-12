<?php

declare(strict_types=1);

namespace Aidphp\Routing\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class PrefixMiddleware implements MiddlewareInterface
{
    protected $prefixes = [];

    public function __construct(array $prefixes = [])
    {
        foreach ($prefixes as $prefix => $middleware)
        {
            $this->register($prefix, $middleware);
        }
    }

    public function register(string $prefix, MiddlewareInterface $middleware): self
    {
        $this->prefixes[$prefix] = $middleware;
        return $this;
    }

    public function process(ServerRequestInterface $req, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri  = $req->getUri();
        $path = $uri->getPath();

        foreach ($this->prefixes as $prefix => $middleware)
        {
            if (0 === strpos($path, $prefix))
            {
                $req = $req->withUri($uri->withPath(substr($path, strlen($prefix))))
                           ->withAttribute(self::class, $req->getAttribute(self::class, '') . $prefix);

                return $middleware->process($req, $handler);
            }
        }

        return $handler->handle($req);
    }
}