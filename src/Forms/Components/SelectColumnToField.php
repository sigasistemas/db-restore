<?php


/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Forms\Components;
 
use Callcocam\DbRestore\Models\AbstractModelRestore; 
use Callcocam\DbRestore\Traits\HasTraduction; 

class SelectColumnToField extends SelectColumnField
{
    use HasTraduction;

    public static function makeColumn(string $name, AbstractModelRestore | null $record = null, $label = null): static
    {  
        $static = app(static::class, ['name' => $name]);
        $static->configure()
            ->label($static->getTraductionFormLabel($label ?? $name))
            ->placeholder($static->getTraductionFormPlaceholder($label ?? $name))
            ->options($static->getColumnsOptions($record->connectionTo,  $record->table_to, 'to'));

        return $static;
    }
}
