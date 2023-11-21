<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Forms\Components;

use Callcocam\DbRestore\Traits\HasTraduction;

class ConnectionToField extends ConnectionField
{
    use HasTraduction;

    public static function make(string $name): static
    {
        $static = app(static::class, ['name' => $name]);
        $static->configure()
            ->label($static->getTraductionFormLabel($name))
            ->placeholder($static->getTraductionFormPlaceholder($name))
            ->relationship(
                name: 'connectionTo',
                titleAttribute: 'name',
            )
            ->manageOptionForm($static->getFormSchemaConnectionOptions());

        return $static;
    }
}
