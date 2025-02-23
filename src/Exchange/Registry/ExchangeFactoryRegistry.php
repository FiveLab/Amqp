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

class ExchangeFactoryRegistry implements ExchangeFactoryRegistryInterface
{
    /**
     * @var array<ExchangeFactoryInterface>
     */
    private array $factories = [];

    public function add(string $key, ExchangeFactoryInterface $exchangeFactory): void
    {
        $this->factories[$key] = $exchangeFactory;
    }

    public function get(string $key): ExchangeFactoryInterface
    {
        if (!\array_key_exists($key, $this->factories)) {
            throw new ExchangeFactoryNotFoundException(\sprintf(
                'The exchange factory with key "%s" was not found.',
                $key
            ));
        }

        return $this->factories[$key];
    }
}
