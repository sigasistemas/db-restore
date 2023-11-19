<?php

/**
* Created by Claudio Campos.
* User: callcocam@gmail.com, contato@sigasmart.com.br
* https://www.sigasmart.com.br
*/
namespace Callcocam\DbRestore\Filament\Resources\Restores\ImportResource\Pages;

use Callcocam\DbRestore\Filament\Resources\Restores\ImportResource;
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
               Forms\Components\TextInput::make('name')
               ->label($this->getTraductionFormLabel('name'))
                ->placeholder($this->getTraductionFormPlaceholder('name'))
                ->columnSpan([
                    'md' => 6
                ])
               ->required(),
                Forms\Components\Select::make('connection_id')
                ->label($this->getTraductionFormLabel('connection_id'))
                ->placeholder($this->getTraductionFormPlaceholder('connection_id'))
                ->relationship('connection', 'name')
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
