<?php

namespace App\Http\ViewComposers;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\Attribute;
use DB;

class GlobalSettingComposer
{
    public function compose(View $view)
    {
        $username =  Auth::user() ? Auth::user()->username : '';
        
        $lists['decimalPlaces'] = Attribute::where('attr_id','maindecimalPlaces')->value('attr_value');
        $lists['vatValue'] = Attribute::where('attr_id','mainppn')->value('attr_value');
        $lists['pph23Value'] = Attribute::where('attr_id','mainpph23')->value('attr_value');
        $lists['termValue'] = Attribute::where('attr_id','mainterm')->value('attr_value');
        $lists['currentDateValue'] = date('d-m-Y');

        $view->with($lists);
    }
}