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

namespace FiveLab\Component\Amqp\Consumer;

use FiveLab\Component\Amqp\Consumer\Middleware\ConsumerMiddlewareInterface;
use FiveLab\Component\Amqp\Queue\QueueInterface;
use Psr\Log\LoggerInterface;

readonly class LoggingConsumer implements ConsumerInterface, MiddlewareAwareInterface
{
    public function __construct(
        private ConsumerInterface $decoratedConsumer,
        private LoggerInterface   $logger
    ) {
    }

    public function getQueue(): QueueInterface
    {
        return $this->decoratedConsumer->getQueue();
    }

    public function run(): void
    {
        $this->logger->info(\sprintf(
            'Start consume on "%s" queue.',
            $this->getQueue()->getName()
        ));

        try {
            $this->decoratedConsumer->run();
        } catch (\Throwable $error) {
            $this->logger->error(\sprintf(
                'Error consume: %s %s in file %s on line %d.',
                \get_class($error),
                $error->getMessage(),
                $error->getFile(),
                $error->getLine()
            ));

            throw $error;
        }

        $this->logger->info(\sprintf(
            'End consume on "%s" queue.',
            $this->getQueue()->getName()
        ));
    }

    public function pushMiddleware(ConsumerMiddlewareInterface $middleware): void
    {
        if (!$this->decoratedConsumer instanceof MiddlewareAwareInterface) {
            throw new \BadMethodCallException('Decorated consumer must implement MiddlewareAwareInterface');
        }

        $this->decoratedConsumer->pushMiddleware($middleware);
    }
}
