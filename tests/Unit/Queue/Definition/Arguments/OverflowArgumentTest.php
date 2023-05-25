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

use FiveLab\Component\Amqp\Queue\Definition\Arguments\OverflowArgument;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class OverflowArgumentTest extends TestCase
{
    #[Test]
    #[TestWith(['drop-head'])]
    #[TestWith(['reject-publish'])]
    #[TestWith(['reject-publish-dlx'])]
    public function shouldSuccessCreate(string $mode): void
    {
        $argument = new OverflowArgument($mode);

        self::assertEquals('x-overflow', $argument->name);
        self::assertEquals($mode, $argument->value);
    }

    #[Test]
    public function shouldThrowExceptionForInvalidMode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid overflow mode "some". Possible modes: "drop-head", "reject-publish", "reject-publish-dlx".');

        new OverflowArgument('some');
    }
}
