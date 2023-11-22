<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Filament\Resources\Restores\RestoreResource\RelationManagers;

use Callcocam\DbRestore\Forms\Components\SelectColumnField;
use Callcocam\DbRestore\Forms\Components\SelectTableFromField;
use Callcocam\DbRestore\Forms\Components\SelectTableToField;
use Callcocam\DbRestore\Forms\Components\TextInputField;
use Callcocam\DbRestore\Models\Children;
use Callcocam\DbRestore\Traits\HasTraduction;
use Callcocam\DbRestore\Traits\WithColumns;
use Callcocam\DbRestore\Traits\WithFormSchemas;
use Callcocam\DbRestore\Traits\WithSections;
use Callcocam\DbRestore\Traits\WithTables;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ChildrensRelationManager extends RelationManager
{
    use WithColumns, WithFormSchemas, HasTraduction, WithTables, WithSections;

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
            TextInputField::make('name')
                ->required()
                ->columnSpan([
                    'md' => '8'
                ]),
            // 'one-to-one', 'one-to-many', 'one-to-many-inverse','polymorphic'
            SelectColumnField::make('relation_type')
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
            SelectTableFromField::makeTable('table_from', $record)
                ->required()
                ->live()
                ->columnSpan([
                    'md' => '3'
                ]),
            SelectColumnField::makeFromOptions('join_from_column', $ownerRecord, 'table_from')
                ->required()
                ->columnSpan([
                    'md' => '3'
                ]),
            SelectTableToField::makeTable('table_to', $record)
                ->required()
                ->live()
                ->columnSpan([
                    'md' => '3'
                ]),
            SelectColumnField::makeToOptions('join_to_column', $ownerRecord, 'table_to')
                ->required()
                ->columnSpan([
                    'md' => '3'
                ]),

            Forms\Components\Section::make()
                ->visible(fn (Children | null $record = null) => $record)
                ->schema(function (Children | null $record = null) use ($ownerRecord) {
                    return [
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
                                        ->schema(function () use ($ownerRecord) {
                                            $cloneRecord = clone $ownerRecord;
                                            $cloneRecord->connectionTo = $ownerRecord->connectionTo;
                                            $cloneRecord->connectionFrom = $ownerRecord->connectionFrom;
                                            return $this->getColumnsSchemaForm($cloneRecord);
                                        })
                                        ->columns(12)
                                        ->columnSpanFull()
                                ];
                            }),
                        $this->getSectionFiltersSchema($ownerRecord, $ownerRecord->connectionFrom, $record->table_from)->visible($ownerRecord->table_from),
                        $this->getSectionOrderingsSchema($ownerRecord)->visible($ownerRecord->table_from),
                    ];
                }),

        ];
    }
}
