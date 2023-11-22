<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Jobs;

use Callcocam\DbRestore\Models\Children;
use Callcocam\DbRestore\Helpers\RestoreHelper;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class DbRestoreChidrenJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Children $record, public $chunk, public $to_columns, public $from_columns, public $restore) 
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
            ->table($this->record->table_to);

        $values = RestoreHelper::getDataValues($this->chunk, $this->to_columns, $fromConnection, $this->record->table_to, $this->record->type, $this->restore);

        $model->insert($values);
    }
}
