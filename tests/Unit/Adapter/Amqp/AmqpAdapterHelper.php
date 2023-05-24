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

namespace FiveLab\Component\Amqp\Tests\Unit\Adapter\Amqp;

use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\Rule\AnyInvokedCount;
use PHPUnit\Framework\TestCase;

class AmqpAdapterHelper
{
    /**
     * Make envelope mock for testing.
     *
     * @param TestCase     $testCase
     * @param string|false $body
     * @param array        $headers
     * @param string       $routingKey
     * @param string       $exchangeName
     * @param int          $deliveryTag
     * @param string       $contentType
     * @param string|null  $contentEncoding
     * @param int          $deliveryMode
     * @param int|null     $expiration
     * @param string|null  $messageId
     * @param string|null  $appId
     * @param string|null  $userId
     *
     * @return \AMQPEnvelope
     */
    public static function makeEnvelope(
        TestCase     $testCase,
        string|false $body = '',
        array        $headers = [],
        string       $routingKey = '',
        string       $exchangeName = '',
        int          $deliveryTag = 1,
        string       $contentType = 'text/plain',
        string       $contentEncoding = null,
        int          $deliveryMode = 1,
        int          $expiration = null,
        string       $messageId = null,
        string       $appId = null,
        string       $userId = null
    ): \AMQPEnvelope {
        $envelope = (new MockBuilder($testCase, \AMQPEnvelope::class))
            ->getMock();

        $envelope->expects(new AnyInvokedCount())
            ->method('getBody')
            ->willReturn($body);

        $envelope->expects(new AnyInvokedCount())
            ->method('getHeaders')
            ->willReturn($headers);

        $envelope->expects(new AnyInvokedCount())
            ->method('getRoutingKey')
            ->willReturn($routingKey);

        $envelope->expects(new AnyInvokedCount())
            ->method('getExchangeName')
            ->willReturn($exchangeName);

        $envelope->expects(new AnyInvokedCount())
            ->method('getDeliveryTag')
            ->willReturn($deliveryTag);

        $envelope->expects(new AnyInvokedCount())
            ->method('getContentType')
            ->willReturn($contentType);

        $envelope->expects(new AnyInvokedCount())
            ->method('getContentEncoding')
            ->willReturn($contentEncoding);

        $envelope->expects(new AnyInvokedCount())
            ->method('getDeliveryMode')
            ->willReturn($deliveryMode);

        $envelope->expects(new AnyInvokedCount())
            ->method('getExpiration')
            ->willReturn($expiration);

        $envelope->expects(new AnyInvokedCount())
            ->method('getMessageId')
            ->willReturn($messageId);

        $envelope->expects(new AnyInvokedCount())
            ->method('getUserId')
            ->willReturn($userId);

        $envelope->expects(new AnyInvokedCount())
            ->method('getAppId')
            ->willReturn($appId);

        return $envelope;
    }
}
