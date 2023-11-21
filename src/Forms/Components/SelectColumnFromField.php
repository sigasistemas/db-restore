<?php


/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Forms\Components;
 
use Callcocam\DbRestore\Models\AbstractModelRestore; 
use Callcocam\DbRestore\Traits\HasTraduction; 

class SelectColumnFromField extends SelectColumnField
{
    use HasTraduction;


    public static function makeColumn(string $name, AbstractModelRestore | null $record = null, $label = null): static
    {
        $static = app(static::class, ['name' => $name]);
        $static->configure()
            ->label($static->getTraductionFormLabel($name))
            ->placeholder($static->getTraductionFormPlaceholder($name))
            ->options($static->getColumnsOptions($record->connectionFrom, $record->table_from, 'from'));

        return $static;
    }
}
