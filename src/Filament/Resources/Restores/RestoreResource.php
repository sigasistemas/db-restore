<?php

/**
* Created by Claudio Campos.
* User: callcocam@gmail.com, contato@sigasmart.com.br
* https://www.sigasmart.com.br
*/
namespace Callcocam\DbRestore\Filament\Resources\Restores;

use Callcocam\DbRestore\Filament\Resources\Restores\RestoreResource\Pages; 
use Callcocam\DbRestore\Filament\Resources\Restores\RestoreResource\RelationManagers\ChildrensRelationManager;
use Callcocam\DbRestore\Filament\Resources\Restores\RestoreResource\RelationManagers\SharedsRelationManager;
use Callcocam\DbRestore\Models\Restore;
use Callcocam\DbRestore\Traits\HasDatesFormForTableColums;
use Callcocam\DbRestore\Traits\HasStatusColumn; 
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RestoreResource extends Resource
{
    use HasStatusColumn, HasDatesFormForTableColums;

    // protected static ?string $model = Restore::class;

    protected static ?string $navigationIcon = 'fas-window-restore';

    protected static ?int $navigationSort = 1;

    
    public static function getModel(): string
    {
        return config('db-restore.models.restore', Restore::class);
    }
    
    public static function getNavigationGroup(): ?string
    {
        return 'Restores';
    }

    public static function getPluralModelLabel(): string
    {
        return __('db-restore::db-restore.restore.plural');
    }

    public static function getModelLabel(): string
    {
        return __('db-restore::db-restore.restore.singular');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('db-restore::db-restore.restore.form.name.label'))
                    ->sortable()
                    ->searchable(),
                static::getStatusTableIconColumn(),
                ...static::getFieldDatesFormForTable()

            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ReplicateAction::make(),
                Tables\Actions\ViewAction::make(),
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
            ChildrensRelationManager::class,
            SharedsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRestores::route('/'),
            'create' => Pages\CreateRestore::route('/create'),
            'view' => Pages\ViewRestore::route('/{record}'),
            'edit' => Pages\EditRestore::route('/{record}/edit'),
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
