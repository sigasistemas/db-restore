<?php
/**
* Created by Claudio Campos.
* User: callcocam@gmail.com, contato@sigasmart.com.br
* https://www.sigasmart.com.br
*/

namespace Callcocam\DbRestore\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Callcocam\DbRestore\DbRestore
 */
class DbRestore extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Callcocam\DbRestore\DbRestore::class;
    }
}
