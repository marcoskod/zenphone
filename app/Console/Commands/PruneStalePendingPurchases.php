<?php

namespace App\Console\Commands;

use App\Models\PendingPurchase;
use Illuminate\Console\Command;

class PruneStalePendingPurchases extends Command
{
    protected $signature = 'purchases:prune-pending';

    protected $description = 'Delete abandoned, never-paid purchase reservations older than 30 days';

    public function handle(): int
    {
        // Kept for 30 days (not hours): an unpaid-looking reservation may belong to a
        // customer whose payment is still being reconciled, so it must outlive any
        // webhook retry window by a wide margin.
        $deleted = PendingPurchase::where('status', 'awaiting_payment')
            ->where('created_at', '<', now()->subDays(30))
            ->delete();

        $this->info("Pruned {$deleted} abandoned reservation(s).");

        return self::SUCCESS;
    }
}
