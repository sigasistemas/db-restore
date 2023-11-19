<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Filament\Resources\Restores\ExportResource\Pages;

use  Callcocam\DbRestore\Filament\Resources\Restores\ExportResource;
use Callcocam\DbRestore\Helpers\RestoreHelper;
use  Callcocam\DbRestore\Models\Export;
use Callcocam\DbRestore\Traits\HasDatesFormForTableColums;
use Callcocam\DbRestore\Traits\HasStatusColumn;
use Callcocam\DbRestore\Traits\HasTraduction;
use Callcocam\DbRestore\Traits\WithColumns;
use Callcocam\DbRestore\Traits\WithFormSchemas;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class EditExport extends EditRecord
{
    use HasTraduction, HasStatusColumn, HasDatesFormForTableColums, WithColumns, WithFormSchemas;

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

                    $sheet = $file->getActiveSheet(); 

                    $sheet->setTitle($record->name);

                    $sheet->fromArray($columns->pluck('column_to')->toArray(), null, 'A1');

                    $rows = RestoreHelper::getFromDatabaseRows($record, $record->table_name);

                    $to_columns = RestoreHelper::getColumsSchema($columns,$record->table_name, 'column_to');

                    $values = RestoreHelper::getDataExportValues($rows, $to_columns, $record->connectionTo); 
                    $key=0;
                    foreach ($values as   $row) {
                        $sheet->fromArray($row, null, sprintf('A%s', $key++));
                    } 

                    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($file,  \PhpOffice\PhpSpreadsheet\IOFactory::WRITER_XLSX);

                    

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
                Forms\Components\TextInput::make('name')
                    ->label($this->getTraductionFormLabel('name'))
                    ->placeholder($this->getTraductionFormPlaceholder('name'))
                    ->columnSpan([
                        'md' => 5
                    ])
                    ->required(),
                Forms\Components\Select::make('connection_id')
                    ->label($this->getTraductionFormLabel('connection_id'))
                    ->placeholder($this->getTraductionFormPlaceholder('connection_id'))
                    ->relationship('connectionTo', 'name')
                    ->columnSpan([
                        'md' => 3
                    ])
                    ->required(),
                Forms\Components\Select::make('restore_model_id')
                    ->label($this->getTraductionFormLabel('restore_model_id'))
                    ->placeholder($this->getTraductionFormPlaceholder('restore_model_id'))
                    ->relationship('restoreModel', 'name')
                    ->columnSpan([
                        'md' => 4
                    ]),
                Forms\Components\TextInput::make('file')
                    ->label($this->getTraductionFormLabel('file'))
                    ->placeholder($this->getTraductionFormPlaceholder('file'))
                    ->readOnly()
                    ->columnSpanFull(),
                Forms\Components\Select::make('table_name')
                    ->label($this->getTraductionFormLabel('table_name'))
                    ->placeholder($this->getTraductionFormPlaceholder('table_name'))
                    ->options(function (Export $import) {
                        return $this->getTables($import->connectionTo, 'from');
                    })
                    ->columnSpan([
                        'md' => 6
                    ])->required(),
                Forms\Components\Select::make('disk')
                    ->label($this->getTraductionFormLabel('disk'))
                    ->placeholder($this->getTraductionFormPlaceholder('disk'))
                    ->options(function () {
                        $options = config('filesystems.disks', []);
                        $disks = array_keys($options);
                        return array_combine($disks, $disks);
                    })
                    ->columnSpan([
                        'md' => 2
                    ])->required(),
                Forms\Components\Select::make('extension')
                    ->label($this->getTraductionFormLabel('extension'))
                    ->placeholder($this->getTraductionFormPlaceholder('extension'))
                    ->options(function () {
                        $options = config('restore.extension', ['csv', 'xls', 'xlsx']);
                        $extensions = $options;
                        return array_combine($extensions, $extensions);
                    })
                    ->columnSpan([
                        'md' => 2
                    ])->required(),
                Forms\Components\Select::make('delimiter')
                    ->label($this->getTraductionFormLabel('delimiter'))
                    ->placeholder($this->getTraductionFormPlaceholder('delimiter'))
                    ->options(function () {
                        $options = config('restore.delimiter', [';', '|', ',']);
                        $delimiters = $options;
                        return array_combine($delimiters, $delimiters);
                    })
                    ->columnSpan([
                        'md' => 2
                    ]),
                Forms\Components\Section::make($this->getTraduction('columns', 'restore', 'form',  'label'))
                    ->visible(fn (Export $record) => $record->table_name)
                    ->description($this->getTraduction('columns', 'restore', 'form',  'description'))
                    ->collapsed()
                    ->schema(function (Export $record) {
                        return  [
                            Forms\Components\Repeater::make('columns')
                                ->relationship('columns')
                                ->hiddenLabel()
                                ->schema(function () use ($record) {
                                    return $this->getColumnsSchemaFileExportForm($record, $record->table_name);
                                })
                                ->columns(12)
                                ->columnSpanFull()
                        ];
                    }),
                Forms\Components\Section::make($this->getTraduction('filters', 'restore', 'form',  'label'))
                    ->description($this->getTraduction('filters', 'restore', 'form',  'description'))
                    ->visible(fn (Export $record) => $record->table_name)
                    ->collapsed()
                    ->schema(function (Export $record) {
                        return  [
                            Forms\Components\Repeater::make('filters')
                                ->relationship('filters')
                                ->hiddenLabel()
                                ->schema(function () use ($record) {
                                    return $this->getFiltersSchemaForm($record->connectionTo, $record->table_name);
                                })
                                ->columns(12)
                                ->columnSpanFull()
                        ];
                    }),
                static::getStatusFormRadioField(),
                Forms\Components\Textarea::make('description')
                    ->label($this->getTraductionFormLabel('description'))
                    ->placeholder($this->getTraductionFormPlaceholder('description'))
                    ->columnSpanFull()
            ])->columns(12);
    }
}
