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

namespace FiveLab\Component\Amqp\Exception;

/**
 * Throw this exception if you want get the queue factory but the factory was not found.
 */
class QueueFactoryNotFoundException extends \Exception
{
}
