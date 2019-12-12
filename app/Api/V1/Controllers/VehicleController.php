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
use App\Model\Vehicle;

class VehicleController extends Controller
{
    private $carbon;

    public function __construct(Carbon $carbon)
    {
        $this->carbon = $carbon;
    }

    public function index(Request $request, $keywords) 
    {   
        if ($keywords == 'all') {
            $res = Vehicle::orderBy('id', 'ASC')->get();
        } else {
            $res = Vehicle::where('is_active', 1)->orderBy('id', 'ASC')->get();
        }

        return response()
        ->json([
            'status' => 'ok',
            'data' => $res
        ]);
    }
    
    public function find(Request $request, $id)
    {   
        $res = Vehicle::find($id);
        
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
        $res =  Vehicle::create([
            'code' => $request->input('code'),
            'name' => $request->input('name'),
            'description' => $request->input('description'),
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
        $res = Vehicle::find($id);

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
}
