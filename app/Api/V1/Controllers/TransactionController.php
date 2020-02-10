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
use App\Model\TransactionDetail;

class TransactionController extends Controller
{
    private $carbon;

    public function __construct(Carbon $carbon)
    {
        date_default_timezone_set('Asia/Manila');
        $this->carbon = $carbon;
    }

    public function convertToHoursMins($time, $format = '%02d:%02d') 
    {
        $hours = floor($time / 60);
        $minutes = ($time % 60);
        return sprintf($format, $hours, $minutes);
    }

    public function index(Request $request, $keywords) 
    {   
        if ($keywords == 'all') {
            $res = Transaction::orderBy('id', 'ASC')->get();
        } elseif ($keywords == 'all-queued-parking') {
            $res = Transaction::with([
                'customer.type.subrate',
                'detail.vehicle.fixrate' 
            ])->where([
                'status' => 'queued',
                'transaction_type_id' => '1',
                'is_active' => 1
            ])->orderBy('id', 'ASC')->get();
    
            $res = $res->map(function($trans) {
                if ($trans->customer->subscriber_rate_option == 'SUB_RATE') {
                    return [
                        'id' => $trans->id,
                        'rfid_no' => $trans->customer->rfid_no,
                        'transaction_no' => $trans->transaction_no,
                        'customer_id' => $trans->customer->id,
                        'customer_name' => $trans->customer->firstname,
                        'customer_type' => $trans->customer->type->name,
                        'payment_type_id' => $trans->customer->payment_type_id,
                        'credits' => $trans->customer->credits,
                        'type' => $trans->customer->customer_type_id,
                        'color' => $trans->customer->type->badges_color,
                        'created_at' => $trans->created_at,
                        'vehicle_id' => $trans->detail->vehicle->id,
                        'vehicle_name' => $trans->detail->vehicle->name,
                        'plate_no' => $trans->detail->plate_no,
                        'model' => $trans->detail->model,
                        'timed_in' => $trans->detail->timed_in,
                        'timed_allowance' => $this->convertToHoursMins($trans->customer->allowance_minute, '%02d:%02d'),
                        'vehicle_rate' => $trans->customer->type->subrate->subscription_rate,
                        'validity' => $this->convertToHoursMins(0, '%02d:%02d'),
                        'excess_option' => $trans->customer->excess_rate_option,
                        'excess_amount_multiplier' => ($trans->customer->excess_rate_option == 'EX_PER_MIN') ? $trans->customer->type->subrate->excess_rate_per_minute : $trans->customer->type->subrate->excess_rate_per_hour,
                        'rate_option' => $trans->customer->subscriber_rate_option,
                        'starting_period' =>  date('H:i', strtotime($trans->customer->type->subrate->starting_period)),
                        'ending_period' =>  date('H:i', strtotime($trans->customer->type->subrate->ending_period))
                    ];
                } else {
                    return [
                        'id' => $trans->id,
                        'rfid_no' => $trans->customer->rfid_no,
                        'transaction_no' => $trans->transaction_no,
                        'customer_id' => $trans->customer->id,
                        'customer_name' => $trans->customer->firstname,
                        'customer_type' => $trans->customer->type->name,
                        'payment_type_id' => $trans->customer->payment_type_id,
                        'credits' => $trans->customer->credits,
                        'type' => $trans->customer->customer_type_id,
                        'color' => $trans->customer->type->badges_color,
                        'created_at' => $trans->created_at,
                        'vehicle_id' => $trans->detail->vehicle->id,
                        'vehicle_name' => $trans->detail->vehicle->name,
                        'plate_no' => $trans->detail->plate_no,
                        'model' => $trans->detail->model,
                        'timed_in' => $trans->detail->timed_in,
                        'timed_allowance' => $this->convertToHoursMins($trans->customer->allowance_minute, '%02d:%02d'),
                        'vehicle_rate' => $trans->detail->vehicle->fixrate->fixed_rate,
                        'validity' => $this->convertToHoursMins($trans->detail->vehicle->fixrate->validity_minute, '%02d:%02d'),
                        'excess_option' => $trans->customer->excess_rate_option,
                        'excess_amount_multiplier' => ($trans->customer->excess_rate_option == 'EX_PER_MIN') ? $trans->detail->vehicle->fixrate->excess_rate_per_minute : $trans->detail->vehicle->fixrate->excess_rate_per_hour,
                        'rate_option' => $trans->customer->subscriber_rate_option,
                        'starting_period' => '00:00',
                        'ending_period' => '00:00'
                    ];
                }
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

    public function create(Request $request, $rfid)
    {   
        $timestamp = $this->carbon::now();

        $cus = Customer::where([
            'rfid_no' => $rfid,
            'status' => 'subscribed'   
        ])->get();

        if (!$cus) {
            return response()
            ->json([
                'status' => 'not',
                'data' => $cus,
                'notify' => null
            ]);
        } else {
            if ($cus->first()->customer_type_id != 4) { 
                $res = Transaction::with([
                    'customer'
                ])->where([
                    'status' => 'queued',
                    'customer_id' => $cus->first()->id
                ])->get();
                
                if ($res->count() > 0) {
                    return response()
                    ->json([
                        'status' => 'not',
                        'data' => $res
                    ]);
                }
            
                $trans =  Transaction::create([
                    'customer_id' => $cus->first()->id,
                    'transaction_type_id' => 1,
                    'payment_type_id' => 1,
                    'transaction_no' => $this->generateTransNo(1),                
                    'total_amount' => 0,
                    'status' => 'queued',
                    'created_at' => $timestamp,
                    'created_by' => 0
                ]);

                if ($trans) {
                    $trans_detail = TransactionDetail::create([
                        'transaction_id' => $trans->id,
                        'vehicle_id' => $cus->first()->vehicle_id,
                        'plate_no' => $cus->first()->plate_no,
                        'model' => $cus->first()->model,
                        'timed_in' => $timestamp
                    ]);

                    return response()
                    ->json([
                        'status' => 'ok',
                        'data' => $trans,
                        'notify' => true
                    ]);
                }
            } 
            else {

                $res = Transaction::with([
                    'customer'
                ])->where([
                    'status' => 'queued',
                    'customer_id' => $cus->first()->id
                ])->get();
                
                if ($res->count() > 0) {
                    return response()
                    ->json([
                        'status' => 'not',
                        'data' => $res
                    ]);
                }

                $cus = $cus->map(function($customer) {
                    return [
                        'rfid_no' => $customer->rfid_no,
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->firstname,
                        'timed_in' => date('H:i', strtotime($this->carbon::now()))
                    ];
                });

                return response()
                ->json([
                    'status' => 'ok',
                    'data' => $cus,
                    'notify' => false
                ]);
            }
        }
    }

    public function checkin(Request $request, $rfid)
    {   
        $timestamp = $this->carbon::now();

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
            'created_at' => $timestamp,
            'created_by' => Auth::user()->id
        ]);

        if ($trans) {
            $trans_detail = TransactionDetail::create([
                'transaction_id' => $trans->id,
                'vehicle_id' => $request->input('vehicle_id'),
                'plate_no' => $request->input('plate_no'),
                'model' => $request->input('model'),
                'timed_in' => $timestamp
            ]);

            return response()
            ->json([
                'status' => 'ok',
                'data' => $trans,
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
                'status' => 'not',
                'data' => $res
            ]); 
        }
    }

    public function checkout(Request $request, $id)
    {   
        $timestamp = $this->carbon::now();
        
        $res = Transaction::find($id);

        $res->status = 'completed';
        $res->is_paid = 1;
        $res->payment_type_id = $request->input('payment_method');
        $res->total_amount = $request->input('total_amount');
        $res->total_paid = $request->input('amount_paid');
        $res->total_change = $request->input('amount_change');
        $res->updated_at = $timestamp;
        $res->updated_by = Auth::user()->id;

        if ($res->update()) {

            if ($request->input('payment_method') == 2) {
                $cus = Customer::find($res->customer_id);
                $cus->credits = floatval($cus->credits) - floatval($request->input('total_amount'));
                $cus->update();
            }
            
            TransactionDetail::where([
                'transaction_id' => $id
            ])->update(['timed_out' => $timestamp]);

            return response()
            ->json([
                'status' => 'ok',
                'data' => $res
            ]);
        } else {
            throw new NotFoundHttpException();
        }
    }

    public function generate(Request $request) 
    {   
        $fromDate = date('Y-m-d', strtotime($request->input('start_date'))).' 00:00:00';
        $toDate = date('Y-m-d', strtotime($request->input('end_date'))).' 23:59:59';

        if ($request->input('type') != 'all') {
            $res = Transaction::with([
                'customer.type',
                'detail.vehicle.fixrate',
                'type'
            ])
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->where([
                'status' => 'completed',
                'is_active' => 1,
                'transaction_type_id' => $request->input('type'),
            ])->orderBy('id', $request->input('orderby'))->get();
        } else {
            $res = Transaction::with([
                'customer.type',
                'detail.vehicle.fixrate',
                'type'
            ])
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->where([
                'status' => 'completed',
                'is_active' => 1,
            ])->orderBy('id', $request->input('orderby'))->get();
        }

        $res = $res->map(function($trans) {
            return [
                'id' => $trans->id,
                'transaction_no' => $trans->transaction_no,
                'type' => $trans->type->name,
                'customer' => $trans->customer->rfid_no,
                'transaction_date' => date('d-M-Y', strtotime($trans->updated_at)),
                'total_amount' => number_format($trans->total_amount, 2)
            ];
        });

        $totalAmount = 0;
        foreach ($res as $ras) {
            $totalAmount += floatval($ras['total_amount']);
        }

        return response()
        ->json([
            'status' => 'ok',
            'total_amount' => $totalAmount, //array_sum(array_column($res, 'total_amount')),
            'data' => $res
        ]);
    }
}
