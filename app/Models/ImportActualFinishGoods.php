<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportActualFinishGoods extends Model
{
    // public $timestamps = false;
    protected $table = 'import_actual_finish_goods_tmp';
    protected $fillable = [
        'file_name',
        'urutan',
        'prod_code',
        'so_code',
        'article_code',
        'article_code_1',
        'qty_finish_goods'
    ];
}