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

namespace FiveLab\Component\Amqp\Connection;

readonly class Dsn
{
    /**
     * @var array<string, callable-string>
     */
    private const QUERY_DATA = [
        'read_timeout'        => 'floatval',
        'write_timeout'       => 'floatval',
        'heartbeat'           => 'floatval',
        'insist'              => 'boolval',
        'keepalive'           => 'boolval',
        'channel_rpc_timeout' => 'floatval',
    ];

    /**
     * Constructor.
     *
     * @param Driver                   $driver
     * @param string                   $host
     * @param int                      $port
     * @param string                   $vhost
     * @param string                   $username
     * @param string                   $password
     * @param array<int|string, mixed> $options
     */
    public function __construct(
        public Driver $driver,
        public string $host,
        public int    $port = 5672,
        public string $vhost = '/',
        public string $username = 'guest',
        public string $password = 'guest',
        public array  $options = []
    ) {
    }

    /**
     * Make a DSN from string
     *
     * @param string $dsn
     *
     * @return self
     */
    public static function fromDsn(string $dsn): self
    {
        $urlParts = \parse_url($dsn);

        if (!$urlParts) {
            throw new \InvalidArgumentException(\sprintf(
                'Can\'t parse DSN: "%s".',
                $dsn
            ));
        }

        if (!$driver = $urlParts['scheme'] ?? null) {
            throw new \InvalidArgumentException(\sprintf(
                'Invalid dsn "%s". Missed driver (scheme).',
                $dsn
            ));
        }

        if (!$host = $urlParts['host'] ?? null) {
            throw new \InvalidArgumentException(\sprintf(
                'Invalid dsn "%s". Missed host. Maybe you not add scheme (amqp://%s)?',
                $dsn,
                $dsn
            ));
        }

        \parse_str($urlParts['query'] ?? '', $queryParts);

        $options = [];

        foreach ($queryParts as $key => $value) {
            if (\array_key_exists($key, self::QUERY_DATA)) {
                $options[$key] = (self::QUERY_DATA[$key])($value);
            } else {
                $options[$key] = $value;
            }
        }

        return new self(
            Driver::from($driver),
            $host,
            $urlParts['port'] ?? 5672,
            \array_key_exists('path', $urlParts) ? \urldecode(\ltrim($urlParts['path'], '/')) : '/',
            \urldecode($urlParts['user'] ?? 'guest'),
            \urldecode($urlParts['pass'] ?? 'guest'),
            $options
        );
    }
}
