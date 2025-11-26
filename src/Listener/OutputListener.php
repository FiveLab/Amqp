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

namespace FiveLab\Component\Amqp\Listener;

use FiveLab\Component\Amqp\Consumer\ConsumerStoppedReason;
use FiveLab\Component\Amqp\Event\ConsumerStoppedEvent;
use FiveLab\Component\Amqp\Event\ReceiveMessageEvent;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OutputListener implements EventSubscriberInterface
{
    private ?OutputInterface $output = null;

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND      => ['onConsoleCommand', 0],
            ConsumerStoppedEvent::class => ['onConsumerStopped', 0],
            ReceiveMessageEvent::class  => ['onReceiveMessage', 0],
        ];
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $this->output = $event->getOutput();
    }

    public function onConsumerStopped(ConsumerStoppedEvent $event): void
    {
        $message = match ($event->reason) {
            ConsumerStoppedReason::Timeout        => '<comment>Receive consumer timeout exceed error.</comment>',
            ConsumerStoppedReason::StopConsuming  => '<comment>Stop consuming.</comment>',
            ConsumerStoppedReason::ChangeConsumer => \sprintf(
                'Select next consumer for queue <comment>%s</comment>.',
                $event->options['next_consumer']->getQueue()->getName() // @phpstan-ignore-line offsetAccess.notFound
            )
        };

        $this->output?->writeln($message, OutputInterface::VERBOSITY_VERBOSE);
    }

    public function onReceiveMessage(ReceiveMessageEvent $event): void
    {
        if (!$this->output) {
            return;
        }

        if ($this->output->isDebug()) {
            $this->output->writeln([
                \str_repeat('--', 16),
                \sprintf('Memory usage: <comment>%s</comment>', $this->formatMemory(\memory_get_usage(true))),
                \sprintf('Routing key: <comment>%s</comment>', $event->message->routingKey),
                \sprintf('Persistent: <comment>%s</comment>', $event->message->options->persistent ? 'yes' : 'no'),
                \sprintf('Delivery tag: <comment>%s</comment>', $event->message->deliveryTag),
                \sprintf('Payload content type: <comment>%s</comment>', $event->message->payload->contentType),
                \sprintf('Payload data: %s', $event->message->payload->data),
                '',
            ]);
        } elseif ($this->output->isVerbose()) {
            $this->output->writeln(\sprintf(
                'Received message from routing <comment>%s</comment> with delivery tag <info>%s</info>.',
                $event->message->routingKey,
                $event->message->deliveryTag
            ));
        }
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
