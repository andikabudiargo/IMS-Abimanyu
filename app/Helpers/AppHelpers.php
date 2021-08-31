<?php
namespace App\Helpers;
use DB;

/*global function supaya bisa di panggil dari controller yang lain 
*/

class AppHelpers
{

    public static function resetCode($condKey){
        $afterEffect = DB::select("UPDATE 
            master_code set code_number = 1, 
            last_reset = now(),
            updated_at = now(),
            updated_by = 'system'
            WHERE 
            (date_part(reset_by,last_reset) <> date_part(reset_by,current_date::timestamp) or last_reset is null) and 
            code_key = '$condKey'");
        return $afterEffect;
    }

}