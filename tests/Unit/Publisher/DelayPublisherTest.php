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

use FiveLab\Component\Amqp\Message\DelayMessage;
use FiveLab\Component\Amqp\Message\MessageInterface;
use FiveLab\Component\Amqp\Publisher\DelayPublisher;
use FiveLab\Component\Amqp\Publisher\PublisherInterface;
use PHPUnit\Framework\TestCase;

class DelayPublisherTest extends TestCase
{
    /**
     * @var PublisherInterface
     */
    private $originalPublisher;

    /**
     * @var DelayPublisher
     */
    private $delayPublisher;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->originalPublisher = $this->createMock(PublisherInterface::class);
        $this->delayPublisher = new DelayPublisher($this->originalPublisher, 'foo.bar');
    }

    /**
     * @test
     */
    public function shouldSuccessPublish(): void
    {
        $message = $this->createMock(DelayMessage::class);

        $this->originalPublisher->expects(self::once())
            ->method('publish')
            ->with($message, 'foo.bar');

        $this->delayPublisher->publish($message);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfPassRoutingKey(): void
    {
        $message = $this->createMock(DelayMessage::class);

        $this->originalPublisher->expects(self::never())
            ->method('publish');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The routing key can\'t be specified for delay publisher.');

        $this->delayPublisher->publish($message, '');
    }

    /**
     * @test
     */
    public function shouldThrowExceptionForNonDelayMessage(): void
    {
        $message = $this->createMock(MessageInterface::class);

        $this->originalPublisher->expects(self::never())
            ->method('publish');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only FiveLab\Component\Amqp\Message\DelayMessage supported.');

        $this->delayPublisher->publish($message);
    }
}
