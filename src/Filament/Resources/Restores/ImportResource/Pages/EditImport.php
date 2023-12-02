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
use Callcocam\DbRestore\Forms\Components\SelectField;
use Callcocam\DbRestore\Forms\Components\SelectTableField;
use Callcocam\DbRestore\Forms\Components\SelectTableToField;
use Callcocam\DbRestore\Forms\Components\TextareaField;
use Callcocam\DbRestore\Forms\Components\TextInputField;
use Callcocam\DbRestore\Helpers\DataBaseHelper;
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
        if (class_exists('App\Core\Helpers\TenantHelper')) {
            if (method_exists(app('App\Core\Helpers\TenantHelper'), 'importForTenant')) {
                $actions[] =  Actions\Action::make('genearte-colums-chilrens-tenant')
                    ->icon('fas-copy')
                    ->color('info')
                    ->label('Gerar para o tenant selecionado')
                    ->visible(fn (Import $record) => $record->columns->count() > 0)
                    ->requiresConfirmation()
                    ->action(function (Import $record) {
                        return app('App\Core\Helpers\TenantHelper')->importForTenant($record);
                    });
            }
        }

        // $actions[] =  $this->getActionGeraColumns();
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
                SelectField::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->columnSpan([
                        'md' => 3
                    ]),

                //Não é nessário reacaregar as colunas baseado em um outro select
                SelectField::make('table_to')
                    ->options($record->tableToOptions)
                    ->columnSpan([
                        'md' => 3
                    ]),
                RestoreModelField::makeColumn('restore_model_id')
                    ->columnSpan([
                        'md' => 4
                    ]),
                SelectField::make('disk')
                    ->options(function () {
                        $options = config('filesystems.disks', []);
                        $disks = array_keys($options);
                        return array_combine($disks, $disks);
                    })
                    ->columnSpan([
                        'md' => 2
                    ])->required(),
                SelectField::make('extension')
                    ->options(function () {
                        $options = config('restore.extension', ['csv', 'xls', 'xlsx']);
                        $extensions = $options;
                        return array_combine($extensions, $extensions);
                    })
                    ->columnSpan([
                        'md' => 2
                    ])->required(),
                SelectField::make('delimiter')
                    ->options(function () {
                        $options = config('restore.delimiter', [';', '|', ',']);
                        $delimiters = $options;
                        return array_combine($delimiters, $delimiters);
                    })
                    ->columnSpan([
                        'md' => 2
                    ]),
                SelectField::make('type')
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
        if (Storage::disk(config('db-restore.disk'))->exists($record->file)) {


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
