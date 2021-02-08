<?php

declare(strict_types=1);

namespace FiveLab\Component\Amqp\Adapter\AmqpLib\Exchange;

use FiveLab\Component\Amqp\Adapter\AmqpLib\Channel\AmqpChannel;
use FiveLab\Component\Amqp\Channel\ChannelInterface;
use FiveLab\Component\Amqp\Exchange\Definition\ExchangeDefinition;
use FiveLab\Component\Amqp\Exchange\ExchangeInterface;
use FiveLab\Component\Amqp\Message\MessageInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * The exchanged provided via php-amqplib library.
 */
class AmqpExchange implements ExchangeInterface
{
    /**
     * @var AmqpChannel
     */
    private $channel;

    /**
     * @var ExchangeDefinition
     */
    private $definition;

    /**
     * @param AmqpChannel        $channel
     * @param ExchangeDefinition $definition
     */
    public function __construct(AmqpChannel $channel, ExchangeDefinition $definition)
    {
        $this->channel = $channel;
        $this->definition = $definition;
    }

    /**
     * {@inheritdoc}
     */
    public function getChannel(): ChannelInterface
    {
        return $this->channel;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->definition->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function publish(MessageInterface $message, string $routingKey = ''): void
    {
        $headers = $message->getHeaders()->all();
        $identifier = $message->getIdentifier();

        $options = [
            'content_type'  => $message->getPayload()->getContentType(),
            'delivery_mode' => $message->getOptions()->isPersistent() ? 2 : 1,
        ];

        if ($message->getOptions()->getExpiration()) {
            $options['expiration'] = $message->getOptions()->getExpiration();
        }

        if ($message->getPayload()->getContentEncoding()) {
            $options['content_encoding'] = $message->getPayload()->getContentEncoding();
        }

        if ($identifier->getId()) {
            $options['message_id'] = $identifier->getId();
        }

        if ($identifier->getUserId()) {
            $options['user_id'] = $identifier->getUserId();
        }

        if ($identifier->getAppId()) {
            $options['app_id'] = $identifier->getAppId();
        }

        if (\count($headers)) {
            $options['application_headers'] = new AMQPTable($headers);
        }

        $amqplibMessage = new AMQPMessage($message->getPayload()->getData(), $options);
        $this->channel->getChannel()->basic_publish($amqplibMessage, $this->getName(), $routingKey);
    }

    /**
     * Declares exchange (creates or validates existing one)
     */
    public function declare(): void
    {
        // Default exchange always exists and "declare" call is not permitted
        if (!$this->getName()) {
            return;
        }

        $arguments = new AMQPTable();

        foreach ($this->definition->getArguments() as $argument) {
            $arguments->set($argument->getName(), $argument->getValue());
        }

        $this->channel->getChannel()->exchange_declare(
            $this->getName(),
            $this->definition->getType(),
            $this->definition->isPassive(),
            $this->definition->isDurable(),
            false,
            false,
            false,
            $arguments
        );
    }

    /**
     * @param string $exchangeName
     * @param string $routingKey
     */
    public function bind(string $exchangeName, string $routingKey): void
    {
        $this->channel->getChannel()->exchange_bind($this->getName(), $exchangeName, $routingKey);
    }

    /**
     * @param string $exchangeName
     * @param string $routingKey
     */
    public function unbind(string $exchangeName, string $routingKey): void
    {
        $this->channel->getChannel()->exchange_unbind($this->getName(), $exchangeName, $routingKey);
    }
}
