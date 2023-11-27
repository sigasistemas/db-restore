<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Jobs;
 
use Callcocam\DbRestore\Helpers\RestoreHelper;
use Callcocam\DbRestore\Models\Pivot;
use Callcocam\DbRestore\Models\Shared;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class DbRestorePivotJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Pivot $pivot, public $chunks,  public $restore) 
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $fromConnection = RestoreHelper::getConnectionCloneOptions($this->restore->connectionTo);

        $model = DB::connection($fromConnection)
            ->table($this->pivot->table_to);

        $values = RestoreHelper::getPivotDataValues(rows: $this->chunks, pivot:$this->pivot, restore: $this->restore);
 
        $model->insert($values);
    }
}
