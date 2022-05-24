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
        
        $lists['decimalPlaces'] = Attribute::where('attr_code','decimalPlaces')->value('attr_value');
        $lists['vatValue'] = Attribute::where('attr_code','mainppn')->value('attr_value');
        $lists['pph23'] = Attribute::where('attr_code','mainpph23')->value('attr_value');

        $view->with($lists);
    }
}