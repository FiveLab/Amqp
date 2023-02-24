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
    /**
     * @var string
     */
    protected static $defaultName = 'event-broker:initialize:exchanges';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Initialize exchanges.';

    /**
     * @var ExchangeFactoryRegistryInterface
     */
    private ExchangeFactoryRegistryInterface $registry;

    /**
     * @var array<string>
     */
    private array $exchanges;

    /**
     * Constructor.
     *
     * @param ExchangeFactoryRegistryInterface $registry
     * @param array<string>                    $exchanges
     */
    public function __construct(ExchangeFactoryRegistryInterface $registry, array $exchanges)
    {
        parent::__construct();

        $this->registry = $registry;
        $this->exchanges = $exchanges;
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
