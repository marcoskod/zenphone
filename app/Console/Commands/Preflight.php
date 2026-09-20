<?php

namespace App\Console\Commands;

use App\Services\SmsProvider\Contracts\SmsProviderInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Preflight extends Command
{
    protected $signature = 'zensms:preflight';

    protected $description = 'Check the environment is ready for production (keys, debug flags, HTTPS, mail, database)';

    public function handle(): int
    {
        $checks = [
            ['APP_ENV = production', config('app.env') === 'production', 'critical', 'Set APP_ENV=production'],
            ['APP_DEBUG = false', config('app.debug') === false, 'critical', 'Set APP_DEBUG=false (debug pages leak secrets)'],
            ['APP_KEY set', filled(config('app.key')), 'critical', 'Run php artisan key:generate'],
            ['APP_URL uses https://', str_starts_with((string) config('app.url'), 'https://'), 'critical', 'Set APP_URL=https://your-domain'],
            ['SMSPOOL_API_KEY set', filled(config('smspool.api_key')), 'critical', 'Add your SMSPool API key'],
            ['SMSPool balance available', $this->supplierBalanceOk(), 'warning', 'Top up your SMSPool account (or the key is wrong) - orders fail when it is empty'],
            ['FEDAPAY_PUBLIC_KEY set', filled(config('fedapay.public_key')), 'critical', 'Add your FedaPay public key'],
            ['FEDAPAY_SECRET_KEY set', filled(config('fedapay.secret_key')), 'critical', 'Add your FedaPay secret key'],
            ['FEDAPAY_ENVIRONMENT = live', config('fedapay.environment') === 'live', 'critical', 'Set FEDAPAY_ENVIRONMENT=live with live keys (sandbox takes no real money)'],
            ['Mail is not "log"', ! in_array(config('mail.default'), ['log', 'array'], true), 'warning', 'Configure SMTP - password resets and notifications only write to the log otherwise'],
            ['Database is not SQLite', config('database.default') !== 'sqlite', 'warning', 'Use MySQL/PostgreSQL in production'],
            ['Exchange rate configured', (float) config('smspool.exchange_rate_usd_fcfa') > 0, 'critical', 'Set EXCHANGE_RATE_USD_FCFA (USD -> FCFA)'],
            ['Database reachable', $this->databaseReachable(), 'critical', 'Check DB_* settings'],
        ];

        $failures = 0;
        $rows = [];

        foreach ($checks as [$label, $ok, $severity, $fix]) {
            $rows[] = [$ok ? 'OK' : strtoupper($severity), $label, $ok ? '' : $fix];

            if (! $ok && $severity === 'critical') {
                $failures++;
            }
        }

        $this->table(['Status', 'Check', 'Fix'], $rows);

        if ($failures > 0) {
            $this->error("{$failures} critical issue(s) - not ready for production.");

            return self::FAILURE;
        }

        $this->info('All critical checks passed.');

        return self::SUCCESS;
    }

    private function supplierBalanceOk(): bool
    {
        if (! filled(config('smspool.api_key'))) {
            return false;
        }

        try {
            return (app(SmsProviderInterface::class)->getBalance() ?? 0) > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    private function databaseReachable(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
