<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Filament\Resources\Restores\ImportResource\Pages;

use Callcocam\DbRestore\Filament\Resources\Restores\ImportResource;
use Callcocam\DbRestore\Models\Import;
use Callcocam\DbRestore\Helpers\RestoreHelper;
use Callcocam\DbRestore\Traits\HasStatusColumn;
use Callcocam\DbRestore\Traits\HasTraduction;
use Callcocam\DbRestore\Traits\HasUploadFormField;
use Callcocam\DbRestore\Traits\WithColumns;
use Callcocam\DbRestore\Traits\WithFormSchemas;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Forms\Set;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class EditImport extends EditRecord
{
    use HasTraduction, HasStatusColumn, WithColumns, WithFormSchemas, HasUploadFormField;

    protected static string $resource = ImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Restore')
                ->visible(fn (Import $record) => $record->columns->count() > 0)
                ->icon('fas-upload')
                ->color('success')
                ->action(function (Import $record) {
                    $columns = $record->columns;

                    $from_table = $record->table_name;

                    if (Storage::exists($record->file)) {

                        $to_columns = RestoreHelper::getColumsSchema($columns, $from_table, 'column_to');

                        $sheetData = Cache::rememberForever("{$record->file}-column1", function () use ($record) {
                            $inputFileName = Storage::path($record->file);

                            $testAgainstFormats = [
                                \PhpOffice\PhpSpreadsheet\IOFactory::READER_XLS,
                                \PhpOffice\PhpSpreadsheet\IOFactory::READER_XLSX,
                                \PhpOffice\PhpSpreadsheet\IOFactory::READER_CSV,
                            ];
                            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName, 0, $testAgainstFormats);

                            return  $spreadsheet->getActiveSheet()->toArray(true, true, true, true);
                        });
                        unset($sheetData[1]);

                        $chunks = array_chunk($sheetData, 1000);

                        $batch =  Bus::batch([])->then(function (Batch $batch) use ($record) {
                        })->name($record->name)->dispatch();

                        $record->table_to = $from_table;

                        RestoreHelper::beforeRemoveFilters($record);

                        foreach ($chunks as $chunk) {
                            $batch->add(new \Callcocam\DbRestore\Jobs\DbRestoreFileJob($record, $chunk, $to_columns));
                        }
                    }
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
                    ->relationship('connection', 'name')
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
                Forms\Components\Select::make('table_name')
                    ->label($this->getTraductionFormLabel('table_name'))
                    ->placeholder($this->getTraductionFormPlaceholder('table_name'))
                    ->options(function (Import $import) {
                        return $this->getTables($import->connection, 'from');
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
                static::getUploadFormField('file')
                    ->afterStateUpdated(function (Set $set) {
                        // $set('columns', []);
                    }),
                Forms\Components\Section::make($this->getTraduction('columns', 'restore', 'form',  'label'))
                    ->visible(fn (Import $record) => $record->table_name && $record->file)
                    ->description($this->getTraduction('columns', 'restore', 'form',  'description'))
                    ->collapsed()
                    ->schema(function (Import $record) {
                        return  [
                            Forms\Components\Repeater::make('columns')
                                ->relationship('columns')
                                ->hiddenLabel()
                                ->schema(function () use ($record) {
                                    return $this->getColumnsSchemaFileForm($record, $record->table_name);
                                })
                                ->columns(12)
                                ->columnSpanFull()
                        ];
                    }),
                Forms\Components\Section::make($this->getTraduction('filters', 'restore', 'form',  'label'))
                    ->description($this->getTraduction('filters', 'restore', 'form',  'description'))
                    ->visible(fn (Import $record) => $record->table_name)
                    ->collapsed()
                    ->schema(function (Import $record) {
                        return  [
                            Forms\Components\Repeater::make('filters')
                                ->relationship('filters')
                                ->hiddenLabel()
                                ->schema(function () use ($record) {
                                    return $this->getFiltersSchemaForm($record->connection, $record->table_name);
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
