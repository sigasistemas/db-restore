<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Callcocam\DbRestore\Models\AbstractModelRestore;

class SharedItem extends AbstractModelRestore
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'restore_shared_items';

    protected $with = ['shared'];

    public function shared()
    {
        return $this->belongsTo(Shared::class);
    }

    public function restore()
    {
        return $this->belongsTo(Restore::class);
    }

    protected function slugTo()
    {
        return false;
    }
}
