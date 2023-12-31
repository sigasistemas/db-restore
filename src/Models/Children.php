<?php
/**
* Created by Claudio Campos.
* User: callcocam@gmail.com, contato@sigasmart.com.br
* https://www.sigasmart.com.br
*/
namespace Callcocam\DbRestore\Models;

use Callcocam\DbRestore\Traits\WithColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Children extends AbstractModelRestore
{
    use HasFactory ;

    protected $guarded = ['id'];

    protected $table = 'restore_childrens';

    protected $with = ['restore'];

    public function childrenable()
    {
        return $this->morphTo();
    }

    public function restore()
    {
        return $this->belongsTo(Restore::class, 'chidrenable_id');
    }

    public function columns()
    {
        return $this->morphMany(Column::class, 'columnable');
    }

    public function filters()
    {
        return $this->morphMany(Filter::class, 'filterable');
    }

    public function pivots()
    {
        return $this->hasMany(Pivot::class, 'pivotable');
    }
    
    public function orderings()
    {
        return $this->morphMany(Ordering::class, 'orderingable')->orderBy('ordering', 'ASC');
    }
}
