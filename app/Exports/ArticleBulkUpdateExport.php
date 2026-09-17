<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ArticleBulkUpdateExport implements WithMultipleSheets
{
    protected $columns;
    protected $articles;
    protected $accounts;

    public function __construct(array $columns, $articles = null, $accounts = null)
    {
        $this->columns  = $columns;
        $this->articles = $articles ?? collect();
        $this->accounts = $accounts ?? collect();
    }

    public function sheets(): array
    {
        $sheets = [
            new ArticleBulkUpdateTemplateSheet($this->columns),
            new ArticleBulkUpdateArticleRefSheet($this->articles),
        ];

        if (in_array('coa', $this->columns)) {
            $sheets[] = new ArticleBulkUpdateCoaRefSheet($this->accounts);
        }

        return $sheets;
    }
}
