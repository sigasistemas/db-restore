<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Filament\Resources\Restores\ExportResource\Pages;

use  Callcocam\DbRestore\Filament\Resources\Restores\ExportResource;
use Callcocam\DbRestore\Forms\Components\ConnectionToField;
use Callcocam\DbRestore\Forms\Components\SelectColumnField;
use Callcocam\DbRestore\Forms\Components\SelectTableField;
use Callcocam\DbRestore\Forms\Components\SelectTableToField;
use Callcocam\DbRestore\Forms\Components\TextareaField;
use Callcocam\DbRestore\Forms\Components\TextInputField;
use Callcocam\DbRestore\Helpers\FileHelper;
use Callcocam\DbRestore\Helpers\RestoreHelper;
use  Callcocam\DbRestore\Models\Export;
use Callcocam\DbRestore\Traits\HasDatesFormForTableColums;
use Callcocam\DbRestore\Traits\HasStatusColumn;
use Callcocam\DbRestore\Traits\HasTraduction;
use Callcocam\DbRestore\Traits\WithColumns;
use Callcocam\DbRestore\Traits\WithFormSchemas;
use Callcocam\DbRestore\Traits\WithSections;
use Callcocam\DbRestore\Traits\WithTables;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Str;

class EditExport extends EditRecord
{
    use HasTraduction, HasStatusColumn, HasDatesFormForTableColums, WithColumns, WithFormSchemas, WithTables, WithSections;

    protected static string $resource = ExportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->icon('fas-file-export')
                ->visible(fn (Export $record) => $record->columns->count())
                ->label($this->getTraduction('export', 'export', 'action',  'label'))
                ->action(function (Export $record) {

                    $columns = $record->columns; 

                    $rows = RestoreHelper::getFromDatabaseRows($record, $record->table_from);

                    $to_columns = RestoreHelper::getColumsSchema($columns, $record->table_from, 'column_to');

                    $values = RestoreHelper::getDataExportValues($rows, $to_columns, $record->connectionTo);
                    FileHelper::make($record)
                        ->fileName()
                        ->sheet()
                        ->columns('column_from', 'column_to')
                        ->writer()
                        ->rows($values)
                        ->save(); 

                    return Storage::disk($record->disk)->download(sprintf('%s.%s', $record->slug, $record->extension));
                }),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    public function form(Form $form): Form
    {
        $record = $this->record;
        return $form
            ->schema([
                TextInputField::make('name')
                    ->columnSpan([
                        'md' => 3
                    ])
                    ->required(),
                ConnectionToField::make('connection_id')
                    ->columnSpan([
                        'md' => 3
                    ])
                    ->required(),
                SelectColumnField::make('restore_model_id')
                    ->relationship('restoreModel', 'name')
                    ->columnSpan([
                        'md' => 3
                    ]),

                SelectTableField::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->columnSpan([
                        'md' => 3
                    ]),
                TextInputField::make('file')
                    ->readOnly()
                    ->columnSpanFull(),
                SelectTableToField::makeTable('table_from', $record)
                    ->columnSpan([
                        'md' => 6
                    ])->required(),
                SelectColumnField::make('disk')
                    ->options(function () {
                        $options = config('filesystems.disks', []);
                        $disks = array_keys($options);
                        return array_combine($disks, $disks);
                    })
                    ->columnSpan([
                        'md' => 2
                    ])->required(),
                SelectColumnField::make('extension')
                    ->options(function () {
                        $options = config('restore.extension', ['csv', 'xls', 'xlsx', 'pdf']);
                        $extensions = $options;
                        return array_combine($extensions, $extensions);
                    })
                    ->columnSpan([
                        'md' => 2
                    ])->required(),
                SelectColumnField::make('delimiter')
                    ->options(function () {
                        $options = config('restore.delimiter', [';', '|', ',']);
                        $delimiters = $options;
                        return array_combine($delimiters, $delimiters);
                    })
                    ->columnSpan([
                        'md' => 2
                    ]),
                $this->getSectionColumnsSchema($record, function ($record) {
                    return $this->getColumnsSchemaFileExportForm($record);
                }),
                $this->getSectionFiltersSchema(
                    record: $record, //pode ser tanto um model connection, restore, children, import, export ou shared 
                    tableTo: $record->table_from, //pode ser tanto um tabela de destino ou de origem, passado quando a tabela para o campo name for diferente da tabela de origem
                ),
                $this->getSectionOrderingsSchema($record),
                static::getStatusFormRadioField(),
                TextareaField::makeText('description')
            ])->columns(12);
    }
}
