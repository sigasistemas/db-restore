<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Filament\Resources\Restores\ExportResource\Pages;

use  Callcocam\DbRestore\Filament\Resources\Restores\ExportResource;
use Callcocam\DbRestore\Forms\Components\ConnectionField;
use Callcocam\DbRestore\Forms\Components\ConnectionToField;
use Callcocam\DbRestore\Forms\Components\SelectColumnField;
use Callcocam\DbRestore\Forms\Components\SelectTableToField;
use Callcocam\DbRestore\Forms\Components\TextareaField;
use Callcocam\DbRestore\Forms\Components\TextInputField;
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
use Filament\Forms;
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

                    $file = new Spreadsheet();
                    $file->getProperties()
                        ->setCreator('Callcocam')
                        ->setLastModifiedBy('Callcocam')
                        ->setTitle($record->name)
                        ->setSubject($record->name)
                        ->setDescription($record->description ?? '');
                    $sheet = $file->getActiveSheet();

                    $sheet->setTitle($record->name);
                    foreach ($columns as   $column) {
                        $sheet->setCellValue(sprintf('%s1', $column->column_from), Str::title($column->column_to));
                    }

                    $rows = RestoreHelper::getFromDatabaseRows($record, $record->table_name);

                    $to_columns = RestoreHelper::getColumsSchema($columns, $record->table_name, 'column_to');

                    $values = RestoreHelper::getDataExportValues($rows, $to_columns, $record->connectionTo);
                    $key = 1;
                    foreach ($values as   $row) {
                        $key++;
                        foreach ($columns as   $column) {
                            $sheet->setCellValue(sprintf('%s%s', $column->column_from, $key), data_get($row, $column->column_to));
                        }
                    }
                    $class = \PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf::class;
                    \PhpOffice\PhpSpreadsheet\IOFactory::registerWriter('Pdf', $class);
                    $extensions = [
                        'csv' => \PhpOffice\PhpSpreadsheet\IOFactory::WRITER_CSV,
                        'xls' => \PhpOffice\PhpSpreadsheet\IOFactory::WRITER_XLS,
                        'xlsx' => \PhpOffice\PhpSpreadsheet\IOFactory::WRITER_XLSX,
                        'pdf' => 'Pdf',
                    ];
                    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($file,  data_get($extensions, $record->extension));



                    $file_name =  storage_path(sprintf('app/public/%s', sprintf('%s.%s', $record->slug, $record->extension)));

                    $writer->save($file_name);

                    $record->update([
                        'file' => sprintf('%s.%s', $record->slug, $record->extension),
                    ]);

                    return Storage::disk($record->disk)->download(sprintf('%s.%s', $record->slug, $record->extension));
                }),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInputField::make('name')
                    ->columnSpan([
                        'md' => 5
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
                        'md' => 4
                    ]),
                TextInputField::make('file')
                    ->readOnly()
                    ->columnSpanFull(),
                SelectTableToField::make('table_name')
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
                $this->getSectionColumnsSchema($this->record, function ($record) {
                    return $this->getColumnsSchemaFileExportForm($record, $record->table_name);
                }),
                $this->getSectionFiltersSchema($this->record),
                $this->getSectionOrderingsSchema($this->record),
                static::getStatusFormRadioField(),
                TextareaField::makeText('description')
            ])->columns(12);
    }
}
