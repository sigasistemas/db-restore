<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Filament\Resources\Restores\ModelResource\Pages;

use Callcocam\DbRestore\Filament\Resources\Restores\ModelResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageModels extends ManageRecords
{
    protected static string $resource = ModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
