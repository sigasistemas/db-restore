<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Forms\Components;

use Callcocam\DbRestore\Traits\HasTraduction;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;

class ConnectionField extends Select
{
    use HasTraduction;

    public static function make(string $name): static
    {
        $static = app(static::class, ['name' => $name]);
        $static->configure()
            ->label($static->getTraductionFormLabel($name))
            ->placeholder($static->getTraductionFormPlaceholder($name))
            ->manageOptionForm($static->getFormSchemaConnectionOptions());

        return $static;
    }
    public function getFormSchemaConnectionOptions()
    {
        return [
            Group::make([
                TextInputField::makeText('name')
                    ->required()
                    ->columnSpan([
                        'md' => '4',
                    ])
                    ->maxLength(255),
                TextInputField::makeText('host')
                    ->required()
                    ->default('localhost')
                    ->columnSpan([
                        'md' => '3',
                    ])
                    ->maxLength(255),
                TextInputField::makeText('port')
                    ->default('3306')
                    ->required()
                    ->columnSpan([
                        'md' => '2',
                    ])
                    ->maxLength(255),
                TextInputField::makeText('database')
                    ->required()
                    ->columnSpan([
                        'md' => '3',
                    ])
                    ->maxLength(255),
                TextInputField::makeText('username')
                    ->required()
                    ->default('root')
                    ->columnSpan([
                        'md' => '3',
                    ])
                    ->maxLength(255),
                TextInputField::makeText('password')
                    ->columnSpan([
                        'md' => '3',
                    ])
                    ->maxLength(255),
                TextInputField::makeText('charset')
                    ->default('utf8mb4')
                    ->required()
                    ->columnSpan([
                        'md' => '2',
                    ])
                    ->maxLength(255),
                TextInputField::makeText('prefix')
                    ->columnSpan([
                        'md' => '2',
                    ])
                    ->maxLength(255),
                TextInputField::makeText('engine')
                    ->default('InnoDB')
                    ->required()
                    ->columnSpan([
                        'md' => '2',
                    ])
                    ->maxLength(255),
                TextInputField::makeText('collation')
                    ->default('utf8mb4_unicode_ci')
                    ->required()
                    ->columnSpan([
                        'md' => '4',
                    ])
                    ->maxLength(255),
                TextInputField::make('url')
                    ->label($this->getTraductionFormLabel('url'))
                    ->placeholder($this->getTraductionFormPlaceholder('url'))
                    ->columnSpan([
                        'md' => '8',
                    ])
                    ->maxLength(255),
                SelectTableField::make('driver')
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
                SelectTableField::make('type')
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
                SelectTableField::make('status')
                    ->required()
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ])
                    ->columnSpan([
                        'md' => '4',
                    ]),
                Textarea::make('description')
                    ->label($this->getTraductionFormLabel('description'))
                    ->placeholder($this->getTraductionFormPlaceholder('description'))
                    ->maxLength(65535)
                    ->columnSpanFull(),

            ])->columns(12),
        ];
    }
}
