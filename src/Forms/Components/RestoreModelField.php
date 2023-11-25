<?php


/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Forms\Components;

use Callcocam\DbRestore\Helpers\RestoreHelper;
use Callcocam\DbRestore\Traits\HasStatusColumn;
use Callcocam\DbRestore\Traits\HasTraduction;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;

class RestoreModelField extends Select
{
    use HasTraduction, HasStatusColumn;

    public static function makeColumn(string $name,  $label = null): static
    {
        $static = app(static::class, ['name' => $name]);
        $static->configure()
            ->label($static->getTraductionFormLabel($label ?? $name))
            ->placeholder($static->getTraductionFormPlaceholder($label ?? $name))
            ->relationship(
                name: 'restoreModel',
                titleAttribute: 'name'
            )->manageOptionForm($static->getFormSchemaModelOptions());;

        return $static;
    }

    public function getFormSchemaModelOptions()
    {
        return [
            Group::make([
                Select::make('name')
                    ->label(__('db-restore::db-restore.model.form.name.label'))
                    ->placeholder(__('db-restore::db-restore.model.form.name.placeholder'))
                    ->required()
                    ->columnSpanFull()
                    ->options(RestoreHelper::getModelsOptions()),
                static::getStatusFormRadioField(),
                Textarea::make('description')
                    ->label(__('db-restore::db-restore.model.form.description.label'))
                    ->placeholder(__('db-restore::db-restore.model.form.description.placeholder'))
                    ->maxLength(65535)
                    ->columnSpanFull(),

            ])->columns(12),
        ];
    }
}
