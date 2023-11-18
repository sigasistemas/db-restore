<?php
/**
* Created by Claudio Campos.
* User: callcocam@gmail.com, contato@sigasmart.com.br
* https://www.sigasmart.com.br
*/
namespace Callcocam\DbRestore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Column extends AbstractModelRestore
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'restore_columns';

    protected $with = ['relation'];

    public function columnable()
    {
        return $this->morphTo();
    }

    public function relation()
    {
        return $this->belongsTo(Relation::class);
    }

    protected function slugTo()
    {
        return false;
    }
}
