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

namespace FiveLab\Component\Amqp\Consumer\Checker;

use FiveLab\Component\Amqp\Exception\CannotRunConsumerException;

/**
 * All consumer checkers should implement this interface.
 */
interface RunConsumerCheckerInterface
{
    /**
     * Call to this method before run consumer.
     *
     * @param string $consumer
     *
     * @throws CannotRunConsumerException
     */
    public function checkBeforeRun(string $consumer): void;
}
