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
use App\Model\Transaction;
use App\Model\Customer;

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

    public function create(Request $request, $id, $total_amount)
    {   
        $trans =  Transaction::create([
            'customer_id' => $id,
            'transaction_type_id' => 2,
            'payment_type_id' => 1,
            'transaction_no' => $this->generateTransNo(2),                
            'total_amount' => $total_amount,
            'status' => 'queued',
            'created_at' => $this->carbon::now(),
            'created_by' => Auth::user()->id
        ]);

        $res =  Subscription::create([
            'transaction_id' => $trans->id,
            'registration_date' => date('Y-m-d', strtotime($request->input('registration_date'))),
            'expiration_date' => date('Y-m-d', strtotime($request->input('expiration_date'))),
            'allowance_minute' => $request->input('allowance_minute'),
            'excess_rate_option' => $request->input('excess_rate_option'),
            'status' => 'draft',
            'created_at' => $this->carbon::now(),
            'created_by' => Auth::user()->id
        ]);
        
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
    }

    public function update(Request $request, $id, $total_amount)
    {
        $res = Subscription::find($id);

        if(!$res) {
            throw new NotFoundHttpException();
        }

        $trans = Transaction::find($res->transaction_id);
        $trans->total_amount = $total_amount;
        $trans->update();

        $res->registration_date = date('Y-m-d', strtotime($request->input('registration_date')));
        $res->expiration_date = date('Y-m-d', strtotime($request->input('expiration_date')));
        $res->allowance_minute = $request->input('allowance_minute');
        $res->excess_rate_option = $request->input('excess_rate_option');
        $res->updated_at = $this->carbon::now();
        $res->updated_by = Auth::user()->id;

        if ($res->update()) {
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
            throw new NotFoundHttpException();
        }
    }

    public function modify(Request $request, $id)
    {
        $res = Subscription::find($id);

        if(!$res) {
            throw new NotFoundHttpException();
        }

        $trans = Transaction::find($res->transaction_id);
        $trans->status = 'completed';
        $trans->is_paid = '1';

        $cus = Customer::find($trans->customer_id);
        $cus->status = 'subscribed';
        $cus->allowance_minute = $res->allowance_minute;
        $cus->excess_rate_option = $res->excess_rate_option;

        $res->status = 'valid';

        if ($res->update() && $trans->update() && $cus->update()) {
            return response()
            ->json([
                'status' => 'ok',
                'data' => $res,
                'message' => 
                [
                    'info' => 'Success!',
                    'text' => 'The information has been successfully modified.',
                    'type' => 'success'
                ]
            ]);
        }
    }

    public function delete(Request $request, $id)
    {
        $res = Subscription::find($id);

        if(!$res) {
            throw new NotFoundHttpException();
        }

        $trans = Transaction::find($res->transaction_id)->forceDelete();
        $res->forceDelete();

        return response()
        ->json([
            'status' => 'ok',
            'data' => $res,
            'message' => 
            [
                'info' => 'Success!',
                'text' => 'The information has been successfully deleted.',
                'type' => 'success'
            ]
        ]);
    }
}
