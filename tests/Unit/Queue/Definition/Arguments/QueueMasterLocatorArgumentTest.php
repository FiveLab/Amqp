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

namespace FiveLab\Component\Amqp\Tests\Unit\Queue\Definition\Arguments;

use FiveLab\Component\Amqp\Queue\Definition\Arguments\QueueMasterLocatorArgument;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class QueueMasterLocatorArgumentTest extends TestCase
{
    #[Test]
    #[TestWith(['min-masters'])]
    #[TestWith(['client-local'])]
    #[TestWith(['random'])]
    public function shouldSuccessCreate(string $locator): void
    {
        $argument = new QueueMasterLocatorArgument($locator);

        self::assertEquals('x-queue-master-locator', $argument->name);
        self::assertEquals($locator, $argument->value);
    }

    #[Test]
    public function shouldThrowExceptionForInvalidLocator(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid master locator "some". Possible locators: "min-masters", "client-local", "random".');

        new QueueMasterLocatorArgument('some');
    }
}
