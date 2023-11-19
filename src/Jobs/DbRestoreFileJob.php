<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Jobs;
 
use Callcocam\DbRestore\Helpers\RestoreHelper;
use Callcocam\DbRestore\Models\Import;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class DbRestoreFileJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Import $record, public $rows, public $to_columns)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $fromConnection = RestoreHelper::getConnectionCloneOptions($this->record->connection);

        $model = DB::connection($fromConnection)
            ->table($this->record->table_name);

        $values = RestoreHelper::getDataValues($this->rows, $this->to_columns, $fromConnection);
 

        $model->insert($values);
    }
}
