<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Filament\Resources\Restores\ImportResource\Pages;

use Callcocam\DbRestore\Filament\Resources\Restores\ImportResource; 
use Callcocam\DbRestore\Forms\Components\SelectTableField; 
use Callcocam\DbRestore\Helpers\PlanilhaHelper; 
use Callcocam\DbRestore\Traits\WithTables;
use Callcocam\Tenant\Models\Tenant;
use Filament\Actions;
use Filament\Forms\Components\Group; 
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; 

class ListImports extends ListRecords
{
    use WithTables;

    protected static string $resource = ImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('sample')
                ->icon('fas-file-import')
                ->color('warning')
                ->label('Gerar um modelo')
                ->form(function () {
                    return [
                        Group::make([
                            SelectTableField::make('tenant_id')
                                ->required()
                                ->searchable()
                                ->columnSpan([
                                    'md' => 2
                                ])
                                ->options(Tenant::query()->whereStatus('published')->pluck('name', 'id')->toArray()),
                            SelectTableField::make('extension')
                                ->options(function () {
                                    $options = config('restore.extension', ['csv', 'xls', 'xlsx']);
                                    $extensions = $options;
                                    return array_combine($extensions, $extensions);
                                })
                                ->default('xlsx')
                                ->columnSpan([
                                    'md' => 1
                                ])->required(),
                        ])->columns(3)
                    ];
                })
                ->action(function (array $data) {

                    if (class_exists('App\Core\Helpers\TenantHelper')) {
                        if (method_exists(app('App\Core\Helpers\TenantHelper'), 'generateModel')) {
                            return app('App\Core\Helpers\TenantHelper')->generateModel($data);
                        }
                    }

                    $tenant = Tenant::find($data['tenant_id']);

                    $fields = DB::connection(config('database.default'))->table(config('db-restore.tables.fields', 'fields'))->where('tenant_id', $tenant['id'])->get();

                    if ($fields->count()) {
                        $fileName = sprintf('%s.%s', $tenant->slug, data_get($data, 'extension'));
                        PlanilhaHelper::make($tenant, $fields)
                            ->fileName($fileName)
                            ->sheet()
                            ->getHeaders()
                            ->save();
                        return Storage::disk(data_get($data, 'disk'))->download($fileName);
                    }
                    Notification::make()
                        ->title('Não foi possível gerar o modelo!')
                        ->danger()
                        ->send();
                }),
        ];
    }
}
