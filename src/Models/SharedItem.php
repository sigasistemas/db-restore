<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\DbRestore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Callcocam\DbRestore\Models\AbstractModelRestore;
use Callcocam\DbRestore\Models\Model as RestoreMomdel;

class SharedItem extends AbstractModelRestore
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'restore_shared_items';

    protected $appends = ['restore_momdel_name'];

    protected $with = ['shared', 'restoreMomdel'];

    public function shared()
    {
        return $this->belongsTo(Shared::class);
    }

    //Não executar no with, lupping infinito
    public function restore()
    {
        return $this->belongsTo(Restore::class);
    }

    public function restoreMomdel()
    {
        return $this->belongsTo(RestoreMomdel::class, 'restore_model_id');
    }

    public function getRestoreMomdelNameAttribute($value)
    {
        return $this->restoreMomdel?->name;
    }

    protected function slugTo()
    {
        return false;
    }
}
