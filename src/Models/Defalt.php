<?php
/**
* Created by Claudio Campos.
* User: callcocam@gmail.com, contato@sigasmart.com.br
* https://www.sigasmart.com.br
*/
namespace Callcocam\DbRestore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;  
use Callcocam\DbRestore\Models\AbstractModelRestore;

class Defalt extends AbstractModelRestore
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'restore_defalts';

    public function defaultable()
    {
        return $this->morphTo();
    }

    // protected $casts = [
    //     'column_value' => 'array'
    // ];

    protected function slugTo()
    {
        return false;
    }
}