<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Traits;

use Callcocam\DbRestore\Forms\Components\SelectColumnField;
use Callcocam\DbRestore\Forms\Components\SelectColumnFromField;
use Callcocam\DbRestore\Forms\Components\SelectColumnToField;
use Callcocam\DbRestore\Forms\Components\SelectTableField;
use Callcocam\DbRestore\Forms\Components\SelectTableFromField;
use Callcocam\DbRestore\Forms\Components\SelectTableToField;
use Callcocam\DbRestore\Forms\Components\TextareaField;
use Callcocam\DbRestore\Forms\Components\TextInputField;
use Callcocam\DbRestore\Helpers\RestoreHelper;
use Callcocam\DbRestore\Models\Column;
use Callcocam\DbRestore\Models\Connection;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Get;
use Illuminate\Support\Facades\DB;

trait WithFormSchemas
{


    protected function getFormSchemaRelationOptions($record)
    {
        return [
            Group::make([
                SelectTableField::make('restore_model_id')
                    ->relationship(
                        name: 'restoreModel',
                        titleAttribute: 'name'
                    )
                    ->columnSpan([
                        'md' => '4',
                    ]),
                TextInputField::make('name')
                    ->required()
                    ->columnSpan([
                        'md' => '4',
                    ]),
                SelectTableToField::makeTable('table_from', $record,  'relation_table_name')
                    ->live()
                    ->required()
                    ->columnSpan([
                        'md' => '4',
                    ]),

                SelectColumnField::makeToOptions('column_from', $record, 'table_from', 'relation_column_to')
                    ->required()
                    ->columnSpan([
                        'md' => '6',
                    ]),
                SelectColumnField::makeToOptions('column_value', $record, 'table_from',  'relation_column_value')
                    ->required()
                    ->columnSpan([
                        'md' => '6',
                    ]),
                TextareaField::makeText('description'),

            ])->columns(12),
        ];
    }

    protected function getColumnsSchemaFileForm($record, $relation = 'relation')
    {
        $columns = [];
        if (empty($record->file)) {
            return [];
        }
        if (Storage::disk(config('db-restore.disk'))->exists($record->file)) {


            $headers = Cache::rememberForever("{$record->file}-header", function () use ($record) {
                $inputFileName = Storage::disk(config('db-restore.disk'))->path($record->file);

                $testAgainstFormats = [
                    \PhpOffice\PhpSpreadsheet\IOFactory::READER_XLS,
                    \PhpOffice\PhpSpreadsheet\IOFactory::READER_XLSX,
                    \PhpOffice\PhpSpreadsheet\IOFactory::READER_CSV,
                ];

                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName, 0, $testAgainstFormats);
                $headers = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
                if (isset($headers[1])) {
                    return $headers[1];
                }
                return [];
            });
            $headers = array_filter($headers);
            $columns[] =  SelectColumnField::make('column_from')
                ->options(function () use ($headers) {
                    return $headers;
                })
                ->columnSpan([
                    'md' => '2',
                ]);
        }

        $columns[] = SelectColumnToField::makeColumn('column_to', $record)
            ->required()
            ->columnSpan([
                'md' => '2',
            ]);

