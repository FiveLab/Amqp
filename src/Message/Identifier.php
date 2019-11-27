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

namespace FiveLab\Component\Amqp\Message;

/**
 * The message identifier
 */
class Identifier
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $appId;

    /**
     * @var string
     */
    private $userId;

    /**
     * Constructor.
     *
     * @param string $id
     * @param string $appId
     * @param string $userId
     */
    public function __construct(string $id = null, string $appId = null, string $userId = null)
    {
        $this->id = $id;
        $this->appId = $appId;
        $this->userId = $userId;
    }

    /**
     * Get message identifier
     *
     * @return string
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Get application identifier
     *
     * @return string
     */
    public function getAppId(): ?string
    {
        return $this->appId;
    }

    /**
     * Get user identifier
     *
     * @return string
     */
    public function getUserId(): ?string
    {
        return $this->userId;
    }
}
