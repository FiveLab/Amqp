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

namespace FiveLab\Component\Amqp\Exchange\Registry;

use FiveLab\Component\Amqp\Exception\ExchangeFactoryNotFoundException;
use FiveLab\Component\Amqp\Exchange\ExchangeFactoryInterface;

/**
 * All exchange factory registries should implement this interface.
 */
interface ExchangeFactoryRegistryInterface
{
    /**
     * Get the exchange factory by key
     *
     * @param string $key
     *
     * @return ExchangeFactoryInterface
     *
     * @throws ExchangeFactoryNotFoundException
     */
    public function get(string $key): ExchangeFactoryInterface;
}
