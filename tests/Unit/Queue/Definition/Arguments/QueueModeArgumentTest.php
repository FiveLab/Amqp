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

use FiveLab\Component\Amqp\Queue\Definition\Arguments\QueueModeArgument;
use PHPUnit\Framework\TestCase;

class QueueModeArgumentTest extends TestCase
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
        $argument = new QueueModeArgument($mode);

        self::assertEquals('x-queue-mode', $argument->getName());
        self::assertEquals($mode, $argument->getValue());
    }

    /**
     * @test
     */
    public function shouldThrowExceptionForInvalidMode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid queue mode "some". Possible modes: "default", "lazy".');

        new QueueModeArgument('some');
    }

    /**
     * Provide possible modes
     *
     * @return array
     */
    public function providePossibleModes(): array
    {
        return [
            ['lazy'],
            ['default'],
        ];
    }
}
