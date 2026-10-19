<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class TrialBalanceGridExport implements FromView, ShouldAutoSize
{
    protected $year;
    protected $accounts;
    protected $groups;
    protected $monthlyData;
    protected $months;

    public function __construct($year, $accounts, $groups, $monthlyData, $months)
    {
        $this->year = $year;
        $this->accounts = $accounts;
        $this->groups = $groups;
        $this->monthlyData = $monthlyData;
        $this->months = $months;
    }

    public function view(): View
    {
        return view('accounting.trialBalance.excel', [
            'year' => $this->year,
            'accounts' => $this->accounts,
            'groups' => $this->groups,
            'monthlyData' => $this->monthlyData,
            'months' => $this->months,
        ]);
    }
}
