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
use App\Model\FixedRate;

class FixedRateController extends Controller
{
    private $carbon;

    public function __construct(Carbon $carbon)
    {
        $this->carbon = $carbon;
    }

    public function index(Request $request, $keywords) 
    {   
        if ($keywords == 'all') {
            $res = FixedRate::with([
                'vehicle'
            ])->orderBy('id', 'ASC')->get();

            $res = $res->map(function($fixrate) {
                return [
                    'id' => $fixrate->id,
                    'vehicle_name' => $fixrate->vehicle->name,
                    'validity_minute' => $fixrate->validity_minute,
                    'fixed_rate' => $fixrate->fixed_rate,
                    'excess_rate_per_minute' => $fixrate->excess_rate_per_minute,
                    'created_at' => $fixrate->created_at,
                    'updated_at' => $fixrate->updated_at,
                    'is_active' => $fixrate->is_active
                ];
            });
        } else {
            $res = FixedRate::where('is_active', 1)->orderBy('id', 'ASC')->get();
        }

        return response()
        ->json([
            'status' => 'ok',
            'data' => $res
        ]);
    }
    
    public function find(Request $request, $id)
    {   
        $res = FixedRate::find($id);
        
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
        $res =  FixedRate::create([
            'vehicle_id' => $request->input('vehicle_id'),
            'validity_minute' => $request->input('validity_minute'),
            'fixed_rate' => $request->input('fixed_rate'),
            'excess_rate_per_minute' => $request->input('excess_rate_per_minute'),
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
        $res = FixedRate::find($id);

        if(!$res) {
            throw new NotFoundHttpException();
        }

        $res->code = $request->input('code');
        $res->name = $request->input('name') ;
        $res->description = $request->input('description') ;
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
        $res = FixedRate::find($id);

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
