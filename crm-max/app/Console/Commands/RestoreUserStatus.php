<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RestoreUserStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:restore-user-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Fetching statuses from backup database...");

        // Fetch id + status from backup DB
        $backupUsers = DB::connection('mysql_backup')
            ->table('users')
            ->select('id', 'status')
            ->get();

        $this->info("Found " . $backupUsers->count() . " users in backup.");

        // Update current DB in chunks
        $bar = $this->output->createProgressBar($backupUsers->count());
        $bar->start();

        foreach ($backupUsers->chunk(1000) as $chunk) {
            foreach ($chunk as $user) {
                DB::connection('mysql')
                    ->table('users')
                    ->where('id', $user->id)
                    ->update(['status' => $user->status]);
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info("User statuses restored successfully!");

    }
}
