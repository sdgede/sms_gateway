<?php

namespace App\Commands;

use App\Models\SmsJobModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SmsQueueWorkerCommand extends BaseCommand
{
    protected $group       = 'SMS Gateway';
    protected $name        = 'sms:worker';
    protected $description = 'Processes retry backoff schedules and recovers stale claimed jobs.';

    public function run(array $params)
    {
        CLI::write('[SMS Worker] Starting queue maintenance...', 'yellow');

        $jobModel = new SmsJobModel();

        // 1. Recover stale claimed jobs
        $recovered = $jobModel->recoverStaleClaims();
        if ($recovered > 0) {
            CLI::write("[SMS Worker] Recovered {$recovered} stale/unresponsive claimed job(s) back to PENDING.", 'green');
        } else {
            CLI::write('[SMS Worker] No stale claims found.', 'dark_gray');
        }

        // 2. Process retry queue
        $retried = $jobModel->processRetries();
        if ($retried > 0) {
            CLI::write("[SMS Worker] Released {$retried} retry job(s) back to PENDING.", 'green');
        } else {
            CLI::write('[SMS Worker] No retry jobs ready to release.', 'dark_gray');
        }

        // 3. Print current queue summary
        $stats = $jobModel->getStatistics();
        CLI::write("--- Queue Stats: Total={$stats['total']} | Pending={$stats['pending']} | Sending={$stats['sending']} | Sent={$stats['sent']} | Delivered={$stats['delivered']} | Retry={$stats['retry']} | Failed={$stats['failed_permanent']} ---", 'cyan');

        CLI::write('[SMS Worker] Queue maintenance cycle complete.', 'yellow');
    }
}
