<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Jobs;

use Callcocam\DbRestore\Models\Children;
use Callcocam\DbRestore\Helpers\RestoreHelper;
use Callcocam\DbRestore\Models\Shared;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class DbRestoreSharedJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Shared $record, public $chunk, public $to_columns, public $from_columns, public $restore) 
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $connectionTo = RestoreHelper::getConnectionCloneOptions($this->restore->connectionTo);

        $model = DB::connection($connectionTo)
            ->table($this->record->table_to);

        $values = RestoreHelper::getDataValues($this->chunk, $this->to_columns, $connectionTo, $this->record->table_to, $this->record->type, $this->restore);
 
        $model->insert($values);
    }
}