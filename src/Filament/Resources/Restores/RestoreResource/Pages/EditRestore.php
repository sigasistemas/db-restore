<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Filament\Resources\Restores\RestoreResource\Pages;

use Callcocam\DbRestore\Filament\Resources\Restores\RestoreResource;
use Callcocam\DbRestore\Forms\Components\ConnectionFromField;
use Callcocam\DbRestore\Forms\Components\ConnectionToField;
use Callcocam\DbRestore\Forms\Components\RestoreModelField;
use Callcocam\DbRestore\Forms\Components\SelectColumnField;
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
        $record = $this->record;

        if (!$record->columns->count()) {
            $this->getColumnOptions($record, $record->connectionFrom, $record->connectionTo);
        }

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
                    ]),
                TextInputField::make('name')
                    ->required()
                    ->columnSpan([
                        'md' => '4'
                    ])
                    ->maxLength(255),
                SelectTableFromField::makeTable('table_from', $record)
                    ->required()
                    ->columnSpan([
                        'md' => '3'
                    ]),
                SelectTableToField::makeTable('table_to', $record)
                    ->required()
                    ->columnSpan([
                        'md' => '3'
                    ]),
                RestoreModelField::makeColumn('restore_model_id')
                    ->columnSpan([
                        'md' => '3'
                    ]),
                SelectColumnField::make('type')
                    ->options([
                        'duplicar' => 'Duplicar',
                        'excluir' => 'Excluir',
                        'ignorar' => 'Ignorar',
                    ])
                    ->required()
                    ->columnSpan([
                        'md' => '3'
                    ]),
                $this->getSectionColumnsSchema($record, function ($record) {
                    return $this->getColumnsSchemaForm($record);
                })->visible(fn (Restore $record) => $record->table_to && $record->table_from),

                $this->getSectionPivotsSchema($record)->visible(fn (Restore $record) => $record->table_to && $record->table_from),
                
                $this->getSectionFiltersSchema(
                    record: $record, //pode ser tanto um model connection, restore, children, import, export ou shared
                    connection: $record->connectionFrom, // passar porque vamos a coxao de origem
                    tableTo: $record->table_from, // passar porque vamos a tabela de origem
                    connectionTo: $record->connectionTo //passar porque a conexao padrão é a de origem, para o campo name teremos que passar a conexao de destino
                )->visible(fn (Restore $record) => $record->table_to),
                $this->getSectionOrderingsSchema($record)
                    ->visible(fn (Restore $record) => $record->table_from),
                static::getStatusFormRadioField(),

                Forms\Components\Textarea::make('description')
                    ->label($this->getTraductionFormLabel('description'))
                    ->placeholder($this->getTraductionFormPlaceholder('description'))
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ])->columns(12);
    }
}
