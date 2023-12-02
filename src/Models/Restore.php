<?php
/**
* Created by Claudio Campos.
* User: callcocam@gmail.com, contato@sigasmart.com.br
* https://www.sigasmart.com.br
*/
namespace Callcocam\DbRestore\Models;

use Callcocam\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory; 

class Restore extends AbstractModelRestore
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'restores';

    protected $with = ['connectionFrom', 'connectionTo'];

    protected $appends = ['connTo', 'connFrom', 'tableToOptions'];
    
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
}
