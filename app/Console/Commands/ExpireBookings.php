<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireBookings extends Command
{
    protected $signature = 'bookings:expire';
    protected $description = 'Expire PENDING bookings whose hold time has passed';

    public function handle(): int
    {
        $count = DB::table('bookings')
            ->where('status', 'PENDING')
            ->where('expires_at', '<', now())
            ->update(['status' => 'EXPIRED', 'updated_at' => now()]);

        $this->info("Expired {$count} booking(s).");

        return self::SUCCESS;
    }
}