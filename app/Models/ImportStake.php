<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportStake extends Model
{
    // public $timestamps = false;
    protected $table = 'import_stock_take_tmp';
    protected $fillable = [
        'file_name','article_code','qty'
    ];
}