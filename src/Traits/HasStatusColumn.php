<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Traits;

use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\IconColumn;

trait HasStatusColumn
{

    public static function getStatusColumnLabel(): string
    {
        return 'Status';
    }

    public static function getStatusTableIconColumn(): IconColumn
    {
        return  IconColumn::make('status')
            ->label(static::getStatusColumnLabel())
            ->color(fn (string $state): string => match ($state) {
                'draft' => 'danger',
                'reviewing' => 'warning',
                'published' => 'success',
                default => 'gray',
            })
            ->icon(fn (string $state): string => match ($state) {
                'draft' => 'heroicon-o-no-symbol',
                'reviewing' => 'heroicon-o-clock',
                'published' => 'heroicon-o-check-circle',
            });
    }

    public static function getStatusFormSelectField(bool $required = true): Select
    {
        return Select::make(static::getStatusColumn())
            ->label(static::getStatusColumnLabel())
            ->options(static::getStatuses())
            ->default('draft')
            ->required($required);
    }

    public static function getStatusFormRadioField(bool $required = true, $inline = true ): Fieldset
    {
        return Fieldset::make()->schema([
            Radio::make(static::getStatusColumn())
                ->label(static::getStatusColumnLabel())
                ->options(static::getStatuses()) 
                ->default('draft')
                ->inline($inline)
                ->columnSpanFull()
                ->required($required),
        ]);
    }

    public static function getStatuses(): array
    {
        return [
            'draft' => 'Rascunho',
            'published' => 'Publicado',
        ];
    }

    public static function getStatusColumn(): string
    {
        return 'status';
    }

    public static function getStatusesForTable(): array
    {
        return static::getStatuses();
    }

    public static function getStatusesForForm(): array
    {
        return  static::getStatuses();
    }
}
