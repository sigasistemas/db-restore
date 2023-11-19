<?php

/**
* Created by Claudio Campos.
* User: callcocam@gmail.com, contato@sigasmart.com.br
* https://www.sigasmart.com.br
*/
namespace Callcocam\DbRestore\Filament\Resources\Restores\ExportResource\Pages;

use  Callcocam\DbRestore\Filament\Resources\Restores\ExportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExports extends ListRecords
{
    protected static string $resource = ExportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
