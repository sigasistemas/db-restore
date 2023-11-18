<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Traits;

trait HasTraduction
{
    public function getTraduction($column, $model = 'restore', $cantext = 'form', $key = 'label', $default = null)
    {
        return __(sprintf('db-restore::db-restore.%s.%s.%s.%s', $model, $cantext, $column, $key));
    }

    public function getTraductionFormLabel($column, $model = 'restore', $default = null)
    {
        return __(sprintf('db-restore::db-restore.%s.form.%s.label', $model, $column));
    }

    public function getTraductionFormPlaceholder($column, $model = 'restore', $default = null)
    {
        return __(sprintf('db-restore::db-restore.%s.form.%s.placeholder', $model, $column));
    }
}
