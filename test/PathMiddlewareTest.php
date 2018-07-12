<?php

declare(strict_types=1);

namespace Test\Aidphp\Routing\Middleware;

use PHPUnit\Framework\TestCase;
use Aidphp\Routing\Middleware\PathMiddleware;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class PathMiddlewareTest extends TestCase
{
    private function getRequest(string $path): ServerRequestInterface
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->expects($this->once())
            ->method('getPath')
            ->willReturn($path);

        $req = $this->createMock(ServerRequestInterface::class);
        $req->expects($this->once())
            ->method('getUri')
            ->willReturn($uri);

        return $req;
    }

    private function getMiddlewareNeverCalled(): MiddlewareInterface
    {
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->never())
            ->method('process');

        return $middleware;
    }

    public function testRegister()
    {
        $path = new PathMiddleware();
        $this->assertSame($path, $path->register('/foo', $this->createMock(MiddlewareInterface::class)));
    }

    public function testProcess()
    {
        $req = $this->getRequest('/foo');
        $res = $this->createMock(ResponseInterface::class);

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->with($req, $this->isInstanceOf(RequestHandlerInterface::class))
            ->willReturnCallback(function ($req, $handler) use ($res) {
                return $res;
            });

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())
            ->method('handle');

        $path = new PathMiddleware([
            '/bar' => $this->getMiddlewareNeverCalled(),
            '/foo' => $middleware,
        ]);

        $this->assertSame($res, $path->process($req, $handler));
    }

    public function testProcessPathNotFound()
    {
        $req = $this->getRequest('/foo');
        $res = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($req)
            ->willReturn($res);

        $path = new PathMiddleware([
            '/bar' => $this->getMiddlewareNeverCalled(),
            '/baz' => $this->getMiddlewareNeverCalled(),
        ]);

        $this->assertSame($res, $path->process($req, $handler));
    }
}