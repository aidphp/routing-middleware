<?php

declare(strict_types=1);

namespace Test\Aidphp\Routing\Middleware;

use PHPUnit\Framework\TestCase;
use Aidphp\Routing\Middleware\PatternMiddleware;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class PatternMiddlewareTest extends TestCase
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
        $pattern = new PatternMiddleware();
        $this->assertSame($pattern, $pattern->register('/(?P<id>[0-9]+)', $this->createMock(MiddlewareInterface::class)));
    }

    public function testProcess()
    {
        $req = $this->getRequest('/foo/5');
        $newReq = $this->createMock(ServerRequestInterface::class);

        $req->expects($this->once())
            ->method('withAttribute')
            ->with('id', '5')
            ->willReturn($newReq);

        $res = $this->createMock(ResponseInterface::class);

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($newReq), $this->isInstanceOf(RequestHandlerInterface::class))
            ->willReturnCallback(function ($req, $handler) use ($res) {
                return $res;
            });

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())
            ->method('handle');

        $pattern = new PatternMiddleware([
            '/bar/(?P<id>[0-9]+)' => $this->getMiddlewareNeverCalled(),
            '/foo/(?P<id>[0-9]+)' => $middleware,
        ]);

        $this->assertSame($res, $pattern->process($req, $handler));
    }

    public function testProcessNoMatch()
    {
        $req = $this->getRequest('/foo/5');
        $res = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($req)
            ->willReturn($res);

        $pattern = new PatternMiddleware([
            '/bar/(?P<id>[a-zA-Z]+)' => $this->getMiddlewareNeverCalled(),
            '/foo/(?P<id>[a-zA-Z]+)' => $this->getMiddlewareNeverCalled(),
        ]);

        $this->assertSame($res, $pattern->process($req, $handler));
    }
}