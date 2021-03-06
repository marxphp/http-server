<?php

declare(strict_types=1);

/**
 * This file is part of MaxPHP.
 *
 * @link     https://github.com/marxphp
 * @license  https://github.com/marxphp/max/blob/master/LICENSE
 */

namespace Max\Http\Server\Middlewares;

use Max\Http\Message\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class ExceptionHandleMiddleware implements MiddlewareInterface
{
    /**
     * @throws Throwable
     */
    final public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $throwable) {
            $this->reportException($throwable, $request);

            return $this->renderException($throwable, $request);
        }
    }

    /**
     * 报告异常.
     */
    protected function reportException(Throwable $throwable, ServerRequestInterface $request): void
    {
    }

    /**
     * 将异常转为ResponseInterface对象
     */
    protected function renderException(Throwable $throwable, ServerRequestInterface $request): ResponseInterface
    {
        $message = $throwable->getMessage();
        if (str_contains($request->getHeaderLine('Accept'), 'application/json')
            || strcasecmp('XMLHttpRequest', $request->getHeaderLine('X-REQUESTED-WITH')) === 0) {
            return new Response(500, [], json_encode([
                'status'  => false,
                'code'    => 500,
                'data'    => $throwable->getTrace(),
                'message' => $message,
            ], JSON_UNESCAPED_UNICODE));
        }
        return new Response(500, [], sprintf(
            '<html lang="zh"><head><title>%s</title></head><body><pre style="font-size: 1.5em; white-space: break-spaces"><p><b>%s</b></p><b>Stack Trace</b><br>%s</pre></body></html>',
            $message,
            $message,
            $throwable->getTraceAsString(),
        ));
    }
}
