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

use FiveLab\Component\Amqp\Message\Message;
use FiveLab\Component\Amqp\Message\Payload;
use FiveLab\Component\Amqp\Publisher\PublisherInterface;
use FiveLab\Component\Amqp\Publisher\SavepointPublisherDecorator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use PHPUnit\Framework\TestCase;

class SavepointPublisherTest extends TestCase
{
    /**
     * @var PublisherInterface
     */
    private PublisherInterface $originalPublisher;

    /**
     * @var SavepointPublisherDecorator
     */
    private SavepointPublisherDecorator $publisher;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->originalPublisher = $this->createMock(PublisherInterface::class);

        $this->publisher = new SavepointPublisherDecorator($this->originalPublisher);
    }

    #[Test]
    public function shouldSuccessPublishIfNoSavepoints(): void
    {
        $this->expectPublish(self::once(), 'foo.bar', new Message(new Payload('some')));

        $this->publisher->publish(new Message(new Payload('some')), 'foo.bar');
    }

    #[Test]
    public function shouldSuccessPublishInOneSavepoint(): void
    {
        $this->expectPublish(self::once(), 'foo.bar', new Message(new Payload('some')));

        $this->publisher->start('savepoint_1');
        $this->publisher->publish(new Message(new Payload('some')), 'foo.bar');
        $this->publisher->flush();
    }

    #[Test]
    public function shouldNotPublishBeforeFlush(): void
    {
        $this->expectPublish(self::never(), 'foo.bar', new Message(new Payload('some')));

        $this->publisher->start('savepoint_1');
        $this->publisher->publish(new Message(new Payload('some')), 'foo.bar');
    }

    #[Test]
    public function shouldSuccessPublishWithManySavepoints(): void
    {
        $matcher = self::exactly(3);

        $resolveExpected = static function () use ($matcher) {
            return match ($matcher->numberOfInvocations()) {
                1 => [new Message(new Payload('1')), 'foo.1'],
                2 => [new Message(new Payload('2')), 'foo.2'],
                3 => [new Message(new Payload('3')), 'foo.3']
            };
        };

        $this->originalPublisher->expects($matcher)
            ->method('publish')
            ->with(
                self::callback(static function (Message $message) use ($resolveExpected) {
                    self::assertEquals($resolveExpected()[0], $message);

                    return true;
                }),
                self::callback(static function (string $routingKey) use ($resolveExpected) {
                    self::assertEquals($resolveExpected()[1], $routingKey);

                    return true;
                })
            );

        $this->publisher->start('savepoint_1');
        $this->publisher->publish(new Message(new Payload('1')), 'foo.1');

        $this->publisher->start('savepoint_2');
        $this->publisher->publish(new Message(new Payload('2')), 'foo.2');

        $this->publisher->start('savepoint_3');
        $this->publisher->publish(new Message(new Payload('3')), 'foo.3');

        $this->publisher->flush();
    }

    #[Test]
    public function shouldSuccessRollbackToSavepoint(): void
    {
        $this->expectPublish(self::once(), 'foo.1', new Message(new Payload('1')));

        $this->publisher->start('savepoint_1');
        $this->publisher->publish(new Message(new Payload('1')), 'foo.1');

        $this->publisher->start('savepoint_2');
        $this->publisher->publish(new Message(new Payload('2')), 'foo.2');

        $this->publisher->start('savepoint_3');
        $this->publisher->publish(new Message(new Payload('3')), 'foo.3');

        $this->publisher->rollback('savepoint_2');

        $this->publisher->flush();
    }

    #[Test]
    public function shouldThrowExceptionIfStartWithExistenceSavepoint(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The savepoint "savepoint_1" already declared.');

        $this->publisher->start('savepoint_1');
        $this->publisher->start('savepoint_2');
        $this->publisher->start('savepoint_1');
    }

    #[Test]
    public function shouldThrowExceptionIfRollbackToNonExistenceSavepoint(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The savepoint "savepoint_2" does not exist.');

        $this->publisher->start('savepoint_1');
        $this->publisher->start('savepoint_3');
        $this->publisher->rollback('savepoint_2');
    }

    /**
     * Create executable
     *
     * @param InvocationOrder $invocation
     * @param string|null     $expectedRouting
     * @param Message|null    $expectedMessage
     */
    private function expectPublish(InvocationOrder $invocation, string $expectedRouting, Message $expectedMessage): void
    {
        $this->originalPublisher->expects($invocation)
            ->method('publish')
            ->with($expectedMessage, $expectedRouting);
    }
}
