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

namespace FiveLab\Component\Amqp\Tests\Unit\Consumer\Checker;

use FiveLab\Component\Amqp\Consumer\Checker\ContainerRunConsumerCheckerRegistry;
use FiveLab\Component\Amqp\Consumer\Checker\RunConsumerCheckerInterface;
use FiveLab\Component\Amqp\Exception\RunConsumerCheckerNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ContainerRunConsumerCheckerRegistryTest extends TestCase
{
    /**
     * @var array<RunConsumerCheckerInterface>
     */
    private array $checkers;

    /**
     * @var ContainerRunConsumerCheckerRegistry
     */
    private ContainerRunConsumerCheckerRegistry $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->checkers = [
            $this->createMock(RunConsumerCheckerInterface::class),
            $this->createMock(RunConsumerCheckerInterface::class),
        ];

        $container = $this->createMock(ContainerInterface::class);

        $container->expects(self::any())
            ->method('has')
            ->willReturnCallback(static function (string $key) {
                return \in_array($key, ['consumer_1', 'consumer_2'], true);
            });

        $container->expects(self::any())
            ->method('get')
            ->willReturnMap([
                ['consumer_1', $this->checkers[0]],
                ['consumer_2', $this->checkers[1]],
            ]);

        $this->registry = new ContainerRunConsumerCheckerRegistry($container);
    }

    #[Test]
    public function shouldSuccessGet(): void
    {
        $consumer2 = $this->registry->get('consumer_2');
        self::assertSame($this->checkers[1], $consumer2);

        $consumer1 = $this->registry->get('consumer_1');
        self::assertSame($this->checkers[0], $consumer1);
    }

    #[Test]
    public function shouldFailGetIfNotFound(): void
    {
        $this->expectException(RunConsumerCheckerNotFoundException::class);
        $this->expectExceptionMessage('The checker for consumer "foo" was not found.');

        $this->registry->get('foo');
    }
}
