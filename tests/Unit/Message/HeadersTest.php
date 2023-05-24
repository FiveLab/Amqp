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

namespace FiveLab\Component\Amqp\Tests\Unit\Message;

use FiveLab\Component\Amqp\Exception\HeaderNotFoundException;
use FiveLab\Component\Amqp\Message\Headers;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HeadersTest extends TestCase
{
    #[Test]
    public function shouldSuccessCreateWithoutHeaders(): void
    {
        $headers = new Headers([]);

        self::assertEquals([], $headers->all());
    }

    #[Test]
    public function shouldSuccessGetAndCheckHeader(): void
    {
        $headers = new Headers([
            'foo' => 'bar',
        ]);

        self::assertTrue($headers->has('foo'));
        self::assertFalse($headers->has('bar'));

        self::assertEquals('bar', $headers->get('foo'));
    }

    #[Test]
    public function shouldThrowExceptionIfHeaderNotFound(): void
    {
        $this->expectException(HeaderNotFoundException::class);
        $this->expectExceptionMessage('The header "bar" was not found.');

        $headers = new Headers([
            'foo' => 'bar',
        ]);

        $headers->get('bar');
    }

    #[Test]
    public function shouldReturnArrayHeader(): void
    {
        $xDeathHeaderValue = [
            [
                'count' => 1,
            ],
        ];

        $headers = new Headers([
            'x-death' => $xDeathHeaderValue,
        ]);

        self::assertEquals($xDeathHeaderValue, $headers->get('x-death'));
    }
}
