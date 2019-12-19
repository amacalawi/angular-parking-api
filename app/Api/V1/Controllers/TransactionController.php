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
use App\Model\Transaction;
use App\Model\CustomerType;
use App\Model\Customer;

class TransactionController extends Controller
{
    private $carbon;

    public function __construct(Carbon $carbon)
    {
        $this->carbon = $carbon;
    }

    public function index(Request $request, $keywords) 
    {   
        if ($keywords == 'all') {
            $res = Transaction::orderBy('id', 'ASC')->get();
        } elseif ($keywords == 'all-queued-parking') {
            $res = Transaction::with([
                'customer.type' 
            ])->where([
                'status' => 'queued',
                'transaction_type_id' => '1',
                'is_active' => 1
            ])->orderBy('id', 'ASC')->get();
    
            $res = $res->map(function($trans) {
                return [
                    'id' => $trans->id,
                    'transaction_no' => $trans->transaction_no,
                    'customer_id' => $trans->customer->id,
                    'customer_name' => $trans->customer->firstname,
                    'type' => $trans->customer->customer_type_id,
                    'color' => $trans->customer->type->badges_color,
                    'created_at' => $trans->created_at
                ];
            });
        } else {
            $res = Transaction::where('is_active', 1)->orderBy('id', 'ASC')->get();
        }

        return response()
        ->json([
            'status' => 'ok',
            'data' => $res
        ]);
    }   

    public function generateTransNo($transType)
    {
        $now = Carbon::now();
        $count = Transaction::where('created_at', 'like', '%'. $now->year .'-'. $now->month .'-'. $now->day .'%')
        ->where('transaction_type_id', $transType)
        ->get()->count();

        if ($transType == 1) {
            $transNo = 'P';
        } else {
            $transNo = 'R';
        }
        
        $transNo .= '-'.substr( $now->year, -2).''.$now->month.''.$now->day.'-';

        if($count < 9)
        {
            return $transNo .= '0000'.($count + 1);
        } 
        else if($count < 99)
        {
            return $transNo .= '000'.($count + 1);
        }
        else if($count < 999)
        {
            return $transNo .= '00'.($count + 1);
        }
        else if($count < 9999)
        {
            return $transNo .= '0'.($count + 1);
        } 
        else {
            return $transNo .= ''.($count + 1);
        }
    }

    public function create(Request $request, $rfid)
    { 
        $res = Customer::where([
            'rfid_no' => $rfid,
            'status' => 'subscribed'   
        ])->get();

        if (!$res) {
            return response()
            ->json([
                'status' => 'not',
                'data' => $res
            ]);
        } else {

            $res = Transaction::with([
                'customer'
            ])->where([
                'status' => 'queued',
                'customer_id' => Customer::where('rfid_no', $rfid)->first()->id
            ])->get();
            
            if ($res->count() > 0) {
                return response()
                ->json([
                    'status' => 'not',
                    'data' => $res
                ]);
            }
           
            $trans =  Transaction::create([
                'customer_id' => Customer::where('rfid_no', $rfid)->first()->id,
                'transaction_type_id' => 1,
                'payment_type_id' => 1,
                'transaction_no' => $this->generateTransNo(1),                
                'total_amount' => 0,
                'status' => 'queued',
                'created_at' => $this->carbon::now(),
                'created_by' => 0
            ]);

            return response()
            ->json([
                'status' => 'ok',
                'data' => $trans
            ]);
        }
    }
}
