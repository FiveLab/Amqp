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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FlushSavepointPublisherTransactionalTest extends TestCase
{
    /**
     * @var SavepointPublisherInterface|MockObject
     */
    private $publisher;

    /**
     * @var FlushSavepointPublisherTransactional
     */
    private $transactional;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->publisher = $this->createMock(SavepointPublisherInterface::class);
        $this->transactional = new FlushSavepointPublisherTransactional($this->publisher);
    }

    /**
     * @test
     */
    public function shouldSuccessBegin(): void
    {
        $this->publisher->expects(self::once())
            ->method('start')
            ->with('savepoint_0');

        $this->transactional->begin();
    }

    /**
     * @test
     */
    public function shouldSuccessCommit(): void
    {
        $this->publisher->expects(self::once())
            ->method('flush');

        $this->transactional->begin();
        $this->transactional->commit();
    }

    /**
     * @test
     */
    public function shouldSuccessRollback(): void
    {
        $this->publisher->expects(self::once())
            ->method('rollback')
            ->with('savepoint_0');

        $this->transactional->begin();
        $this->transactional->rollback();
    }

    /**
     * @test
     */
    public function shouldSuccessExecute(): void
    {
        $this->publisher->expects(self::at(0))
            ->method('start')
            ->with('savepoint_0');

        $this->publisher->expects(self::at(1))
            ->method('flush');

        $result = $this->transactional->execute(static function () {
            return 'some foo';
        });

        self::assertEquals('some foo', $result);
    }

    /**
     * @test
     */
    public function shouldSuccessExecuteWithException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('some foo');

        $this->publisher->expects(self::at(0))
            ->method('start')
            ->with('savepoint_0');

        $this->publisher->expects(self::at(1))
            ->method('rollback')
            ->with('savepoint_0');

        $this->transactional->execute(static function () {
            throw new \InvalidArgumentException('some foo');
        });
    }

    /**
     * @test
     */
    public function shouldSuccessExecuteWithControlNestingLevel(): void
    {
        $this->publisher->expects(self::at(0))
            ->method('start')
            ->with('savepoint_0');

        $this->publisher->expects(self::at(1))
            ->method('start')
            ->with('savepoint_1');

        $this->publisher->expects(self::at(2))
            ->method('rollback')
            ->with('savepoint_1');

        $this->publisher->expects(self::at(3))
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
