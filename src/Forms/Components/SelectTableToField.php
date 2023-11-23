<?php


/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Forms\Components;

use Callcocam\DbRestore\Models\AbstractModelRestore;
use Callcocam\DbRestore\Models\Connection;
use Callcocam\DbRestore\Traits\HasTraduction;

class SelectTableToField extends SelectTableField
{
    use HasTraduction;

    public static function makeTable(string $name, AbstractModelRestore $record = null, $label = null): static
    {
        if ($record instanceof Connection) {
            $connectionTo = $record;
        } else {
            $connectionTo = $record->connectionTo;
        }
        $static = app(static::class, ['name' => $name]);
        $static->configure()
            ->label($static->getTraductionFormLabel($label ?? $name))
            ->placeholder($static->getTraductionFormPlaceholder($label ?? $name))
            ->live()
            ->required()
            ->options($static->getTablesOptions($connectionTo));

        return $static;
    }
}
