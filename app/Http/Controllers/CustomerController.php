<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;
use League\Csv\Statement;
// use League\Csv\Reader;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

class CustomerController extends Controller
{
    //CSV/READER

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:csv,txt|max:1024000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $file = $request->file('file');

        $startTime = microtime(true);

       
        $reader = Reader::createFromPath($file->getPathname(), 'r');
        $reader->setHeaderOffset(0); // Assuming the first row is the header

      
        $batchSize = 5000;
        $batchData = [];
        $rows = Statement::create()->process($reader);

        foreach ($rows as $row) {
            $subscriptionDate = Carbon::createFromFormat('d-m-Y', $row['Subscription Date'])->format('Y-m-d');

            $batchData[] = [
                'customer_id' => $row['Customer Id'],
                'first_name' => $row['First Name'],
                'last_name' => $row['Last Name'],
                'company' => $row['Company'],
                'city' => $row['City'],
                'country' => $row['Country'],
                'phone1' => $row['Phone 1'],
                'phone2' => $row['Phone 2'],
                'email' => $row['Email'],
                'subscription_date' => $subscriptionDate,
                'website' => $row['Website'],
            ];


            if (count($batchData) === $batchSize) {
                Customer::insert($batchData);
                $batchData = [];
            }
        }

        if (!empty($batchData)) {
            Customer::insert($batchData);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        Log::info('Import execution time: ' . $executionTime . ' seconds');

        return redirect()->back()->with('success', 'Customers imported successfully.');
    }

   
   
}
