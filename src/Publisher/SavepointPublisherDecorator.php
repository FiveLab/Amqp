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

namespace FiveLab\Component\Amqp\Publisher;

use FiveLab\Component\Amqp\Message\MessageInterface;

/**
 * The publisher with support savepoint functionality.
 */
class SavepointPublisherDecorator implements SavepointPublisherInterface
{
    /**
     * @var PublisherInterface
     */
    private PublisherInterface $publisher;

    /**
     * @var array
     */
    private array $savepoints = [];

    /**
     * @var string
     */
    private string $activeSavepoint = '';

    /**
     * Constructor.
     *
     * @param PublisherInterface $publisher
     */
    public function __construct(PublisherInterface $publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * {@inheritdoc}
     */
    public function publish(MessageInterface $message, string $routingKey = ''): void
    {
        if ($this->activeSavepoint) {
            $this->savepoints[$this->activeSavepoint][] = [$message, $routingKey];
        } else {
            $this->publisher->publish($message, $routingKey);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function start(string $savepoint): void
    {
        if (\array_key_exists($savepoint, $this->savepoints)) {
            throw new \RuntimeException(\sprintf(
                'The savepoint "%s" already declared.',
                $savepoint
            ));
        }

        $this->savepoints[$savepoint] = [];
        $this->activeSavepoint = $savepoint;
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(string $savepoint): void
    {
        if (!\array_key_exists($savepoint, $this->savepoints)) {
            throw new \RuntimeException(\sprintf(
                'The savepoint "%s" does not exist.',
                $savepoint
            ));
        }

        $mustDelete = false;
        $mustDeleteSavepoints = [];

        foreach ($this->savepoints as $savepointName => $messages) {
            if ($savepointName === $savepoint) {
                $mustDelete = true;
            }

            if ($mustDelete) {
                $mustDeleteSavepoints[] = $savepointName;
            }
        }

        foreach ($mustDeleteSavepoints as $mustDeleteSavepoint) {
            unset($this->savepoints[$mustDeleteSavepoint]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): void
    {
        foreach ($this->savepoints as $messages) {
            foreach ($messages as $messageInfo) {
                $this->publisher->publish($messageInfo[0], $messageInfo[1]);
            }
        }

        $this->savepoints = [];
        $this->activeSavepoint = '';
    }
}
