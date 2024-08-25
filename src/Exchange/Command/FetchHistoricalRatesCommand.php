<?php

declare(strict_types=1);

namespace App\Exchange\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use App\Exchange\Message\FetchHistoricalRateDto;
use DateTimeImmutable;

#[AsCommand(
    name: 'app:fetch-historical-rates',
    description: 'Fetch historical exchange rates for the last 180 days',
)]
class FetchHistoricalRatesCommand extends Command
{
    private const HISTORY_THRESHOLD = 180;

    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Fetch historical exchange rates for the last 180 days')
            ->addOption('currency', null, InputOption::VALUE_REQUIRED, 'Currency code')
            ->addOption('base-currency', null,InputOption::VALUE_OPTIONAL, 'Base currency code', 'RUB');
    }

    /**
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $currency = $input->getOption('currency');
        $baseCurrency = $input->getOption('base-currency');

        if (is_string($currency) === false || is_string($baseCurrency) === false) {
            throw new \InvalidArgumentException('Wrong command options types');
        }

        $output->writeln('Fetching historical rates for ' . $currency . ' ' . ' (base: ' . $baseCurrency . ')');

        $progressBar = new ProgressBar($output, self::HISTORY_THRESHOLD);
        $progressBar->start();

        for ($i = 0; $i < self::HISTORY_THRESHOLD; $i++) {
            $date = (new DateTimeImmutable())->modify('-' . $i . 'days');

            $this->messageBus->dispatch(new FetchHistoricalRateDto($date->format('Y-m-d'), $currency, $baseCurrency));

            $progressBar->advance();
        }

        $progressBar->finish();

        $output->writeln(PHP_EOL . 'Historical rate fetch jobs have been queued.');

        return Command::SUCCESS;
    }
}
