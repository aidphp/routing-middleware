<?php

declare(strict_types=1);

namespace Test\Aidphp\Routing\Middleware;

use PHPUnit\Framework\TestCase;
use Aidphp\Routing\Middleware\MethodMiddleware;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class MethodMiddlewareTest extends TestCase
{
    private function getRequest(): ServerRequestInterface
    {
        $req = $this->createMock(ServerRequestInterface::class);
        $req->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

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
        $method = new MethodMiddleware();
        $this->assertSame($method, $method->register('GET', $this->createMock(MiddlewareInterface::class)));
    }

    public function testProcess()
    {
        $req = $this->getRequest();
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

        $method = new MethodMiddleware([
            'GET'  => $middleware,
            'POST' => $this->getMiddlewareNeverCalled(),
        ]);

        $this->assertSame($res, $method->process($req, $handler));
    }

    public function testProcessMethodNotAllowed()
    {
        $req = $this->getRequest();
        $newReq = $this->createMock(ServerRequestInterface::class);

        $req->expects($this->once())
            ->method('withAttribute')
            ->with(MethodMiddleware::class, ['POST', 'PUT'])
            ->willReturn($newReq);

        $res = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($newReq))
            ->willReturn($res);

        $method = new MethodMiddleware([
            'POST' => $this->getMiddlewareNeverCalled(),
            'PUT'  => $this->getMiddlewareNeverCalled(),
        ]);

        $this->assertSame($res, $method->process($req, $handler));
    }
}