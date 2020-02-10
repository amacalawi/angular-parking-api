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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DashboardController extends Controller
{
    private $carbon;

    public function __construct(Carbon $carbon)
    {   
        date_default_timezone_set('Asia/Manila');
        $this->carbon = $carbon;
    }

    public function index() 
    {   
        $count = 0; $today = $this->carbon::now();
        $arrs = array();
        while ($count < 15) {
            $day = date('Y-m-d', strtotime('-'.$count.' day', strtotime($today)));
            $res = Transaction::with([
                'customer.type',
                'detail.vehicle.fixrate',
                'type'
            ])
            ->where('created_at', 'like', '%' . $day . '%')
            ->where([
                'status' => 'completed',
                'is_active' => 1,
            ])->orderBy('id', 'ASC')
            ->sum('total_amount');
            
            $arrs[] = (object) array(
                'day' => $day,
                'amount' => $res
            );

            $count++;
        }
        
        $days = array(); $amounts = array();
        foreach($arrs as $arr) {
            $days[] = date('M-d', strtotime($arr->day));
            $amounts[] = $arr->amount;
        }

        $day = date('m', strtotime('-'.$count.' day', strtotime($today)));

        $thismonth = Transaction::with([
            'customer.type',
            'detail.vehicle.fixrate',
            'type'
        ])
        ->whereYear('created_at', Carbon::now()->year)
        ->whereMonth('created_at', Carbon::now()->month)
        ->where([
            'status' => 'completed',
            'is_active' => 1,
        ])->orderBy('id', 'ASC')
        ->sum('total_amount');

        $lastmonth = Transaction::with([
            'customer.type',
            'detail.vehicle.fixrate',
            'type'
        ])
        ->whereYear('created_at', Carbon::now()->subMonth()->year)
        ->whereMonth('created_at', Carbon::now()->subMonth()->month)
        ->where([
            'status' => 'completed',
            'is_active' => 1,
        ])->orderBy('id', 'ASC')
        ->sum('total_amount');

        return response()
        ->json([
            'status' => 'ok',
            'data' => $arrs,
            'days' => array_reverse($days),
            'amounts' => array_reverse($amounts),
            'thismonth' => floor(($thismonth*100))/100,
            'lastmonth' => floor(($lastmonth*100))/100
        ]);
    }  

    public function download()
    {   
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $row = 1;
        $sheet->setCellValue('A'.$row, 'Transaction No');
        $sheet->setCellValue('B'.$row, 'Type');
        $sheet->setCellValue('C'.$row, 'Customer RFID');
        $sheet->setCellValue('D'.$row, 'Amount');
        $sheet->setCellValue('E'.$row, 'Date');

        $transactions = Transaction::with([
            'customer.type',
            'detail.vehicle.fixrate',
            'type'
        ])
        ->whereYear('created_at', Carbon::now()->subMonth()->year)
        ->whereMonth('created_at', Carbon::now()->subMonth()->month)
        ->where([
            'status' => 'completed',
            'is_active' => 1,
        ])->orderBy('id', 'ASC')
        ->get();

        $transactions = $transactions->map(function($trans) {
            return (object) [
                'id' => $trans->id,
                'transaction_no' => $trans->transaction_no,
                'type' => $trans->type->name,
                'customer' => $trans->customer->rfid_no,
                'transaction_date' => date('d-M-Y', strtotime($trans->created_at)),
                'total_amount' => number_format($trans->total_amount, 2)
            ];
        });

        foreach($transactions as $trans) {
            $row++;
            $sheet->setCellValue('A'.$row, $trans->transaction_no);
            $sheet->setCellValue('B'.$row, $trans->type);
            $sheet->setCellValue('C'.$row, $trans->customer);
            $sheet->setCellValue('D'.$row, $trans->total_amount);
            $sheet->setCellValue('E'.$row, $trans->transaction_date);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save('sales.xls');
        header("Content-Type: application/vnd.ms-excel");
        return redirect(url('/')."/sales.xls");
    }
}
