<?php


/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Forms\Components;

use Callcocam\DbRestore\Traits\HasTraduction;
use Filament\Forms\Components\Textarea;

class TextareaField extends Textarea
{
    use HasTraduction;

    public static function makeText(string $name): static
    {
        $static = app(static::class, ['name' => $name]);
        $static->configure()
            ->label($static->getTraductionFormLabel($name))
            ->placeholder($static->getTraductionFormPlaceholder($name))
            ->maxLength(65535)
            ->columnSpanFull();

        return $static;
    }
}
