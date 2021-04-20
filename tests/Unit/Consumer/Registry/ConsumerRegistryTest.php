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

namespace FiveLab\Component\Amqp\Tests\Unit\Consumer\Registry;

use FiveLab\Component\Amqp\Consumer\ConsumerInterface;
use FiveLab\Component\Amqp\Consumer\Registry\ConsumerRegistry;
use FiveLab\Component\Amqp\Exception\ConsumerNotFoundException;
use PHPUnit\Framework\TestCase;

class ConsumerRegistryTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessGet(): void
    {
        $consumer1 = $this->createUniqueConsumer();
        $consumer2 = $this->createUniqueConsumer();
        $consumer3 = $this->createUniqueConsumer();

        $registry = new ConsumerRegistry();

        $registry->add('test_1', $consumer1);
        $registry->add('test_2', $consumer2);
        $registry->add('test_3', $consumer3);

        $result = $registry->get('test_2');

        self::assertEquals($consumer2, $result);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfConsumerWasNotFound(): void
    {
        self::expectException(ConsumerNotFoundException::class);
        self::expectExceptionMessage('The consumer with key "some" was not found.');

        $consumer = $this->createUniqueConsumer();

        $registry = new ConsumerRegistry();

        $registry->add('test_1', $consumer);

        $registry->get('some');
    }

    /**
     * @test
     */
    public function shouldThrowEceptionIfRegistryEmpty(): void
    {
        self::expectException(ConsumerNotFoundException::class);
        self::expectExceptionMessage('The consumer with key "foo" was not found.');

        $registry = new ConsumerRegistry();

        $registry->get('foo');
    }

    /**
     * Create unique consumer
     *
     * @return ConsumerInterface
     */
    private function createUniqueConsumer(): ConsumerInterface
    {
        $consumer = $this->createMock(ConsumerInterface::class);
        $consumer->uniqueIdentifier = \uniqid('', true);

        return $consumer;
    }
}
