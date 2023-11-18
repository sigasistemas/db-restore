<?php
/**
* Created by Claudio Campos.
* User: callcocam@gmail.com, contato@sigasmart.com.br
* https://www.sigasmart.com.br
*/
namespace Callcocam\DbRestore\Filament\Resources\Restores\RestoreResource\Pages;

use Callcocam\DbRestore\Filament\Resources\Restores\RestoreResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRestores extends ListRecords
{
    protected static string $resource = RestoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