        return  $this->getColumnsSchema($record, $columns, $relation);
    }


    protected function getColumnsSchemaFileExportForm($record,  $relation = 'relation')
    {
        $columns = [];
        $columns[] =    SelectColumnField::make('column_from')
            ->required()
            ->searchable()
            ->options(function () use ($record) {
                $columns = Cache::rememberForever("colunas-header", function () {
                    $alfabetoExcel = [];
                    for ($i = 65; $i <= 90; $i++) {
                        $alfabetoExcel[] = chr($i);
                    }
                    for ($i = 65; $i <= 90; $i++) {
                        for ($j = 65; $j <= 90; $j++) {
                            $alfabetoExcel[] = chr($i) . chr($j);
                        }
                    }
                    return  array_combine($alfabetoExcel, $alfabetoExcel);
                });

                return $columns;
            })
            ->columnSpan([
                'md' => '2',
            ]);
        $columns[] = SelectColumnFromField::makeColumn('column_to', $record)
            ->required()
            ->columnSpan([
                'md' => '2',
            ]);

        return  $this->getColumnsSchema($record, $columns, $relation);
    }

    protected function getColumnsSchemaForm($record, $relation = 'relation')
    {
        $columns = [];

        if ($record->connectionFrom) {
            $columns[] = SelectColumnFromField::makeColumn('column_from', $record)
                ->required()
                ->columnSpan([
                    'md' => '2',
                ]);
        }

        if ($record->connectionTo) {
            $columns[] =  SelectColumnToField::makeColumn('column_to', $record)
                ->required()
                ->columnSpan([
                    'md' => '2',
                ]);
        }
        return  $this->getColumnsSchema($record, $columns, $relation);
    }

    protected function getColumnsSchema($record, $columns, $relation = 'relation')
    {

        $columns[] =  SelectColumnField::make('relation_id')
            ->relationship(
                name: $relation,
                titleAttribute: 'name'
            )
            ->manageOptionForm($this->getFormSchemaRelationOptions($record))
            ->columnSpan([
                'md' => '3',
            ]);

        $columns[] =  TextInputField::make('default_value')
            ->hintAction(
                Action::make('searchDefaultValue')
                    ->fillForm(function (Column $column) {
                        if ($model = $column->defaults) {
                            return $model->toArray();
                        }
                        return [];
                    })
                    ->visible(fn (Column $column) => $column->exists)
                    ->label($this->getTraductionFormLabel('searchDefaultValue'))
                    ->icon('fas-search')
                    ->form(fn (Column $column) => $this->getsearchDefaultValueSchemaForm($column))

                    ->action(function ($data, Column $column, Set $set) {
                        if ($model = $column->defaults) {
                            $model->update($data);
                            $column->update([
                                'default_value' =>  data_get($data, 'column_value')
                            ]);
                        } else {
                            $model = $column->defaults()->create($data);
                            $column->update([
                                'default_value' =>   data_get($data, 'column_value')
                            ]);
                        }
                        $set('default_value', data_get($data, 'column_value'));
                    })
            )
            ->columnSpan([
                'md' => '3',
            ]);

        $columns[] = SelectColumnField::make('type')
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
            ->default('string')
            ->columnSpan([
                'md' => '2',
            ]);
        return  $columns;
    }

    protected function getFiltersSchemaForm($connection, $table, $connectionTo)
    {


        return [
            SelectTableToField::makeTable('name', $connectionTo)
                ->required()
                ->columnSpan([
                    'md' => '3',
                ]),
            SelectColumnToField::makeColumnConnection('column_to', $connection, $table)
                ->required()
                ->columnSpan([
                    'md' => '2',
                ]),
            SelectColumnField::make('operator')
                ->required()
                ->options([
                    '=' => '=',
                    '!=' => '!=',
                    '<' => '<',
                    '<=' => '<=',
                    '>' => '>',
                    '>=' => '>=',
                    'like' => 'like',
                    'not like' => 'not like',
                    'in' => 'in',
                    'not in' => 'not in',
                    'between' => 'between',
                    'not between' => 'not between',
                    'is null' => 'is null',
                    'is not null' => 'is not null',
                ])
                ->columnSpan([
                    'md' => '2',
                ]),
            TextInputField::make('value')
                ->columnSpan([
                    'md' => '3',
                ]),
            SelectColumnField::make('type')
                ->required()
                ->options([
                    'create' => 'Create',
                    'update' => 'Update',
                    'delete' => 'Delete',
                    'restore' => 'Restore',
                    'list' => 'List',
                ])
                ->default('list')
                ->columnSpan([
                    'md' => '2',
                ]),
        ];
    }
    protected function getOrderingsSchemaForm($connection, $table)
    {

        return [
            TextInputField::make('name')
                ->required()
                ->columnSpan([
                    'md' => '3',
                ]),
            SelectColumnField::make('column')
                ->required()
                ->options(function () use ($connection, $table) {
                    if ($connection) {
                        return $this->getColumns($connection, $table, 'to');
                    }

                    return [];
                })
                ->columnSpan([
                    'md' => '4',
                ]),
            SelectColumnField::make('direction')
                ->required()
                ->options([
                    'ASC' => 'ASC',
                    'DESC' => 'DESC',
                ])
                ->columnSpan([
                    'md' => '3',
                ]),
            SelectColumnField::make('ordering')
                ->required()
                ->options([
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ])
                ->columnSpan([
                    'md' => '2',
                ]),
        ];
    }

    protected function getPivotschemaForm($record)
    {
        return [
            Section::make()
                ->schema(function () use ($record) {
                    return [
                        TextInputField::make('name')
                            ->required()
                            ->columnSpan([
                                'md' => '2',
                            ]),
                        SelectTableToField::makeTable('table_to', $record, 'pivot_table_to')
                            ->live()
                            ->required()
                            ->columnSpan([
                                'md' => '2',
                            ]),
                        SelectColumnToField::makeToOptions('column_to', $record, 'table_to', 'pivot_column_to')
                            ->required()
                            ->columnSpan([
                                'md' => '3',
                            ]),

                        SelectTableToField::makeTable('table_from', $record, 'pivot_table_from')
                            ->live()
                            ->required()
                            ->columnSpan([
                                'md' => '2',
                            ]),
                        SelectColumnToField::makeToOptions('column_from', $record, 'table_to', 'pivot_column_from')
                            ->required()
                            ->columnSpan([
                                'md' => '3',
                            ]),

                    ];
                })
                ->columns(12)
        ];
    }

    protected function getsearchDefaultValueSchemaForm($column)
    {

        return [
            Section::make()
                ->schema(function () {
                    return [
                        SelectColumnField::make('connection_id')
                            ->live()
                            ->afterStateUpdated(function (string $state) {
                                if ($connectionTo = Connection::find($state)) {
                                    Cache::put($state, $connectionTo);
                                }
                                return $state;
                            })
                            ->required()
                            ->options(Connection::query()->pluck('name', 'id')->toArray())
                            ->columnSpan([
                                'md' => '3',
                            ]),
                        SelectColumnField::make('table_from')
                            ->live()
                            ->required()
                            ->options(function (Get $get) {
                                return $this->getTablesOptions($get('connection_id'));
                            })
                            ->columnSpan([
                                'md' => '3',
                            ]),
                        SelectColumnField::make('column_key')
                            ->live()
                            ->required()
                            ->options(function (Get $get) {
                                $connectionKey = $get('connection_id');
                                if ($connectionTo = Cache::rememberForever($connectionKey, function () use ($connectionKey) {
                                    return Connection::find($connectionKey);
                                })) {
                                    return $this->getColumns($connectionTo, $get('table_from'), 'to');
                                }

                                return [];
                            })
                            ->columnSpan([
                                'md' => '3',
                            ]),
                        SelectColumnField::make('column_label')
                            ->live()
                            ->required()
                            ->options(function (Get $get) {
                                $connectionKey = $get('connection_id');
                                if ($connectionTo = Cache::rememberForever($connectionKey, function () use ($connectionKey) {
                                    return Connection::find($connectionKey);
                                })) {
                                    return $this->getColumns($connectionTo, $get('table_from'), 'to');
                                }

                                return [];
                            })
                            ->columnSpan([
                                'md' => '3',
                            ]),
                        SelectColumnField::make('column_value')
                            ->visible(function (Get $get) {
                                return $get('column_label') && $get('column_key');
                            })
                            ->searchable()
                            ->required()
                            ->options(function (Get $get) {
                                $connectionKey = $get('connection_id');
                                if ($connectionTo = Cache::rememberForever($connectionKey, function () use ($connectionKey) {
                                    return Connection::find($connectionKey);
                                })) {
                                    return DB::connection(RestoreHelper::getConnectionCloneOptions($connectionTo))
                                        ->table($get('table_from'))
                                        ->whereNotNull($get('column_label'))
                                        ->pluck($get('column_label'), $get('column_key'))->toArray();
                                }

                                return [];
                            })
                            ->columnSpanFull()

                    ];
                })
                ->columns(12)
        ];
    }
}
