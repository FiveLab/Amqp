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
use FiveLab\Component\Amqp\Consumer\Registry\ContainerConsumerRegistry;
use FiveLab\Component\Amqp\Exception\ConsumerNotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ContainerConsumerRegistryTest extends TestCase
{
    /**
     * @var array<ConsumerInterface>
     */
    private array $consumers;

    /**
     * @var ContainerConsumerRegistry
     */
    private ContainerConsumerRegistry $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->consumers = [
            $this->makeUniqueConsumer(),
            $this->makeUniqueConsumer(),
        ];

        $container = $this->createMock(ContainerInterface::class);

        $container->expects(self::any())
            ->method('has')
            ->willReturnCallback(static function (string $key) {
                return \in_array($key, ['consumer_1', 'consumer_2'], true);
            });

        $container->expects(self::any())
            ->method('get')
            ->willReturnMap([
                ['consumer_1', $this->consumers[0]],
                ['consumer_2', $this->consumers[1]],
            ]);

        $this->registry = new ContainerConsumerRegistry($container, [
            'consumer_1',
            'consumer_2',
        ]);
    }

    /**
     * @test
     */
    public function shouldSuccessGet(): void
    {
        $consumer2 = $this->registry->get('consumer_2');
        self::assertEquals($this->consumers[1], $consumer2);

        $consumer1 = $this->registry->get('consumer_1');
        self::assertEquals($this->consumers[0], $consumer1);
    }

    /**
     * @test
     */
    public function shouldFailGetIfNotFound(): void
    {
        $this->expectException(ConsumerNotFoundException::class);
        $this->expectExceptionMessage('The consumer with key "foo" was not found.');

        $this->registry->get('foo');
    }

    /**
     * @test
     */
    public function shouldSuccessGetAll(): void
    {
        $consumers = $this->registry->all();

        self::assertEquals([
            'consumer_1' => $this->consumers[0],
            'consumer_2' => $this->consumers[1],
        ], $consumers);
    }

    /**
     * Make unique consumer
     *
     * @return ConsumerInterface
     */
    private function makeUniqueConsumer(): ConsumerInterface
    {
        $consumer = $this->createMock(ConsumerInterface::class);

        // phpcs:ignore Zend.NamingConventions.ValidVariableName.NotCamelCaps
        $consumer->__unique = \uniqid((string) \random_int(0, PHP_INT_MAX), true);

        return $consumer;
    }
}
