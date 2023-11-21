<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Filament\Resources\Restores\ImportResource\Pages;

use Callcocam\DbRestore\Filament\Resources\Restores\ImportResource;
use Callcocam\DbRestore\Forms\Components\ConnectionField;
use Callcocam\DbRestore\Forms\Components\SelectTableToField;
use Callcocam\DbRestore\Forms\Components\TextInputField;
use Callcocam\DbRestore\Traits\HasStatusColumn;
use Callcocam\DbRestore\Traits\HasTraduction;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Resources\Pages\CreateRecord;

class CreateImport extends CreateRecord
{
    use HasTraduction, HasStatusColumn;

    protected static string $resource = ImportResource::class;

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
                    ->relationship('connectionTo', 'name')
                    ->columnSpan([
                        'md' => 6
                    ])
                    ->required(),
                static::getStatusFormRadioField(),
                Forms\Components\Textarea::make('description')
                    ->label($this->getTraductionFormLabel('description'))
                    ->placeholder($this->getTraductionFormPlaceholder('description'))
                    ->columnSpanFull()
            ])->columns(12);
    }
}
