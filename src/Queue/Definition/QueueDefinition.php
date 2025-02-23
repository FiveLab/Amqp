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

namespace FiveLab\Component\Amqp\Queue\Definition;

use FiveLab\Component\Amqp\Argument\ArgumentDefinitions;
use FiveLab\Component\Amqp\Binding\Definition\BindingDefinitions;

readonly class QueueDefinition
{
    public BindingDefinitions $bindings;
    public BindingDefinitions $unbindings;
    public ArgumentDefinitions $arguments;

    public function __construct(
        public string        $name,
        ?BindingDefinitions  $bindings = null,
        ?BindingDefinitions  $unBindings = null,
        public bool          $durable = true,
        public bool          $passive = false,
        public bool          $exclusive = false,
        public bool          $autoDelete = false,
        ?ArgumentDefinitions $arguments = null
    ) {
        $this->bindings = $bindings ?: new BindingDefinitions();
        $this->unbindings = $unBindings ?: new BindingDefinitions();
        $this->arguments = $arguments ?: new ArgumentDefinitions();
    }
}
