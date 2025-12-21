<?php

namespace App\Console\Commands;

use App\Models\Otp;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanupExpiredOtps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'otp:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired and verified OTPs from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Cleaning up expired OTPs...');

        // Delete expired OTPs
        $expiredCount = Otp::where('expires_at', '<', Carbon::now())->delete();

        // Delete verified OTPs older than 24 hours
        $verifiedCount = Otp::where('is_verified', true)
            ->where('updated_at', '<', Carbon::now()->subHours(24))
            ->delete();

        $totalDeleted = $expiredCount + $verifiedCount;

        $this->info("Deleted {$expiredCount} expired OTPs");
        $this->info("Deleted {$verifiedCount} old verified OTPs");
        $this->info("Total: {$totalDeleted} OTPs removed");

        return Command::SUCCESS;
    }
}
