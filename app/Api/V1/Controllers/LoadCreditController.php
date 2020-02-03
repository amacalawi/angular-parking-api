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
use App\Model\LoadCredit;
use App\Model\Customer;

class LoadCreditController extends Controller
{
    private $carbon;

    public function __construct(Carbon $carbon)
    {   
        date_default_timezone_set('Asia/Manila');
        $this->carbon = $carbon;
    }

    public function index(Request $request, $id) 
    {   
        $res = LoadCredit::with([
            'transaction'
        ])
        ->where(['customer_id' => $id])
        ->orderBy('id', 'ASC')->get();

        $res = $res->map(function($credit) {
            return [
                'id' => $credit->id,
                'transaction_no' => $credit->transaction->transaction_no,
                'credit_amount' => $credit->credit_amount,
                'created_at' => $credit->created_at
            ];
        });

        return response()
        ->json([
            'status' => 'ok',
            'data' => $res
        ]);
    }
    
    // public function find(Request $request, $id)
    // {   
    //     $res = LoadCredit::find($id);
        
    //     if (!$res) {
    //         throw new NotFoundHttpException();
    //     }

    //     return response()
    //     ->json([
    //         'status' => 'ok',
    //         'data' => $res
    //     ]);
    // }   

    public function generateTransNo($transType)
    {
        $now = Carbon::now();
        if ($now->month < 10) {
            $month = '0'.$now->month;
        } else {
            $month = $now->month;
        }

        if ($now->day < 10) {
            $day = '0'.$now->day;
        } else {
            $day = $now->day;
        }

        $count = Transaction::where('created_at', 'like', '%'. $now->year .'-'. $month .'-'. $day .'%')
        ->where('transaction_type_id', $transType)
        ->get()->count();

        if ($transType == 1) {
            $transNo = 'P';
        } else if ($transType == 2) {
            $transNo = 'R';
        } else {
            $transNo = 'L';
        }

        $transNo .= '-'.substr( $now->year, -2).''.$month.''.$day.'-';

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

    public function create(Request $request, $id, $amount)
    {   
        $timestamp = $this->carbon::now();

        $trans =  Transaction::create([
            'customer_id' => $id,
            'transaction_type_id' => 3,
            'payment_type_id' => 1,
            'transaction_no' => $this->generateTransNo(3),                
            'total_amount' => $amount,
            'status' => 'completed',
            'created_at' => $timestamp,
            'created_by' => Auth::user()->id
        ]);

        if ($trans) {
            $res =  LoadCredit::create([
                'transaction_id' => $trans->id,
                'customer_id' => $id,
                'credit_amount' => $amount,
                'created_at' => $this->carbon::now(),
                'created_by' => Auth::user()->id
            ]);

            $cus = Customer::find($id);
            $cus->credits = floatval($cus->credits) + floatval($amount);
            $cus->update();

            if (!$res) {
                throw new NotFoundHttpException();
            }   

            return response()
            ->json([
                'status' => 'ok',
                'data' => $res,
                'message' => 
                [
                    'info' => 'Success!',
                    'text' => 'The information has been successfully saved.',
                    'type' => 'success'
                ]
            ]);
        } else {
            return response()
            ->json([
                'status' => 'ok',
                'data' => $trans,
                'message' => 
                [
                    'info' => 'Warning!',
                    'text' => 'The information has not been successfully saved.',
                    'type' => 'warning'
                ]
            ]);
        }
    }
}
