# Laravel CSV Importer

This project demonstrates how to efficiently import large CSV files into a Laravel application using the `league/csv` package.

## Requirements

- PHP 8.0 or higher
- Laravel 11.x
- Composer

## Installation

1. Clone the repository:
    ```bash
    git clone https://github.com/neel-ast01/import.git
    cd laravel-csv-importer
    ```

2. Install dependencies:
    ```bash
    composer install
    ```

3. Set up your environment:
    - Copy `.env.example` to `.env`:
      ```bash
      cp .env.example .env
      ```
    - Update your `.env` file with your database credentials.

4. Generate application key:
    ```bash
    php artisan key:generate
    ```

5. Run migrations:
    ```bash
    php artisan migrate
    ```

6. Install `league/csv` package:
    ```bash
    composer require league/csv
    ```

## Usage

1. Add the `import` method to your `CustomerController`:
    ```php
    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Validator;
    use Illuminate\Support\Facades\Log;
    use League\Csv\Reader;
    use League\Csv\Statement;
    use Carbon\Carbon;
    use App\Models\Customer;

    class CustomerController extends Controller
    {
        public function import(Request $request)
        {
            $validator = Validator::make($request->all(), [
                'file' => 'required|mimes:csv,txt|max:1024000', // 1000MB in kilobytes
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $file = $request->file('file');

            $startTime = microtime(true);

            // Create a new CSV reader
            $reader = Reader::createFromPath($file->getPathname(), 'r');
            $reader->setHeaderOffset(0); // Assuming the first row is the header

            // Prepare the data in batches
            $batchSize = 5000;
            $batchData = [];
            $rows = Statement::create()->process($reader);

            foreach ($rows as $row) {
                // Note the use of exact keys matching the CSV header
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
    ```

2. Update your routes in `routes/web.php`:
    ```php
    use App\Http\Controllers\CustomerController;

    Route::post('/import', [CustomerController::class, 'import'])->name('customers.import');
    ```

3. Create a form to upload the CSV file in a Blade view (e.g., `resources/views/import.blade.php`):
    ```html
    <!DOCTYPE html>
    <html>
    <head>
        <title>Import Customers</title>
    </head>
    <body>
        @if ($errors->any())
            <div>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div>
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('customers.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label for="file">Choose CSV File:</label>
            <input type="file" name="file" id="file" required>
            <button type="submit">Upload</button>
        </form>
    </body>
    </html>
    ```

4. Navigate to your form in the browser to test the import functionality (e.g., `http://127.0.0.1:8000`).

## Logging

The import execution time is logged for performance monitoring. Check the logs in `storage/logs/laravel.log`.

## License

This project is open-source and available under the [MIT License](LICENSE).
