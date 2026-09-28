<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ArticleBulkUpdateExport implements WithMultipleSheets
{
    protected $columns;
    protected $articles;
    protected $accounts;
    protected $cashflowCategoryValues;

    public function __construct(array $columns, $articles = null, $accounts = null, array $cashflowCategoryValues = [])
    {
        $this->columns  = $columns;
        $this->articles = $articles ?? collect();
        $this->accounts = $accounts ?? collect();
        $this->cashflowCategoryValues = $cashflowCategoryValues;
    }

    public function sheets(): array
    {
        $sheets = [
            new ArticleBulkUpdateTemplateSheet($this->columns, $this->cashflowCategoryValues),
            new ArticleBulkUpdateArticleRefSheet($this->articles),
        ];

        if (in_array('coa', $this->columns)) {
            $sheets[] = new ArticleBulkUpdateCoaRefSheet($this->accounts);
        }

        if (in_array('cashflow_category', $this->columns)) {
            $sheets[] = new ArticleBulkUpdateCashflowGuideSheet();
        }

        return $sheets;
    }
}
