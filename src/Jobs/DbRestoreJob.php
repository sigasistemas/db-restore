<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Jobs;

use Callcocam\DbRestore\Models\Restore;
use Callcocam\DbRestore\Helpers\RestoreHelper;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class DbRestoreJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Restore $record, public $chunks, public $to_columns, public $from_columns)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $fromConnection = RestoreHelper::getConnectionCloneOptions($this->record->connectionTo);

        $model = DB::connection($fromConnection)
            ->table($this->record->table_to);

        $values = RestoreHelper::getDataValues(rows: $this->chunks, to_columns: $this->to_columns, connectionTo: $fromConnection);

        $model->insert($values);
    }
}
