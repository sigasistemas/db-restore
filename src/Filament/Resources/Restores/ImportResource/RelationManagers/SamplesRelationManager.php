<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Filament\Resources\Restores\ImportResource\RelationManagers;

use App\Models\Tenant;
use Callcocam\DbRestore\Forms\Components\SelectColumnField;
use Callcocam\DbRestore\Forms\Components\TextareaField;
use Callcocam\DbRestore\Forms\Components\TextInputField;
use Callcocam\DbRestore\Models\AbstractModelRestore;
use Callcocam\DbRestore\Traits\HasTraduction;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Cache;

class SamplesRelationManager extends RelationManager
{
    use HasTraduction;

    protected static string $relationship = 'samples';

    public function form(Form $form): Form
    {
        $ownerRecord = $this->ownerRecord;
        return $form
            ->schema([
                SelectColumnField::make('tenant_id', $ownerRecord)
                    ->required()
                    ->searchable()
                    ->columnSpanFull()
                    ->options(Tenant::query()->whereStatus('published')->pluck('name', 'id')->toArray()),
                TextInputField::make('name')
                    ->required()
                    ->columnSpan([
                        'md' => 8
                    ])
                    ->maxLength(255),
                SelectColumnField::make('extension', null)
                    ->required()
                    ->columnSpan([
                        'md' => 4
                    ])
                    ->options([
                        'csv' => 'csv',
                        'xls' => 'xls',
                        'xlsx' => 'xlsx',
                    ]),
                TextareaField::makeText('description')
                    ->columnSpanFull(),
                Repeater::make('columns')
                    ->label('Colunas')
                    ->statePath('columns')
                    ->schema(function () use ($ownerRecord) {
                        return [
                            SelectColumnField::make('column')
                                ->required()
                                ->searchable()
                                ->options(function () use ($ownerRecord) {
                                    $columns = Cache::rememberForever($ownerRecord->id, function () {
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
                                }),
                            SelectColumnField::makeToOptions('name',  $ownerRecord, $ownerRecord)
                                ->required(),
                            TextInputField::makeText('description')
                                ->required(),
                        ];
                    })->columns(3)->columnSpanFull()
            ])->columns(12);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(static::getTraductionTableLabel('name')),
                Tables\Columns\TextColumn::make('file')
                    ->label(static::getTraductionTableLabel('file')),
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
}
