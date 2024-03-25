<?php

use App\Http\Controllers\Customer\PusherController;
use App\Http\Controllers\Debug\DebugController;
use App\Http\Controllers\LanguageController;
use App\Library\Tool;
use App\Models\AppConfig;
use App\Models\Automation;
use App\Models\Campaigns;
use App\Models\PaymentMethods;
use Database\Seeders\Countries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


/*Route::get('/', function () {
    return view('welcome');
});*/

Route::get('/about', function () {
    return view('about');
});
Route::get('/contact', function () {
    return view('contact');
});
Route::get('/privacy', function () {
    return view('privacy');
});
Route::get('/service', function () {
    return view('service');
});
Route::get('/faq', function () {
    return view('faq');
});
Route::get('/', function () {

    if (config('app.stage') == 'new') {
        return redirect('install');
    }

    if (config('app.stage') == 'Live' && config('app.version') == '3.3.0') {
        return redirect('update');
    }

    return view('welcome');
});

// locale Route
Route::get('lang/{locale}', [LanguageController::class, 'swap']);
Route::any('languages', [LanguageController::class, 'languages'])->name('languages');

Route::post('/pusher/auth', [PusherController::class, 'pusherAuth'])
    ->middleware('auth')->name('pusher.auth');

if (config('app.stage') == 'local') {
    Route::get('run-campaign/{uid}', function (Request $request) {
        $campaign = Campaigns::where('uid', $request->uid)->first();
        $campaign?->execute();

        return 'Campaign On Processing';
    });

    Route::get('run-automation/{uid}', function (Request $request) {
        $automation = Automation::where('uid', $request->uid)->first();
        $automation?->start();

        return 'Automation On Processing';
    });

    Route::get('get-contacts', function () {

        $campaign = Campaigns::find(1);
        if ($campaign) {
            $campaign->getContactList();
        }
    });

    Route::get('update-file', function (BufferedOutput $outputLog) {
        $app_path = base_path().'/bootstrap/cache/';
        if (File::isDirectory($app_path)) {
            File::cleanDirectory($app_path);
        }

        Artisan::call('optimize:clear');
        Artisan::call('migrate', ['--force' => true], $outputLog);
        Tool::versionSeeder(config('app.version'));

        AppConfig::setEnv('APP_VERSION', '3.4.0');

        return redirect()->route('login')->with([
            'status' => 'success',
            'message' => 'You have successfully updated your application.',
        ]);
    });

    Route::get('update-country', function () {
        $countries = new Countries();
        $countries->run();
    });

    Route::get('debug', [DebugController::class, 'index'])->name('debug');

    Route::get('update-demo', function () {
        Artisan::call('demo:update');

        return 'Demo Updated';
    });

}

Route::get('/clear', function () {

    Artisan::call('optimize:clear');

    return 'Cleared!';

});

Route::get('/add-vodacom', function () {
    $check_exist = PaymentMethods::where('type', 'vodacommpesa')->first();
    if (! $check_exist) {
        $data = PaymentMethods::create(
            [
                'name' => 'VodacomMPesa',
                'type' => 'vodacommpesa',
                'options' => json_encode([
                    'apiKey' => 'API KEY',
                    'publicKey' => 'Public Key',
                    'serviceProviderCode' => 'Service Provider Code',
                    'environment' => 'sandbox',
                ]),
                'status' => false,
            ]);
        if ($data) {
            return redirect()->route('admin.payment-gateways.show', $data->uid)->with([
                'status' => 'success',
                'message' => 'Gateway was successfully Added',
            ]);
        }

        return redirect()->route('login')->with([
            'status' => 'error',
            'message' => __('locale.exceptions.something_went_wrong'),
        ]);
    }

    return redirect()->route('admin.payment-gateways.show', $check_exist->uid)->with([
        'status' => 'success',
        'message' => 'Payment gateway have already available',
    ]);
});

