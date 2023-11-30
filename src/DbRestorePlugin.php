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
    protected $useRestoreResource = true;
    protected $useImportResource = true;
    protected $useExportResource = true;
    protected $useModelResource = false;

    public function __construct($useRestoreResource = true, $useImportResource = true, $useExportResource = true, $useModelResource = false)
    {
        $this->useRestoreResource = $useRestoreResource;
        $this->useImportResource = $useImportResource;
        $this->useExportResource = $useExportResource;
        $this->useModelResource = $useModelResource;
    }

    public function getId(): string
    {
        return 'db-restore';
    }

    public function register(Panel $panel): void
    {

        $resourses = [];

        if ($this->useRestoreResource) {
            $resourses[] = Resources\Restores\RestoreResource::class;
        }

        if ($this->useModelResource) {
            $resourses[] = Resources\Restores\ModelResource::class;
        }

        if ($this->useImportResource) {
            $resourses[] = Resources\Restores\ImportResource::class;
        }

        if ($this->useExportResource) {
            $resourses[] = Resources\Restores\ExportResource::class;
        }



        $panel->resources($resourses);
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
