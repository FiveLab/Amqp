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

use FiveLab\Component\Amqp\Argument\Arguments;
use FiveLab\Component\Amqp\Argument\ArgumentDefinition;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinitions;
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
     * @var Arguments
     */
    private $arguments;

    /**
     * @var BindingDefinitions|BindingDefinition[]
     */
    private $bindings;

    /**
     * @var BindingDefinitions|BindingDefinition[]
     */
    private $unbindings;

    /**
     * Constructor.
     *
     * @param string                  $name
     * @param string                  $type
     * @param bool                    $durable
     * @param bool                    $passive
     * @param Arguments|null          $arguments
     * @param BindingDefinitions|null $bindings
     * @param BindingDefinitions|null $unbindings
     */
    public function __construct(string $name, string $type, bool $durable = true, bool $passive = false, Arguments $arguments = null, BindingDefinitions $bindings = null, BindingDefinitions $unbindings = null)
    {
        if ('' === $name) {
            // Try to create default direct exchange.
            if ('direct' !== $type) {
                throw new \InvalidArgumentException(\sprintf(
                    'The default exchange allow only direct type but "%s" given.',
                    $type
                ));
            }

            if (!$durable) {
                throw new \InvalidArgumentException('The default exchange not allow not durable flag.');
            }

            if ($passive) {
                throw new \InvalidArgumentException('The default exchange not allow passive flag.');
            }

            if ($arguments && \count($arguments)) {
                throw new \InvalidArgumentException('The default exchange not allow arguments.');
            }

            if ($bindings && \count($bindings)) {
                throw new \InvalidArgumentException('The default exchange not allow bindings.');
            }

            if ($unbindings && \count($unbindings)) {
                throw new \InvalidArgumentException('The default exchange not allow un-bindings.');
            }
        }

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
        $this->arguments = $arguments ?: new Arguments();
        $this->bindings = $bindings ?: new BindingDefinitions();
        $this->unbindings = $unbindings ?: new BindingDefinitions();
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
     * @return Arguments|ArgumentDefinition[]
     */
    public function getArguments(): Arguments
    {
        return $this->arguments;
    }

    /**
     * Get bindings
     *
     * @return BindingDefinitions|BindingDefinition[]
     */
    public function getBindings(): BindingDefinitions
    {
        return $this->bindings;
    }

    /**
     * Get unbindings
     *
     * @return BindingDefinitions|BindingDefinition[]
     */
    public function getUnBindings(): BindingDefinitions
    {
        return $this->unbindings;
    }
}
