<?php

/*
 * This file is part of the FiveLab Amqp package
 *
 * (c) FiveLab
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

declare(strict_types = 1);

namespace FiveLab\Component\Amqp\Tests\Unit\Publisher\Middleware;

use FiveLab\Component\Amqp\Message\Headers;
use FiveLab\Component\Amqp\Message\Identifier;
use FiveLab\Component\Amqp\Message\Message;
use FiveLab\Component\Amqp\Message\MessageInterface;
use FiveLab\Component\Amqp\Message\Options;
use FiveLab\Component\Amqp\Message\Payload;
use FiveLab\Component\Amqp\Publisher\Middleware\AddHeaderToMessageMiddleware;
use PHPUnit\Framework\TestCase;

class AddHeaderToMessageMiddlewareTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessAddHeader(): void
    {
        $middleware = new AddHeaderToMessageMiddleware('x-custom-header', 'foo-bar');
        $message = new Message(new Payload('foo'));
        $executed = false;

        $next = static function (string $routingKey, MessageInterface $message) use (&$executed) {
            $executed = true;
            self::assertEquals('foo.bar', $routingKey);
            self::assertEquals(new Message(
                new Payload('foo'),
                new Options(),
                new Headers(['x-custom-header' => 'foo-bar']),
                new Identifier()
            ), $message);
        };

        $middleware->handle('foo.bar', $message, $next);

        self::assertTrue($executed, 'The next callable don\'t called.');
    }
}
