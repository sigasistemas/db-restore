<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Helpers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

class FileHelper
{

    protected $record;

    protected $file;

    protected $writer;

    protected $sheet;

    protected $fileName;

    public function __construct($record)
    {
        $this->record = $record;
    }

    public static function make($record)
    {
        $static = new static($record);

        $static->file = new Spreadsheet();

        $static->file->getProperties()
            ->setCreator('Callcocam')
            ->setLastModifiedBy('Callcocam')
            ->setTitle($record->name)
            ->setSubject($record->name)
            ->setDescription($record->description ?? '');


        return $static;
    }

    public function fileName()
    {
        $this->fileName = storage_path(sprintf('app/public/%s', sprintf('%s.%s', $this->record->slug, $this->record->extension)));

        return $this;
    }


    public function sheet()
    {
        $this->sheet = $this->file->getActiveSheet();

        return $this;
    }

    public function columns($columnName = 'column', $columnDescription = 'description', $columnLabel = 'name')
    {
        $columns = $this->record->columns;

        foreach ($columns as   $column) {
            $this->sheet->setCellValue(sprintf('%s1', data_get($column, $columnName)),  data_get($column, $columnDescription) ?? data_get($column, $columnLabel));
        }

        return $this;
    }

    public function rows($values)
    {
        $columns = $this->record->columns;
        $key = 1;
        foreach ($values as   $row) {
            $key++;
            foreach ($columns as   $column) {
                $this->sheet->setCellValue(sprintf('%s%s', $column->column_from, $key), data_get($row, $column->column_to));
            }
        }

        return $this;
    }

    /**
     * @return $this
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\SaveException
     * @throws \PhpOffice\PhpSpreadsheet\Writer\UnsupportedMetaException
     */
    public function writer()
    {
        $class = \PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf::class;

        \PhpOffice\PhpSpreadsheet\IOFactory::registerWriter('Pdf', $class);
        $extensions = [
            'csv' => \PhpOffice\PhpSpreadsheet\IOFactory::WRITER_CSV,
            'xls' => \PhpOffice\PhpSpreadsheet\IOFactory::WRITER_XLS,
            'xlsx' => \PhpOffice\PhpSpreadsheet\IOFactory::WRITER_XLSX,
            'pdf' => 'Pdf',
        ];

        $this->writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($this->file,  data_get($extensions, $this->record->extension));

        return $this;
    }

    public function save()
    {

        $this->writer->save($this->fileName);

        $this->record->update([
            'file' => sprintf('%s.%s', $this->record->slug, $this->record->extension),
        ]);

        return $this;
    }
}
