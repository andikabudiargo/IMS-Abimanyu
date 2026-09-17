<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleBulkUpdateStake extends Model
{
    public $timestamps = false;
    protected $table = 'article_bulk_update_tmp';
    protected $fillable = [
        'batch_id', 'article_code', 'safety_stock', 'coa', 'min_package'
    ];
}