Route::get('/add-fedapay', function () {
    $check_exist = PaymentMethods::where('type', 'fedapay')->first();
    if (! $check_exist) {
        $data = PaymentMethods::create(
            [
                'name' => 'FedaPay',
                'type' => 'fedapay',
                'options' => json_encode([
                    'public_key' => 'public key',
                    'secret_key' => 'secret key',
                    'environment' => 'sandbox',
                ]),
                'status' => false,
            ]);
        if ($data) {
            return redirect()->route('admin.payment-gateways.show', $data->uid)->with([
                'status' => 'success',
                'message' => 'Gateway was successfully Added',
            ]);
        }

        return redirect()->route('login')->with([
            'status' => 'error',
            'message' => __('locale.exceptions.something_went_wrong'),
        ]);
    }

    return redirect()->route('admin.payment-gateways.show', $check_exist->uid)->with([
        'status' => 'success',
        'message' => 'Payment gateway have already available',
    ]);
});

Route::get('/add-selcommobile', function () {
    $check_exist = PaymentMethods::where('type', 'selcommobile')->first();
    if (! $check_exist) {
        $data = PaymentMethods::create([
            'name' => 'SelcomMobile',
            'type' => 'selcommobile',
            'options' => json_encode([
                'payment_url' => 'https://apigw.selcommobile.com/v1',
                'vendor' => 'VENDORTILL',
                'api_key' => '202cb962ac59075b964b07152d234b70',
                'api_secret' => '81dc9bdb52d04dc20036dbd8313ed055',
            ]),
            'status' => false,
        ]);
        if ($data) {
            return redirect()->route('admin.payment-gateways.show', $data->uid)->with([
                'status' => 'success',
                'message' => 'Gateway was successfully Added',
            ]);
        }

        return redirect()->route('login')->with([
            'status' => 'error',
            'message' => __('locale.exceptions.something_went_wrong'),
        ]);
    }

    return redirect()->route('admin.payment-gateways.show', $check_exist->uid)->with([
        'status' => 'success',
        'message' => 'Payment gateway have already available',
    ]);
});

Route::get('/add-mpgs', function () {
    $check_exist = PaymentMethods::where('type', 'mpgs')->first();
    if (! $check_exist) {
        $data = PaymentMethods::create(
            [
                'name' => 'MasterCard Payment Gateway Services (MPGS)',
                'type' => PaymentMethods::TYPE_MPGS,
                'options' => json_encode([
                    'payment_url' => 'https://ap-gateway.mastercard.com/',
                    'api_version' => '66',
                    'merchant_id' => 'merchant_id',
                    'authentication_password' => 'authentication_password',
                    'merchant_name' => 'Merchant Name',
                    'merchant_address' => 'Merchant Address',
                ]),
                'status' => false,
            ],
        );
        if ($data) {
            return redirect()->route('admin.payment-gateways.show', $data->uid)->with([
                'status' => 'success',
                'message' => 'Gateway was successfully Added',
            ]);
        }

        return redirect()->route('login')->with([
            'status' => 'error',
            'message' => __('locale.exceptions.something_went_wrong'),
        ]);
    }

    return redirect()->route('admin.payment-gateways.show', $check_exist->uid)->with([
        'status' => 'success',
        'message' => 'Payment gateway have already available',
    ]);
});

Route::get('/add-0xprocessing', function () {
    $check_exist = PaymentMethods::where('type', PaymentMethods::TYPE_0XPROCESSING)->first();
    if (! $check_exist) {
        $data = PaymentMethods::create(
            [
                'name' => PaymentMethods::TYPE_0XPROCESSING,
                'type' => PaymentMethods::TYPE_0XPROCESSING,
                'options' => json_encode([
                    'merchant_id' => 'merchant id',
                    'api_key' => 'api key',
                    'environment' => 'sandbox',
                ]),
                'status' => true,
            ],
        );
        if ($data) {
            return redirect()->route('admin.payment-gateways.show', $data->uid)->with([
                'status' => 'success',
                'message' => 'Gateway was successfully Added',
            ]);
        }

        return redirect()->route('login')->with([
            'status' => 'error',
            'message' => __('locale.exceptions.something_went_wrong'),
        ]);
    }

    return redirect()->route('admin.payment-gateways.show', $check_exist->uid)->with([
        'status' => 'success',
        'message' => 'Payment gateway have already available',
    ]);
});
