<?php
/**
* Created by Claudio Campos.
* User: callcocam@gmail.com, contato@sigasmart.com.br
* https://www.sigasmart.com.br
*/
namespace Callcocam\DbRestore\Filament\Resources\Restores\RestoreResource\Pages;

use Callcocam\DbRestore\Filament\Resources\Restores\RestoreResource; 
use Callcocam\DbRestore\Models\Restore;
use Callcocam\DbRestore\Traits\HasDatesFormForTableColums;
use Callcocam\DbRestore\Traits\HasStatusColumn;
use Callcocam\DbRestore\Traits\HasTraduction;
use Callcocam\DbRestore\Traits\WithColumns;
use Callcocam\DbRestore\Traits\WithFormSchemas;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Forms; 
use Filament\Resources\Pages\EditRecord; 

class EditRestore extends EditRecord
{
    use HasStatusColumn, HasDatesFormForTableColums, WithFormSchemas, WithColumns, HasTraduction;

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
                Forms\Components\Select::make('connection_from_id')
                    ->label($this->getTraductionFormLabel('connection_from_id'))
                    ->placeholder($this->getTraductionFormPlaceholder('connection_from_id'))
                    ->required()
                    ->relationship(
                        name: 'connectionFrom',
                        titleAttribute: 'name',
                    )
                    ->columnSpan([
                        'md' => '4'
                    ])
                    ->manageOptionForm($this->getFormSchemaConnectionOptions()),
                Forms\Components\Select::make('connection_to_id')
                    ->label($this->getTraductionFormLabel('connection_to_id'))
                    ->placeholder($this->getTraductionFormPlaceholder('connection_to_id'))
                    ->required()
                    ->manageOptionForm($this->getFormSchemaConnectionOptions())
                    ->columnSpan([
                        'md' => '4'
                    ])
                    ->relationship(
                        name: 'connectionTo',
                        titleAttribute: 'name',
                    ),
                Forms\Components\TextInput::make('name')
                    ->label($this->getTraductionFormLabel('name'))
                    ->placeholder($this->getTraductionFormPlaceholder('name'))
                    ->required()
                    ->columnSpan([
                        'md' => '4'
                    ])
                    ->maxLength(255),
                Forms\Components\Select::make('table_from')
                    ->label($this->getTraductionFormLabel('table_from'))
                    ->placeholder($this->getTraductionFormPlaceholder('table_from'))
                    ->required()
                    ->columnSpan([
                        'md' => '4'
                    ])
                    ->options(function (Restore $record) {
                        if ($record->connectionFrom)
                            return $this->getTables($record->connectionFrom);
                        return [];
                    }),
                Forms\Components\Select::make('table_to')
                    ->label($this->getTraductionFormLabel('table_to'))
                    ->placeholder($this->getTraductionFormPlaceholder('table_to'))
                    ->required()
                    ->columnSpan([
                        'md' => '4'
                    ])
                    ->options(function (Restore $record) {
                        if ($record->connectionTo)
                            return $this->getTables($record->connectionTo);
                        return [];
                    }),
                Forms\Components\Select::make('restore_model_id')
                    ->label($this->getTraductionFormLabel('restore_model_id'))
                    ->placeholder($this->getTraductionFormPlaceholder('restore_model_id'))
                    ->relationship(
                        name: 'restoreModel',
                        titleAttribute: 'name'
                    )
                    ->columnSpan([
                        'md' => '4'
                    ]),
                Forms\Components\Section::make($this->getTraduction('columns', 'restore', 'form',  'label'))
                    ->description($this->getTraduction('columns', 'restore', 'form',  'description'))
                    ->collapsed()
                    ->schema(function (Restore $record) {
                        return  [
                            Forms\Components\Repeater::make('columns')
                                ->relationship('columns')
                                ->hiddenLabel()
                                ->schema(function () use ($record) {
                                    return $this->getColumnsSchemaForm($record, $record->table_from, $record->table_to);
                                })
                                ->columns(12)
                                ->columnSpanFull()
                        ];
                    }),
                Forms\Components\Section::make($this->getTraduction('filters', 'restore', 'form',  'label'))
                    ->description($this->getTraduction('filters', 'restore', 'form',  'description'))
                    ->collapsed()
                    ->schema(function (Restore $record) {
                        return  [
                            Forms\Components\Repeater::make('filters')
                                ->relationship('filters')
                                ->hiddenLabel()
                                ->schema(function () use ($record) {
                                    return $this->getFiltersSchemaForm($record->connectionTo, $record->table_to);
                                })
                                ->columns(12)
                                ->columnSpanFull()
                        ];
                    }),
                static::getStatusFormRadioField(),
                Forms\Components\Textarea::make('description')
                    ->label($this->getTraductionFormLabel('description'))
                    ->placeholder($this->getTraductionFormPlaceholder('description'))
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ])->columns(12);
    }
}
