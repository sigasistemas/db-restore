<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore;

use Callcocam\DbRestore\Filament\Resources; 
use Filament\Contracts\Plugin;
use Filament\Panel;

class DbRestorePlugin implements Plugin
{
    public function getId(): string
    {
        return 'db-restore';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            Resources\Restores\RestoreResource::class,
            Resources\Restores\ModelResource::class,
            Resources\Restores\ImportResource::class,

        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
