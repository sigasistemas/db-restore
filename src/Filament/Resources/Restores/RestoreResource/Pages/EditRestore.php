<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Filament\Resources\Restores\RestoreResource\Pages;

use Callcocam\DbRestore\Filament\Resources\Restores\RestoreResource;
use Callcocam\DbRestore\Forms\Components\ConnectionField;
use Callcocam\DbRestore\Forms\Components\ConnectionFromField;
use Callcocam\DbRestore\Forms\Components\ConnectionToField;
use Callcocam\DbRestore\Forms\Components\SelectTableField;
use Callcocam\DbRestore\Forms\Components\SelectTableFromField;
use Callcocam\DbRestore\Forms\Components\SelectTableToField;
use Callcocam\DbRestore\Forms\Components\TextInputField;
use Callcocam\DbRestore\Models\Restore;
use Callcocam\DbRestore\Traits\HasDatesFormForTableColums;
use Callcocam\DbRestore\Traits\HasStatusColumn;
use Callcocam\DbRestore\Traits\HasTraduction;
use Callcocam\DbRestore\Traits\WithColumns;
use Callcocam\DbRestore\Traits\WithFormSchemas;
use Callcocam\DbRestore\Traits\WithSections;
use Callcocam\DbRestore\Traits\WithTables;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;

class EditRestore extends EditRecord
{
    use HasStatusColumn, HasDatesFormForTableColums, WithFormSchemas, WithColumns, HasTraduction, WithTables, WithSections;

    protected static string $resource = RestoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                ConnectionFromField::make('connection_from_id')
                    ->required() 
                    ->columnSpan([
                        'md' => '4'
                    ]),
                ConnectionToField::make('connection_to_id')
                    ->required() 
                    ->columnSpan([
                        'md' => '4'
                    ]) ,
                TextInputField::make('name')
                    ->required()
                    ->columnSpan([
                        'md' => '4'
                    ])
                    ->maxLength(255),
                SelectTableFromField::makeTable('table_from', $this->record)
                    ->required()
                    ->columnSpan([
                        'md' => '4'
                    ]),
                SelectTableToField::makeTable('table_to', $this->record)
                    ->required()
                    ->columnSpan([
                        'md' => '4'
                    ]),
                SelectTableField::make('restore_model_id')
                    ->relationship(
                        name: 'restoreModel',
                        titleAttribute: 'name'
                    )
                    ->columnSpan([
                        'md' => '4'
                    ]),
                $this->getSectionColumnsSchema($this->record, function ($record) {
                    return $this->getColumnsSchemaForm($record );
                })->visible(fn (Restore $record) => $record->table_to && $record->table_from),
                $this->getSectionFiltersSchema($this->record)->visible(fn (Restore $record) => $record->table_to),
                $this->getSectionOrderingsSchema($this->record)->visible(fn (Restore $record) => $record->table_from),
                static::getStatusFormRadioField(),
                Forms\Components\Textarea::make('description')
                    ->label($this->getTraductionFormLabel('description'))
                    ->placeholder($this->getTraductionFormPlaceholder('description'))
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ])->columns(12);
    }
}
