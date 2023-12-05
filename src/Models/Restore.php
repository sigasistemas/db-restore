<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Models;

use Callcocam\DbRestore\Helpers\RestoreHelper;
use Callcocam\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Restore extends AbstractModelRestore
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'restores';

    protected $with = ['connectionFrom', 'connectionTo'];

    protected $appends = ['connTo', 'connFrom', 'tableToOptions'];

    protected static function booted()
    {
        static::deleting(function (Restore $model) {  
            $model->columns()->forceDelete();
            $model->filters()->forceDelete();
            $model->orderings()->forceDelete();
            $model->childrens()->forceDelete();
            $model->pivots()->forceDelete();
            $model->shareds()->forceDelete(); 
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function connections()
    {
        return $this->belongsTo(Connection::class);
    }

    public function connectionFrom()
    {
        return $this->belongsTo(Connection::class, 'connection_from_id');
    }

    public function connectionTo()
    {
        return $this->belongsTo(Connection::class, 'connection_to_id');
    }

    public function columns()
    {
        return $this->morphMany(Column::class, 'columnable');
    }

    public function filters()
    {
        return $this->morphMany(Filter::class, 'filterable');
    }

    public function orderings()
    {
        return $this->morphMany(Ordering::class, 'orderingable')->orderBy('ordering', 'ASC');
    }

    public function restoreModel()
    {
        return $this->belongsTo(Model::class);
    }

    public function childrens()
    {
        return $this->morphMany(Children::class, 'childrenable');
    }

    public function pivots()
    {
        return $this->morphMany(Pivot::class, 'pivotable');
    }

    public function shareds()
    {
        return $this->hasMany(SharedItem::class);
    }


    public function getConnToAttribute()
    {
        if (!$this->connectionTo) {
            return null;
        }
        return RestoreHelper::getConnectionCloneOptions($this->connectionTo);
    }

    public function getConnFromAttribute()
    {
        if (!$this->connectionFrom) {
            return null;
        }
        return RestoreHelper::getConnectionCloneOptions($this->connectionFrom);
    }
}
