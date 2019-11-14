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
use PHPUnit\Framework\TestCase;

class OverflowArgumentTest extends TestCase
{
    /**
     * @test
     *
     * @param string $mode
     *
     * @dataProvider providePossibleModes
     */
    public function shouldSuccessCreate(string $mode): void
    {
        $argument = new OverflowArgument($mode);

        self::assertEquals('x-overflow', $argument->getName());
        self::assertEquals($mode, $argument->getValue());
    }

    /**
     * @test
     */
    public function shouldThrowExceptionForInvalidMode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid overflow mode "some". Possible modes: "drop-head", "reject-publish", "reject-publish-dlx".');

        new OverflowArgument('some');
    }

    /**
     * Provide possible modes
     *
     * @return array
     */
    public function providePossibleModes(): array
    {
        return [
            ['drop-head'],
            ['reject-publish'],
            ['reject-publish-dlx'],
        ];
    }
}
