<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Filament\Resources\Restores\RestoreResource\RelationManagers;

use Callcocam\DbRestore\Models\Children;
use Callcocam\DbRestore\Traits\HasTraduction;
use Callcocam\DbRestore\Traits\WithColumns;
use Callcocam\DbRestore\Traits\WithFormSchemas;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ChildrensRelationManager extends RelationManager
{
    use WithColumns, WithFormSchemas, HasTraduction;

    protected static string $relationship = 'childrens';

    public function form(Form $form): Form
    {
        return $form
            ->schema(function () {
                return [
                    Group::make($this->getChildrensSchemaForm($this->ownerRecord))->columns(12)->columnSpanFull(),
                ];
            });
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function getChildrensSchemaForm($record)
    {

        $ownerRecord = $record;

        return [
            Forms\Components\TextInput::make('name')
                ->label($this->getTraductionFormLabel('name'))
                ->placeholder($this->getTraductionFormPlaceholder('name'))
                ->required()
                ->columnSpan([
                    'md' => '8'
                ]),
            // 'one-to-one', 'one-to-many', 'one-to-many-inverse','polymorphic'
            Forms\Components\Select::make('relation_type')
                ->label($this->getTraductionFormLabel('type'))
                ->placeholder($this->getTraductionFormPlaceholder('type'))
                ->required()
                ->options([
                    'one-to-one' => $this->getTraduction('one-to-one', 'restore', 'form', 'label'),
                    'one-to-many' => $this->getTraduction('one-to-many', 'restore', 'form', 'label'),
                    'one-to-many-inverse' => $this->getTraduction('one-to-many-inverse', 'restore', 'form', 'label'),
                    'polymorphic' => $this->getTraduction('polymorphic', 'restore', 'form', 'label'),
                ])
                ->columnSpan([
                    'md' => '4'
                ]),
            Forms\Components\Select::make('table_from')
                ->label($this->getTraductionFormLabel('table_from'))
                ->placeholder($this->getTraductionFormPlaceholder('table_from'))
                ->required()
                ->live()
                ->options(function () use ($ownerRecord) {
                    if ($ownerRecord->connectionFrom)
                        return $this->getTables($ownerRecord->connectionFrom);
                    return [];
                })
                ->columnSpan([
                    'md' => '3'
                ]),
            Forms\Components\Select::make('join_from_column')
                ->label($this->getTraductionFormLabel('join_from_column'))
                ->placeholder($this->getTraductionFormPlaceholder('join_from_column'))
                ->required()
                ->options(function (Get $get) use ($ownerRecord) {
                    if ($ownerRecord->connectionFrom)
                        return $this->getColumns($ownerRecord->connectionFrom, $get('table_from'));
                    return [];
                })
                ->columnSpan([
                    'md' => '3'
                ]),
            Forms\Components\Select::make('table_to')
                ->label($this->getTraductionFormLabel('table_to'))
                ->placeholder($this->getTraductionFormPlaceholder('table_to'))
                ->required()
                ->live()
                ->options(function () use ($ownerRecord) {
                    if ($ownerRecord->connectionTo)
                        return $this->getTables($ownerRecord->connectionTo);
                    return [];
                })
                ->columnSpan([
                    'md' => '3'
                ]),
            Forms\Components\Select::make('join_to_column')
                ->label($this->getTraductionFormLabel('join_to_column'))
                ->placeholder($this->getTraductionFormPlaceholder('join_to_column'))
                ->required()
                ->options(function (Get $get) use ($ownerRecord) {
                    if ($ownerRecord->connectionTo)
                        return $this->getColumns($ownerRecord->connectionTo, $get('table_to'));
                    return [];
                })
                ->columnSpan([
                    'md' => '3'
                ]),
            Forms\Components\Section::make()
                ->visible(fn (Children | null $record = null) => $record)
                ->schema([
                    Forms\Components\Section::make($this->getTraduction('columns', 'restore', 'form',  'label'))
                        ->description($this->getTraduction('columns', 'restore', 'form', 'description'))
                        ->visible($ownerRecord->table_from && $ownerRecord->table_to)
                        ->collapsed()
                        ->schema(function (Children | null $record = null) use ($ownerRecord) { 
                            if (!$record) {
                                return [];
                            }
                            return  [
                                Forms\Components\Repeater::make('columns')
                                    ->relationship('columns')
                                    ->hiddenLabel()
                                    ->schema(function () use ($ownerRecord, $record)  {
                                        return $this->getColumnsSchemaForm($ownerRecord, $record->table_from, $record->table_to);
                                    })
                                    ->columns(12)
                                    ->columnSpanFull()
                            ];
                        }),
                    Forms\Components\Section::make($this->getTraduction('filters', 'restore', 'form',  'label'))
                        ->description($this->getTraduction('filters', 'restore', 'form',  'description'))
                        ->visible($ownerRecord->table_from)
                        ->collapsed()
                        ->schema(function (Children | null $record = null) use ($ownerRecord) {
                            if (!$record) {
                                return [];
                            }
                            return  [
                                Forms\Components\Repeater::make('filters')
                                    ->relationship('filters')
                                    ->hiddenLabel()
                                    ->schema(function () use ($ownerRecord) {
                                        return $this->getFiltersSchemaForm($ownerRecord->connectionFrom, $ownerRecord->table_from);
                                    })
                                    ->columns(12)
                                    ->columnSpanFull()
                            ];
                        }),

                    Forms\Components\Section::make($this->getTraduction('orderings', 'restore', 'form',  'label'))
                        ->description($this->getTraduction('orderings', 'restore', 'form',  'description'))
                        ->visible($ownerRecord->table_from)
                        ->collapsed()
                        ->schema(function (Children | null $record = null) use ($ownerRecord) {
                            if (!$record) {
                                return [];
                            }
                            return  [
                                Forms\Components\Repeater::make('orderings')
                                    ->relationship('orderings')
                                    ->hiddenLabel()
                                    ->schema(function () use ($ownerRecord) {
                                        return $this->getOrderingsSchemaForm($ownerRecord->connectionFrom, $ownerRecord->table_from);
                                    })
                                    ->columns(12)
                                    ->columnSpanFull()
                            ];
                        })
                ]),

        ];
    }
}
