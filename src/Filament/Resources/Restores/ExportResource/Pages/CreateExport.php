<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Filament\Resources\Restores\ExportResource\Pages;

use  Callcocam\DbRestore\Filament\Resources\Restores\ExportResource;
use Callcocam\DbRestore\Forms\Components\ConnectionField;
use Callcocam\DbRestore\Forms\Components\TextareaField;
use Callcocam\DbRestore\Forms\Components\TextInputField;
use Illuminate\Database\Eloquent\Builder;
use Callcocam\DbRestore\Traits\HasStatusColumn;
use Callcocam\DbRestore\Traits\HasTraduction;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Resources\Pages\CreateRecord;

class CreateExport extends CreateRecord
{
    use HasTraduction, HasStatusColumn;

    protected static string $resource = ExportResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInputField::make('name')
                    ->columnSpan([
                        'md' => 6
                    ])
                    ->required(),
                ConnectionField::make('connection_id')
                    ->relationship(name: 'connectionTo', titleAttribute: 'name' )
                    ->columnSpan([
                        'md' => 6
                    ])
                    ->required(),
                static::getStatusFormRadioField(),
                TextareaField::makeText('description')
            ])->columns(12);
    }
}
