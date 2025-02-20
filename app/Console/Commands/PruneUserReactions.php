<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PruneUserReactions extends Command
{
    protected $signature = 'user-reactions:prune
                            {--days=90 : Количество дней для хранения записей}
                            {--dry-run : Показать сколько записей будет удалено без фактического удаления}';

    protected $description = 'Удаляет записи реакций старше указанного количества дней';

    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        $query = DB::table('user_reactions')
            ->where('date', '<', $cutoffDate);

        if ($this->option('dry-run')) {
            $count = $query->count();
            $this->info("[Dry Run] Будет удалено {$count} записей (старше {$days} дней).");
            return 0;
        }

        $deleted = $query->delete();
        $this->info("Удалено {$deleted} записей (старше {$days} дней).");

        return 0;
    }
}
