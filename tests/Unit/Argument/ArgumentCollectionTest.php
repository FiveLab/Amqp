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

namespace FiveLab\Component\Amqp\Tests\Unit\Argument;

use FiveLab\Component\Amqp\Argument\ArgumentCollection;
use FiveLab\Component\Amqp\Argument\ArgumentDefinition;
use PHPUnit\Framework\TestCase;

class ArgumentCollectionTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessCreate(): void
    {
        $argument1 = new ArgumentDefinition('foo', 'bar');
        $argument2 = new ArgumentDefinition('bar', 'foo');

        $collection = new ArgumentCollection($argument1, $argument2);

        self::assertEquals([$argument1, $argument2], \iterator_to_array($collection));
    }
}
