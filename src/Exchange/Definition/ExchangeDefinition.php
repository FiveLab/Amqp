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

use FiveLab\Component\Amqp\Argument\ArgumentCollection;
use FiveLab\Component\Amqp\Argument\ArgumentDefinition;
use FiveLab\Component\Amqp\Binding\Definition\BindingCollection;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinition;

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
     * @var ArgumentCollection
     */
    private $arguments;

    /**
     * @var BindingCollection|BindingDefinition[]
     */
    private $bindings;

    /**
     * @var BindingCollection|BindingDefinition[]
     */
    private $unbindings;

    /**
     * Constructor.
     *
     * @param string                  $name
     * @param string                  $type
     * @param bool                    $durable
     * @param bool                    $passive
     * @param ArgumentCollection|null $arguments
     * @param BindingCollection|null  $bindings
     * @param BindingCollection|null  $unbindings
     */
    public function __construct(string $name, string $type, bool $durable = true, bool $passive = false, ArgumentCollection $arguments = null, BindingCollection $bindings = null, BindingCollection $unbindings = null)
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
        $this->arguments = $arguments ?: new ArgumentCollection();
        $this->bindings = $bindings ?: new BindingCollection();
        $this->unbindings = $unbindings ?: new BindingCollection();
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

    /**
     * Get arguments
     *
     * @return ArgumentCollection|ArgumentDefinition[]
     */
    public function getArguments(): ArgumentCollection
    {
        return $this->arguments;
    }

    /**
     * Get bindings
     *
     * @return BindingCollection|BindingDefinition[]
     */
    public function getBindings(): BindingCollection
    {
        return $this->bindings;
    }

    /**
     * Get unbindings
     *
     * @return BindingCollection|BindingDefinition[]
     */
    public function getUnBindings(): BindingCollection
    {
        return $this->unbindings;
    }
}
