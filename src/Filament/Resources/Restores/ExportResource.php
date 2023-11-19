<?php

/**
* Created by Claudio Campos.
* User: callcocam@gmail.com, contato@sigasmart.com.br
* https://www.sigasmart.com.br
*/
namespace Callcocam\DbRestore\Filament\Resources\Restores;

use Callcocam\DbRestore\Filament\Resources\Restores\ExportResource\Pages;
use Callcocam\DbRestore\Filament\Resources\Restores\ExportResource\RelationManagers;
use Callcocam\DbRestore\Models\Export;
use Callcocam\DbRestore\Traits\HasDatesFormForTableColums;
use Callcocam\DbRestore\Traits\HasStatusColumn;
use Callcocam\DbRestore\Traits\HasTraduction; 
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExportResource extends Resource
{  
    use HasTraduction, HasStatusColumn, HasDatesFormForTableColums;

    // protected static ?string $model = Export::class;

    protected static ?string $navigationIcon = 'fas-file-export';

    protected static ?int $navigationSort = 3;

    
    public static function getModel(): string
    {
        return config('db-restore.models.export', Export::class);
    }
    
    public static function getNavigationGroup(): ?string
    {
        return 'Restores';
    }

    public static function getPluralModelLabel(): string
    {
        return __('db-restore::db-restore.export.plural');
    }

    public static function getModelLabel(): string
    {
        return __('db-restore::db-restore.export.singular');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([  
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('table_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('disk')
                    ->searchable(),
                Tables\Columns\TextColumn::make('extension')
                    ->searchable(), 
                    static::getStatusTableIconColumn(),
                    ...static::getFieldDatesFormForTable()
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
            'index' => Pages\ListExports::route('/'),
            'create' => Pages\CreateExport::route('/create'),
            'edit' => Pages\EditExport::route('/{record}/edit'),
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
