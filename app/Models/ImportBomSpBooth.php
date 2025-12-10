<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportBomSpBooth extends Model
{
    public $timestamps = false;
    protected $table = 'bom_spray_booth_upload_tmp';
    protected $fillable = [
        'customer',
        'article_code_fg',
        'article_code_rm',
        'spray_booth',
        'tone',
        'tack',
        'pass_rate',
        'pass_thru',
        'cycle_time',
        'urutan'
    ];
}