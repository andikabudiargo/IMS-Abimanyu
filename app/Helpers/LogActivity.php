<?php
namespace App\Helpers;
use Request;
use App\Models\LogActivity as LogActivityModel;
use DB;

class LogActivity
{
    public static function addToLog($subject,$description=null)
    {
    	$log = [];
    	$log['subject'] = $subject;
		$log['description'] = $description;
    	$log['url'] = substr(Request::fullUrl(),250);
    	$log['method'] = Request::method();
    	$log['ip'] = Request::getClientIp();
    	$log['agent'] = Request::header('user-agent');
    	$log['user_id'] = auth()->check() ? auth()->user()->username : 1;
    	LogActivityModel::create($log);
    }

    public static function logActivityLists()
    {
    	return LogActivityModel::where('created_at','>',db::raw("CURRENT_DATE - INTERVAL '1 months'"))->latest()->get();
    }
}