<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Filament\Resources\Restores\ImportResource\Pages;

use Callcocam\DbRestore\Filament\Resources\Restores\ImportResource;
use Callcocam\DbRestore\Forms\Components\ConnectionToField;
use Callcocam\DbRestore\Forms\Components\RestoreModelField;
use Callcocam\DbRestore\Forms\Components\SelectColumnField; 
use Callcocam\DbRestore\Forms\Components\SelectTableField; 
use Callcocam\DbRestore\Forms\Components\SelectTableToField;
use Callcocam\DbRestore\Forms\Components\TextareaField;
use Callcocam\DbRestore\Forms\Components\TextInputField;
use Callcocam\DbRestore\Models\Import;
use Callcocam\DbRestore\Helpers\RestoreHelper; 
use Callcocam\DbRestore\Traits\HasStatusColumn;
use Callcocam\DbRestore\Traits\HasTraduction;
use Callcocam\DbRestore\Traits\HasUploadFormField;
use Callcocam\DbRestore\Traits\WithActions;
use Callcocam\DbRestore\Traits\WithColumns;
use Callcocam\DbRestore\Traits\WithFormSchemas;
use Callcocam\DbRestore\Traits\WithSections;
use Callcocam\DbRestore\Traits\WithTables;
use Filament\Actions;
use Filament\Forms\Form; 
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EditImport extends EditRecord
{
    use HasTraduction, HasStatusColumn, WithColumns, WithFormSchemas, HasUploadFormField, WithTables, WithSections, WithActions;

    protected static string $resource = ImportResource::class;

    protected function getHeaderActions(): array
    {

        $actions[] = Actions\CreateAction::make();
        $actions[] = Actions\Action::make('remove-colums')
            ->icon('fas-minus')
            ->color('danger')
            ->label('Remover colunas')
            ->visible(fn (Import $record) => $record->columns->count() > 0)
            ->requiresConfirmation()
            ->action(function (Import $record) {
                if ($childrens = $record->childrens) {
                    foreach ($childrens as $children) {
                        $children->columns()->forceDelete();
                        $children->forceDelete();
                    }
                }
                $record->columns()->forceDelete();
                Notification::make()
                    ->title('Colunas removidas com sucesso!')
                    ->success()
                    ->send();
            });

        if (config('db-restore.actions.tenant', true)) {
            $actions[] =  Actions\Action::make('genearte-colums-chilrens-tenant')
                ->icon('fas-copy')
                ->color('info')
                ->label('Gerar para o tenant colunas')
                ->visible(fn (Import $record) => $record->columns->count() > 0)
                ->requiresConfirmation()
                ->action(function (Import $record) {
                    if (class_exists('App\Core\Helpers\TenantHelper')) {
                        if (method_exists(app('App\Core\Helpers\TenantHelper'), 'generateChildrens')) {
                            return app('App\Core\Helpers\TenantHelper')->generateChildrens($record);
                        }
                    }
                });
        } else {
            $actions[] = $this->getActionGeraColumnsChildren();
        }
        $actions[] =  $this->getActionGeraColumns();
        $actions[] =  $this->getActionRestoreColumns();
        $actions[] =   Actions\DeleteAction::make();
        $actions[] =   Actions\ForceDeleteAction::make();
        $actions[] =   Actions\RestoreAction::make();

        return $actions;
    }

    public function form(Form $form): Form
    {
        //Import model
        $record = $this->record;
        // if (!$record->columns->count()) {
        //     $this->getColumnOptions($record, $record->connectionFrom, $record->connectionTo);
        // }

        return $form
            ->schema([
                TextInputField::makeText('name')
                    ->columnSpan([
                        'md' => 3
                    ])
                    ->required(),
                ConnectionToField::make('connection_id')
                    ->columnSpan([
                        'md' => 3
                    ])
                    ->required(),
                SelectTableField::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->columnSpan([
                        'md' => 3
                    ]),
                SelectTableToField::makeTable('table_to', $record)
                    ->columnSpan([
                        'md' => 3
                    ]),
                RestoreModelField::makeColumn('restore_model_id')
                    ->columnSpan([
                        'md' => 4
                    ]),
                SelectTableField::make('disk')
                    ->options(function () {
                        $options = config('filesystems.disks', []);
                        $disks = array_keys($options);
                        return array_combine($disks, $disks);
                    })
                    ->columnSpan([
                        'md' => 2
                    ])->required(),
                SelectTableField::make('extension')
                    ->options(function () {
                        $options = config('restore.extension', ['csv', 'xls', 'xlsx']);
                        $extensions = $options;
                        return array_combine($extensions, $extensions);
                    })
                    ->columnSpan([
                        'md' => 2
                    ])->required(),
                SelectTableField::make('delimiter')
                    ->options(function () {
                        $options = config('restore.delimiter', [';', '|', ',']);
                        $delimiters = $options;
                        return array_combine($delimiters, $delimiters);
                    })
                    ->columnSpan([
                        'md' => 2
                    ]),
                SelectColumnField::make('type')
                    ->options([
                        'duplicar' => 'Duplicar',
                        'excluir' => 'Excluir',
                        'ignorar' => 'Ignorar',
                    ])
                    ->required()
                    ->columnSpan([
                        'md' => '2'
                    ]),
                static::getUploadFormField('file')
                    ->afterStateUpdated(function (Set $set) {
                        // $set('columns', []);
                    }),
                $this->getSectionColumnsSchema($record, function ($record) {
                    return $this->getColumnsSchemaFileForm($record);
                })
                    ->description($this->getTraduction('column_imports', 'restore', 'form',  'label'))
                    ->visible(fn (Import $record) => $record->table_to && $record->file),

                // Forms\Components\Section::make($this->getTraduction('childrens', 'restore', 'form',  'label'))
                //     ->description($this->getTraduction('childrens_imports', 'restore', 'form',  'description'))
                //     ->visible(fn (Import $record) => $record->table_to && $record->file)
                //     ->collapsed()
                //     ->schema(function (Import $record) {
                //         return  [
                //             Forms\Components\Repeater::make('childrens')
                //                 ->relationship('childrens')
                //                 ->hiddenLabel()
                //                 ->maxItems(1)
                //                 ->schema(function () use ($record) {
                //                     return [
                //                         TextInputField::make('name')
                //                             ->columnSpan([
                //                                 'md' => 2
                //                             ])->required(),
                //                         SelectTableFromField::makeTable('table_from', $record, 'table_from_import')
                //                             ->columnSpan([
                //                                 'md' => 2
                //                             ]),
                //                         SelectTableField::make('join_from_column')
                //                             ->options(function (Get $get) use ($record) {
                //                                 $table = $get('table_from');
                //                                 if ($connectionTo =  $record->connectionTo) {
                //                                     return $this->getColumns($connectionTo, $table, 'to');
                //                                 }
                //                                 return [];
                //                             })
                //                             ->columnSpan([
                //                                 'md' => 3
                //                             ])->required(function (Get $get) {
                //                                 return $get('table_from');
                //                             }),
                //                         SelectColumnToField::makeColumn('join_to_column', $record)
                //                             ->columnSpan([
                //                                 'md' => 3
                //                             ]),
                //                         SelectTableToField::makeTable('table_to', $record, 'table_to_import_fields')
                //                             ->columnSpan([
                //                                 'md' => 2
                //                             ]),
                //                         Section::make()
                //                             ->visible(fn (Children $children) => $children->exists)
                //                             ->schema(function (Children  $children) use ($record) {
                //                                 if (!$children->exists) {
                //                                     return [];
                //                                 }
                //                                 $clone = clone $children;
                //                                 $clone->file = $record->file;
                //                                 $clone->tenant_id  = $record->tenant_id;
                //                                 $clone->tenant  = $record->tenant;
                //                                 $clone->connectionTo = $record->connectionTo;
                //                                 return [
                //                                     $this->getSectionColumnsSchema($clone, function ($record) {
                //                                         return $this->getColumnsSchemaFileChildrensForm($record);
                //                                     })->visible(fn (Children $record) => $record->table_to)
                //                                 ];
                //                             }),

                //                     ];
                //                 })
                //                 ->columns(12)
                //                 ->columnSpanFull()
                //         ];
                //     }),


                $this->getSectionFiltersSchema(record: $record)->visible(fn (Import $record) => $record->table_to && $record->file),

                $this->getSectionOrderingsSchema($record)->visible(fn (Import $record) => $record->table_to && $record->file),

                static::getStatusFormRadioField(),
                TextareaField::makeText('description')
            ])->columns(12);
    }


    protected function getColumnsSchemaFileChildrensForm($record, $relation = 'relation')
    {
        $columns = [];
        if (class_exists('App\Core\Helpers\TenantHelper')) {
            if (method_exists(app('App\Core\Helpers\TenantHelper'), 'getColumns')) {
                return app('App\Core\Helpers\TenantHelper')->getColumns($record, $relation);
            }
        }
        if (!$record->file) {
            return $columns;
        }
        if (Storage::exists($record->file)) {


            $headers = RestoreHelper::getFromColumnsFileOptions($record);


            $headers = array_filter($headers);
            foreach ($headers as $key => $header) {
                $headers[$key] = sprintf("%s - %s", $key, $header);
            }

            $columns[] = SelectColumnField::make('column_from', null, 'column_from_file')
                ->options(function () use ($headers) {
                    return $headers;
                })
                ->columnSpan([
                    'md' => '2',
                ]);
        }
        $columns[] = SelectColumnField::make('column_to', $record)
            ->options(function () use ($record) {
                if (class_exists('App\Core\Helpers\TenantHelper')) {
                    if (method_exists(app('App\Core\Helpers\TenantHelper'), 'getTables')) {
                        return app('App\Core\Helpers\TenantHelper')->getTables($record);
                    }
                } else {
                    return DB::connection(RestoreHelper::getConnectionCloneOptions($record->connectionTo))->table($record->table_to)->pluck('name', 'id')->toArray();
                }
            })
            ->required()->columnSpan([
                'md' => '2',
            ]);

        return  $this->getColumnsSchema($record, $columns, $relation);
    }
}
