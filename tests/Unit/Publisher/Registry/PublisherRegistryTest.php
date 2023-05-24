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

namespace FiveLab\Component\Amqp\Tests\Unit\Publisher\Registry;

use FiveLab\Component\Amqp\Exception\PublisherNotFoundException;
use FiveLab\Component\Amqp\Publisher\PublisherInterface;
use FiveLab\Component\Amqp\Publisher\Registry\PublisherRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PublisherRegistryTest extends TestCase
{
    /**
     * @var PublisherRegistry
     */
    private PublisherRegistry $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->registry = new PublisherRegistry();
    }

    #[Test]
    public function shouldSuccessGet(): void
    {
        $publisher = $this->createMock(PublisherInterface::class);
        $this->registry->add('some', $publisher);

        self::assertEquals($publisher, $this->registry->get('some'));
    }

    #[Test]
    public function shouldThrowExceptionIfPublisherNotFound(): void
    {
        $this->expectException(PublisherNotFoundException::class);
        $this->expectExceptionMessage('The publisher "foo" was not found.');

        $publisher = $this->createMock(PublisherInterface::class);
        $this->registry->add('some', $publisher);

        $this->registry->get('foo');
    }
}
