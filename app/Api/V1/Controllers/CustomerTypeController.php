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
use App\Model\CustomerType;
use App\Model\SubscriptionRate;

class CustomerTypeController extends Controller
{
    private $carbon;

    public function __construct(Carbon $carbon)
    {
        $this->carbon = $carbon;
    }

    public function index(Request $request, $keywords) 
    {   
        if ($keywords == 'not-rate') {
            $res = CustomerType::whereNotIn('id', SubscriptionRate::get(['customer_type_id']))->where('is_active', 1)->orderBy('id', 'ASC')->get();
        } else {
            $res = CustomerType::where('is_active', 1)->orderBy('id', 'ASC')->get();
        }

        return response()
        ->json([
            'status' => 'ok',
            'data' => $res
        ]);
    }
    
    public function filter(Request $request, $id)
    {   
        $res = CustomerType::whereNotIn('id', SubscriptionRate::where('id', '!=', $id)->get(['customer_type_id']))->where('is_active', 1)->orderBy('id', 'ASC')->get();
        
        if (!$res) {
            throw new NotFoundHttpException();
        }

        return response()
        ->json([
            'status' => 'ok',
            'data' => $res
        ]);
    }   

}
