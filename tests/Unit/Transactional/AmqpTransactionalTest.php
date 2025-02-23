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

namespace FiveLab\Component\Amqp\Tests\Unit\Transactional;

use FiveLab\Component\Amqp\Channel\ChannelFactoryInterface;
use FiveLab\Component\Amqp\Channel\ChannelInterface;
use FiveLab\Component\Amqp\Transactional\AmqpTransactional;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AmqpTransactionalTest extends TestCase
{
    private ChannelInterface $channel;
    private AmqpTransactional $transactional;

    protected function setUp(): void
    {
        $this->channel = $this->createMock(ChannelInterface::class);

        $channelFactory = $this->createMock(ChannelFactoryInterface::class);

        $channelFactory->expects(self::any())
            ->method('create')
            ->willReturn($this->channel);

        $this->transactional = new AmqpTransactional($channelFactory);
    }

    #[Test]
    public function shouldSuccessBegin(): void
    {
        $this->channel->expects(self::once())
            ->method('startTransaction');

        $this->transactional->begin();
    }

    #[Test]
    public function shouldSuccessCommit(): void
    {
        $this->channel->expects(self::once())
            ->method('commitTransaction');

        $this->transactional->commit();
    }

    #[Test]
    public function shouldSuccessRollback(): void
    {
        $this->channel->expects(self::once())
            ->method('rollbackTransaction');

        $this->transactional->rollback();
    }

    #[Test]
    public function shouldSuccessExecute(): void
    {
        $this->channel->expects(self::once())
            ->method('startTransaction');

        $this->channel->expects(self::once())
            ->method('commitTransaction');

        $result = $this->transactional->execute(static function () {
            return 'some foo';
        });

        self::assertEquals('some foo', $result);
    }

    #[Test]
    public function shouldSuccessExecuteWithFail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('some foo');

        $this->channel->expects(self::once())
            ->method('startTransaction');

        $this->channel->expects(self::once())
            ->method('rollbackTransaction');

        $this->transactional->execute(static function () {
            throw new \InvalidArgumentException('some foo');
        });
    }

    #[Test]
    public function shouldSuccessExecuteWithControlNestingLevel(): void
    {
        $this->channel->expects(self::once())
            ->method('startTransaction');

        $this->channel->expects(self::once())
            ->method('commitTransaction');

        $this->transactional->begin();
        $this->transactional->begin();
        $this->transactional->begin();

        $this->transactional->commit();
        $this->transactional->commit();
        $this->transactional->commit();
    }

    #[Test]
    public function shouldFailExecuteWithControlNestingLevel(): void
    {
        $this->channel->expects(self::once())
            ->method('startTransaction');

        $this->channel->expects(self::once())
            ->method('rollbackTransaction');

        $this->transactional->begin();
        $this->transactional->begin();
        $this->transactional->begin();

        $this->transactional->rollback();
        $this->transactional->rollback();
        $this->transactional->rollback();
    }
}
