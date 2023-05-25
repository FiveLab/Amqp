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

use FiveLab\Component\Amqp\Queue\Definition\Arguments\QueueTypeArgument;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class QueueTypeArgumentTest extends TestCase
{
    #[Test]
    #[TestWith(['classic'])]
    #[TestWith(['quorum'])]
    public function shouldSuccessCreate(string $type): void
    {
        $argument = new QueueTypeArgument($type);

        self::assertEquals('x-queue-type', $argument->name);
        self::assertEquals($type, $argument->value);
    }

    #[Test]
    public function shouldThrowExceptionForInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid queue type "some". Possible types: "classic", "quorum".');

        new QueueTypeArgument('some');
    }
}
