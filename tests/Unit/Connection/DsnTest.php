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

namespace FiveLab\Component\Amqp\Tests\Unit\Connection;

use FiveLab\Component\Amqp\Connection\Driver;
use FiveLab\Component\Amqp\Connection\Dsn;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class DsnTest extends TestCase
{
    #[Test]
    #[DataProvider('provideDsn')]
    public function shouldSuccessParse(string $dsn, Dsn $expected): void
    {
        $actual = Dsn::fromDsn($dsn);

        self::assertEquals($expected, $actual);
    }

    #[Test]
    public function shouldSuccessIterateAllHosts(): void
    {
        $dsn = Dsn::fromDsn('amqp://foo:bar@host1,host2,host3:5673/%2frr?read_timeout=30&write_timeout=25');

        self::assertEquals('host1', $dsn->host);
        self::assertEquals(['host1', 'host2', 'host3'], $dsn->hosts);

        self::assertCount(3, $dsn);

        $dsns = \iterator_to_array($dsn);

        self::assertEquals(new Dsn(
            Driver::AmqpExt,
            'host1',
            5673,
            '/rr',
            'foo',
            'bar',
            ['read_timeout' => 30, 'write_timeout' => 25]
        ), $dsns[0]);

        self::assertEquals(new Dsn(
            Driver::AmqpExt,
            'host2',
            5673,
            '/rr',
            'foo',
            'bar',
            ['read_timeout' => 30, 'write_timeout' => 25]
        ), $dsns[1]);

        self::assertEquals(new Dsn(
            Driver::AmqpExt,
            'host3',
            5673,
            '/rr',
            'foo',
            'bar',
            ['read_timeout' => 30, 'write_timeout' => 25]
        ), $dsns[2]);
    }

    #[Test]
    #[TestWith(['amqp://localhost', false, 'amqp://guest:guest@localhost:5672/%2F'])]
    #[TestWith(['amqp://foo:bar@host1,host2:5673/%2foo?read=1', false, 'amqp://foo:bar@host1,host2:5673/%2Foo?read=1'])]
    #[TestWith(['amqp://foo:bar@host1,host2:5673/%2foo?read=1', true, 'amqp://foo:***@host1,host2:5673/%2Foo?read=1'])]
    public function shouldSuccessConvertToString(string $dsnStr, bool $hidePassword, string $expected): void
    {
        $dsn = Dsn::fromDsn($dsnStr);

        self::assertEquals($expected, $dsn->toString($hidePassword));
    }

    #[Test]
    public function shouldSuccessRemoveOptions(): void
    {
        $dsn = Dsn::fromDsn('amqp://foo:bar@host1,host2:5673/%2Foo?read=1&foo=bar&bar=foo&some=1234');

        self::assertEquals([
            'read' => '1',
            'foo'  => 'bar',
            'bar'  => 'foo',
            'some' => '1234',
        ], $dsn->options);

        $dsn = $dsn->removeOption('bar');
        $dsn = $dsn->removeOption('some');

        self::assertEquals([
            'read' => '1',
            'foo'  => 'bar',
        ], $dsn->options);
    }

    public static function provideDsn(): array
    {
        return [
            'small' => [
                'amqp://host1.com',
                new Dsn(Driver::AmqpExt, 'host1.com'),
            ],

            'with credentials' => [
                'amqp://some:pass@host1.com',
                new Dsn(Driver::AmqpExt, 'host1.com', username: 'some', password: 'pass'),
            ],

            'with spec credentials' => [
                'amqp://user%2f123:some%40pass%3Afoo%2Fbar@host2.net',
                new Dsn(Driver::AmqpExt, 'host2.net', username: 'user/123', password: 'some@pass:foo/bar'),
            ],

            'with port and vhost' => [
                'amqp-sockets://user:pass@host3.ia:5673/%2fother',
                new Dsn(Driver::AmqpSockets, 'host3.ia', 5673, '/other', 'user', 'pass'),
            ],

            'with common query' => [
                'amqp-lib://host.com?read_timeout=30&write_timeout=32&heartbeat=10&insist=1&keepalive=1&channel_rpc_timeout=123',
                new Dsn(Driver::AmqpLib, 'host.com', options: [
                    'read_timeout'        => 30.,
                    'write_timeout'       => 32.,
                    'heartbeat'           => 10.,
                    'insist'              => true,
                    'keepalive'           => true,
                    'channel_rpc_timeout' => 123.,
                ]),
            ],

            'with multiple hosts' => [
                'amqp://host1.com,host2.com,host3.com',
                new Dsn(Driver::AmqpExt, 'host1.com,host2.com,host3.com'),
            ],
        ];
    }
}
