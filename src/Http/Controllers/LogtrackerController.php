<?php

namespace Obd\Logtracker\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Obd\Logtracker\Models\Logtracker;

class LogtrackerController extends Controller
{
    
    /**
     * @TODO: 
     *  - RETURN LOGS INFORMATION IN DETAILS
     *  - RETURN TABLE LIST OF THIS PROJECTS
     *  - RETURN USERS LIST OF THIS PROJECTS
     *  - RETURN SERVICE LIST ONLY FOR MY GOV PROJECTS
     *
     * @param Request $request
     * @return JSON
     */
    public function logApidata(Request $request)
    {
        // Fetch all table from the database
        $allTable = array_map('current', DB::select('SHOW TABLES'));

        // Exclude unnecessary table from the table list
        $exclude = ['failed_jobs', 'password_resets', 'migrations', 'logtrackers','personal_access_tokens'];
        
        // Prepare Executable table list 
        $tables = array_diff($allTable, $exclude);
        

        // Check action for filter
        if( !blank(request('action')) ) {
            $this->validate($request, [
                'action'    => 'required|string'
            ]);
        }
        
        // Fetch logable data
        $data = Logtracker::orderBy('id', 'desc')->get();
        

        // Check conditions
        
        if ($request->has('log_type') && !is_null(request('log_type'))) {
            $data = $data->where('log_type', request('log_type'));
        }
        if ($request->has('table')  && !is_null(request('table'))) {
            $data = $data->where('table_name', request('table'));
        }
        if (($request->has('from_date') && $request->has('to_date'))  && (!is_null(request('from_date')) && !is_null(request('to_date')))) {
            $from = request('from_date') . " 00:00:00";
            $to = request('to_date') . " 23:59:59";
            $data = $data->whereBetween('log_date', [$from, $to]);
        }

        // Data formatting
        $logs = $data->map(function($data) {
            return [
                'id' => $data->id,
                'user_id' => $data->user_id,
                'username' => $data->user_id,
                'log_date' => $data->log_date->format('Y-m-d'),
                'human_date' => $data->log_date->diffForHumans(),
                'table_name' => $data->table_name,
                'log_type' => $data->log_type,
                'log_details' => json_encode($data->data),
                'details' => $data->data,
            ];
        });

        return response()->json(['data' => $logs, 'tables' => $tables],200);
    }

    
    /*************This two method only for Mongo Database************/

    /**
     * @ TODO
     * @ Return only unsynchronous data
     *
     * @return json
     */
    public function getUnsynchronousData()
    {
        $synchronous = Logtracker::where('synchronous',0)->get();
        return response()->json(['data' => $synchronous],200);
    }

    /**
     * @ TODO
     * @ Need to change synchronous field false to true
     *
     * @param Request $request
     * @return string
     */
    public function synchronousProcess(Request $request)
    {
        DB::table('logs')->where('id',$request->id)->update([
            'synchronous' => $request->synchronous
        ]);
        return response()->json(['message' => 'success'],200);
    }


    /**************Only for Google Analytic Reports***************/

    public function googleAnalyticData()
    {
        $analyticsData = Analytics::fetchVisitorsAndPageViews(Period::days(30));
        
        $mostVisitedPage = Analytics::fetchMostVisitedPages(Period::days(7));
        
        $TopReferrers = Analytics::fetchTopReferrers(Period::days(7));
        
        $chart = Analytics::fetchUserTypes(Period::days(7));
        
        $chartData = [
            'NewVisitor' => $chart[0]['sessions'] ?? 1,
            'ReturningVisitor' => $chart[1]['sessions'] ?? 2
        ];

        return response()->json(['analyticsData' => $analyticsData, 'mostVisitedPage' => $mostVisitedPage, 'TopReferrers' => $TopReferrers, 'chartData' => $chartData],200);

        return view('auditpanel.analytic-dashboard.index', [
            'analyticsData' => $analyticsData,
            'chartData'=>json_encode($chartData),
            'mostVisitedPage' => $mostVisitedPage,
            'TopReferrers' => $TopReferrers,
        ]);
    }

}
