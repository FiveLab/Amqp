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

namespace FiveLab\Component\Amqp\Tests\Functional;

use GuzzleHttp\Client;

class AmqpManagement
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $vhost;

    /**
     * Constructor.
     *
     * @param string $host
     * @param int    $port
     * @param string $login
     * @param string $password
     * @param string $vhost
     */
    public function __construct(string $host, int $port, string $login, string $password, string $vhost)
    {
        $this->client = new Client([
            'base_uri' => \sprintf('http://%s:%d', $host, $port),
            'headers'  => [
                'Authorization' => \sprintf('Basic %s', \base64_encode($login.':'.$password)),
            ],
        ]);

        $this->vhost = $vhost;
    }

    /**
     * Create queue
     *
     * @param string $name
     * @param bool   $durable
     */
    public function createQueue(string $name, bool $durable = true): void
    {
        $data = [
            'auto_delete' => false,
            'durable'     => $durable,
            'arguments'   => [],
        ];

        $json = \json_encode($data);

        $this->client->put(
            \sprintf('/api/queues/%s/%s', \urlencode($this->vhost), $name),
            [
                'body' => $json,
            ]
        );
    }

    /**
     * List queues
     *
     * @return array
     */
    public function queues(): array
    {
        $response = $this->client->get(\sprintf('/api/queues/%s', \urlencode($this->vhost)));

        return \json_decode((string) $response->getBody(), true);
    }

    /**
     * Get the queue by name
     *
     * @param string $name
     *
     * @return array
     */
    public function queueByName(string $name): array
    {
        $queues = $this->queues();

        foreach ($queues as $queue) {
            if ($name === $queue['name']) {
                return $queue;
            }
        }

        throw new \RuntimeException(\sprintf(
            'The queue with name "%s" was not found.',
            $name
        ));
    }

    /**
     * Get queue bindings
     *
     * @param string $name
     *
     * @return array
     */
    public function queueBindings(string $name): array
    {
        $response = $this->client->get(\sprintf('/api/queues/%s/%s/bindings', \urlencode($this->vhost), \urlencode($name)));

        return \json_decode((string) $response->getBody(), true);
    }

    /**
     * Bind queue
     *
     * @param string $name
     * @param string $exchangeName
     * @param string $routingKey
     */
    public function queueBind(string $name, string $exchangeName, string $routingKey): void
    {
        $data = [
            'routing_key' => $routingKey,
        ];

        $json = \json_encode($data);

        $this->client->post(
            \sprintf('/api/bindings/%s/e/%s/q/%s', \urlencode($this->vhost), \urlencode($exchangeName), \urlencode($name)),
            [
                'body' => $json,
            ]
        );
    }

    /**
     * Delete queue
     *
     * @param string $name
     */
    public function deleteQueue(string $name): void
    {
        $this->client->delete(\sprintf('/api/queues/%s/%s', \urlencode($this->vhost), \urlencode($name)));
    }

    /**
     * Get last message
     *
     * @param string $name
     * @param int    $count
     *
     * @return array
     */
    public function queueGetMessages(string $name, int $count): array
    {
        $data = [
            'ackmode'  => 'ack_requeue_true',
            'count'    => $count,
            'encoding' => 'auto',
        ];

        $json = \json_encode($data);

        $response = $this->client->post(
            \sprintf('/api/queues/%s/%s/get', \urlencode($this->vhost), \urlencode($name)),
            [
                'body' => $json,
            ]
        );

        return \json_decode((string) $response->getBody(), true);
    }

    /**
     * Create exchange
     *
     * @param string $type
     * @param string $name
     */
    public function createExchange(string $type, string $name): void
    {
        $data = [
            'type'        => $type,
            'auto_delete' => false,
            'durable'     => true,
            'internal'    => false,
            'arguments'   => [],
        ];

        $json = \json_encode($data);

        $this->client->put(
            \sprintf(
                '/api/exchanges/%s/%s',
                \urlencode($this->vhost),
                \urlencode($name)
            ),
            [
                'body' => $json,
            ]
        );
    }

    /**
     * List exchanges
     *
     * @return array
     */
    public function exchanges(): array
    {
        $response = $this->client->get(\sprintf('/api/exchanges/%s', \urlencode($this->vhost)));

        return \json_decode((string) $response->getBody(), true);
    }

    /**
     * Get exchange by name
     *
     * @param string $name
     *
     * @return array
     */
    public function exchangeByName(string $name): array
    {
        $exchanges = $this->exchanges();

        foreach ($exchanges as $exchange) {
            if ($name === $exchange['name']) {
                return $exchange;
            }
        }

        throw new \RuntimeException(\sprintf(
            'The exchange with name "%s" was not found.',
            $name
        ));
    }

    /**
     * Get exchange bindings
     *
     * @param string $name
     *
     * @return array
     */
    public function exchangeBindings(string $name): array
    {
        $response = $this->client->get(\sprintf('/api/exchanges/%s/%s/bindings/destination', \urlencode($this->vhost), $name));

        return \json_decode((string) $response->getBody(), true);
    }

    /**
     * Delete exchange
     *
     * @param string $name
     */
    public function deleteExchange(string $name): void
    {
        $this->client->delete(\sprintf('/api/exchanges/%s/%s', \urlencode($this->vhost), \urlencode($name)));
    }

    /**
     * Publish message to exchange
     *
     * @param string $exchangeName
     * @param string $routingKey
     * @param string $payload
     */
    public function publishMessage(string $exchangeName, string $routingKey, string $payload): void
    {
        $data = [
            'properties'       => [],
            'routing_key'      => $routingKey,
            'payload'          => $payload,
            'payload_encoding' => 'string',
        ];

        $json = \json_encode($data, JSON_FORCE_OBJECT);

        $this->client->post(
            \sprintf('/api/exchanges/%s/%s/publish', \urlencode($this->vhost), \urlencode($exchangeName)),
            [
                'body' => $json,
            ]
        );
    }
}
