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

namespace FiveLab\Component\Amqp\Tests\Unit\Queue\Registry;

use FiveLab\Component\Amqp\Exception\QueueFactoryNotFoundException;
use FiveLab\Component\Amqp\Queue\QueueFactoryInterface;
use FiveLab\Component\Amqp\Queue\Registry\QueueFactoryRegistry;
use PHPUnit\Framework\TestCase;

class QueueFactoryRegistryTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessGet(): void
    {
        $factory1 = $this->createUniqueFactory();
        $factory2 = $this->createUniqueFactory();
        $factory3 = $this->createUniqueFactory();

        $registry = new QueueFactoryRegistry();

        $registry->add('test_1', $factory1);
        $registry->add('test_2', $factory2);
        $registry->add('test_3', $factory3);

        $result = $registry->get('test_2');

        self::assertEquals($factory2, $result);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfFactoryWasNotFound(): void
    {
        self::expectException(QueueFactoryNotFoundException::class);
        self::expectExceptionMessage('The queue factory with key "some" was not found.');

        $factory = $this->createUniqueFactory();

        $registry = new QueueFactoryRegistry();

        $registry->add('test_1', $factory);

        $registry->get('some');
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfRegistryEmpty(): void
    {
        self::expectException(QueueFactoryNotFoundException::class);
        self::expectExceptionMessage('The queue factory with key "foo" was not found.');

        $registry = new QueueFactoryRegistry();

        $registry->get('foo');
    }

    /**
     * Create unique factory
     *
     * @return QueueFactoryInterface
     */
    private function createUniqueFactory(): QueueFactoryInterface
    {
        return $this->createMock(QueueFactoryInterface::class);
    }
}
