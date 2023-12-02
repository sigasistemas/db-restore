<?php


/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Forms\Components;
 
use Callcocam\DbRestore\Traits\HasTraduction;
use Filament\Forms\Components\Select; 

class SelectField extends Select
{
    use HasTraduction;


    public static function make(string $name): static
    {
        $static = app(static::class, ['name' => $name]);
        $static->configure()->label($static->getTraductionFormLabel($name))
            ->placeholder($static->getTraductionFormPlaceholder($name));

        return $static;
    }
 
}
