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

namespace FiveLab\Component\Amqp\Tests\Unit\Consumer\Handler;

use FiveLab\Component\Amqp\Consumer\Handler\HandleExpiredMessageHandler;
use FiveLab\Component\Amqp\Message\Headers;
use FiveLab\Component\Amqp\Message\Identifier;
use FiveLab\Component\Amqp\Message\Message;
use FiveLab\Component\Amqp\Message\Options;
use FiveLab\Component\Amqp\Message\Payload;
use FiveLab\Component\Amqp\Publisher\PublisherInterface;
use FiveLab\Component\Amqp\Publisher\Registry\PublisherRegistryInterface;
use FiveLab\Component\Amqp\Tests\Unit\Message\ReceivedMessageStub;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class HandleExpiredMessageHandlerTest extends TestCase
{
    /**
     * @var PublisherRegistryInterface
     */
    private PublisherRegistryInterface $publisherRegistry;

    /**
     * @var PublisherInterface
     */
    private PublisherInterface $delayPublisher;

    /**
     * @var HandleExpiredMessageHandler
     */
    private HandleExpiredMessageHandler $handler;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->publisherRegistry = $this->createMock(PublisherRegistryInterface::class);
        $this->delayPublisher = $this->createMock(PublisherInterface::class);
        $this->handler = new HandleExpiredMessageHandler($this->publisherRegistry, $this->delayPublisher, 'landfill');
    }

    #[Test]
    #[TestWith(['landfill', true])]
    #[TestWith(['foo', false])]
    public function shouldSuccessSupports(string $routing, bool $supports): void
    {
        $message = new ReceivedMessageStub(new Payload(''), 0, '', '', null, new Headers([
            'x-death' => [
                [
                    'routing-keys' => [$routing],
                ],
            ],
        ]));

        $result = $this->handler->supports($message);

        self::assertEquals($supports, $result);
    }

    #[Test]
    public function shouldSuccessPublishToTarget(): void
    {
        $message = new ReceivedMessageStub(
            new Payload('foo bar'),
            0,
            '',
            '',
            new Options(false),
            new Headers([
                'x-delay-publisher'   => 'processing',
                'x-delay-routing-key' => 'some.process',
                'x-delay-counter'     => 1,
                'some-header'         => 'some-value',
            ]),
            new Identifier('qq')
        );

        $publisher = $this->createMock(PublisherInterface::class);

        $publisher->expects(self::once())
            ->method('publish')
            ->with(new Message(
                new Payload('foo bar'),
                null,
                new Headers(['some-header' => 'some-value']),
                new Identifier('qq')
            ), 'some.process');

        $this->publisherRegistry->expects(self::once())
            ->method('get')
            ->with('processing')
            ->willReturn($publisher);

        $this->delayPublisher->expects(self::never())
            ->method('publish');

        $this->handler->handle($message);
    }

    #[Test]
    public function shouldSuccessRetryPublishToDelay(): void
    {
        $message = new ReceivedMessageStub(
            new Payload('foo bar'),
            0,
            '',
            '',
            null,
            new Headers([
                'x-delay-publisher'   => 'processing',
                'x-delay-routing-key' => 'some.process',
                'x-delay-counter'     => 5,
                'some-header'         => 'some-value',
            ]),
            new Identifier('qq')
        );

        $this->delayPublisher->expects(self::once())
            ->method('publish')
            ->with(new Message(
                new Payload('foo bar'),
                null,
                new Headers([
                    'x-delay-publisher'   => 'processing',
                    'x-delay-routing-key' => 'some.process',
                    'x-delay-counter'     => 4,
                    'some-header'         => 'some-value',
                ]),
                new Identifier('qq')
            ), 'landfill');

        $this->handler->handle($message);
    }
}
