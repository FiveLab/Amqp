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

/**
 * DSN for connect to RabbitMQ.
 *
 * @implements \IteratorAggregate<Dsn>
 */
readonly class Dsn implements \IteratorAggregate, \Countable, \Stringable
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
     * @var string
     */
    public string $host;

    /**
     * @var array<string>
     */
    public array $hosts;

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
        string        $host,
        public int    $port = 5672,
        public string $vhost = '/',
        public string $username = 'guest',
        public string $password = 'guest',
        public array  $options = []
    ) {
        $hosts = \explode(',', $host);
        $hosts = \array_map('\trim', $hosts);
        $hosts = \array_filter($hosts);

        if (!\count($hosts)) {
            $hosts = [''];
        }

        $this->host = $hosts[0];
        $this->hosts = $hosts;
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

    /**
     * {@inheritdoc}
     *
     * @return \ArrayIterator<int, Dsn>
     */
    public function getIterator(): \ArrayIterator
    {
        $dsns = [];

        foreach ($this->hosts as $host) {
            $dsns[] = new self(
                $this->driver,
                $host,
                $this->port,
                $this->vhost,
                $this->username,
                $this->password,
                $this->options
            );
        }

        return new \ArrayIterator($dsns);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->hosts);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Convert DSN to string
     *
     * @param bool $hidePassword
     *
     * @return string
     */
    public function toString(bool $hidePassword = true): string
    {
        $query = \count($this->options) ? '?'.\http_build_query($this->options) : '';

        return \sprintf(
            '%s://%s:%s@%s:%d/%s%s',
            $this->driver->value,
            $this->username,
            $hidePassword ? '***' : $this->password,
            \implode(',', $this->hosts),
            $this->port,
            \urlencode($this->vhost),
            $query
        );
    }
}
