<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Traits;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;

trait HasEditorColumn
{
    public static function getEditorColumnLabel(): string
    {
        return 'Descrição ou Detalhes';
    }

    public static function getTextareaFormField(): Textarea
    {
        return Textarea::make(static::getEditorColumn())
            ->label(static::getEditorColumnLabel())
            ->columnSpanFull();
    }

    public static function getEditorFormField(bool $required = false): RichEditor
    {
        return RichEditor::make(static::getEditorColumn())
            ->label(static::getEditorColumnLabel())
            ->columnSpanFull()
            ->required($required);
    }

    public static function getEditorColumn(): string
    {
        return 'description';
    }
}
