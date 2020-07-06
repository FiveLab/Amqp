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

namespace FiveLab\Component\Amqp\Tests\Unit\Publisher;

use FiveLab\Component\Amqp\Exchange\ExchangeFactoryInterface;
use FiveLab\Component\Amqp\Exchange\ExchangeInterface;
use FiveLab\Component\Amqp\Message\Message;
use FiveLab\Component\Amqp\Message\MessageInterface;
use FiveLab\Component\Amqp\Message\Payload;
use FiveLab\Component\Amqp\Publisher\Middleware\PublisherMiddlewareCollection;
use FiveLab\Component\Amqp\Publisher\SavepointPublisher;
use PHPUnit\Framework\MockObject\Matcher\Invocation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SavepointPublisherTest extends TestCase
{
    /**
     * @var ExchangeInterface|MockObject
     */
    private $exchange;

    /**
     * @var ExchangeFactoryInterface|MockObject
     */
    private $exchangeFactory;

    /**
     * @var PublisherMiddlewareCollection|MockObject
     */
    private $middlewares;

    /**
     * @var SavepointPublisher
     */
    private $publisher;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->exchange = $this->createMock(ExchangeInterface::class);
        $this->exchangeFactory = $this->createMock(ExchangeFactoryInterface::class);
        $this->middlewares = $this->createMock(PublisherMiddlewareCollection::class);

        $this->exchangeFactory->expects(self::any())
            ->method('create')
            ->willReturn($this->exchange);

        $this->publisher = new SavepointPublisher($this->exchangeFactory, $this->middlewares);
    }

    /**
     * @test
     */
    public function shouldSuccessPublishIfNoSavepoints(): void
    {
        $executed = false;

        $this->mustPublish($executed, self::once(), 'foo.bar', new Message(new Payload('some')));

        $this->publisher->publish(new Message(new Payload('some')), 'foo.bar');

        self::assertTrue($executed, 'The publisher don\'t execute middleware callback.');
    }

    /**
     * @test
     */
    public function shouldSuccessPublishInOneSavepoint(): void
    {
        $executed = false;

        $this->mustPublish($executed, self::once(), 'foo.bar', new Message(new Payload('some')));

        $this->publisher->start('savepoint_1');
        $this->publisher->publish(new Message(new Payload('some')), 'foo.bar');
        $this->publisher->flush();

        self::assertTrue($executed, 'The publisher don\'t execute middleware callback.');
    }

    /**
     * @test
     */
    public function shouldNotPublishBeforeFlush(): void
    {
        $executed = false;

        $this->mustPublish($executed, self::never(), 'foo.bar', new Message(new Payload('some')));

        $this->publisher->start('savepoint_1');
        $this->publisher->publish(new Message(new Payload('some')), 'foo.bar');

        self::assertFalse($executed, 'The publisher can\'t publish message before flush.');
    }

    /**
     * @test
     */
    public function shouldSuccessPublishWithManySavepoints(): void
    {
        $executed1 = false;
        $executed2 = false;
        $executed3 = false;

        $this->mustPublish($executed1, self::at(0), 'foo.1', new Message(new Payload('1')));
        $this->mustPublish($executed2, self::at(1), 'foo.2', new Message(new Payload('2')));
        $this->mustPublish($executed3, self::at(2), 'foo.3', new Message(new Payload('3')));

        $this->publisher->start('savepoint_1');
        $this->publisher->publish(new Message(new Payload('1')), 'foo.1');

        $this->publisher->start('savepoint_2');
        $this->publisher->publish(new Message(new Payload('2')), 'foo.2');

        $this->publisher->start('savepoint_3');
        $this->publisher->publish(new Message(new Payload('3')), 'foo.3');

        $this->publisher->flush();

        self::assertTrue($executed1, 'The publisher don\'t execute middleware callback for savepoint_1.');
        self::assertTrue($executed2, 'The publisher don\'t execute middleware callback for savepoint_2.');
        self::assertTrue($executed3, 'The publisher don\'t execute middleware callback for savepoint_3.');
    }

    /**
     * @test
     */
    public function shouldSuccessRollbackToSavepoint(): void
    {
        $executed1 = false;

        $this->mustPublish($executed1, self::once(), 'foo.1', new Message(new Payload('1')));

        $this->publisher->start('savepoint_1');
        $this->publisher->publish(new Message(new Payload('1')), 'foo.1');

        $this->publisher->start('savepoint_2');
        $this->publisher->publish(new Message(new Payload('2')), 'foo.2');

        $this->publisher->start('savepoint_3');
        $this->publisher->publish(new Message(new Payload('3')), 'foo.3');

        $this->publisher->rollback('savepoint_2');

        $this->publisher->flush();

        self::assertTrue($executed1, 'The publisher don\'t execute middleware callback for savepoint_1.');
    }

    /**
     * Create executable
     *
     * @param bool         $executed
     * @param Invocation   $invocation
     * @param string|null  $expectedRouting
     * @param Message|null $expectedMessage
     */
    private function mustPublish(bool &$executed, Invocation $invocation, string $expectedRouting = null, Message $expectedMessage = null): void
    {
        $executable = static function (MessageInterface $message, string $routingKey = '') use (&$executed, $expectedRouting, $expectedMessage) {
            $executed = true;

            if (null !== $expectedRouting) {
                self::assertEquals($expectedRouting, $routingKey);
            }

            if (null !== $expectedMessage) {
                self::assertEquals($expectedMessage, $message);
            }
        };

        $this->middlewares->expects($invocation)
            ->method('createExecutable')
            ->with(self::isInstanceOf(\Closure::class))
            ->willReturn($executable);
    }
}
