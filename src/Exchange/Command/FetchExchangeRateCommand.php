<?php

declare(strict_types=1);

namespace App\Exchange\Command;

use App\Core\Exception\ValidationException;
use App\Exchange\Service\ExchangeRateService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use App\Exchange\Dto\Request\FetchExchangeRateDto;
use DateTimeImmutable;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:fetch-exchange-rate',
    description: 'Fetch exchange rate',
)]
class FetchExchangeRateCommand extends Command
{
    public function __construct(
        private readonly ExchangeRateService $exchangeRateService,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Fetch exchange rate for the specified date')
            ->addOption('currency', null, InputOption::VALUE_REQUIRED, 'Currency code')
            ->addOption('base-currency', null,InputOption::VALUE_OPTIONAL, 'Base currency code', 'RUB')
            ->addOption('date', null, InputOption::VALUE_OPTIONAL, 'Date', (new DateTimeImmutable())->format('Y-m-d'));
    }

    /**
     * @throws InvalidArgumentException
     * @throws ValidationException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $currency = $input->getOption('currency');
        $baseCurrency = $input->getOption('base-currency');
        $date = $input->getOption('date');

        if (is_string($date) === false || is_string($currency) === false || is_string($baseCurrency) === false) {
            throw new \InvalidArgumentException('Wrong command options types');
        }

        $fetchRateDto = new FetchExchangeRateDto(
            date: $date,
            currency: $currency,
            baseCurrency: $baseCurrency
        );

        $errors = $this->validator->validate($fetchRateDto);
        if ($errors->count() > 0) {
            throw new ValidationException($errors);
        }

        $output->writeln('Fetching current exchange rate for ' . $currency . '(base: ' . $baseCurrency . ')');

        $result = $this->exchangeRateService->getExchangeRate($fetchRateDto);

        $output->writeln(sprintf(
            'Current exchange rate: 1 %s = %.4f %s, Difference: %.4f',
            $baseCurrency,
            $result->getRate(),
            $currency,
            $result->getDifference()
        ));

        return Command::SUCCESS;
    }
}
