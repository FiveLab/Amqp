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

namespace FiveLab\Component\Amqp\Exchange\Definition;

/**
 * Exchange definition.
 */
class ExchangeDefinition
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var bool
     */
    private $durable;

    /**
     * @var bool
     */
    private $passive;

    /**
     * Constructor.
     *
     * @param string $name
     * @param string $type
     * @param bool   $durable
     * @param bool   $passive
     */
    public function __construct(string $name, string $type, bool $durable = true, bool $passive = false)
    {
        $possibleTypes = [
            'direct',
            'topic',
            'fanout',
            'headers',
        ];

        if (!\in_array($type, $possibleTypes, true)) {
            throw new \InvalidArgumentException(\sprintf(
                'The type "%s" is invalid. Possible types: "%s".',
                $type,
                \implode('", "', $possibleTypes)
            ));
        }

        $this->name = $name;
        $this->type = $type;
        $this->durable = $durable;
        $this->passive = $passive;
    }

    /**
     * Get the name of exchange
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the type of exchange
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Is exchange durable?
     *
     * @return bool
     */
    public function isDurable(): bool
    {
        return $this->durable;
    }

    /**
     * Is passive?
     *
     * @return bool
     */
    public function isPassive(): bool
    {
        return $this->passive;
    }
}
