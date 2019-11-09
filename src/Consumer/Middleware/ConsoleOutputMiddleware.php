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

namespace FiveLab\Component\Amqp\Consumer\Middleware;

use FiveLab\Component\Amqp\Message\ReceivedMessageInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The middleware for debug received messages in console output.
 */
class ConsoleOutputMiddleware implements MiddlewareInterface
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * Constructor.
     *
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Throwable
     */
    public function handle(ReceivedMessageInterface $message, callable $next): void
    {
        if (OutputInterface::VERBOSITY_DEBUG === $this->output->getVerbosity()) {
            $this->fullDebugReceivedMessage($message, $next);
        } else if (OutputInterface::VERBOSITY_VERBOSE <= $this->output->getVerbosity()) {
            $this->verboseReceivedMessage($message, $next);
        } else {
            $next($message);
        }
    }

    /**
     * Verbose received message
     *
     * @param ReceivedMessageInterface $message
     * @param callable                 $next
     *
     * @throws \Throwable
     */
    private function verboseReceivedMessage(ReceivedMessageInterface $message, callable $next): void
    {
        try {
            $next($message);
        } catch (\Throwable $e) {
            $this->output->writeln(\sprintf(
                '<error>Error: [%s] %s in %s:%d',
                \get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));

            throw $e;
        }

        $this->output->writeln(\sprintf(
            'Success process message from routing key <comment>%s</comment> with delivery tag <info>%s</info>.',
            $message->getRoutingKey(),
            $message->getDeliveryTag()
        ));
    }

    /**
     * Full debug received message
     *
     * @param ReceivedMessageInterface $message
     * @param callable                 $next
     *
     * @throws \Throwable
     */
    private function fullDebugReceivedMessage(ReceivedMessageInterface $message, callable $next): void
    {
        $this->output->writeln([
            \str_repeat('--', 16),
            \sprintf('Memory usage: <comment>%s</comment>', $this->formatMemory(\memory_get_usage(true))),
            \sprintf('Routing key: <comment>%s</comment>', $message->getRoutingKey()),
            \sprintf('Persistent: <comment>%s</comment>', $message->getOptions()->isPersistent() ? 'yes' : 'no'),
            \sprintf('Delivery tag: <comment>%s</comment>', $message->getDeliveryTag()),
            \sprintf('Payload content type: <comment>%s</comment>', $message->getPayload()->getContentType()),
            \sprintf('Payload data: %s', $message->getPayload()->getData()),
            '',
        ]);

        try {
            $next($message);
        } catch (\Throwable $e) {
            $this->output->writeln(\sprintf(
                '<error>Error: [%s] %s in %s:%d',
                \get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));

            throw $e;
        }

        $this->output->writeln([
            '<info>Success process message.</info>',
        ]);
    }

    /**
     * Format memory
     *
     * @param int $memory
     *
     * @return string
     */
    private function formatMemory(int $memory): string
    {
        switch (true) {
            case $memory > (1024 * 1024 * 1024):
                return \sprintf('%.2f Mb', $memory / (1024 * 1024 * 1024));

            case $memory > (1024 * 1024):
                return \sprintf('%.2f Kb', $memory / (1024 * 1024));

            default:
                return \sprintf('%.2f B', $memory);
        }
    }
}
