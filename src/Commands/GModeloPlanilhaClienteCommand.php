<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GModeloPlanilhaClienteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:g-modelo-planilha-cliente {--connection=mysql} {--table=fields}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gere um modelo de planilha para importação de produtos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //Pega a conexão
        $connection = $this->option('connection');
        //Pega a tabela
        $table = $this->option('table');
        //Pega os campos da tabela
        $fields = DB::connection($connection)->table($table)->get();

        $data = [];
        //Pega o alfabeto do excel
        $alfabetoExcel = $this->getColumnsPlanilha();
        //Gera os dados fakes
        $data = $this->getFakeData($alfabetoExcel, $fields, $data);
        //Gera o arquivo
        $file = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        //Seta as propriedades
        $file->getProperties()
            ->setCreator('Callcocam')
            ->setLastModifiedBy('Callcocam')
            ->setTitle("Importação de dados");
        //Pega a planilha
        $sheet = $file->getActiveSheet();
        //Seta os cabeçalhos
        $key = 65;
        foreach ($fields as   $column) {
            $sheet->setCellValue(sprintf('%s1',  chr($key++)), $column->name);
        }
        //Seta os dados
        unset($data[0]);
        foreach ($data as  $item) {
            foreach ($item as $key => $value) {
                $sheet->setCellValue($key, $value);
            }
        }
        //Salva o arquivo
        $class = \PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf::class;
        \PhpOffice\PhpSpreadsheet\IOFactory::registerWriter('Pdf', $class);
        $extensions = [
            'csv' => \PhpOffice\PhpSpreadsheet\IOFactory::WRITER_CSV,
            'xls' => \PhpOffice\PhpSpreadsheet\IOFactory::WRITER_XLS,
            'xlsx' => \PhpOffice\PhpSpreadsheet\IOFactory::WRITER_XLSX,
            'pdf' => 'Pdf',
        ];
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($file,  data_get($extensions, 'xlsx'));



        $file_name =  storage_path(sprintf('app/public/import-%s.xlsx',  date('Y-m-d-H-i-s')));

        $writer->save($file_name);

        $this->info("Arquivo gerado com sucesso em {$file_name}");
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
        // 'text',
        // 'number',
        // 'email',
        // 'password',
        // 'date',
        // 'time',
        // 'datetime-local',
        // 'color',
        // 'file',
        // 'select',
        // 'radio',
        // 'checkbox',
        // 'textarea',
        // 'hidden',
        // 'editor',
        // 'image',
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
                } elseif ($type == 'password') {
                    $item[$chave] = fake()->password();
                } elseif ($type == 'date') {
                    $item[$chave] = fake()->dateTime()->format('Y-m-d');
                } elseif ($type == 'time') {
                    $item[$chave] = fake()->dateTime()->format('H:i:s');
                } elseif ($type == 'datetime-local') {
                    $item[$chave] = fake()->dateTime()->format('Y-m-d H:i:s');
                } elseif ($type == 'color') {
                    $item[$chave] = sprintf("Color %s", fake()->sentence());
                } elseif ($type == 'file') {
                    $item[$chave] = sprintf("/import/%s.png",  rand(1, 10));
                } elseif ($type == 'select') {
                    $item[$chave] = sprintf("Select %s", fake()->sentence());
                } elseif ($type == 'radio') {
                    $item[$chave] = sprintf("Radio %s", fake()->word);
                } elseif ($type == 'checkbox') {
                    $item[$chave] = sprintf("Checkbox %s", fake()->word);
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
