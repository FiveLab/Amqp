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

namespace FiveLab\Component\Amqp\Tests\Unit\Consumer\Checker;

use FiveLab\Component\Amqp\Consumer\Checker\RunConsumerCheckerInterface;
use FiveLab\Component\Amqp\Consumer\Checker\RunConsumerCheckerRegistry;
use FiveLab\Component\Amqp\Exception\RunConsumerCheckerNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RunConsumerCheckerRegistryTest extends TestCase
{
    #[Test]
    public function shouldSuccessGet(): void
    {
        $checker1 = $this->createUniqueChecker();
        $checker2 = $this->createUniqueChecker();
        $checker3 = $this->createUniqueChecker();

        $registry = new RunConsumerCheckerRegistry();

        $registry->add('test_1', $checker1);
        $registry->add('test_2', $checker2);
        $registry->add('test_3', $checker3);

        $result = $registry->get('test_2');

        self::assertSame($checker2, $result);
    }

    #[Test]
    public function shouldFailIfCheckerNotFound(): void
    {
        $registry = new RunConsumerCheckerRegistry();

        $registry->add('foo', $this->createUniqueChecker());

        $this->expectException(RunConsumerCheckerNotFoundException::class);
        $this->expectExceptionMessage('The checker for consumer "bar" was not found.');

        $registry->get('bar');
    }

    #[Test]
    public function shouldFailIfRegistryIsEmpty(): void
    {
        $registry = new RunConsumerCheckerRegistry();

        $this->expectException(RunConsumerCheckerNotFoundException::class);
        $this->expectExceptionMessage('The checker for consumer "foo" was not found.');

        $registry->get('foo');
    }

    private function createUniqueChecker(): RunConsumerCheckerInterface
    {
        return $this->createMock(RunConsumerCheckerInterface::class);
    }
}
