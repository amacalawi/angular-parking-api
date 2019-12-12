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
                'customers' 
            ])->where([
                'status' => 'queued',
                'transaction_type_id' => '1',
                'is_active' => 1
            ])->orderBy('id', 'ASC')->get();
    
            $res->map(function($trans) {
                return [
                    'id' => $trans->id,
                    'transaction_no' => $trans->transaction_no,
                    'customer_id' => $trans->customers->id,
                    'customer_name' => $trans->customers->firstname,
                    'type' => $trans->customers->customer_type_id,
                    'color' => CustomerType::where('id', $trans->customers->customer_type_id)->get(['badges_color']),
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
    
    public function find(Request $request, $id)
    {   
        $res = Transaction::find($id);
        
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
        $res =  Transaction::create([
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
        $res = Transaction::find($id);

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
