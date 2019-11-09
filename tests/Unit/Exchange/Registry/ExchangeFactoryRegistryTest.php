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

namespace FiveLab\Component\Amqp\Tests\Unit\Exchange\Registry;

use FiveLab\Component\Amqp\Exception\ExchangeFactoryNotFoundException;
use FiveLab\Component\Amqp\Exchange\ExchangeFactoryInterface;
use FiveLab\Component\Amqp\Exchange\Registry\ExchangeFactoryRegistry;
use PHPUnit\Framework\TestCase;

class ExchangeFactoryRegistryTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessGet(): void
    {
        $factory1 = $this->createUniqueFactory();
        $factory2 = $this->createUniqueFactory();
        $factory3 = $this->createUniqueFactory();

        $registry = new ExchangeFactoryRegistry();

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
        self::expectException(ExchangeFactoryNotFoundException::class);
        self::expectExceptionMessage('The exchange factory with key "some" was not found.');

        $factory = $this->createUniqueFactory();

        $registry = new ExchangeFactoryRegistry();

        $registry->add('test_1', $factory);

        $registry->get('some');
    }

    /**
     * @test
     */
    public function shouldThrowEceptionIfRegistryEmpty(): void
    {
        self::expectException(ExchangeFactoryNotFoundException::class);
        self::expectExceptionMessage('The exchange factory with key "foo" was not found.');

        $registry = new ExchangeFactoryRegistry();

        $registry->get('foo');
    }

    /**
     * Create unique factory
     *
     * @return ExchangeFactoryInterface
     */
    private function createUniqueFactory(): ExchangeFactoryInterface
    {
        $factory = $this->createMock(ExchangeFactoryInterface::class);
        $factory->uniqueIdentifier = \uniqid();

        return $factory;
    }
}
