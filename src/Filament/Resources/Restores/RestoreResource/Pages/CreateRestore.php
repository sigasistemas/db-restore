<?php
/**
* Created by Claudio Campos.
* User: callcocam@gmail.com, contato@sigasmart.com.br
* https://www.sigasmart.com.br
*/
namespace Callcocam\DbRestore\Filament\Resources\Restores\RestoreResource\Pages;

use Callcocam\DbRestore\Filament\Resources\Restores\RestoreResource;
use Callcocam\DbRestore\Traits\HasDatesFormForTableColums;
use Callcocam\DbRestore\Traits\HasStatusColumn;
use Callcocam\DbRestore\Traits\HasTraduction;
use Callcocam\DbRestore\Traits\WithFormSchemas;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Builder;

class CreateRestore extends CreateRecord
{
    use HasStatusColumn, HasDatesFormForTableColums, WithFormSchemas, HasTraduction;

    protected static string $resource = RestoreResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('connection_from_id')
                    ->label($this->getTraductionFormLabel('connection_from_id'))
                    ->placeholder($this->getTraductionFormPlaceholder('connection_from_id'))
                    ->required()
                    ->relationship(
                        name: 'connectionFrom',
                        titleAttribute: 'name',
                    )
                    ->manageOptionForm($this->getFormSchemaConnectionOptions()),
                Forms\Components\Select::make('connection_to_id')
                    ->label($this->getTraductionFormLabel('connection_to_id'))
                    ->placeholder($this->getTraductionFormPlaceholder('connection_to_id'))
                    ->required()
                    ->manageOptionForm($this->getFormSchemaConnectionOptions())

                    ->relationship(
                        name: 'connectionTo',
                        titleAttribute: 'name',
                    ),
                Forms\Components\TextInput::make('name')
                    ->label($this->getTraductionFormLabel('name'))
                    ->placeholder($this->getTraductionFormPlaceholder('name'))
                    ->required()
                    ->maxLength(255),
                static::getStatusFormRadioField(),
                Forms\Components\Textarea::make('description')
                    ->label($this->getTraductionFormLabel('description'))
                    ->placeholder($this->getTraductionFormPlaceholder('description'))
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ])->columns(3);
    }
}
