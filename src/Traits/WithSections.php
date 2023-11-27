<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Traits;

use Callcocam\DbRestore\Models\AbstractModelRestore;
use Closure;
use Filament\Forms;

trait WithSections
{

    /**
     * @param AbstractModelRestore $record
     * @return Forms\Components\Section
     */
    public function getSectionColumnsSchema(AbstractModelRestore $record, Closure $callback = null)
    {
        return  Forms\Components\Section::make($this->getTraduction('columns', 'restore', 'form',  'label'))
            ->description($this->getTraduction('columns', 'restore', 'form',  'description'))
            ->collapsed()
            ->schema(function () use ($callback, $record) {
                return  [
                    Forms\Components\Repeater::make('columns')
                        ->relationship('columns')
                        ->hiddenLabel()
                        ->schema($callback($record))
                        ->columns(12)
                        ->columnSpanFull()
                ];
            });
    }
    /**
     * @param AbstractModelRestore $record
     * @return Forms\Components\Section
     */
    public function getSectionOrderingsSchema(AbstractModelRestore $record)
    {
        return  Forms\Components\Section::make($this->getTraduction('orderings', 'restore', 'form',  'label'))
            ->description($this->getTraduction('orderings', 'restore', 'form',  'description'))
            ->collapsed()
            ->schema(function () use ($record) {
                return  [
                    Forms\Components\Repeater::make('orderings')
                        ->relationship('orderings')
                        ->hiddenLabel()
                        ->schema(function () use ($record) {
                            return $this->getOrderingsSchemaForm($record->connectionFrom,   $record->table_from);
                        })
                        ->columns(12)
                        ->columnSpanFull()
                ];
            });
    }

    /**
     * @param AbstractModelRestore $record pode ser um model connection, restore, children, import, export ou shared
     * $connection pode ser tanto um conexao de destino ou de origem, passado quando a conexao para o campo name for diferente da conexao de origem
     * $tableTo passar quando a tabela for diferente da tabela de origem
     * $connectionTo tem que ser um conexao de destino
     * @return Forms\Components\Section
     */
    public function getSectionFiltersSchema(AbstractModelRestore $record, $connection = null, $tableTo = null, $connectionTo = null)
    {
        return Forms\Components\Section::make($this->getTraduction('filters', 'restore', 'form',  'label'))
            ->description($this->getTraduction('filters', 'restore', 'form',  'description'))
            ->collapsed()
            ->schema(function () use ($record, $connection, $tableTo, $connectionTo) {
                return  [
                    Forms\Components\Repeater::make('filters')
                        ->relationship('filters')
                        ->hiddenLabel()
                        ->schema(function () use ($record, $connection, $tableTo, $connectionTo) {
                            if (!$connection) {
                                $connection = $record->connectionTo;
                            }
                            if (!$connectionTo) {
                                $connectionTo = $record->connectionTo;
                            }
                            if (!$tableTo) {
                                $tableTo = $record->table_to;
                            }
                            return $this->getFiltersSchemaForm($connection,  $tableTo, $connectionTo);
                        })
                        ->columns(12)
                        ->columnSpanFull()
                ];
            });
    }

    public function getSectionPivotsSchema(AbstractModelRestore $record)
    {
        return  Forms\Components\Section::make($this->getTraduction('pivots', 'restore', 'form',  'label'))
            ->description($this->getTraduction('pivots', 'restore', 'form',  'description'))
            ->collapsed()
            ->schema(function () use ($record) {
                return  [
                    Forms\Components\Repeater::make('pivots')
                        ->relationship('pivots')
                        ->hiddenLabel()
                        ->schema(function () use ($record) {
                            return  $this->getPivotschemaForm($record);
                        })
                        ->columns(12)
                        ->columnSpanFull()
                ];
            });
    }
}
