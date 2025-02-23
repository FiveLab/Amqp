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

namespace FiveLab\Component\Amqp\Exchange;

interface ExchangeFactoryInterface
{
    /**
     * Create the AMQP exchange
     *
     * @return ExchangeInterface
     */
    public function create(): ExchangeInterface;
}
