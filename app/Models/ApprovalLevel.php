<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalLevel extends Model
{
    protected $primaryKey = null;
    public $incrementing = false;
    
    protected $table = 'approval_level';

    protected $fillable = [
        'module_code',
        'approval_order',
        'username',
        'job_position',
        'departement',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at'
    ];

    /*
        kalo edit mau pakai field lain selain id
    */
    // public function getRouteKeyName()
    // {
    //     return 'module_code';
    // }
}
