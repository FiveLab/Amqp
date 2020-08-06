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

namespace FiveLab\Component\Amqp\Command;

use FiveLab\Component\Amqp\Exchange\Registry\ExchangeFactoryRegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The command for initialize exchanges.
 */
class InitializeExchangesCommand extends Command
{
    private const DEFAULT_NAME = 'event-broker:initialize:exchanges';

    /**
     * @var ExchangeFactoryRegistryInterface
     */
    private $registry;

    /**
     * @var array
     */
    private $exchanges;

    /**
     * Constructor.
     *
     * @param ExchangeFactoryRegistryInterface $registry
     * @param array                            $exchanges
     * @param string                           $name
     */
    public function __construct(ExchangeFactoryRegistryInterface $registry, array $exchanges, string $name = self::DEFAULT_NAME)
    {
        parent::__construct($name);

        $this->registry = $registry;
        $this->exchanges = $exchanges;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Initialize exchanges.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->exchanges as $exchange) {
            $factory = $this->registry->get($exchange);

            $factory->create();

            if ($output->isVerbose()) {
                $output->writeln(\sprintf(
                    'Success initialize exchange <info>%s</info>.',
                    $exchange
                ));
            }
        }

        return 0;
    }
}
