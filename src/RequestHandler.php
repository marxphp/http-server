<?php

declare(strict_types=1);

/**
 * This file is part of MaxPHP.
 *
 * @link     https://github.com/marxphp
 * @license  https://github.com/marxphp/max/blob/master/LICENSE
 */

namespace Max\Http\Server;

use Max\Routing\Exceptions\RouteNotFoundException;
use Max\Routing\Route;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionException;

class RequestHandler implements RequestHandlerInterface
{
    /**
     * 容器是否有make方法.
     */
    private bool $hasMakeMethod;

    public function __construct(
        protected ContainerInterface $container,
        protected array $middlewares = []
    ) {
        $this->hasMakeMethod = method_exists($this->container, 'make');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($middleware = array_shift($this->middlewares)) {
            return $this->handleMiddleware(
                $this->hasMakeMethod ? $this->container->make($middleware) : new $middleware(),
                $request
            );
        }
        return $this->handleRequest($request);
    }

    /**
     * 向当前中间件后插入中间件.
     */
    public function appendMiddlewares(array $middlewares): void
    {
        array_unshift($this->middlewares, ...$middlewares);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException|RouteNotFoundException
     */
    protected function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        if ($route = $request->getAttribute(Route::class)) {
            $parameters            = $route->getParameters();
            $parameters['request'] = $request;
            return $this->container->call($route->getAction(), $parameters);
        }
        throw new RouteNotFoundException('No route in request attributes', 404);
    }

    /**
     * 处理中间件.
     */
    protected function handleMiddleware(MiddlewareInterface $middleware, ServerRequestInterface $request): ResponseInterface
    {
        return $middleware->process($request, $this);
    }
}
