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
use Callcocam\DbRestore\Forms\Components\TextareaField;
use Callcocam\DbRestore\Forms\Components\TextInputField;
use Callcocam\DbRestore\Traits\HasDatesFormForTableColums;
use Callcocam\DbRestore\Traits\HasStatusColumn;
use Callcocam\DbRestore\Traits\HasTraduction;
use Callcocam\DbRestore\Traits\WithFormSchemas; 
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Resources\Pages\CreateRecord; 

class CreateRestore extends CreateRecord
{
    use HasStatusColumn, HasDatesFormForTableColums, WithFormSchemas, HasTraduction;

    protected static string $resource = RestoreResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                ConnectionFromField::make('connection_from_id')
                    ->required() ,
                ConnectionToField::make('connection_to_id')
                    ->required(),
                TextInputField::make('name')
                    ->required()
                    ->maxLength(255),
                static::getStatusFormRadioField(),
                TextareaField::makeText('description'),
            ])->columns(3);
    }
}
