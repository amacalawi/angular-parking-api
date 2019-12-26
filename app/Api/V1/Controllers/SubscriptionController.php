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
use App\Model\Subscription;

class SubscriptionController extends Controller
{
    private $carbon;

    public function __construct(Carbon $carbon)
    {   
        date_default_timezone_set('Asia/Manila');
        $this->carbon = $carbon;
    }
    
    public function find(Request $request, $id)
    {   
        $res = Subscription::with([
            'transaction'
        ])
        ->whereHas('transaction', function($query) use ($id) {
            $query->where('customer_id', '=', $id);
        })
        ->orderBy('id', 'ASC')->get();
        
        if (!$res) {
            throw new NotFoundHttpException();
        }

        $res = $res->map(function($subs) {
            return [
                'id' => $subs->id,
                'transaction_no' => $subs->transaction->transaction_no,
                'customer_id' => $subs->transaction->customer_id,
                'total_amount' => $subs->transaction->total_amount,
                'registration_date' => $subs->registration_date,
                'expiration_date' => $subs->expiration_date,
                'excess_rate_option' => $subs->excess_rate_option,
                'allowance_minute' => $subs->allowance_minute,
                'status' => $subs->status,
                'created_at' => $subs->created_at,
                'updated_at' => $subs->updated_at,
                'is_active' => $subs->is_active
            ];
        });
        
        return response()
        ->json([
            'status' => 'ok',
            'data' => $res
        ]);
    }   
}
