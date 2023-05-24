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

use FiveLab\Component\Amqp\Argument\ArgumentDefinitions;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinition;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinitions;

/**
 * Exchange definition.
 */
readonly class ExchangeDefinition
{
    /**
     * @var ArgumentDefinitions
     */
    public ArgumentDefinitions $arguments;

    /**
     * @var BindingDefinitions|BindingDefinition[]
     */
    public BindingDefinitions $bindings;

    /**
     * @var BindingDefinitions|BindingDefinition[]
     */
    public BindingDefinitions $unbindings;

    /**
     * Constructor.
     *
     * @param string                   $name
     * @param string                   $type
     * @param bool                     $durable
     * @param bool                     $passive
     * @param ArgumentDefinitions|null $arguments
     * @param BindingDefinitions|null  $bindings
     * @param BindingDefinitions|null  $unbindings
     */
    public function __construct(
        public string       $name,
        public string       $type,
        public bool         $durable = true,
        public bool         $passive = false,
        ArgumentDefinitions $arguments = null,
        BindingDefinitions  $bindings = null,
        BindingDefinitions  $unbindings = null
    ) {
        if ('' === $this->name) {
            // Try to create default direct exchange.
            if ('direct' !== $this->type) {
                throw new \InvalidArgumentException(\sprintf(
                    'The default exchange allow only direct type but "%s" given.',
                    $type
                ));
            }

            if (!$this->durable) {
                throw new \InvalidArgumentException('The default exchange not allow not durable flag.');
            }

            if ($this->passive) {
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

        $this->arguments = $arguments ?: new ArgumentDefinitions();
        $this->bindings = $bindings ?: new BindingDefinitions();
        $this->unbindings = $unbindings ?: new BindingDefinitions();
    }
}
