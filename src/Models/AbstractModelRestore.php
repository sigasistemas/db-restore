<?php
/**
* Created by Claudio Campos.
* User: callcocam@gmail.com, contato@sigasmart.com.br
* https://www.sigasmart.com.br
*/
namespace Callcocam\DbRestore\Models;

use Callcocam\Acl\Traits\HasUlids;
use Callcocam\DbRestore\Helpers\DataBaseHelper;
use Callcocam\DbRestore\Helpers\RestoreHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tall\Sluggable\HasSlug;
use Tall\Sluggable\SlugOptions;

class AbstractModelRestore extends Model
{
    use HasFactory, HasUlids, SoftDeletes, HasSlug;

     

     /**
     * @return SlugOptions
     */
    public function getSlugOptions()
    {
        if (is_string($this->slugTo())) {
            return SlugOptions::create()
                ->generateSlugsFrom($this->slugFrom())
                ->saveSlugsTo($this->slugTo());
        }
    }

    
    public function getConnFromAttribute()
    {
        if (!$this->connectionFrom) {
            return [];
        }
        return RestoreHelper::getConnectionCloneOptions($this->connectionFrom);
    }

    public function getTableToOptionsAttribute()
    {
        if (!$this->connFrom) {
            return [];
        }
        return DataBaseHelper::getTables($this->connFrom);
    }
}
