<?php
/**
* Created by Claudio Campos.
* User: callcocam@gmail.com, contato@sigasmart.com.br
* https://www.sigasmart.com.br
*/
namespace Callcocam\DbRestore\Models;

use Callcocam\DbRestore\Helpers\RestoreHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Callcocam\DbRestore\Models\AbstractModelRestore;
use Callcocam\DbRestore\Models\Column;
use Callcocam\DbRestore\Models\Connection;
use Callcocam\DbRestore\Models\Filter;
use Callcocam\DbRestore\Models\Model;
use Callcocam\Tenant\Models\Tenant;

class Export extends AbstractModelRestore
{
    use HasFactory;

    protected $guarded = ['id'];
 
    protected $table = 'restore_exports';

    protected $with = ['connectionFrom', 'connectionTo'];   

    protected $appends = ['connTo', 'connFrom', 'tableToOptions'];
    
    protected static function booted()
    {
        static::deleting(function (Export $model) {  
            $model->columns()->forceDelete();
            $model->filters()->forceDelete();
            $model->orderings()->forceDelete();
            $model->childrens()->forceDelete();
            $model->pivots()->forceDelete(); 
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
    
    public function connectionTo()
    {
        return $this->belongsTo(Connection::class , 'connection_id');
    }
    
    public function connectionFrom()
    {
        return $this->belongsTo(Connection::class , 'connection_id');
    }
    
    public function columns()
    {
        return $this->morphMany(Column::class, 'columnable');
    }

    public function restoreModel()
    {
        return $this->belongsTo(Model::class, 'restore_model_id');
    }
    
    public function filters()
    {
        return $this->morphMany(Filter::class, 'filterable');
    }

    public function orderings()
    {
        return $this->morphMany(Ordering::class, 'orderingable')->orderBy('ordering', 'ASC');
    }

    public function pivots()
    {
        return $this->hasMany(Pivot::class, 'pivotable');
    }
    
    public function childrens()
    {
        return $this->morphMany(Children::class, 'childrenable');
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
