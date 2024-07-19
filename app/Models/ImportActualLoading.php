<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportActualLoading extends Model
{
    // public $timestamps = false;
    protected $table = 'import_actual_loading_tmp';
    protected $fillable = [
        'file_name',
        'urutan',
        'wo_code',
        'article_code',
        'qty_fresh',
        'qty_repaint',
        'qty_tag'
    ];
}