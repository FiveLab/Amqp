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

namespace FiveLab\Component\Amqp\Consumer\Tag;

/**
 * Generate static consume tag
 */
class StaticConsumerTagGenerator implements ConsumerTagGeneratorInterface
{
    /**
     * @var string
     */
    private $consumerTag;

    /**
     * Constructor.
     *
     * @param string $consumerTag
     */
    public function __construct(string $consumerTag)
    {
        $this->consumerTag = $consumerTag;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(): string
    {
        return $this->consumerTag;
    }
}
