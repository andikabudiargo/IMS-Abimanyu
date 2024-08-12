<?php

namespace App\Imports;

use App\Models\ImportActualFinishGoods;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use DB;
 
class ActualFinishGoodsImport implements ToModel, WithStartRow,WithHeadingRow
{
    private $data;

    public function __construct(array $data = [])
    {
        $this->data = $data; 
    }

    public function model(array $row)
    {
        return new ImportActualFinishGoods([
            'file_name' => $this->data['filename'],
            'urutan' => $row['no'],
            'prod_code' => $row['prod_code'],
            'so_code' => $row['sales_order'],
            'article_code' => $row['article_code'],
            'article_code_1' => db::table('article')->where('article_alternative_code',$row['article_code'])->value('article_code'),
            'qty_finish_goods' => $row['qty_finish_goods']
        ]);
    }

    public function startRow(): int
    {
        return 2;
    }
}