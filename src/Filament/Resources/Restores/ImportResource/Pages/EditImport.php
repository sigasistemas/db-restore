<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Filament\Resources\Restores\ImportResource\Pages;

use Callcocam\DbRestore\Filament\Resources\Restores\ImportResource;
use Callcocam\DbRestore\Forms\Components\ConnectionToField;
use Callcocam\DbRestore\Forms\Components\SelectColumnField;
use Callcocam\DbRestore\Forms\Components\SelectColumnToField;
use Callcocam\DbRestore\Forms\Components\SelectTableField;
use Callcocam\DbRestore\Forms\Components\SelectTableFromField;
use Callcocam\DbRestore\Forms\Components\SelectTableToField;
use Callcocam\DbRestore\Forms\Components\TextareaField;
use Callcocam\DbRestore\Forms\Components\TextInputField;
use Callcocam\DbRestore\Models\Import;
use Callcocam\DbRestore\Helpers\RestoreHelper;
use Callcocam\DbRestore\Models\Children;
use Callcocam\DbRestore\Traits\HasStatusColumn;
use Callcocam\DbRestore\Traits\HasTraduction;
use Callcocam\DbRestore\Traits\HasUploadFormField;
use Callcocam\DbRestore\Traits\WithColumns;
use Callcocam\DbRestore\Traits\WithFormSchemas;
use Callcocam\DbRestore\Traits\WithSections;
use Callcocam\DbRestore\Traits\WithTables;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EditImport extends EditRecord
{
    use HasTraduction, HasStatusColumn, WithColumns, WithFormSchemas, HasUploadFormField, WithTables, WithSections;

    protected static string $resource = ImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Restore')
                ->visible(fn (Import $record) => $record->columns->count() > 0)
                ->icon('fas-upload')
                ->color('success')
                ->action(function (Import $record) {
                    //Pega as colunas do modelo Import
                    $columns = $record->columns;
                    //Pega os filhos do modelo Import
                    $childrens = $record->childrens;
                    //pegar os filters
                    $filters = $record->filters;
                    //Verifica se o campo file está preenchido
                    if (!$record->file) {
                        return;
                    }
                    $to_columns = [];
                    //Pega a tabela de destino
                    $from_table = $record->table_to;

                    //Verifica se existe o arquivo
                    if (Storage::exists($record->file)) {
                        //Pega as colunas da tabela de destino
                        $to_columns = RestoreHelper::getColumsSchema($columns, $from_table, 'column_to');
                        //Pega os dados do arquivo e armazena em cache para não ter que ficar lendo o arquivo toda vez
                        $sheetData = Cache::rememberForever("{$record->file}-column", function () use ($record) {
                            $inputFileName = Storage::path($record->file);
                            $testAgainstFormats = [
                                \PhpOffice\PhpSpreadsheet\IOFactory::READER_XLS,
                                \PhpOffice\PhpSpreadsheet\IOFactory::READER_XLSX,
                                \PhpOffice\PhpSpreadsheet\IOFactory::READER_CSV,
                            ];
                            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName, 0, $testAgainstFormats);

                            return  $spreadsheet->getActiveSheet()->toArray(true, true, true, true);
                        });
                        unset($sheetData[1]);

                        $chunks = array_chunk($sheetData, 1000);

                        $children = null;
                        if ($childrens) {
                            $children = $childrens->first();
                        }
                        if ($record->type == 'excluir') {
                            $connectionTo = RestoreHelper::getConnectionCloneOptions($record->connectionTo);
                            //Se o tipo for excluir, vamos excluir os dados da tabela de destino
                            $query = DB::connection($connectionTo)->table($from_table);
                            //Vamos verificar se temos filtros
                            if ($filters->count()) {
                                //Vamos pegar os filtros do tipo delete
                                $filterDeletes = $filters->filter(fn ($filter) => $filter->type == 'delete')->all();
                                //Vamos percorrer os filtros
                                foreach ($filterDeletes as $filter) {
                                    //Vamos aplicar os filtros
                                    RestoreHelper::queryFilters($query, $filter->column_to, $filter->operator, $filter->value);
                                }
                            }
                            //Vamos verificar se temos filhos
                            if ($table_from = data_get($children, 'table_from')) {
                                //Vamos pegar os ids dos pais
                                $parents = $query->pluck('id')->toArray();
                                //Vamos excluir os dados da tabela filha
                                $query = DB::connection($connectionTo)->table($table_from)->whereIn($children->join_from_column, $parents)->delete();
                            }
                            //Vamos excluir os dados da tabela de destino
                            $query->delete();
                        }
                       

                        //Vamos verificar se vamos usar uma tabela de filhos
                        //A coluna table_from é a tabela filha do modelo principal
                        //A ideia é que você possa importar dados para uma tabela filha
                        //Exemplo: A tabela principal vai conter alguns compos que serão preenchidos com os dados do arquivo
                        //Exemplo: A coluna A1 do arquivo vai ser preenchida na coluna nome da tabela principal,
                        //a coluna B1 do arquivo vai ser preenchida na coluna email da tabela principal 
                        if (data_get($children, 'table_from')) {
                            //Eschamos a tabela filha, vamos carregar os dados da tabela filha
                            dd($children->table_from);
                        } else {
                            //Não temos uma tabela filha, vamos carregar os dados em uma coluna da tabela principal,
                            //Que é a coluna join_to_column do modelo filho(children)
                            //Pega as colunas da tabela de destino
                            $childremColumns = $children->columns;
                            $to_columns[$children->join_to_column]['column_from'] = $childremColumns->pluck('column_from', 'column_to')->toArray();
                        }
                        //TESTE
                        // $connectionTo = RestoreHelper::getConnectionCloneOptions($record->connectionTo);

                        // foreach ($chunks as $chunk) {
                        //     $values = RestoreHelper::getDataValues($chunk, $to_columns, $connectionTo);
                        //     dd($values);
                        // }
                        //FIN TESTE
                        //Inicia o batch
                        $batch =  Bus::batch([])->then(function (Batch $batch) use ($record) {
                        })->name($record->name)->dispatch();
                        //Seta a tabela de destino
                        $record->table_to = $from_table;
                        //Percorre os chunks
                        foreach ($chunks as $chunk) {
                            $batch->add(new \Callcocam\DbRestore\Jobs\DbRestoreFileJob($record, $chunk, $to_columns, $children));
                        }
                    }
                }),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
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
                        'md' => 5
                    ])
                    ->required(),
                ConnectionToField::make('connection_id')
                    ->columnSpan([
                        'md' => 3
                    ])
                    ->required(),
                SelectTableField::make('restore_model_id')
                    ->relationship('restoreModel', 'name')
                    ->columnSpan([
                        'md' => 4
                    ]),
                SelectTableToField::makeTable('table_to', $record)
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

                Forms\Components\Section::make($this->getTraduction('childrens', 'restore', 'form',  'label'))
                    ->description($this->getTraduction('childrens_imports', 'restore', 'form',  'description'))
                    ->visible(fn (Import $record) => $record->table_to && $record->file)
                    ->collapsed()
                    ->schema(function (Import $record) {
                        return  [
                            Forms\Components\Repeater::make('childrens')
                                ->relationship('childrens')
                                ->hiddenLabel()
                                ->maxItems(1)
                                ->schema(function () use ($record) {
                                    return [
                                        TextInputField::make('name')
                                            ->columnSpan([
                                                'md' => 2
                                            ])->required(),
                                        SelectTableFromField::makeTable('table_from', $record, 'table_from_import')
                                            ->columnSpan([
                                                'md' => 2
                                            ]),
                                        SelectTableField::make('join_from_column')
                                            ->options(function (Get $get) use ($record) {
                                                $table = $get('table_from');
                                                if ($connectionTo =  $record->connectionTo) {
                                                    return $this->getColumns($connectionTo, $table, 'to');
                                                }
                                                return [];
                                            })
                                            ->columnSpan([
                                                'md' => 3
                                            ])->required(function (Get $get) {
                                                return $get('table_from');
                                            }),
                                        SelectColumnToField::makeColumn('join_to_column', $record)
                                            ->columnSpan([
                                                'md' => 3
                                            ]),
                                        SelectTableToField::makeTable('table_to', $record)
                                            ->columnSpan([
                                                'md' => 2
                                            ]),
                                        Section::make()
                                            ->visible(fn (Children $children) => $children->exists)
                                            ->schema(function (Children  $children) use ($record) {
                                                if (!$children->exists) {
                                                    return [];
                                                }
                                                $clone = clone $children;
                                                $clone->file = $record->file;
                                                $clone->connectionTo = $record->connectionTo;
                                                return [
                                                    $this->getSectionColumnsSchema($clone, function ($record) {
                                                        return $this->getColumnsSchemaFileChildrensForm($record);
                                                    })->visible(fn (Children $record) => $record->table_to)
                                                ];
                                            }),

                                    ];
                                })
                                ->columns(12)
                                ->columnSpanFull()
                        ];
                    }),


                $this->getSectionFiltersSchema(record: $record)->visible(fn (Import $record) => $record->table_to && $record->file),

                $this->getSectionOrderingsSchema($record)->visible(fn (Import $record) => $record->table_to && $record->file),

                static::getStatusFormRadioField(),
                TextareaField::makeText('description')
            ])->columns(12);
    }


    protected function getColumnsSchemaFileChildrensForm($record, $relation = 'relation')
    {
        $columns = [];

        if (Storage::exists($record->file)) {


            $headers = RestoreHelper::getFromColumnsFileOptions($record);


            $headers = array_filter($headers);

            $columns[] = SelectColumnField::make('column_from')
                ->options(function () use ($headers) {
                    return $headers;
                })
                ->columnSpan([
                    'md' => '2',
                ]);
        }
        $columns[] = SelectColumnField::make('column_to', $record)
            ->options(function () use ($record) {
                return DB::connection(RestoreHelper::getConnectionCloneOptions($record->connectionTo))->table($record->table_to)->pluck('name', 'id')->toArray();
            })
            ->required()->columnSpan([
                'md' => '2',
            ]);

        return  $this->getColumnsSchema($record, $columns, $relation);
    }
}
