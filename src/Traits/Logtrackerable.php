<?php
namespace Obd\Logtracker\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

trait Logtrackerable
{
    static protected $logTable = 'logtrackers';
    
    
    static function logToDatabase($model, $logType)
    {
        // if (!Session::has('user') || $model->excludeLogging) return;
        if ($logType == 'create') $originalData = json_encode($model);
        else {
            if (version_compare(app()->version(), '7.0.0', '>='))
            $originalData = json_encode($model->getRawOriginal()); // getRawOriginal available from Laravel 7.x
            else
            $originalData = json_encode($model->getOriginal());
        }
        
        

        $tableName = $model->getTable();
        $dateTime = date('Y-m-d H:i:s');

        /************This code only for mygov project (services)************/ 
        
        $d = json_decode($originalData);
        $serviceId = $d->id;
        $service_id = $tableName == 'service' ? $serviceId : '';
        // dd($service_id);
        /************End code only for mygov project (services)************/ 


        // $userId = auth()->user()->id;
        // $userId = auth()->check() ? auth()->user()->id : Session::get('user')['id']; //For SSO login Or Admin Login
        $userId = auth()->check() ? auth()->user()->id : Session::get('user')['id'] ?? 1; //For SSO login Or Admin Login

        DB::table(self::$logTable)->insert([
            'user_id'    => $userId,
            'log_date'   => $dateTime,
            'table_name' => $tableName,
            'log_type'   => $logType,
            'data'       => $originalData
        ]);
    }

    
    public static function bootLogtrackerable()
    {
        // When data updated
        self::updated(function ($model) {
            self::logToDatabase($model, 'edit');
        });

        // When Data deleted
        self::deleted(function ($model) {
            self::logToDatabase($model, 'delete');
        });


        // When data Created
        self::created(function ($model) {
            self::logToDatabase($model, 'create');
        });
        

    }
}
