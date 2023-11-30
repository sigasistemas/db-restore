<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Filament\Resources\Restores\ImportResource\Pages;

use Callcocam\DbRestore\Filament\Resources\Restores\ImportResource;
use Callcocam\DbRestore\Forms\Components\ConnectionField;
use Callcocam\DbRestore\Forms\Components\ConnectionToField;
use Callcocam\DbRestore\Forms\Components\RestoreModelField;
use Callcocam\DbRestore\Forms\Components\SelectColumnField;
use Callcocam\DbRestore\Forms\Components\SelectColumnToField;
use Callcocam\DbRestore\Forms\Components\SelectTableField;
use Callcocam\DbRestore\Forms\Components\SelectTableFromField;
use Callcocam\DbRestore\Forms\Components\SelectTableToField;
use Callcocam\DbRestore\Forms\Components\TextareaField;
use Callcocam\DbRestore\Forms\Components\TextInputField;
use Callcocam\DbRestore\Helpers\FileHelper;
use Callcocam\DbRestore\Helpers\PlanilhaHelper;
use Callcocam\DbRestore\Models\Import;
use Callcocam\DbRestore\Helpers\RestoreHelper;
use Callcocam\DbRestore\Models\Children;
use Callcocam\DbRestore\Models\Connection;
use Callcocam\DbRestore\Models\Sample;
use Callcocam\DbRestore\Traits\HasStatusColumn;
use Callcocam\DbRestore\Traits\HasTraduction;
use Callcocam\DbRestore\Traits\HasUploadFormField;
use Callcocam\DbRestore\Traits\WithColumns;
use Callcocam\DbRestore\Traits\WithFormSchemas;
use Callcocam\DbRestore\Traits\WithSections;
use Callcocam\DbRestore\Traits\WithTables;
use Callcocam\Tenant\Models\Tenant;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class EditImport extends EditRecord
{
    use HasTraduction, HasStatusColumn, WithColumns, WithFormSchemas, HasUploadFormField, WithTables, WithSections;

    protected static string $resource = ImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('remove-colums')
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
                }),

            Actions\Action::make('gerar-columns-childrens')
                ->icon('fas-plus')
                ->color('info')
                ->label('Gerar Filhos')
                ->visible(fn (Import $record) => $record->columns->count() > 0)
                ->form(function (Import $record) {
                    return [
                        Group::make([
                            TextInputField::make('name')
                                ->columnSpan([
                                    'md' => 5
                                ])->required(),
                            ConnectionToField::make('connection_id')
                                ->columnSpan([
                                    'md' => 7
                                ])
                                ->options(Connection::query()->pluck('name', 'id')->toArray())
                                ->default($record->connection_id)
                                ->reactive()
                                ->required(),
                            Section::make('Dados da tebela secundaria')
                                ->description('Obrigatório para gerar os filhos em uma tabela secundaria. ex: tabela de produto_items')
                                ->columnSpanFull()
                                ->columns(12)
                                ->schema([
                                    SelectTableFromField::makeTable('table_from', $record, 'table_from_import')
                                        ->columnSpan([
                                            'md' => 4
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
                                            'md' => 4
                                        ])->required(function (Get $get) {
                                            return $get('table_from');
                                        }),
                                    SelectTableField::make('table_to_fields')
                                        ->options(function (Get $get)  use ($record) {
                                            $connection =  $get('connection_id');
                                            if ($connection) {

                                                return $this->getTablesOptions($connection, 'to');
                                            }
                                            return $this->getTablesOptions($record->connectionTo, 'to');
                                        })
                                        ->columnSpan([
                                            'md' => 4
                                        ])
                                        ->required(function (Get $get) {
                                            return $get('table_from');
                                        }),
                                ]),
                            SelectColumnToField::makeColumn('join_to_column', $record)
                                ->helperText('Coluna da tabela principal que vai ser usada para fazer o join')
                                ->required(function (Get $get) {
                                    return !$get('table_from');
                                })
                                ->columnSpan([
                                    'md' => 9
                                ]),

                            SelectColumnField::make('type')

                                ->columnSpan([
                                    'md' => 3
                                ])
                                ->options([
                                    'string' => 'String',
                                    'integer' => 'Integer',
                                    'float' => 'Float',
                                    'boolean' => 'Boolean',
                                    'date' => 'Date',
                                    'datetime' => 'Datetime',
                                    'time' => 'Time',
                                    'timestamp' => 'Timestamp',
                                    'json' => 'Json',
                                    'jsonb' => 'Jsonb',
                                    'uuid' => 'Uuid',
                                    'binary' => 'Binary',
                                    'enum' => 'Enum',
                                    'array' => 'Array',
                                    'password' => 'Password',
                                ])
                                ->default('string'),

                        ])->columns(12)
                    ];
                })
                ->action(function (Import $record, array $data) {
                    $connection = RestoreHelper::getConnectionCloneOptions(Connection::find($data['connection_id']));
                    $fields  = DB::connection($connection)->table($data['table_to_fields'])->get()->pluck('name', 'id')->toArray();

                    $headers = FileHelper::make($record)
                        ->load()
                        ->getHeaders();
                    $reverts = array_flip($headers);
                    $values = [];
                    foreach ($fields as $key => $header) {
                        if (isset($reverts[$header])) {
                            $values[$reverts[$header]] = $key;
                        }
                    }

                    if ($childrens = $record->childrens) {
                        foreach ($childrens as $children) {
                            $children->columns()->forceDelete();
                            $children->forceDelete();
                        }
                    }
                    $childrem =  $record->childrens()->create([
                        'tenant_id' => $record->tenant_id,
                        'name' => data_get($data, 'name'),
                        'table_from' => data_get($data, 'table_from'),
                        'join_from_column' => data_get($data, 'join_from_column'),
                        'table_to' => data_get($data, 'table_to_fields'),
                        'join_to_column' => data_get($data, 'join_to_column'),
                        'relation_type' => 'one-to-many',
                    ]);

                    if ($headers) {
                        foreach ($values as $key => $header) {
                            $childrem->columns()->create([
                                'tenant_id' => $record->tenant_id,
                                'column_from' => $key,
                                'column_to' => $header,
                                'type' => data_get($data, 'type'),
                            ]);
                        }
                        Notification::make()
                            ->title('Colunas geradas com sucesso!')
                            ->success()
                            ->send();
                    }
                }),
            Actions\Action::make('gerar-columns')
                ->icon('fas-plus')
                ->color('info')
                ->label('Gerar colunas')
                ->visible(fn (Import $record) => $record->table_to && $record->file)
                ->form(function (Import $record) {

                    return [

                        Group::make([
                            SelectColumnField::make('type')
                                ->required()
                                ->options([
                                    'string' => 'String',
                                    'integer' => 'Integer',
                                    'float' => 'Float',
                                    'boolean' => 'Boolean',
                                    'date' => 'Date',
                                    'datetime' => 'Datetime',
                                    'time' => 'Time',
                                    'timestamp' => 'Timestamp',
                                    'json' => 'Json',
                                    'jsonb' => 'Jsonb',
                                    'uuid' => 'Uuid',
                                    'binary' => 'Binary',
                                    'enum' => 'Enum',
                                    'array' => 'Array',
                                    'password' => 'Password',
                                ])
                                ->default('string'),
                            SelectColumnToField::makeColumn('column_to', $record),

                        ])->columns(2)
                    ];
                })
                ->action(function (Import $record, array $data) {

                    $headers = FileHelper::make($record)
                        ->load()
                        ->getHeaders();

                    if ($headers) {
                        $record->columns()->forceDelete();
                        foreach ($headers as $key => $header) {
                            if (!$record->columns()->where('column_from', $key)->exists()) {
                                $record->columns()->create([
                                    'tenant_id' => $record->tenant_id,
                                    'column_from' => $key,
                                    'column_to' => data_get($data, 'column_to', $header),
                                    'type' => data_get($data, 'type'),
                                ]);
                            }
                        }
                        Notification::make()
                            ->title('Colunas geradas com sucesso!')
                            ->success()
                            ->send();
                    }
                }),
            Actions\Action::make('sample')
                ->icon('fas-file-import')
                ->color('warning')
                ->label('Gerar um modelo')
                ->form(function (Import $record) {
                    return [
                        SelectColumnField::make('tenant_id', $record)
                            ->required()
                            ->searchable()
                            ->columnSpanFull()
                            ->options(Tenant::query()->whereStatus('published')->pluck('name', 'id')->toArray())
                    ];
                })
                ->action(function (Import $record, array $data) {

                    if (class_exists('App\Core\Helpers\TenantHelper')) {
                        if (method_exists(app('App\Core\Helpers\TenantHelper'), 'generateModel')) {
                            return app('App\Core\Helpers\TenantHelper')->generateModel($data);
                        }
                    }

                    $tenant = Tenant::find($data['tenant_id']);

                    $fields = DB::connection(config('database.default'))->table(config('db-restore.tables.fields', 'fields'))->where('tenant_id', $tenant['id'])->get();

                    if ($fields->count()) {
                        $fileName = sprintf('%s.%s', $record->slug, $record->extension);
                        PlanilhaHelper::make($record, $fields)
                            ->fileName($fileName)
                            ->sheet()
                            ->getHeaders()
                            ->save();
                        return Storage::disk($record->disk)->download($fileName);
                    }
                    Notification::make()
                        ->title('Não foi possível gerar o modelo!')
                        ->danger()
                        ->send();
                }),
            Actions\Action::make('Restore')
                ->label('Importar')
                ->visible(fn (Import $record) => $record->columns->count() > 0)
                ->icon('fas-upload')
                ->color('success')
                ->action(function (Import $record) {
                    if (class_exists('App\Core\Helpers\TenantHelper')) {
                        if (method_exists(app('App\Core\Helpers\TenantHelper'), 'import')) {
                            return app('App\Core\Helpers\TenantHelper')->import($record);
                        }
                    } else {
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

                            //Vamos verificar se temos o campo tenant_id
                            if ($column = data_get($to_columns, 'tenant_id')) {
                                //Vamos verificar se temos um valor padrão
                                //Se não tiver vamos usar o tenant_id selecionado no formulário
                                if (!data_get($column, 'default_value')) {
                                    data_set($column, 'default_value', $record->tenant_id);
                                }
                                //Vamos atualizar o tenant_id da lista de colunas to_columns
                                data_set($to_columns, 'tenant_id', $column);
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
                                if ($children && $childremColumns = $children->columns) {
                                    $to_columns[$children->join_to_column]['column_from'] = $childremColumns->pluck('column_from', 'column_to')->toArray();
                                }
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
                                        SelectTableToField::makeTable('table_to', $record, 'table_to_import_fields')
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
                                                $clone->tenant_id  = $record->tenant_id;
                                                $clone->tenant  = $record->tenant;
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
