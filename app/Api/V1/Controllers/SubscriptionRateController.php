<?php

namespace App\Api\V1\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Api\V1\Requests\CreateEventRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Auth;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Model\SubscriptionRate;

class SubscriptionRateController extends Controller
{
    private $carbon;

    public function __construct(Carbon $carbon)
    {   
        date_default_timezone_set('Asia/Manila');
        $this->carbon = $carbon;
    }

    public function index(Request $request, $keywords) 
    {   
        if ($keywords == 'all') {
            $res = SubscriptionRate::with([
                'type'
            ])->orderBy('id', 'ASC')->get();

            $res = $res->map(function($rate) {
                return [
                    'id' => $rate->id,
                    'customer_type' => $rate->type->name,
                    'starting_period' => date('h:i A', strtotime($rate->starting_period)),
                    'ending_period' => date('h:i A', strtotime($rate->ending_period)),
                    'subscription_rate' => $rate->subscription_rate,
                    'excess_rate_per_minute' => $rate->excess_rate_per_minute,
                    'excess_rate_per_hour' => $rate->excess_rate_per_hour,
                    'created_at' => $rate->created_at,
                    'updated_at' => $rate->updated_at,
                    'is_active' => $rate->is_active
                ];
            });
        } else {
            $res = SubscriptionRate::where('is_active', 1)->orderBy('id', 'ASC')->get();
        }

        return response()
        ->json([
            'status' => 'ok',
            'data' => $res
        ]);
    }
    
    public function find(Request $request, $id)
    {   
        $res = SubscriptionRate::find($id);
        
        if (!$res) {
            throw new NotFoundHttpException();
        }

        return response()
        ->json([
            'status' => 'ok',
            'data' => $res
        ]);
    }   

    public function create(Request $request)
    {   
        $res =  SubscriptionRate::create([
            'customer_type_id' => $request->input('customer_type_id'),
            'starting_period' => date('H:i', strtotime($request->input('starting_period'))),
            'ending_period' => date('H:i', strtotime($request->input('ending_period'))),
            'subscription_rate' => $request->input('subscription_rate'),
            'excess_rate_per_minute' => $request->input('excess_rate_per_minute'),
            'excess_rate_per_hour' => $request->input('excess_rate_per_hour'),
            'created_at' => $this->carbon::now(),
            'created_by' => Auth::user()->id
        ]);
        
        if (!$res) {
            throw new NotFoundHttpException();
        }        

        return response()
        ->json([
            'status' => 'ok',
            'data' => $res
        ]);
    }
    
    public function update(Request $request, $id)
    {
        $res = SubscriptionRate::find($id);

        if(!$res) {
            throw new NotFoundHttpException();
        }

        $res->customer_type_id = $request->input('customer_type_id');
        $res->starting_period = date('H:i', strtotime($request->input('starting_period')));
        $res->ending_period = date('H:i', strtotime($request->input('ending_period')));
        $res->subscription_rate = $request->input('subscription_rate');
        $res->excess_rate_per_minute = $request->input('excess_rate_per_minute');
        $res->excess_rate_per_hour = $request->input('excess_rate_per_hour');
        $res->updated_at = $this->carbon::now();
        $res->updated_by = Auth::user()->id;

        if ($res->update()) {
            return response()
            ->json([
                'status' => 'ok',
                'data' => $res
            ]);
        } else {
            throw new NotFoundHttpException();
        }
    }

    public function modify(Request $request, $id)
    {
        $res = SubscriptionRate::find($id);

        if(!$res) {
            throw new NotFoundHttpException();
        }

        $res->is_active = ($res->is_active == 0) ? 1 : 0;
        $res->updated_at = $this->carbon::now();
        $res->updated_by = Auth::user()->id;

        if ($res->update()) {
            return response()
            ->json([
                'status' => 'ok',
                'data' => $res
            ]);
        } else {
            throw new NotFoundHttpException();
        }
    }
}
