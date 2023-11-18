<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Traits;

use Filament\Tables\Columns\TextColumn;

trait HasDatesFormForTableColums
{
    protected static function getFieldDatesFormForTable($isToggledHiddenByCreatedDefault = false, $isToggledHiddenByUpdatedDefault = true)
    {
        return [
            TextColumn::make(static::getCreatedAtTableColumn())
                ->label(static::getCreatedAtTableColumnLabel())
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: $isToggledHiddenByCreatedDefault),
            TextColumn::make(static::getUpdatedAtTableColumn())
                ->label(static::getUpdatedAtTableColumnLabel())
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: $isToggledHiddenByUpdatedDefault),
        ];
    }

    public static function getCreatedAtColumnLabel(): string
    {
        return 'Criado em';
    }

    public static function getUpdatedAtColumnLabel(): string
    {
        return 'Atualizado em';
    }

    public static function getDeletedAtColumnLabel(): string
    {
        return 'Deletado em';
    }

    public static function getCreatedAtTableColumn(): string
    {
        return 'created_at';
    }

    public static function getUpdatedAtTableColumn(): string
    {
        return 'updated_at';
    }

    public static function getDeletedAtTableColumn(): string
    {
        return 'deleted_at';
    }

    public static function getCreatedAtTableColumnLabel(): string
    {
        return static::getCreatedAtColumnLabel();
    }

    public static function getUpdatedAtTableColumnLabel(): string
    {
        return static::getUpdatedAtColumnLabel();
    }

    public static function getDeletedAtTableColumnLabel(): string
    {
        return static::getDeletedAtColumnLabel();
    }

    public static function getCreatedAtTableColumnFormat(): string
    {
        return 'd/m/Y H:i:s';
    }

    public static function getUpdatedAtTableColumnFormat(): string
    {
        return 'd/m/Y H:i:s';
    }

    public static function getDeletedAtTableColumnFormat(): string
    {
        return 'd/m/Y H:i:s';
    }

    public static function getCreatedAtTableColumnTimezone(): string
    {
        return 'America/Sao_Paulo';
    }

    public static function getUpdatedAtTableColumnTimezone(): string
    {
        return 'America/Sao_Paulo';
    }

    public static function getDeletedAtTableColumnTimezone(): string
    {
        return 'America/Sao_Paulo';
    }

    public static function getCreatedAtTableColumnFormatLocalized(): string
    {
        return 'd/m/Y H:i:s';
    }

    public static function getUpdatedAtTableColumnFormatLocalized(): string
    {
        return 'd/m/Y H:i:s';
    }

    public static function getDeletedAtTableColumnFormatLocalized(): string
    {
        return 'd/m/Y H:i:s';
    }

    public static function getCreatedAtTableColumnTimezoneLocalized(): string
    {
        return 'America/Sao_Paulo';
    }

    public static function getUpdatedAtTableColumnTimezoneLocalized(): string
    {
        return 'America / Sao_Paulo';
    }
}
