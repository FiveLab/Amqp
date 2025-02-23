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

use FiveLab\Component\Amqp\Message\ReceivedMessage;
use Symfony\Component\Console\Output\OutputInterface;

readonly class ConsoleOutputMiddleware implements ConsumerMiddlewareInterface
{
    public function __construct(private OutputInterface $output)
    {
    }

    public function handle(ReceivedMessage $message, callable $next): void
    {
        if ($this->output->isDebug()) {
            $this->fullDebugReceivedMessage($message, $next);
        } elseif ($this->output->isVerbose()) {
            $this->verboseReceivedMessage($message, $next);
        } else {
            $next($message);
        }
    }

    private function verboseReceivedMessage(ReceivedMessage $message, callable $next): void
    {
        try {
            $next($message);
        } catch (\Throwable $e) {
            $this->output->writeln(\sprintf(
                '<error>Error: [%s] %s in %s:%d</error>',
                \get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));

            throw $e;
        }

        $this->output->writeln(\sprintf(
            'Success process message from routing key <comment>%s</comment> with delivery tag <info>%s</info>.',
            $message->routingKey,
            $message->deliveryTag
        ));
    }

    private function fullDebugReceivedMessage(ReceivedMessage $message, callable $next): void
    {
        $this->output->writeln([
            \str_repeat('--', 16),
            \sprintf('Memory usage: <comment>%s</comment>', $this->formatMemory(\memory_get_usage(true))),
            \sprintf('Routing key: <comment>%s</comment>', $message->routingKey),
            \sprintf('Persistent: <comment>%s</comment>', $message->options->persistent ? 'yes' : 'no'),
            \sprintf('Delivery tag: <comment>%s</comment>', $message->deliveryTag),
            \sprintf('Payload content type: <comment>%s</comment>', $message->payload->contentType),
            \sprintf('Payload data: %s', $message->payload->data),
            '',
        ]);

        try {
            $next($message);
        } catch (\Throwable $e) {
            $this->output->writeln(\sprintf(
                '<error>Error: [%s] %s in %s:%d</error>',
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

    private function formatMemory(int $memory): string
    {
        return match (true) {
            $memory > (1024 * 1024 * 1024) => \sprintf('%.2f Mb', $memory / (1024 * 1024 * 1024)),
            $memory > (1024 * 1024)        => \sprintf('%.2f Kb', $memory / (1024 * 1024)),
            default                        => \sprintf('%.2f B', $memory),
        };
    }
}
