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

namespace FiveLab\Component\Amqp;

trait SplSubjectTrait
{
    /**
     * @var array<\SplObserver>
     */
    private array $observers = [];

    public function attach(\SplObserver $observer): void
    {
        $hash = \spl_object_hash($observer);

        if (!\array_key_exists($hash, $this->observers)) {
            $this->observers[\spl_object_hash($observer)] = $observer;
        }
    }

    public function detach(\SplObserver $observer): void
    {
        unset($this->observers[\spl_object_hash($observer)]);
    }

    public function notify(): void
    {
        foreach ($this->observers as $observer) {
            /** @var \SplSubject $this */
            $observer->update($this);
        }
    }
}
