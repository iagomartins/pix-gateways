<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DbMonitor extends Command
{
    protected $signature = 'db:monitor';
    protected $description = 'Check if database is ready';

    public function handle(): int
    {
        try {
            DB::connection()->getPdo();
            return 0;
        } catch (\Exception $e) {
            return 1;
        }
    }
}

