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

use FiveLab\Component\Amqp\Publisher\SavepointPublisherInterface;
use FiveLab\Component\Amqp\Transactional\FlushSavepointPublisherTransactional;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FlushSavepointPublisherTransactionalTest extends TestCase
{
    /**
     * @var SavepointPublisherInterface
     */
    private SavepointPublisherInterface $publisher;

    /**
     * @var FlushSavepointPublisherTransactional
     */
    private FlushSavepointPublisherTransactional $transactional;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->publisher = $this->createMock(SavepointPublisherInterface::class);
        $this->transactional = new FlushSavepointPublisherTransactional($this->publisher);
    }

    #[Test]
    public function shouldSuccessBegin(): void
    {
        $this->publisher->expects(self::once())
            ->method('start')
            ->with('savepoint_0');

        $this->transactional->begin();
    }

    #[Test]
    public function shouldSuccessCommit(): void
    {
        $this->publisher->expects(self::once())
            ->method('flush');

        $this->transactional->begin();
        $this->transactional->commit();
    }

    #[Test]
    public function shouldSuccessRollback(): void
    {
        $this->publisher->expects(self::once())
            ->method('rollback')
            ->with('savepoint_0');

        $this->transactional->begin();
        $this->transactional->rollback();
    }

    #[Test]
    public function shouldSuccessExecute(): void
    {
        $this->publisher->expects(self::once())
            ->method('start')
            ->with('savepoint_0');

        $this->publisher->expects(self::once())
            ->method('flush');

        $result = $this->transactional->execute(static function () {
            return 'some foo';
        });

        self::assertEquals('some foo', $result);
    }

    #[Test]
    public function shouldSuccessExecuteWithException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('some foo');

        $this->publisher->expects(self::once())
            ->method('start')
            ->with('savepoint_0');

        $this->publisher->expects(self::once())
            ->method('rollback')
            ->with('savepoint_0');

        $this->transactional->execute(static function () {
            throw new \InvalidArgumentException('some foo');
        });
    }

    #[Test]
    public function shouldSuccessExecuteWithControlNestingLevel(): void
    {
        $startMatcher = self::exactly(2);

        $this->publisher->expects($startMatcher)
            ->method('start')
            ->with(self::callback(static function (string $point) use ($startMatcher) {
                $expected = match ($startMatcher->numberOfInvocations()) {
                    1 => 'savepoint_0',
                    2 => 'savepoint_1'
                };

                self::assertEquals($expected, $point);

                return true;
            }));

        $this->publisher->expects(self::once())
            ->method('rollback')
            ->with('savepoint_1');

        $this->publisher->expects(self::once())
            ->method('flush');

        $this->transactional->execute(function () {
            try {
                $this->transactional->execute(static function () {
                    throw new \RuntimeException('some foo');
                });
            } catch (\RuntimeException $e) {
                if ('some foo' !== $e->getMessage()) {
                    throw $e;
                }
            }
        });
    }
}
