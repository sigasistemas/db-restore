<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Filament\Resources\Restores\RestoreResource\Pages;

use Callcocam\DbRestore\Filament\Resources\Restores\RestoreResource;
use Callcocam\DbRestore\Helpers\RestoreHelper;
use Callcocam\DbRestore\Models\Restore;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Throwable;

class ViewRestore extends ViewRecord
{
    protected static string $resource = RestoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('Restore')
                ->icon('fas-upload')
                ->color('success')
                ->action(function (Restore $record) {

                    $pivots = $record->pivots;


                    $columns = $record->columns;

                    $from_table = $record->table_from;

                    $from_columns = RestoreHelper::getColumsSchema($columns, $from_table, 'column_from');

                    $to_table = $record->table_to;

                    $to_columns =  RestoreHelper::getColumsSchema($columns, $to_table, 'column_to');

                    $filterList = $record->filters->filter(fn ($filter) => $filter->type == 'list')->all();

                    $rows = RestoreHelper::getFromDatabaseRows($record, $from_table, $filterList);

                    RestoreHelper::beforeRemoveFilters($record);

                    $chunks = array_chunk($rows, 1000);

                    $batch =  Bus::batch([])->then(function (Batch $batch) use ($record) {
                        // All jobs completed successfully...
                    })->catch(function (Batch $batch, Throwable $e) {
                        // First batch job failure detected...
                    })->finally(function (Batch $batch) use ($record) {
                        // The batch has finished executing... 
                    })->name($record->name)->dispatch();

                    foreach ($chunks as $chunk) {
                        $batch->add(new \Callcocam\DbRestore\Jobs\DbRestoreJob($record, $chunk, $to_columns, $from_columns));
                    }

                    RestoreHelper::afterGetChildresValues($record);

                    RestoreHelper::afterGetSharedValues($record);

                    if ($pivots->count()) {

                        $pivots->each(function ($pivot) use ($record) {
                            $rows =  RestoreHelper::afterGetPivotValues($record, $pivot);
                            $batch =  Bus::batch([])->then(function (Batch $batch) use ($record) {
                                // All jobs completed successfully...
                            })->catch(function (Batch $batch, Throwable $e) {
                                // First batch job failure detected...
                            })->finally(function (Batch $batch) use ($record) {
                                // The batch has finished executing... 
                            })->name($pivot->name)->dispatch();
                            $chunks = array_chunk($rows, 1000);
                            foreach ($chunks as $chunk) {
                                $batch->add(new \Callcocam\DbRestore\Jobs\DbRestorePivotJob($pivot, $chunk, $record));
                            }
                        });
                    }
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\TextEntry::make('name')
                    ->label(__('db-restore::db-restore.restore.form.name.label')),
                Components\TextEntry::make('table_from')
                    ->label(__('db-restore::db-restore.restore.form.table_from.label')),
                Components\TextEntry::make('table_to')
                    ->label(__('db-restore::db-restore.restore.form.table_to.label')),
                Components\TextEntry::make('description')
                    ->label(__('db-restore::db-restore.restore.form.description.label')),
                Components\TextEntry::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'reviewing' => 'warning',
                        'published' => 'success',
                        'rejected' => 'danger',
                    })
            ]);
    }
}
