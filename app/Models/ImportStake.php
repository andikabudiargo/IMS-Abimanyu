<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportStake extends Model
{
    protected $table = 'import_stock_take_tmp';
    protected $fillable = [
        'article_code', 'article_qty'
    ];
}