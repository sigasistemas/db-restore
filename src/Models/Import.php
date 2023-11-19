<?php
/**
* Created by Claudio Campos.
* User: callcocam@gmail.com, contato@sigasmart.com.br
* https://www.sigasmart.com.br
*/
namespace Callcocam\DbRestore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Callcocam\DbRestore\Models\Column;
use Callcocam\DbRestore\Models\Connection;
use Callcocam\DbRestore\Models\Model;

class Import extends AbstractModelRestore
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'restore_imports';

    protected $with = ['connectionFrom', 'connectionTo', 'columns', 'filters', 'orderings'];
    
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
}
