<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Helpers;


class PlanilhaHelper
{

    protected $file;

    protected $sheet;

    protected $fields;

    protected $record;

    protected $fileName;

    public static function make($record, $fields)
    {

        $static = new static();

        $static->record = $record;

        $static->fields = $fields;

        //Gera o arquivo
        $static->file = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        //Seta as propriedades
        $static->file->getProperties()
            ->setCreator('Callcocam')
            ->setLastModifiedBy('Callcocam')
            ->setTitle(sprintf("Importação de dados %s", data_get($record, 'name')));

        return $static;
    }

    public function fileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function sheet()
    {
        $this->sheet = $this->file->getActiveSheet();

        return $this;
    }

    public function getHeaders()
    {
        //Pega o alfabeto do excel
        $alfabetoExcel = $this->getColumnsPlanilha();

        //Seta os cabeçalhos 

        foreach ($this->fields as $key => $column) {
            $char = $alfabetoExcel[$key];
            $this->sheet->setCellValue(sprintf('%s1', $char), data_get($column, 'name'));
        }

        return $this;
    }

    public function getBody($generate = false)
    {
        if (!$generate) {
            return $this;
        }

        //Pega o alfabeto do excel
        $alfabetoExcel = $this->getColumnsPlanilha();
        //Gera os dados fakes
        $data = [];
        if (class_exists('App\Core\Helpers\TenantHelper')) {
            if (method_exists(app('App\Core\Helpers\TenantHelper'), 'getFakeData')) {
                return app('App\Core\Helpers\TenantHelper')->getFakeData($alfabetoExcel, $this->fields, $data);
            } else {
                $data = $this->getFakeData($alfabetoExcel, $this->fields, $data);
            }
        } else {
            $data = $this->getFakeData($alfabetoExcel, $this->fields, $data);
        }
        //Seta os dados
        unset($data[0]);
        foreach ($data as  $item) {
            foreach ($item as $key => $value) {
                $this->sheet->setCellValue($key, $value);
            }
        }
        return $this;
    }

    public function save()
    {
        //Salva o arquivo
        $class = \PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf::class;
        \PhpOffice\PhpSpreadsheet\IOFactory::registerWriter('Pdf', $class);
        $extensions = [
            'csv' => \PhpOffice\PhpSpreadsheet\IOFactory::WRITER_CSV,
            'xls' => \PhpOffice\PhpSpreadsheet\IOFactory::WRITER_XLS,
            'xlsx' => \PhpOffice\PhpSpreadsheet\IOFactory::WRITER_XLSX,
            'pdf' => 'Pdf',
        ];
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($this->file,  data_get($extensions, 'xlsx', 'xlsx'));


        $file_name =  $this->fileName;

        if (class_exists('App\Core\Helpers\TenantHelper')) {
            if (method_exists(app('App\Core\Helpers\TenantHelper'), 'saveFile')) {
                app('App\Core\Helpers\TenantHelper')->saveFile($writer,  $file_name);
                return $this;
            }
        }
        $writer->save(storage_path(sprintf('app/public/%s', $file_name)));

        return $this;
    }

    protected function getColumnsPlanilha()
    {
        $alfabetoExcel = [];
        for ($i = 65; $i <= 90; $i++) {
            $alfabetoExcel[] = chr($i);
        }
        for ($i = 65; $i <= 90; $i++) {
            for ($j = 65; $j <= 90; $j++) {
                $alfabetoExcel[] = chr($i) . chr($j);
            }
        }

        return $alfabetoExcel;
    }

    protected function getFakeData($alfabetoExcel, $fields,  $data)
    {

        for ($i = 1; $i < 20; $i++) {
            $item = [];
            foreach ($fields as $key => $field) {
                $char = $alfabetoExcel[$key];
                $chave = sprintf("%s%s", $char, $i);
                $type = $field->type ?? 'string';
                if ($type == 'text') {
                    $item[$chave] = fake()->sentence();
                } elseif ($type == 'number') {
                    $item[$chave] = fake()->randomNumber();
                } elseif ($type == 'email') {
                    $item[$chave] = fake()->email();
                } elseif ($type == 'date') {
                    $item[$chave] = fake()->dateTime()->format('Y-m-d');
                } elseif ($type == 'time') {
                    $item[$chave] = fake()->dateTime()->format('H:i:s');
                } elseif ($type == 'datetime-local') {
                    $item[$chave] = fake()->dateTime()->format('Y-m-d H:i:s');
                } elseif ($type == 'color') {
                    $item[$chave] = sprintf("Color %s", fake()->word);
                } elseif ($type == 'file') {
                    $item[$chave] = sprintf("/import/%s.png",  rand(1, 10));
                } elseif ($type == 'select') {
                    $item[$chave] = sprintf("Select %s", fake()->word);
                } elseif ($type == 'radio') {
                    $item[$chave] = sprintf("Radio %s", fake()->randomNumber);
                } elseif ($type == 'checkbox') {
                    $item[$chave] = sprintf("Checkbox %s", fake()->randomNumber);
                } elseif ($type == 'textarea') {
                    $item[$chave] = fake()->sentence();
                } elseif ($type == 'hidden') {
                    $item[$chave] = fake()->sentence();
                } elseif ($type == 'editor') {
                    $item[$chave] = fake()->sentence();
                } elseif ($type == 'image') {
                    $item[$chave] = sprintf("/import/%s.png",  rand(1, 10));
                } else {
                    $item[$chave] = fake()->sentence();
                }
            }
            $data[] = $item;
        }

        return $data;
    }
}
