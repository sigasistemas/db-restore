<?php

/**
* Created by Claudio Campos.
* User: callcocam@gmail.com, contato@sigasmart.com.br
* https://www.sigasmart.com.br
*/
namespace Callcocam\DbRestore\Filament\Resources\Restores;

use Callcocam\DbRestore\Filament\Resources\Restores\ImportResource\Pages;
use Callcocam\DbRestore\Filament\Resources\Restores\ImportResource\RelationManagers;
use Callcocam\DbRestore\Models\Import;
use Callcocam\DbRestore\Traits\HasDatesFormForTableColums;
use Callcocam\DbRestore\Traits\HasStatusColumn;
use Callcocam\DbRestore\Traits\HasTraduction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ImportResource extends Resource
{
    use HasTraduction, HasStatusColumn, HasDatesFormForTableColums;

    // protected static ?string $model = Import::class;

    protected static ?string $navigationIcon = 'fas-file-import'; 
    

    protected static ?int $navigationSort = 2;

    
    public static function getModel(): string
    {
        return config('db-restore.models.import', Import::class);
    }
    
    public static function getNavigationGroup(): ?string
    {
        return 'Restores';
    }

    public static function getPluralModelLabel(): string
    {
        return __('db-restore::db-restore.import.plural');
    }

    public static function getModelLabel(): string
    {
        return __('db-restore::db-restore.import.singular');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(static::getTraductionTableLabel('name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('connection.name')
                    ->label(static::getTraductionTableLabel('connection_id'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('table_name')
                    ->label(static::getTraductionTableLabel('table_name'))
                    ->searchable(),
                static::getStatusTableIconColumn(),
                ...static::getFieldDatesFormForTable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListImports::route('/'),
            'create' => Pages\CreateImport::route('/create'),
            'edit' => Pages\EditImport::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
