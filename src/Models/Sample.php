<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; 
use App\Models\AbstractModel;

class Sample extends AbstractModelRestore
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'restore_samples';

    protected $casts = [
        'columns' => 'array'
    ];
 



}