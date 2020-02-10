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

use FiveLab\Component\Amqp\Message\Generator\MessageIdGeneratorInterface;
use FiveLab\Component\Amqp\Message\Headers;
use FiveLab\Component\Amqp\Message\Identifier;
use FiveLab\Component\Amqp\Message\Message;
use FiveLab\Component\Amqp\Message\Options;
use FiveLab\Component\Amqp\Message\Payload;
use FiveLab\Component\Amqp\Publisher\Middleware\AddIdentifierToMessageMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddIdentifierToMessageMiddlewareTest extends TestCase
{
    /**
     * @var MessageIdGeneratorInterface|MockObject
     */
    private $messageIdGenerator;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->messageIdGenerator = $this->createMock(MessageIdGeneratorInterface::class);
    }

    /**
     * @test
     */
    public function shouldSuccessInvokeIfParametersNotSet(): void
    {
        $middleware = new AddIdentifierToMessageMiddleware();

        $executed = false;
        $next = $this->createNextCallable('some', $this->createMessage(), $executed);

        $middleware->handle($this->createMessage(), $next, 'some');

        self::assertTrue($executed, 'The next callable don\'t called.');
    }

    /**
     * @test
     */
    public function shouldAddMessageId(): void
    {
        $this->messageIdGenerator->expects(self::once())
            ->method('generate')
            ->willReturn('123');

        $middleware = new AddIdentifierToMessageMiddleware($this->messageIdGenerator);

        $executed = false;
        $next = $this->createNextCallable('some', $this->createMessage('123'), $executed);

        $middleware->handle($this->createMessage(), $next, 'some');

        self::assertTrue($executed, 'The next callable don\'t called.');
    }

    /**
     * @test
     */
    public function shouldNotAddMessageIdIfExist(): void
    {
        $this->messageIdGenerator->expects(self::never())
            ->method('generate');

        $middleware = new AddIdentifierToMessageMiddleware($this->messageIdGenerator);

        $executed = false;
        $next = $this->createNextCallable('some', $this->createMessage('123'), $executed);

        $middleware->handle($this->createMessage('123'), $next, 'some');

        self::assertTrue($executed, 'The next callable don\'t called.');
    }

    /**
     * @test
     */
    public function shouldAddAppId(): void
    {
        $middleware = new AddIdentifierToMessageMiddleware(null, 'app-id');

        $executed = false;
        $next = $this->createNextCallable('some', $this->createMessage(null, 'app-id'), $executed);

        $middleware->handle($this->createMessage(), $next, 'some');

        self::assertTrue($executed, 'The next callable don\'t called.');
    }

    /**
     * @test
     */
    public function shouldNotAddAppIdIfExist(): void
    {
        $middleware = new AddIdentifierToMessageMiddleware(null, 'app-id');

        $executed = false;
        $next = $this->createNextCallable('some', $this->createMessage(null, '123'), $executed);

        $middleware->handle($this->createMessage(null, '123'), $next, 'some');

        self::assertTrue($executed, 'The next callable don\'t called.');
    }

    /**
     * @test
     */
    public function shouldAddUserId(): void
    {
        $middleware = new AddIdentifierToMessageMiddleware(null, null, 'user-id');

        $executed = false;
        $next = $this->createNextCallable('some', $this->createMessage(null, null, 'user-id'), $executed);

        $middleware->handle($this->createMessage(), $next, 'some');

        self::assertTrue($executed, 'The next callable don\'t called.');
    }

    /**
     * @test
     */
    public function shouldNotAddUserIdIfExist(): void
    {
        $middleware = new AddIdentifierToMessageMiddleware(null, null, 'user-id');

        $executed = false;
        $next = $this->createNextCallable('some', $this->createMessage(null, null, '123'), $executed);

        $middleware->handle($this->createMessage(null, null, '123'), $next, 'some');

        self::assertTrue($executed, 'The next callable don\'t called.');
    }

    /**
     * Create next callable
     *
     * @param string  $expectedRoutingKey
     * @param Message $expectedMessage
     * @param bool    $executed
     *
     * @return callable
     */
    private function createNextCallable(string $expectedRoutingKey, Message $expectedMessage, bool &$executed): callable
    {
        return static function (Message $message, string $routingKey) use ($expectedRoutingKey, $expectedMessage, &$executed) {
            $executed = true;

            self::assertEquals($expectedRoutingKey, $routingKey);
            self::assertEquals($expectedMessage, $message);
        };
    }

    /**
     * Create a message
     *
     * @param string|null $messageId
     * @param string|null $appId
     * @param string|null $userId
     *
     * @return Message
     */
    private function createMessage(string $messageId = null, string $appId = null, string $userId = null): Message
    {
        return new Message(
            new Payload('some'),
            new Options(),
            new Headers([
                'x-custom' => 'foo-bar',
            ]),
            new Identifier($messageId, $appId, $userId)
        );
    }
}
