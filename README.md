# Exchange Rates Service

This service obtaining exchange rates and cross rates from cbr.ru

## Requirements

- PHP 8.2
- Composer
- Docker and Docker Compose

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/lampatrampa/exchange-rates.git
   cd exchange-rates
   ```
2. Add GitHub Personal access tokens (*when needed), due to the GitHub API limit (0 calls/hr) is exhausted limits
   ```bash
   cp auth.json.example auth.json
   ```
   Add GitHub Personal access tokens:
   
   https://github.com/settings/tokens/new
   
   add to auth.json
   ```json
   {
      "github-oauth": {
         "github.com": "your token"
      }
   }
   ```

3. Start docker containers:
   ```bash
   docker-compose up -d --force-recreate --remove-orphans
   ```
4. Install dependencies:
   ```bash
   docker-compose exec -T exchange_rates_php composer install
   ```

## Usage


1. Run the Symfony Messenger consumer (and keep it running):
   ```bash
   docker-compose exec -T exchange_rates_php php bin/console messenger:consume async -vv
   ```
2. Fetch historical rates for a currency:
   ```bash
   docker-compose exec -T exchange_rates_php php bin/console app:fetch-historical-rates --currency=USD --base-currency=RUB
   ```
3. Get exchange rate for the specified date:
   ```bash
   docker-compose exec -T exchange_rates_php php bin/console app:fetch-exchange-rate --currency=MDL --base-currency=KGS --date=2023-04-14
   ```
4. Try out the API via swagger doc: http://localhost:8088/api/doc

## Running Tests

To run the PHPUnit tests:

```bash
docker-compose exec -T exchange_rates_php php bin/phpunit
```