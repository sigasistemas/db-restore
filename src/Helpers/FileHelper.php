<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class FileHelper
{

    protected $record;

    protected $file;

    protected $writer;

    protected $sheet;

    protected $fileName;

    protected $headers;

    protected $rows;

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


    public function file()
    {
        return $this->file;
    }

    public function getHeaders()
    {
        $headers = $this->headers;
        if (isset($headers[1])) {
            return $headers[1];
        }
        return  $this->headers;
    }

    public function getRows()
    {
        $rows = $this->rows;
        if (isset($rows[1])) {
            unset($rows[1]);
        }
        return  $rows;
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

    public function load()
    {

        $inputFileName = Storage::disk(config('db-restore.disk'))->path($this->record->file);
        $testAgainstFormats = [
            \PhpOffice\PhpSpreadsheet\IOFactory::READER_XLS,
            \PhpOffice\PhpSpreadsheet\IOFactory::READER_XLSX,
            \PhpOffice\PhpSpreadsheet\IOFactory::READER_CSV,
        ];

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName, 0, $testAgainstFormats);
        $this->rows =  $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $this->headers =  $this->rows;
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
