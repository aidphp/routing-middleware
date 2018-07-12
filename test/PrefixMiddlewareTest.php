<?php

declare(strict_types=1);

namespace Test\Aidphp\Routing\Middleware;

use PHPUnit\Framework\TestCase;
use Aidphp\Routing\Middleware\PrefixMiddleware;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class PrefixMiddlewareTest extends TestCase
{
    private function getMiddlewareNeverCalled(): MiddlewareInterface
    {
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->never())
            ->method('process');

        return $middleware;
    }

    public function testRegister()
    {
        $prefix = new PrefixMiddleware();
        $this->assertSame($prefix, $prefix->register('/foo', $this->createMock(MiddlewareInterface::class)));
    }

    public function testProcess()
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->expects($this->once())
            ->method('getPath')
            ->willReturn('/foo/bar/baz');

        $newUri = $this->createMock(UriInterface::class);
        $uri->expects($this->once())
            ->method('withPath')
            ->willReturn($newUri);

        $req = $this->createMock(ServerRequestInterface::class);
        $req->expects($this->once())
            ->method('getUri')
            ->willReturn($uri);

        $newReq = $this->createMock(ServerRequestInterface::class);
        $req->expects($this->once())
            ->method('withUri')
            ->with($newUri)
            ->willReturn($newReq);

        $finalReq = $this->createMock(ServerRequestInterface::class);
        $newReq->expects($this->once())
            ->method('withAttribute')
            ->with(PrefixMiddleware::class, '/foo')
            ->willReturn($finalReq);

        $res = $this->createMock(ResponseInterface::class);

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($finalReq), $this->isInstanceOf(RequestHandlerInterface::class))
            ->willReturnCallback(function ($req, $handler) use ($res) {
                return $res;
            });

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())
            ->method('handle');

        $prefix = new PrefixMiddleware([
            '/bar' => $this->getMiddlewareNeverCalled(),
            '/foo' => $middleware,
        ]);

        $this->assertSame($res, $prefix->process($req, $handler));
    }

    public function testProcessPrefixNotFound()
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->expects($this->once())
            ->method('getPath')
            ->willReturn('/baz/foo/bar');

        $req = $this->createMock(ServerRequestInterface::class);
        $req->expects($this->once())
            ->method('getUri')
            ->willReturn($uri);

        $res = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($req)
            ->willReturn($res);

        $prefix = new PrefixMiddleware([
            '/bar' => $this->getMiddlewareNeverCalled(),
            '/foo' => $this->getMiddlewareNeverCalled(),
        ]);

        $this->assertSame($res, $prefix->process($req, $handler));
    }

    public function testProcessAndMergePrefix()
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->expects($this->once())
            ->method('getPath')
            ->willReturn('/bar/baz');

        $newUri = $this->createMock(UriInterface::class);
        $uri->expects($this->once())
            ->method('withPath')
            ->willReturn($newUri);

        $req = $this->createMock(ServerRequestInterface::class);
        $req->expects($this->once())
            ->method('getUri')
            ->willReturn($uri);

        $req->expects($this->once())
            ->method('getAttribute')
            ->with(PrefixMiddleware::class)
            ->willReturn('/foo');

        $newReq = $this->createMock(ServerRequestInterface::class);
        $req->expects($this->once())
            ->method('withUri')
            ->with($newUri)
            ->willReturn($newReq);

        $finalReq = $this->createMock(ServerRequestInterface::class);
        $newReq->expects($this->once())
            ->method('withAttribute')
            ->with(PrefixMiddleware::class, '/foo/bar')
            ->willReturn($finalReq);

        $res = $this->createMock(ResponseInterface::class);

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($finalReq), $this->isInstanceOf(RequestHandlerInterface::class))
            ->willReturnCallback(function ($req, $handler) use ($res) {
                return $res;
            });

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())
            ->method('handle');

        $prefix = new PrefixMiddleware([
            '/baz' => $this->getMiddlewareNeverCalled(),
            '/bar' => $middleware,
        ]);

        $this->assertSame($res, $prefix->process($req, $handler));
    }
}