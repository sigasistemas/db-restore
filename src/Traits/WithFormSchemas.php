<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Traits;

use Filament\Forms;
use Filament\Forms\Components\Group;

trait WithFormSchemas
{
    public function getFormSchemaConnectionOptions()
    {
        return [
            Forms\Components\Group::make([
                Forms\Components\TextInput::make('name')
                    ->label($this->getTraductionFormLabel('name'))
                    ->placeholder($this->getTraductionFormPlaceholder('name'))
                    ->required()
                    ->columnSpan([
                        'md' => '4',
                    ])
                    ->maxLength(255),
                Forms\Components\TextInput::make('host')
                    ->label($this->getTraductionFormLabel('host'))
                    ->placeholder($this->getTraductionFormPlaceholder('host'))
                    ->required()
                    ->default('localhost')
                    ->columnSpan([
                        'md' => '3',
                    ])
                    ->maxLength(255),
                Forms\Components\TextInput::make('port')
                    ->label($this->getTraductionFormLabel('port'))
                    ->placeholder($this->getTraductionFormPlaceholder('port'))
                    ->default('3306')
                    ->required()
                    ->columnSpan([
                        'md' => '2',
                    ])
                    ->maxLength(255),
                Forms\Components\TextInput::make('database')
                    ->label($this->getTraductionFormLabel('database'))
                    ->placeholder($this->getTraductionFormPlaceholder('database'))
                    ->required()
                    ->columnSpan([
                        'md' => '3',
                    ])
                    ->maxLength(255),
                Forms\Components\TextInput::make('username')
                    ->label($this->getTraductionFormLabel('username'))
                    ->placeholder($this->getTraductionFormPlaceholder('username'))
                    ->required()
                    ->default('root')
                    ->columnSpan([
                        'md' => '3',
                    ])
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->label($this->getTraductionFormLabel('password'))
                    ->placeholder($this->getTraductionFormPlaceholder('password'))
                    ->columnSpan([
                        'md' => '3',
                    ])
                    ->maxLength(255),
                Forms\Components\TextInput::make('charset')
                    ->label($this->getTraductionFormLabel('charset'))
                    ->placeholder($this->getTraductionFormPlaceholder('charset'))
                    ->default('utf8mb4')
                    ->required()
                    ->columnSpan([
                        'md' => '2',
                    ])
                    ->maxLength(255),
                Forms\Components\TextInput::make('prefix')
                    ->label($this->getTraductionFormLabel('prefix'))
                    ->placeholder($this->getTraductionFormPlaceholder('prefix'))
                    ->columnSpan([
                        'md' => '2',
                    ])
                    ->maxLength(255),
                Forms\Components\TextInput::make('engine')
                    ->label($this->getTraductionFormLabel('engine'))
                    ->placeholder($this->getTraductionFormPlaceholder('engine'))
                    ->default('InnoDB')
                    ->required()
                    ->columnSpan([
                        'md' => '2',
                    ])
                    ->maxLength(255),
                Forms\Components\TextInput::make('collation')
                    ->label($this->getTraductionFormLabel('collation'))
                    ->placeholder($this->getTraductionFormPlaceholder('collation'))
                    ->default('utf8mb4_unicode_ci')
                    ->required()
                    ->columnSpan([
                        'md' => '4',
                    ])
                    ->maxLength(255),
                Forms\Components\TextInput::make('url')
                    ->label($this->getTraductionFormLabel('url'))
                    ->placeholder($this->getTraductionFormPlaceholder('url'))
                    ->columnSpan([
                        'md' => '8',
                    ])
                    ->maxLength(255),
                Forms\Components\Select::make('driver')
                    ->label($this->getTraductionFormLabel('driver'))
                    ->placeholder($this->getTraductionFormPlaceholder('driver'))
                    ->options([
                        'mysql' => 'Mysql',
                        'pgsql' => 'Pgsql',
                        'sqlite' => 'Sqlite',
                        'sqlsrv' => 'Sqlsrv',
                        'mongodb' => 'Mongodb',
                    ])
                    ->default('mysql')
                    ->required()
                    ->columnSpan([
                        'md' => '4',
                    ]),
                Forms\Components\Select::make('type')
                    ->label($this->getTraductionFormLabel('type'))
                    ->placeholder($this->getTraductionFormPlaceholder('type'))
                    ->required()
                    ->options([
                        'from' => 'From',
                        'to' => 'To',
                        'all' => 'All',
                    ])
                    ->default('all')
                    ->columnSpan([
                        'md' => '4',
                    ]),
                Forms\Components\Select::make('status')
                    ->label($this->getTraductionFormLabel('status'))
                    ->placeholder($this->getTraductionFormPlaceholder('status'))
                    ->required()
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ])
                    ->columnSpan([
                        'md' => '4',
                    ]),
                Forms\Components\Textarea::make('description')
                    ->label($this->getTraductionFormLabel('description'))
                    ->placeholder($this->getTraductionFormPlaceholder('description'))
                    ->maxLength(65535)
                    ->columnSpanFull(),

            ])->columns(12),
        ];
    }

    protected function getFormSchemaRelationOptions($record)
    {
        return [
            Group::make([
                Forms\Components\Select::make('restore_model_id')
                    ->label($this->getTraductionFormLabel('restore_model_id'))
                    ->placeholder($this->getTraductionFormPlaceholder('restore_model_id'))
                    ->relationship(
                        name: 'restoreModel',
                        titleAttribute: 'name'
                    )
                    ->columnSpan([
                        'md' => '4',
                    ]),
                Forms\Components\TextInput::make('name')
                    ->label($this->getTraductionFormLabel('name'))
                    ->placeholder($this->getTraductionFormPlaceholder('name'))
                    ->required()
                    ->columnSpan([
                        'md' => '4',
                    ]),
                Forms\Components\Select::make('table_name')
                    ->label($this->getTraductionFormLabel('table_name'))
                    ->placeholder($this->getTraductionFormPlaceholder('table_name'))
                    ->required()
                    ->options(function () use ($record) {
                        if ($record->connectionTo) {
                            return $this->getTables($record->connectionTo);
                        }

                        return [];
                    })
                    ->columnSpan([
                        'md' => '4',
                    ]),
                Forms\Components\Select::make('column_from')
                    ->label($this->getTraductionFormLabel('column_from'))
                    ->placeholder($this->getTraductionFormPlaceholder('column_from'))
                    ->required()
                    ->options(function () use ($record) {
                        if ($connectionFrom = $record->connectionFrom) {
                            return $this->getColumns($connectionFrom, $record->table_to, 'to');
                        }

                        return [];
                    })
                    ->columnSpan([
                        'md' => '4',
                    ]),
                Forms\Components\Select::make('column_to')
                    ->label($this->getTraductionFormLabel('column_to'))
                    ->placeholder($this->getTraductionFormPlaceholder('column_to'))
                    ->required()
                    ->options(function () use ($record) {
                        if ($connectionTo = $record->connectionTo) {
                            return $this->getColumns($connectionTo, $record->table_to, 'to');
                        }

                        return [];
                    })
                    ->columnSpan([
                        'md' => '4',
                    ]),
                Forms\Components\Select::make('column_value')
                    ->label($this->getTraductionFormLabel('column_value'))
                    ->placeholder($this->getTraductionFormPlaceholder('column_value'))
                    ->required()
                    ->options(function () use ($record) {
                        if ($connectionTo = $record->connectionTo) {
                            return $this->getColumns($connectionTo, $record->table_to, 'to');
                        }

                        return [];
                    })
                    ->columnSpan([
                        'md' => '4',
                    ]),
                Forms\Components\Textarea::make('description')
                    ->label($this->getTraductionFormLabel('description'))
                    ->placeholder($this->getTraductionFormPlaceholder('description'))
                    ->columnSpanFull(),

            ])->columns(12),
        ];
    }

    protected function getColumnsSchemaForm($record, $table_from, $table_to)
    {

        return [
            Forms\Components\Select::make('column_from')
                ->label($this->getTraductionFormLabel('column_from'))
                ->placeholder($this->getTraductionFormPlaceholder('column_from'))
                ->required()
                ->options(function () use ($record, $table_from) {
                    if ($record->connectionFrom) {
                        return $this->getColumns($record->connectionFrom, $table_from, 'from');
                    }

                    return [];
                })
                ->columnSpan([
                    'md' => '2',
                ]),
            Forms\Components\Select::make('column_to')
                ->label($this->getTraductionFormLabel('column_to'))
                ->placeholder($this->getTraductionFormPlaceholder('column_to'))
                ->required()
                ->options(function () use ($record, $table_to) {
                    if ($record->connectionTo) {
                        return $this->getColumns($record->connectionTo, $table_to, 'to');
                    }

                    return [];
                })
                ->columnSpan([
                    'md' => '2',
                ]),
            Forms\Components\Select::make('relation_id')
                ->label($this->getTraductionFormLabel('relation_id'))
                ->placeholder($this->getTraductionFormPlaceholder('relation_id'))
                ->relationship(
                    name: 'relation',
                    titleAttribute: 'name'
                )
                ->manageOptionForm($this->getFormSchemaRelationOptions($record))
                ->columnSpan([
                    'md' => '3',
                ]),
            Forms\Components\TextInput::make('default_value')
                ->label($this->getTraductionFormLabel('default_value'))
                ->placeholder($this->getTraductionFormPlaceholder('default_value'))
                ->columnSpan([
                    'md' => '3',
                ]),
            Forms\Components\Select::make('type')
                ->label($this->getTraductionFormLabel('type'))
                ->placeholder($this->getTraductionFormPlaceholder('type'))
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
                ])
                ->default('string')
                ->columnSpan([
                    'md' => '2',
                ]),
        ];
    }

    protected function getFiltersSchemaForm($connection, $table)
    {

        return [
            Forms\Components\TextInput::make('name')
                ->label($this->getTraductionFormLabel('name'))
                ->placeholder($this->getTraductionFormPlaceholder('name'))
                ->required()
                ->columnSpan([
                    'md' => '3',
                ]),
            Forms\Components\Select::make('column')
                ->label($this->getTraductionFormLabel('column'))
                ->placeholder($this->getTraductionFormPlaceholder('column'))
                ->required()
                ->options(function () use ($connection, $table) {
                    if ($connection) {
                        return $this->getColumns($connection, $table, 'to');
                    }

                    return [];
                })
                ->columnSpan([
                    'md' => '2',
                ]),
            Forms\Components\Select::make('operator')
                ->label($this->getTraductionFormLabel('operator'))
                ->placeholder($this->getTraductionFormPlaceholder('operator'))
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
            Forms\Components\TextInput::make('value')
                ->label($this->getTraductionFormLabel('value'))
                ->placeholder($this->getTraductionFormPlaceholder('value'))
                ->columnSpan([
                    'md' => '3',
                ]),
            Forms\Components\Select::make('type')
                ->label($this->getTraductionFormLabel('type'))
                ->placeholder($this->getTraductionFormPlaceholder('type'))
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
}
