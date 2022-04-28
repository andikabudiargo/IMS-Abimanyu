<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalMaster extends Model
{
    protected $table = 'approval_master';

    protected $fillable = [
        'module_code',
        'approval_number',
        'note',
        'module_name',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at'
    ];

     /*
        kalo edit mau pakai field lain selain id
    */
    public function getRouteKeyName()
    {
        return 'module_code';
    }
}
