<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Filament\Resources\Restores\ImportResource\Pages;

use Callcocam\DbRestore\Filament\Resources\Restores\ImportResource;
use Callcocam\DbRestore\Forms\Components\ConnectionField;
use Callcocam\DbRestore\Forms\Components\ConnectionToField;
use Callcocam\DbRestore\Forms\Components\SelectColumnField;
use Callcocam\DbRestore\Forms\Components\SelectColumnToField;
use Callcocam\DbRestore\Forms\Components\SelectTableField;
use Callcocam\DbRestore\Forms\Components\SelectTableFromField;
use Callcocam\DbRestore\Forms\Components\TextInputField;
use Callcocam\DbRestore\Helpers\RestoreHelper;
use Callcocam\DbRestore\Models\Connection;
use Callcocam\DbRestore\Models\Import;
use Callcocam\DbRestore\Models\Sample;
use Callcocam\DbRestore\Traits\WithTables;
use Filament\Actions;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Mpdf\Tag\Samp;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ListImports extends ListRecords
{
    use WithTables;

    protected static string $resource = ImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
