<?php

declare(strict_types=1);

/**
 * This file is part of MaxPHP.
 *
 * @link     https://github.com/marxphp
 * @license  https://github.com/marxphp/max/blob/master/LICENSE
 */

namespace Max\Http\Server\ResponseEmitter;

use Max\Http\Message\Cookie;
use Max\Http\Server\Contracts\ResponseEmitterInterface;
use Psr\Http\Message\ResponseInterface;

class FPMResponseEmitter implements ResponseEmitterInterface
{
    public function emit(ResponseInterface $psrResponse, $sender = null)
    {
        header(sprintf('HTTP/%s %d %s', $psrResponse->getProtocolVersion(), $psrResponse->getStatusCode(), $psrResponse->getReasonPhrase()), true);
        foreach ($psrResponse->getHeader('Set-Cookie') as $cookie) {
            $cookie = Cookie::parse($cookie);
            setcookie(
                $cookie->getName(),
                $cookie->getValue(),
                $cookie->getExpires(),
                $cookie->getPath(),
                $cookie->getDomain(),
                $cookie->isSecure(),
                $cookie->isHttponly()
            );
        }
        $psrResponse = $psrResponse->withoutHeader('Set-Cookie');
        foreach ($psrResponse->getHeaders() as $name => $value) {
            header($name . ': ' . implode(', ', $value));
        }
        $body = $psrResponse->getBody();
        echo $body?->getContents();
        $body?->close();
    }
}
