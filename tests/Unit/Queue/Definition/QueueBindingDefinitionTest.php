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

namespace FiveLab\Component\Amqp\Tests\Unit\Queue\Definition;

use FiveLab\Component\Amqp\Queue\Definition\QueueBindingDefinition;
use PHPUnit\Framework\TestCase;

class QueueBindingDefinitionTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessCreate(): void
    {
        $def = new QueueBindingDefinition('some', 'test');

        self::assertEquals('some', $def->getExchangeName());
        self::assertEquals('test', $def->getRoutingKey());
    }
}
