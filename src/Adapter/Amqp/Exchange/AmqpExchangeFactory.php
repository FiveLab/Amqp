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

namespace FiveLab\Component\Amqp\Adapter\Amqp\Exchange;

use FiveLab\Component\Amqp\Adapter\Amqp\Channel\AmqpChannel;
use FiveLab\Component\Amqp\Adapter\Amqp\Connection\AmqpConnection;
use FiveLab\Component\Amqp\Channel\ChannelFactoryInterface;
use FiveLab\Component\Amqp\Exchange\Definition\ExchangeDefinition;
use FiveLab\Component\Amqp\Exchange\ExchangeFactoryInterface;
use FiveLab\Component\Amqp\Exchange\ExchangeInterface;

class AmqpExchangeFactory implements ExchangeFactoryInterface, \SplObserver
{
    private ?AmqpExchange $exchange = null;

    public function __construct(
        private readonly ChannelFactoryInterface $channelFactory,
        private readonly ExchangeDefinition      $definition
    ) {
    }

    public function create(): ExchangeInterface
    {
        if ($this->exchange) {
            return $this->exchange;
        }

        $channel = $this->channelFactory->create();

        if (!$channel instanceof AmqpChannel) {
            throw new \InvalidArgumentException(\sprintf(
                'The channel "%s" does not support for create exchange.',
                \get_class($channel)
            ));
        }

        /** @var AmqpConnection $connection */
        $connection = $channel->getConnection();

        $connection->attach($this);

        $exchange = new \AMQPExchange($channel->getChannel());
        $flags = $this->calculateFlagsForExchange();

        $exchange->setName($this->definition->name);
        $exchange->setType($this->definition->type);
        $exchange->setFlags($flags);
        $exchange->setArguments($this->definition->arguments->toArray());

        if ('' !== $this->definition->name) {
            // We must declare only non-default exchanges.
            $exchange->declareExchange();
        }

        foreach ($this->definition->bindings as $binding) {
            $exchange->bind($binding->exchangeName, $binding->routingKey);
        }

        foreach ($this->definition->unbindings as $unbinding) {
            $exchange->unbind($unbinding->exchangeName, $unbinding->routingKey);
        }

        $this->exchange = new AmqpExchange($channel, $exchange);

        return $this->exchange;
    }

    public function update(\SplSubject $subject): void
    {
        $this->exchange = null;
    }

    private function calculateFlagsForExchange(): int
    {
        $flags = AMQP_NOPARAM;

        if ($this->definition->passive) {
            $flags |= AMQP_PASSIVE;
        }

        if ($this->definition->durable) {
            $flags |= AMQP_DURABLE;
        }

        return $flags;
    }
}
