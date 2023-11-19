<?php

/**
* Created by Claudio Campos.
* User: callcocam@gmail.com, contato@sigasmart.com.br
* https://www.sigasmart.com.br
*/
namespace Callcocam\DbRestore\Filament\Resources\Restores;

use Callcocam\DbRestore\Filament\Resources\Restores\ModelResource\Pages;
use Callcocam\DbRestore\Filament\Resources\Restores\ModelResource\RelationManagers;
use Callcocam\DbRestore\Models\Model;
use Callcocam\DbRestore\Helpers\RestoreHelper;
use Callcocam\DbRestore\Traits\HasDatesFormForTableColums;
use Callcocam\DbRestore\Traits\HasStatusColumn;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ModelResource extends Resource
{
    use HasStatusColumn, HasDatesFormForTableColums;

    // protected static ?string $model = Model::class;

    protected static ?string $navigationIcon = 'fas-file-code';
    

    protected static ?int $navigationSort = 4;

    public static function getModel(): string
    {
        return config('db-restore.models.model', Model::class);
    }
    
    public static function getNavigationGroup(): ?string
    {
        return 'Restores';
    }

    public static function getPluralModelLabel(): string
    {
        return __('db-restore::db-restore.model.plural');
    }

    public static function getModelLabel(): string
    {
        return __('db-restore::db-restore.model.singular');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('name')
                    ->label(__('db-restore::db-restore.model.form.name.label'))
                    ->placeholder(__('db-restore::db-restore.model.form.name.placeholder'))
                    ->required()
                    ->columnSpanFull()
                    ->options(RestoreHelper::getModelsOptions()),
                static::getStatusFormRadioField(),
                Forms\Components\Textarea::make('description')
                    ->label(__('db-restore::db-restore.model.form.description.label'))
                    ->placeholder(__('db-restore::db-restore.model.form.description.placeholder'))
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table 
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('db-restore::db-restore.model.table.name.label'))
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageModels::route('/'),
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
