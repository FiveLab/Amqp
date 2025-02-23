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

namespace FiveLab\Component\Amqp\Message;

class MutableReceivedMessages extends ReceivedMessages
{
    public function push(ReceivedMessage $message): void
    {
        $this->messages[] = $message;
    }

    public function clear(): void
    {
        $this->messages = [];
    }

    public function immutable(): ReceivedMessages
    {
        return new ReceivedMessages(...$this->messages);
    }
}
