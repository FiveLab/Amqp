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
use PHPUnit\Framework\TestCase;

class HeadersTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessCreateWithoutHeaders(): void
    {
        $headers = new Headers([]);

        self::assertEquals([], $headers->all());
    }

    /**
     * @test
     */
    public function shouldSuccessGetAndCheckHeader(): void
    {
        $headers = new Headers([
            'foo' => 'bar',
        ]);

        self::assertTrue($headers->has('foo'));
        self::assertFalse($headers->has('bar'));

        self::assertEquals('bar', $headers->get('foo'));
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfHeaderNotFound(): void
    {
        $this->expectException(HeaderNotFoundException::class);
        $this->expectExceptionMessage('The header "bar" was not found.');

        $headers = new Headers([
            'foo' => 'bar',
        ]);

        $headers->get('bar');
    }
}
