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

    public function index() 
    {
        $res = Vehicle::orderBy('id', 'ASC')->get();

        return response()
        ->json([
            'status' => 'ok',
            'data' => $res
        ]);
    }
    
}
