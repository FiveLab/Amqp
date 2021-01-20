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

use FiveLab\Component\Amqp\Queue\QueueInterface;
use Psr\Log\LoggerInterface;

/**
 * Decorate consumer for logging.
 */
class LoggingConsumer implements ConsumerInterface
{
    /**
     * @var ConsumerInterface
     */
    private $decoratedConsumer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param ConsumerInterface $decoratedConsumer
     * @param LoggerInterface   $logger
     */
    public function __construct(ConsumerInterface $decoratedConsumer, LoggerInterface $logger)
    {
        $this->decoratedConsumer = $decoratedConsumer;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueue(): QueueInterface
    {
        return $this->decoratedConsumer->getQueue();
    }

    /**
     * {@inheritdoc}
     */
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
}
