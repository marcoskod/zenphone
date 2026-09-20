# Zen_Sms

Virtual phone numbers for receiving verification SMS (WhatsApp, Telegram, Google...). Laravel 12 + Alpine.js + Tailwind. Numbers come from [SMSPool](https://www.smspool.net), customers pay per order with [FedaPay](https://fedapay.com) (Mobile Money / card) - no wallet top-up.

## How a purchase works

1. Customer picks country + service, enters email + password (account created on first purchase).
2. `POST /api/purchase/pay-init` locks the price in a `pending_purchases` row.
3. FedaPay checkout opens for that exact amount; the widget stamps `pending_purchase_id` into the transaction's `custom_metadata`.
4. The browser calls `POST /api/purchase/pay-confirm` **and** FedaPay calls `POST /webhooks/fedapay`. Both re-verify the transaction server-to-server and share `PurchaseFinalizer` (row-locked), so the number is bought exactly once even if only one of them arrives.
5. Waiting screen polls `/api/orders/{id}/status`. No SMS before expiry -> automatic refund as account credit (`orders:sweep-expired`, every 2 min), used automatically on the next purchase.

## Going live checklist

1. `cp .env.example .env`, then set: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://your-domain`, MySQL `DB_*`, SMTP `MAIL_*`, `SMSPOOL_API_KEY`, `FEDAPAY_PUBLIC_KEY`, `FEDAPAY_SECRET_KEY`, `FEDAPAY_ENVIRONMENT=live`, `EXCHANGE_RATE_USD_FCFA`, and `TRUSTED_PROXIES` if behind a proxy.
2. `composer install --no-dev --optimize-autoloader && npm ci && npm run build`
3. `php artisan key:generate && php artisan migrate --force`
4. `php artisan zensms:preflight` - must print "All critical checks passed".
5. In the FedaPay dashboard, add the webhook `https://your-domain/webhooks/fedapay` for `transaction.approved`.
6. Cron (refunds + cleanup): `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`
7. Queue worker (SMS-received notifications are queued): `php artisan queue:work --tries=3` under Supervisor/systemd.
8. `php artisan config:cache route:cache view:cache`
9. Register on the site, then `php artisan zensms:make-admin you@example.com` to reach `/admin`.
10. **Do one real sandbox payment first** and confirm in the logs/DB that the order is delivered - this also proves FedaPay echoes `custom_metadata` back (the anti-replay check relies on it; see `PurchaseFinalizer`).
11. Fill in the placeholders in the *Mentions légales* modal (company name, address, host) before advertising.

## Tests

`php artisan test`
