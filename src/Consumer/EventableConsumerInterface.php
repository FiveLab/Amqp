<?php

// phpcs:ignoreFile

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

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

interface EventableConsumerInterface extends ConsumerInterface
{
    public function setEventDispatcher(?EventDispatcherInterface $eventDispatcher): void;

    public function getEventDispatcher(): ?EventDispatcherInterface;
}
