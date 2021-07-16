<?php


namespace App\Helpers;
use Request;
use App\Models\LogActivity as LogActivityModel;

class LogActivity
{
    public static function addToLog($subject,$description=null)
    {
    	$log = [];
    	$log['subject'] = $subject;
		$log['description'] = $description;
    	$log['url'] = Request::fullUrl();
    	$log['method'] = Request::method();
    	$log['ip'] = Request::getClientIp();
    	$log['agent'] = Request::header('user-agent');
    	$log['user_id'] = auth()->check() ? auth()->user()->username : 1;
    	LogActivityModel::create($log);
    }

    public static function logActivityLists()
    {
    	return LogActivityModel::latest()->get();
    }
}