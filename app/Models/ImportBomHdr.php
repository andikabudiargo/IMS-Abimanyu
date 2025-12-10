<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportBomHdr extends Model
{
    public $timestamps = false;
    protected $table = 'bom_upload_tmp';
    protected $fillable = [
        'customer',
        'article_code_fg',
        'article_code_rm',
        'article_code',
        'note',
        'part_no',
        'qty',
        'uom',
        'uom_con',
        'article_type',
        'urutan',
        'pos',
        'tone'
    ];
}