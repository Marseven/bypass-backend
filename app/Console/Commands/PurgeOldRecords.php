<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Request;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeOldRecords extends Command
{
    protected $signature = 'app:purge-old-records {--months=6 : Retention period in months} {--dry-run : Show counts without deleting}';

    protected $description = 'Purge old audit logs, soft-deleted requests, and read notifications';

    public function handle(): void
    {
        $months = (int) $this->option('months');
        $dryRun = $this->option('dry-run');
        $cutoff = Carbon::now()->subMonths($months);
        $notificationCutoff = Carbon::now()->subMonths(3);

        $this->info("Purge cutoff date: {$cutoff->toDateTimeString()}");
        $this->info("Notification cutoff: {$notificationCutoff->toDateTimeString()}");

        if ($dryRun) {
            $this->warn('DRY RUN — no records will be deleted.');
        }

        // 1. Audit logs older than retention period
        $auditCount = AuditLog::where('created_at', '<', $cutoff)->count();
        $this->info("Audit logs to purge: {$auditCount}");

        if (!$dryRun && $auditCount > 0) {
            AuditLog::where('created_at', '<', $cutoff)->delete();
            $this->info("Deleted {$auditCount} audit log(s).");
        }

        // 2. Soft-deleted requests older than retention period
        $softDeletedCount = Request::onlyTrashed()
            ->where('deleted_at', '<', $cutoff)
            ->count();
        $this->info("Soft-deleted requests to purge: {$softDeletedCount}");

        if (!$dryRun && $softDeletedCount > 0) {
            Request::onlyTrashed()
                ->where('deleted_at', '<', $cutoff)
                ->forceDelete();
            $this->info("Force-deleted {$softDeletedCount} request(s).");
        }

        // 3. Read notifications older than 3 months
        $notificationCount = DB::table('notifications')
            ->whereNotNull('read_at')
            ->where('read_at', '<', $notificationCutoff)
            ->count();
        $this->info("Read notifications to purge: {$notificationCount}");

        if (!$dryRun && $notificationCount > 0) {
            DB::table('notifications')
                ->whereNotNull('read_at')
                ->where('read_at', '<', $notificationCutoff)
                ->delete();
            $this->info("Deleted {$notificationCount} notification(s).");
        }

        $total = $auditCount + $softDeletedCount + $notificationCount;
        if ($dryRun) {
            $this->info("Total records that would be purged: {$total}");
        } else {
            $this->info("Purge complete. Total records deleted: {$total}");
        }
    }
}
