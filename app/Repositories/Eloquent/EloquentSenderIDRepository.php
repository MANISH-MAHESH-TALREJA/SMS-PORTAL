<?php

    namespace App\Repositories\Eloquent;

    use App\Exceptions\GeneralException;
    use App\Library\aamarPay;
    use App\Library\CoinPayments;
    use App\Library\Flutterwave;
    use App\Library\MPGS;
    use App\Library\OrangeMoney;
    use App\Library\PayHereLK;
    use App\Library\PayU;
    use App\Library\PayUMoney;
    use App\Library\TwoCheckout;
    use App\Models\Country;
    use App\Models\PaymentMethods;
    use App\Models\Senderid;
    use App\Models\SenderidPlan;
    use App\Models\Templates;
    use App\Models\User;
    use App\Notifications\SenderIDConfirmation;
    use App\Repositories\Contracts\SenderIDRepository;
    use Braintree\Gateway;
    use Carbon\Carbon;

    use Exception;
    use Illuminate\Http\JsonResponse;
    use Illuminate\Support\Arr;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Redirect;
    use Illuminate\Support\Facades\Session;
    use Mollie\Api\MollieApiClient;
    use Paynow\Http\ConnectionException;
    use Paynow\Payments\HashMismatchException;
    use Paynow\Payments\InvalidIntegrationException;
    use Paynow\Payments\Paynow;
    use PayPalCheckoutSdk\Core\PayPalHttpClient;
    use PayPalCheckoutSdk\Core\ProductionEnvironment;
    use PayPalCheckoutSdk\Core\SandboxEnvironment;
    use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
    use Psr\SimpleCache\InvalidArgumentException;
    use Razorpay\Api\Api;
    use Razorpay\Api\Errors\BadRequestError;
    use Selcom\ApigwClient\Client;
    use SimpleXMLElement;
    use Stripe\Stripe;
    use Throwable;

    class EloquentSenderIDRepository extends EloquentBaseRepository implements SenderIDRepository
    {
        /**
         * EloquentSenderIDRepository constructor.
         */
        public function __construct(Senderid $senderid)
        {
            parent::__construct($senderid);
        }

        public function store(array $input, array $billingCycle): bool
        {
            if (is_array($input['user_id']) && count($input['user_id']) > 0) {
                foreach ($input['user_id'] as $user_id) {
                    $senderid = $this->make(Arr::only($input, [
                        'sender_id',
                        'description',
                        'entity_id',
                        'status',
                        'price',
                        'billing_cycle',
                        'frequency_amount',
                        'frequency_unit',
                        'currency_id',
                    ]));

                    if (isset($input['billing_cycle']) && $input['billing_cycle'] != 'custom') {
                        $limits                     = $billingCycle[$input['billing_cycle']];
                        $senderid->frequency_amount = $limits['frequency_amount'];
                        $senderid->frequency_unit   = $limits['frequency_unit'];
                    }

                    if ($input['status'] == 'active') {
                        $current                   = Carbon::now();
                        $senderid->validity_date   = $current->add($senderid->frequency_unit, $senderid->frequency_amount);
                        $senderid->payment_claimed = true;
                    }
                    $senderid->user_id = $user_id;
                    $senderid->save();
                }
            }

            return true;
        }

        /**
         * @throws GeneralException
         */
        public function storeCustom(array $input): Senderid
        {
            /** @var Senderid $senderid */
            $senderid = $this->make(Arr::only($input, [
                'sender_id',
                'description',
                'entity_id',
            ]));

            $plan                       = SenderidPlan::find($input['plan']);
            $senderid->user_id          = Auth::user()->id;
            $senderid->currency_id      = $plan->currency_id;
            $senderid->status           = 'Pending';
            $senderid->price            = $plan->price;
            $senderid->billing_cycle    = $plan->billing_cycle;
            $senderid->frequency_amount = $plan->frequency_amount;
            $senderid->frequency_unit   = $plan->frequency_unit;

            if ( ! $this->save($senderid)) {
                throw new GeneralException(__('locale.exceptions.something_went_wrong'));
            }

            return $senderid;

        }

        private function save(Senderid $senderid): bool
        {
            if ( ! $senderid->save()) {
                return false;
            }

            return true;
        }

        /**
         * @throws GeneralException
         */
        public function update(Senderid $senderid, array $input, array $billingCycle): Senderid
        {

            if (isset($input['billing_cycle']) && $input['billing_cycle'] != 'custom') {
                $limits                    = $billingCycle[$input['billing_cycle']];
                $input['frequency_amount'] = $limits['frequency_amount'];
                $input['frequency_unit']   = $limits['frequency_unit'];
            }

            if ($senderid->status != 'active' && $input['status'] == 'active') {
                $current                  = Carbon::now();
                $input['validity_date']   = $current->add($senderid->frequency_unit, $senderid->frequency_amount);
                $input['payment_claimed'] = true;
            }

            if ($senderid->status == 'pending') {
                $user = User::find($senderid->user_id);
                if ($user->customer->getNotifications()['sender_id'] == 'yes') {
                    $user->notify(new SenderIDConfirmation($input['status'], route('customer.senderid.index')));
                }
            }

            if ( ! isset($input['is_dlt'])) {
                $input['entity_id']   = null;
                $input['description'] = null;
            }

            if ( ! $senderid->update($input)) {
                throw new GeneralException(__('locale.exceptions.something_went_wrong'));
            }

            return $senderid;
        }

        /**
         * @throws GeneralException
         */
        public function destroy(Senderid $senderid, $user_id = null): bool
        {
            if ($user_id) {
                $exist = $senderid->where('sender_id', $senderid->sender_id)->where('user_id', $user_id)->first();
                if ($exist) {
                    if ( ! $exist->delete()) {
                        throw new GeneralException(__('locale.exceptions.something_went_wrong'));
                    }

                    return true;
                }
                throw new GeneralException(__('locale.exceptions.something_went_wrong'));
            } else {
                if ( ! $senderid->delete()) {
                    throw new GeneralException(__('locale.exceptions.something_went_wrong'));
                }
            }

            Templates::where('sender_id', $senderid->id)->update([
                'sender_id' => null,
            ]);

            return true;
        }

        /**
         * @return mixed
         *
         * @throws Exception|Throwable
         */
        public function batchDestroy(array $ids): bool
        {
            DB::transaction(function () use ($ids) {
                // This won't call eloquent events, change to destroy if needed
                if ($this->query()->whereIn('uid', $ids)->delete()) {
                    (new Templates)->whereIn('sender_id', $ids)->update([
                        'sender_id' => null,
                    ]);

                    return true;
                }

                throw new GeneralException(__('locale.exceptions.something_went_wrong'));
            });

            return true;
        }

        /**
         * @return mixed
         *
         * @throws Exception|Throwable
         */
        public function batchActive(array $ids): bool
        {
            DB::transaction(function () use ($ids) {
                if ($this->query()->whereIn('uid', $ids)
                    ->update(['status' => 'active'])
                ) {
                    return true;
                }

                throw new GeneralException(__('locale.exceptions.something_went_wrong'));
            });

            return true;
        }

        /**
         * @return mixed
         *
         * @throws Exception|Throwable
         */
        public function batchBlock(array $ids): bool
        {
            DB::transaction(function () use ($ids) {
                if ($this->query()->whereIn('uid', $ids)
                    ->update(['status' => 'block'])
                ) {
                    return true;
                }

                throw new GeneralException(__('locale.exceptions.something_went_wrong'));
            });

            return true;
        }

        /**
         * store sender id plan
         *
         *
         * @return SenderidPlan
         *
         * @throws GeneralException
         */
        public function storePlan(array $input, array $billingCycle)
        {
            if (isset($input['billing_cycle']) && $input['billing_cycle'] != 'custom') {
                $limits                    = $billingCycle[$input['billing_cycle']];
                $input['frequency_amount'] = $limits['frequency_amount'];
                $input['frequency_unit']   = $limits['frequency_unit'];
            }

            $sender_id_plan = SenderidPlan::create($input);
            if ( ! $sender_id_plan) {
                throw new GeneralException(__('locale.exceptions.something_went_wrong'));
            }

            return $sender_id_plan;

        }

        /**
         * pay the payment
         *
         *
         * @throws Exception
         * @throws InvalidArgumentException
         */
        public function payPayment(Senderid $senderid, array $input): JsonResponse
        {

            $paymentMethod = PaymentMethods::where('status', true)->where('type', $input['payment_methods'])->first();

            if ($paymentMethod) {
                $credentials = json_decode($paymentMethod->options);

                switch ($paymentMethod->type) {

                    case PaymentMethods::TYPE_PAYPAL:

                        if ($credentials->environment == 'sandbox') {
                            $environment = new SandboxEnvironment($credentials->client_id, $credentials->secret);
                        } else {
                            $environment = new ProductionEnvironment($credentials->client_id, $credentials->secret);
                        }

                        $client = new PayPalHttpClient($environment);

                        $request = new OrdersCreateRequest();
                        $request->prefer('return=representation');

                        $request->body = [
                            'intent'              => 'CAPTURE',
                            'purchase_units'      => [[
                                'reference_id' => $senderid->user->id . '_' . $senderid->uid,
                                'description'  => __('locale.sender_id.payment_for_sender_id') . ' ' . $senderid->sender_id,
                                'amount'       => [
                                    'value'         => $senderid->price,
                                    'currency_code' => $senderid->currency->code,
                                ],
                            ]],
                            'application_context' => [
                                'brand_name' => config('app.name'),
                                'locale'     => config('app.locale'),
                                'cancel_url' => route('customer.senderid.payment_cancel', $senderid->uid),
                                'return_url' => route('customer.senderid.payment_success', $senderid->uid),
                            ],
                        ];

                        try {
                            $response = $client->execute($request);

                            if (isset($response->result->links)) {
                                foreach ($response->result->links as $link) {
                                    if ($link->rel == 'approve') {
                                        $redirect_url = $link->href;
                                        break;
                                    }
                                }
                            }

                            if (isset($redirect_url)) {
                                if ( ! empty($response->result->id)) {
                                    Session::put('payment_method', $paymentMethod->type);
                                    Session::put('paypal_payment_id', $response->result->id);
                                }

                                return response()->json([
                                    'status'       => 'success',
                                    'redirect_url' => $redirect_url,
                                ]);
                            }

                            return response()->json([
                                'status'  => 'error',
                                'message' => __('locale.exceptions.something_went_wrong'),
                            ]);

                        } catch (Exception $exception) {

                            $errorData    = json_decode($exception->getMessage(), true);
                            $errorMessage = $errorData['details'][0]['description'] ?? 'An error occurred while processing the payment.';

                            return response()->json([
                                'status'  => 'error',
                                'message' => $errorMessage,
                            ]);
                        }

                    case PaymentMethods::TYPE_BRAINTREE:

                        try {
                            $gateway = new Gateway([
                                'environment' => $credentials->environment,
                                'merchantId'  => $credentials->merchant_id,
                                'publicKey'   => $credentials->public_key,
                                'privateKey'  => $credentials->private_key,
                            ]);

                            $clientToken = $gateway->clientToken()->generate();

                            return response()->json([
                                'status' => 'success',
                                'token'  => $clientToken,
                            ]);
                        } catch (Exception $exception) {
                            return response()->json([
                                'status'  => 'error',
                                'message' => $exception->getMessage(),
                            ]);
                        }

                    case PaymentMethods::TYPE_STRIPE:

                        $publishable_key = $credentials->publishable_key;
                        $secret_key      = $credentials->secret_key;

                        Stripe::setApiKey($secret_key);

                        try {
                            $checkout_session = \Stripe\Checkout\Session::create([
                                'payment_method_types' => ['card'],
                                'customer_email'       => $input['email'],
                                'line_items'           => [[
                                    'price_data' => [
                                        'currency'     => $senderid->currency->code,
                                        'unit_amount'  => $senderid->price * 100,
                                        'product_data' => [
                                            'name' => __('locale.sender_id.payment_for_sender_id') . ' ' . $senderid->sender_id,
                                        ],
                                    ],
                                    'quantity'   => 1,
                                ]],
                                'mode'                 => 'payment',
                                'success_url'          => route('customer.senderid.payment_success', $senderid->uid),
                                'cancel_url'           => route('customer.senderid.payment_cancel', $senderid->uid),
                            ]);

                            if ( ! empty($checkout_session->id)) {
                                Session::put('payment_method', $paymentMethod->type);
                                Session::put('session_id', $checkout_session->id);
                            }

                            return response()->json([
                                'status'          => 'success',
                                'session_id'      => $checkout_session->id,
                                'publishable_key' => $publishable_key,
                            ]);

                        } catch (Exception $exception) {

                            return response()->json([
                                'status'  => 'error',
                                'message' => $exception->getMessage(),
                            ]);

                        }

                    case PaymentMethods::TYPE_AUTHORIZE_NET:
                    case PaymentMethods::TYPE_VODACOMMPESA:
                        return response()->json([
                            'status'      => 'success',
                            'credentials' => $credentials,
                        ]);

                    case PaymentMethods::TYPE_2CHECKOUT:

                        Session::put('payment_method', $paymentMethod->type);

                        $checkout = new TwoCheckout();

                        $checkout->param('sid', $credentials->merchant_code);
                        if ($credentials->environment == 'sandbox') {
                            $checkout->param('demo', 'Y');
                        }
                        $checkout->param('return_url', route('customer.senderid.payment_success', $senderid->uid));
                        $checkout->param('li_0_name', __('locale.sender_id.payment_for_sender_id') . ' ' . $senderid->sender_id);
                        $checkout->param('li_0_price', $senderid->price);
                        $checkout->param('li_0_quantity', 1);
                        $checkout->param('card_holder_name', $input['first_name'] . ' ' . $input['last_name']);
                        $checkout->param('city', $input['city']);
                        $checkout->param('country', $input['country']);
                        $checkout->param('email', $input['email']);
                        $checkout->param('phone', $input['phone']);
                        $checkout->param('currency_code', $senderid->currency->code);
                        $checkout->gw_submit();
                        exit();

                    case PaymentMethods::TYPE_PAYSTACK:

                        $curl = curl_init();

                        curl_setopt_array($curl, [
                            CURLOPT_URL            => 'https://api.paystack.co/transaction/initialize',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_CUSTOMREQUEST  => 'POST',
                            CURLOPT_POSTFIELDS     => json_encode([
                                'amount'   => round($senderid->price) * 100,
                                'email'    => $input['email'],
                                'metadata' => [
                                    'sender_id'    => $senderid->uid,
                                    'request_type' => 'senderid_payment',
                                ],
                            ]),
                            CURLOPT_HTTPHEADER     => [
                                'authorization: Bearer ' . $credentials->secret_key,
                                'content-type: application/json',
                                'cache-control: no-cache',
                            ],
                        ]);

                        $response = curl_exec($curl);
                        $err      = curl_error($curl);

                        curl_close($curl);

                        if ($response === false) {
                            return response()->json([
                                'status'  => 'error',
                                'message' => 'Php curl show false value. Please contact with your provider',
                            ]);
                        }

                        if ($err) {
                            return response()->json([
                                'status'  => 'error',
                                'message' => $err,
                            ]);
                        }

                        $result = json_decode($response);

                        if ($result->status != 1) {

                            return response()->json([
                                'status'  => 'error',
                                'message' => $result->message,
                            ]);
                        }

                        return response()->json([
                            'status'       => 'success',
                            'redirect_url' => $result->data->authorization_url,
                        ]);

                    case PaymentMethods::TYPE_PAYU:

                        Session::put('payment_method', $paymentMethod->type);

                        $signature = "$credentials->client_secret~$credentials->client_id~senderID$senderid->uid~$senderid->price~$senderid->currency->code";
                        $signature = md5($signature);

                        $payu = new PayU();

                        $payu->param('merchantId', $credentials->client_id);
                        $payu->param('ApiKey', $credentials->client_secret);
                        $payu->param('referenceCode', 'senderID' . $senderid->uid);
                        $payu->param('description', __('locale.sender_id.payment_for_sender_id') . ' ' . $senderid->sender_id);
                        $payu->param('amount', $senderid->price);
                        $payu->param('currency', $senderid->currency->code);
                        $payu->param('buyerEmail', $input['email']);
                        $payu->param('signature', $signature);
                        $payu->param('confirmationUrl', route('customer.senderid.payment_success', $senderid->uid));
                        $payu->param('responseUrl', route('customer.senderid.payment_cancel', $senderid->uid));
                        $payu->gw_submit();

                        exit();

                    case PaymentMethods::TYPE_PAYNOW:

                        $paynow = new Paynow(
                            $credentials->integration_id,
                            $credentials->integration_key,
                            route('customer.callback.paynow'),
                            route('customer.senderid.payment_success', $senderid->uid)
                        );

                        $payment = $paynow->createPayment($senderid->uid, $input['email']);
                        $payment->add(__('locale.sender_id.payment_for_sender_id') . ' ' . $senderid->sender_id, $senderid->price);

                        try {
                            $response = $paynow->send($payment);

                            if ($response->success()) {

                                Session::put('payment_method', $paymentMethod->type);
                                Session::put('paynow_poll_url', $response->pollUrl());

                                return response()->json([
                                    'status'       => 'success',
                                    'redirect_url' => $response->redirectUrl(),
                                ]);
                            }

                            return response()->json([
                                'status'  => 'error',
                                'message' => __('locale.exceptions.something_went_wrong'),
                            ]);

                        } catch (ConnectionException|HashMismatchException|InvalidIntegrationException|Exception $e) {
                            return response()->json([
                                'status'  => 'error',
                                'message' => $e->getMessage(),
                            ]);
                        }

                    case PaymentMethods::TYPE_COINPAYMENTS:

                        Session::put('payment_method', $paymentMethod->type);

                        $coinPayment = new CoinPayments();

                        $order = [
                            'merchant'    => $credentials->merchant_id,
                            'item_name'   => __('locale.sender_id.payment_for_sender_id') . ' ' . $senderid->sender_id,
                            'amountf'     => $senderid->price,
                            'currency'    => $senderid->currency->code,
                            'success_url' => route('customer.senderid.payment_success', $senderid->uid),
                            'cancel_url'  => route('customer.senderid.payment_cancel', $senderid->uid),
                        ];

                        foreach ($order as $item => $value) {
                            $coinPayment->param($item, $value);
                        }

                        $coinPayment->gw_submit();

                        exit();

                    case PaymentMethods::TYPE_INSTAMOJO:

                        $name = $input['first_name'];
                        if (isset($input['last_name'])) {
                            $name .= ' ' . $input['last_name'];
                        }

                        $payload = [
                            'purpose'                 => __('locale.sender_id.payment_for_sender_id') . ' ' . $senderid->sender_id,
                            'amount'                  => $senderid->price,
                            'phone'                   => $input['phone'],
                            'buyer_name'              => $name,
                            'redirect_url'            => route('customer.senderid.payment_success', $senderid->uid),
                            'send_email'              => true,
                            'email'                   => $input['email'],
                            'allow_repeated_payments' => false,
                        ];

                        $headers = [
                            'X-Api-Key:' . $credentials->api_key,
                            'X-Auth-Token:' . $credentials->auth_token,
                        ];

                        $ch = curl_init();

                        curl_setopt($ch, CURLOPT_URL, 'https://www.instamojo.com/api/1.1/payment-requests/');
                        curl_setopt($ch, CURLOPT_HEADER, false);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
                        $response = curl_exec($ch);
                        curl_close($ch);

                        if (isset($response->success)) {
                            if ($response->success) {

                                Session::put('payment_method', $paymentMethod->type);
                                Session::put('payment_request_id', $response->payment_request->id);

                                return response()->json([
                                    'status'       => 'success',
                                    'redirect_url' => $response->payment_request->longurl,
                                ]);
                            }

                            return response()->json([
                                'status'  => 'error',
                                'message' => $response->message,
                            ]);

                        }

                        return response()->json([
                            'status'  => 'error',
                            'message' => __('locale.exceptions.something_went_wrong'),
                        ]);

                    case PaymentMethods::TYPE_PAYUMONEY:

                        Session::put('payment_method', $paymentMethod->type);

                        $environment = $credentials->environment;
                        $txnid       = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
                        $pinfo       = __('locale.sender_id.payment_for_sender_id') . ' ' . $senderid->sender_id;
                        $hash        = strtolower(hash('sha512', $credentials->merchant_key . '|' . $txnid . '|' . $senderid->price . '|' . $pinfo . '|' . $input['first_name'] . '|' . $input['email'] . '||||||||||||' . $credentials->merchant_salt));

                        $payumoney = new PayUMoney($environment);

                        $payumoney->param('key', $credentials->merchant_key);
                        $payumoney->param('amount', $senderid->price);
                        $payumoney->param('hash', $hash);
                        $payumoney->param('txnid', $txnid);
                        $payumoney->param('firstname', $input['first_name']);
                        $payumoney->param('email', $input['email']);
                        $payumoney->param('phone', $input['phone']);
                        $payumoney->param('productinfo', $pinfo);
                        $payumoney->param('surl', route('customer.senderid.payment_success', $senderid->uid));
                        $payumoney->param('furl', route('customer.senderid.payment_cancel', $senderid->uid));

                        if (isset($input['last_name'])) {
                            $payumoney->param('lastname', $input['last_name']);
                        }

                        if (isset($input['address'])) {
                            $payumoney->param('address1', $input['address']);
                        }

                        if (isset($input['city'])) {
                            $payumoney->param('city', $input['city']);
                        }
                        if (isset($input['country'])) {
                            $payumoney->param('country', $input['country']);
                        }

                        $payumoney->gw_submit();

                        exit();

                    case PaymentMethods::TYPE_PHONEPE:
                        
                        try
                        {
                            Session::put('payment_method', $paymentMethod->type);
                            $merchantTransactionId = strtoupper(substr(hash('sha256', mt_rand() . microtime()), 0, 20));

                            $data = array(
                                'merchantId' => $credentials->merchant_key,
                                'merchantTransactionId' => $merchantTransactionId,
                                'merchantUserId' => $merchantTransactionId,
                                'amount' => $senderid->price * 100,
                                'redirectUrl' => route('customer.senderid.payment_success', $senderid->uid),
                                'redirectMode' => 'POST',
                                'callbackUrl' => route('customer.senderid.payment_success', $senderid->uid),
                                'mobileNumber' => $input['phone'],
                                'paymentInstrument' =>
                                    array(
                                        'type' => 'PAY_PAGE',
                                    ),
                            );
                            $encode = base64_encode(json_encode($data));

                            $string = $encode . '/pg/v1/pay' . $credentials->merchant_salt;
                            $sha256 = hash('sha256', $string);
                            $finalXHeader = $sha256 . '###1';

                            $curl = curl_init();

                            curl_setopt_array($curl, array(
                                CURLOPT_URL => $credentials->environment == 'production' ? 'https://api.phonepe.com/apis/hermes/pg/v1/pay' : 'https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/pay',
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_ENCODING => '',
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 0,
                                CURLOPT_FOLLOWLOCATION => false,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => 'POST',
                                CURLOPT_POSTFIELDS => json_encode(['request' => $encode]),
                                CURLOPT_HTTPHEADER => array(
                                    'Content-Type: application/json',
                                    'X-VERIFY: ' . $finalXHeader
                                ),
                            ));

                            $response = curl_exec($curl);

                            curl_close($curl);

                            $rData = json_decode($response);

                            if(isset($rData->data->instrumentResponse->redirectInfo->url))
                            {
                                return response()->json([
                                    'status'       => 'success',
                                    'redirect_url' => $rData->data->instrumentResponse->redirectInfo->url,
                                ]);
                            }
                            else
                            {
                                return response()->json([
                                    'status'  => 'error',
                                    'message' => __('locale.exceptions.something_went_wrong'),
                                ]);
                            }
                        }
                        catch (Exception $exception)
                        {
                            return response()->json([
                                'status'  => 'error',
                                'message' => $exception->getMessage() . " -> " . $exception->getLine() . " -> " . $exception->getMessage(),
                            ]);

                        }

                    case PaymentMethods::TYPE_RAZORPAY:

                        try {
                            $api = new Api($credentials->key_id, $credentials->key_secret);

                            $link = $api->invoice->create([
                                'type'        => 'link',
                                'amount'      => $senderid->price * 100,
                                'description' => __('locale.sender_id.payment_for_sender_id') . ' ' . $senderid->sender_id,
                                'customer'    => [
                                    'email' => $input['email'],
                                ],
                            ]);

                            if (isset($link->id) && isset($link->short_url)) {

                                Session::put('razorpay_order_id', $link->order_id);

                                $senderid->update([
                                    'transaction_id' => $link->order_id,
                                ]);

                                return response()->json([
                                    'status'       => 'success',
                                    'redirect_url' => $link->short_url,
                                ]);
                            }

                            return response()->json([
                                'status'  => 'error',
                                'message' => __('locale.exceptions.something_went_wrong'),
                            ]);

                        } catch (BadRequestError $exception) {
                            return response()->json([
                                'status'  => 'error',
                                'message' => $exception->getMessage(),
                            ]);
                        }

                    case PaymentMethods::TYPE_SSLCOMMERZ:

                        $post_data                 = [];
                        $post_data['store_id']     = $credentials->store_id;
                        $post_data['store_passwd'] = $credentials->store_passwd;
                        $post_data['total_amount'] = $senderid->price;
                        $post_data['currency']     = $senderid->currency->code;
                        $post_data['tran_id']      = $senderid->uid;
                        $post_data['success_url']  = route('customer.callback.sslcommerz.senderid', $senderid->uid);
                        $post_data['fail_url']     = route('customer.callback.sslcommerz.senderid', $senderid->uid);
                        $post_data['cancel_url']   = route('customer.callback.sslcommerz.senderid', $senderid->uid);

                        $post_data['product_category'] = 'senderid';
                        $post_data['emi_option']       = '0';

                        $post_data['cus_name']    = $input['first_name'];
                        $post_data['cus_email']   = $input['email'];
                        $post_data['cus_add1']    = $input['address'];
                        $post_data['cus_city']    = $input['city'];
                        $post_data['cus_country'] = $input['country'];
                        $post_data['cus_phone']   = $input['phone'];

                        if (isset($input['postcode'])) {
                            $post_data['cus_postcode'] = $input['postcode'];
                        }

                        $post_data['shipping_method'] = 'No';
                        $post_data['num_of_item']     = '1';

                        $post_data['cart']            = json_encode([
                            ['product' => __('locale.sender_id.payment_for_sender_id') . ' ' . $senderid->sender_id, 'amount' => $senderid->price],
                        ]);
                        $post_data['product_name']    = __('locale.sender_id.payment_for_sender_id') . ' ' . $senderid->sender_id;
                        $post_data['product_profile'] = 'non-physical-goods';
                        $post_data['product_amount']  = $senderid->price;

                        if ($credentials->environment == 'sandbox') {
                            $direct_api_url = 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php';
                        } else {
                            $direct_api_url = 'https://securepay.sslcommerz.com/gwprocess/v4/api.php';
                        }

                        $handle = curl_init();
                        curl_setopt($handle, CURLOPT_URL, $direct_api_url);
                        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
                        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
                        curl_setopt($handle, CURLOPT_POST, 1);
                        curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
                        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false); // KEEP IT FALSE IF YOU RUN FROM LOCAL PC

                        $content = curl_exec($handle);
                        $code    = curl_getinfo($handle, CURLINFO_HTTP_CODE);

                        if ($code == 200 && ! (curl_errno($handle))) {
                            curl_close($handle);
                            $response = json_decode($content, true);

                            if (isset($response['GatewayPageURL']) && $response['GatewayPageURL'] != '') {

                                return response()->json([
                                    'status'       => 'success',
                                    'redirect_url' => $response['GatewayPageURL'],
                                ]);

                            } else {
                                return response()->json([
                                    'status'  => 'error',
                                    'message' => $response['failedreason'],
                                ]);
                            }
                        } else {
                            curl_close($handle);

                            return response()->json([
                                'status'  => 'error',
                                'message' => 'FAILED TO CONNECT WITH SSLCOMMERZ API',
                            ]);
                        }

                    case PaymentMethods::TYPE_AAMARPAY:

                        Session::put('payment_method', $paymentMethod->type);

                        $checkout = new aamarPay($credentials->environment);

                        $checkout->param('store_id', $credentials->store_id);
                        $checkout->param('signature_key', $credentials->signature_key);
                        $checkout->param('desc', __('locale.sender_id.payment_for_sender_id') . ' ' . $senderid->sender_id);
                        $checkout->param('amount', $senderid->price);
                        $checkout->param('currency', $senderid->currency->code);
                        $checkout->param('tran_id', $senderid->uid);
                        $checkout->param('success_url', route('customer.callback.aamarpay.senderid', $senderid->uid));
                        $checkout->param('fail_url', route('customer.callback.aamarpay.senderid', $senderid->uid));
                        $checkout->param('cancel_url', route('customer.callback.aamarpay.senderid', $senderid->uid));

                        $checkout->param('cus_name', $input['first_name']);
                        $checkout->param('cus_email', $input['email']);
                        $checkout->param('cus_add1', $input['address']);
                        $checkout->param('cus_add2', $input['address']);
                        $checkout->param('cus_city', $input['city']);
                        $checkout->param('cus_country', $input['country']);
                        $checkout->param('cus_phone', $input['phone']);
                        if (isset($input['postcode'])) {
                            $checkout->param('cus_postcode', $input['postcode']);
                        }

                        $checkout->gw_submit();
                        exit();

                    case PaymentMethods::TYPE_FLUTTERWAVE:

                        $checkout = new Flutterwave();

                        $checkout->param('public_key', $credentials->public_key);
                        $checkout->param('amount', $senderid->price);
                        $checkout->param('currency', $senderid->currency->code);
                        $checkout->param('tx_ref', $senderid->uid);
                        $checkout->param('redirect_url', route('customer.callback.flutterwave.senderid'));
                        $checkout->param('customizations[title]', __('locale.sender_id.payment_for_sender_id') . ' ' . $senderid->sender_id);
                        $checkout->param('customizations[description]', __('locale.sender_id.payment_for_sender_id') . ' ' . $senderid->sender_id);
                        $checkout->param('customer[name]', $input['first_name'] . ' ' . $input['last_name']);
                        $checkout->param('customer[email]', $input['email']);
                        $checkout->param('customer[phone_number]', $input['phone']);
                        $checkout->gw_submit();
                        exit();

                    case PaymentMethods::TYPE_DIRECTPAYONLINE:

                        if ($credentials->environment == 'production') {
                            $payment_url = 'https://secure.3gdirectpay.com';
                        } else {
                            $payment_url = 'https://secure1.sandbox.directpay.online';
                        }

                        $companyToken    = $credentials->company_token;
                        $accountType     = $credentials->account_type;
                        $paymentAmount   = $senderid->price;
                        $paymentCurrency = $senderid->currency->code;
                        $reference       = uniqid();
                        $odate           = date('Y/m/d H:i');
                        $redirectURL     = route('customer.senderid.payment_success', $senderid->uid);
                        $backURL         = route('customer.senderid.payment_cancel', $senderid->uid);

                        $item_name = __('locale.sender_id.payment_for_sender_id') . ' ' . $senderid->sender_id;

                        $customer_email      = auth()->user()->email;
                        $customer_first_name = auth()->user()->first_name;
                        $customer_last_name  = auth()->user()->last_name;

                        $postXml = <<<POSTXML
<?xml version="1.0" encoding="utf-8"?>
        <API3G>
        <CompanyToken>$companyToken</CompanyToken>
        <Request>createToken</Request>
        <Transaction>
        <PaymentAmount>$paymentAmount</PaymentAmount>
        <PaymentCurrency>$paymentCurrency</PaymentCurrency>
        <CompanyRef>$reference</CompanyRef>
        <customerEmail>$customer_email</customerEmail>
        <customerFirstName>$customer_first_name</customerFirstName>
        <customerLastName>$customer_last_name</customerLastName>
        <RedirectURL>$redirectURL</RedirectURL>
        <BackURL>$backURL</BackURL>
        <TransactionSource>whmcs</TransactionSource>
        </Transaction>
        <Services>
        <Service>
        <ServiceType>$accountType</ServiceType>
        <ServiceDescription>$item_name</ServiceDescription>
        <ServiceDate>$odate</ServiceDate>
        </Service>
        </Services>
        </API3G>
POSTXML;

                        $curl = curl_init();
                        curl_setopt_array($curl, [
                            CURLOPT_URL            => $payment_url . '/API/v6/',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING       => '',
                            CURLOPT_MAXREDIRS      => 10,
                            CURLOPT_TIMEOUT        => 30,
                            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST  => 'POST',
                            CURLOPT_SSL_VERIFYPEER => false,
                            CURLOPT_SSL_VERIFYHOST => false,
                            CURLOPT_POSTFIELDS     => $postXml,
                            CURLOPT_HTTPHEADER     => [
                                'cache-control: no-cache',
                            ],
                        ]);

                        $response = curl_exec($curl);
                        $error    = curl_error($curl);

                        curl_close($curl);

                        if ($response != '') {
                            $xml = new SimpleXMLElement($response);

                            if ($xml->xpath('Result')[0] != '000') {
                                return response()->json([
                                    'status'  => 'error',
                                    'message' => ! empty($error) ? $error : 'Unknown error occurred in token creation',
                                ]);
                            }

                            $transToken = $xml->xpath('TransToken')[0]->__toString();

                            try {
                                $curl = curl_init();
                                curl_setopt_array($curl, [
                                    CURLOPT_URL            => $payment_url . '/API/v6/',
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_ENCODING       => '',
                                    CURLOPT_MAXREDIRS      => 10,
                                    CURLOPT_TIMEOUT        => 30,
                                    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                                    CURLOPT_CUSTOMREQUEST  => 'POST',
                                    CURLOPT_POSTFIELDS     => "<?xml version=\"1.0\" encoding=\"utf-8\"?>\r\n<API3G>\r\n  <CompanyToken>" . $companyToken . "</CompanyToken>\r\n  <Request>verifyToken</Request>\r\n  <TransactionToken>" . $transToken . "</TransactionToken>\r\n</API3G>",
                                    CURLOPT_HTTPHEADER     => [
                                        'cache-control: no-cache',
                                    ],
                                ]);

                                $response = curl_exec($curl);
                                $err      = curl_error($curl);

                                curl_close($curl);

                                if (strlen($err) > 0) {

                                    return response()->json([
                                        'status'  => 'error',
                                        'message' => $err,
                                    ]);
                                }

                                $verify = new SimpleXMLElement($response);
                                if ($verify->Result->__toString() === '900') {

                                    Session::put('payment_method', $paymentMethod->type);

                                    return response()->json([
                                        'status'       => 'success',
                                        'redirect_url' => $payment_url . '/payv2.php?ID=' . $transToken,
                                    ]);
                                }
                            } catch (Exception $e) {

                                return response()->json([
                                    'status'  => 'error',
                                    'message' => $e->getMessage(),
                                ]);
                            }
                        }

                        return response()->json([
                            'status'  => 'error',
                            'message' => ! empty($error) ? $error : 'Unknown error occurred in token creation',
                        ]);

                    case PaymentMethods::TYPE_PAYGATEGLOBAL:

                        $order_id = str_random(10);

                        $parameters = [
                            'token'    => $credentials->api_key,
                            'amount'   => $senderid->price,
                            'identify' => $order_id,
                            'url'      => route('customer.senderid.payment_success', $senderid->uid),
                        ];
                        $parameters = http_build_query($parameters);

                        return response()->json([
                            'status'       => 'success',
                            'redirect_url' => 'https://paygateglobal.com/v1/page?' . $parameters,
                        ]);

                    case PaymentMethods::TYPE_ORANGEMONEY:

                        $payment = new OrangeMoney($credentials->auth_header, $credentials->merchant_key);

                        $data = [
                            'merchant_key' => $credentials->merchant_key,
                            'currency'     => $senderid->currency->code,
                            'order_id'     => str_random(10),
                            'amount'       => $senderid->price,
                            'payment_url'  => $credentials->payment_url,
                            'return_url'   => route('customer.senderid.payment_cancel', $senderid->uid),
                            'cancel_url'   => route('customer.senderid.payment_cancel', $senderid->uid),
                            'notif_url'    => route('customer.senderid.payment_success', $senderid->uid),
                            'lang'         => config('app.locale'),
                            'reference'    => $senderid->uid,
                        ];

                        $callback_data = $payment->getPaymentUrl($data);

                        if (array_key_exists('payment_url', $callback_data)) {

                            Session::put('payment_method', $paymentMethod->type);
                            Session::put('payment_request_id', $callback_data['notif_token']);

                            return response()->json([
                                'status'       => 'success',
                                'redirect_url' => $callback_data['payment_url'],
                            ]);
                        } else if (array_key_exists('error', $callback_data)) {
                            return response()->json([
                                'status'  => 'error',
                                'message' => $callback_data['error'],
                            ]);
                        } else {
                            return response()->json([
                                'status'  => 'error',
                                'message' => 'FAILED TO CONNECT WITH OrangeMoney API',
                            ]);
                        }

                    case PaymentMethods::TYPE_CINETPAY:

                        $transaction_id = str_random(10);

                        $payment_data = [
                            'apikey'                => $credentials->api_key,
                            'site_id'               => $credentials->site_id,
                            'transaction_id'        => $transaction_id,
                            'amount'                => $senderid->price,
                            'currency'              => $senderid->currency->code,
                            'description'           => __('locale.sender_id.payment_for_sender_id') . ' ' . $senderid->sender_id,
                            'customer_name'         => $input['first_name'] . ' ' . $input['last_name'],
                            'customer_email'        => $input['email'],
                            'customer_phone_number' => $input['phone'],
                            'customer_address'      => $input['address'],
                            'customer_city'         => $input['city'],
                            'customer_country'      => Country::getIsoCode($input['country']),
                            'return_url'            => route('customer.senderid.payment_success', $senderid->uid),
                            'notify_url'            => route('customer.senderid.payment_cancel', $senderid->uid),
                            'channels'              => 'ALL',
                            'lang'                  => config('app.locale'),
                            'metadata'              => 'sender_id_' . $senderid->uid,
                        ];

                        if (isset($input['postcode'])) {
                            $payment_data['customer_zip_code'] = $input['postcode'];
                        }

                        try {

                            $curl = curl_init();

                            curl_setopt_array($curl, [
                                CURLOPT_URL            => $credentials->payment_url,
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_CUSTOMREQUEST  => 'POST',
                                CURLOPT_POSTFIELDS     => json_encode($payment_data),
                                CURLOPT_HTTPHEADER     => [
                                    'content-type: application/json',
                                    'cache-control: no-cache',
                                ],
                            ]);

                            $response = curl_exec($curl);
                            $err      = curl_error($curl);

                            curl_close($curl);

                            if ($response === false) {
                                return response()->json([
                                    'status'  => 'error',
                                    'message' => 'Php curl show false value. Please contact with your provider',
                                ]);
                            }

                            if ($err) {
                                return response()->json([
                                    'status'  => 'error',
                                    'message' => $err,
                                ]);
                            }

                            $result = json_decode($response, true);

                            if (is_array($result) && array_key_exists('code', $result)) {
                                if ($result['code'] == '201') {

                                    Session::put('payment_method', $paymentMethod->type);
                                    Session::put('cinetPay_transaction_id', $transaction_id);
                                    Session::put('cinetPay_payment_token', $result['data']['payment_token']);

                                    return response()->json([
                                        'status'       => 'success',
                                        'redirect_url' => $result['data']['payment_url'],
                                    ]);
                                }

                                return response()->json([
                                    'status'  => 'error',
                                    'message' => $result['message'],
                                ]);
                            }

                            return response()->json([
                                'status'       => 'error',
                                'redirect_url' => __('locale.exceptions.something_went_wrong'),
                            ]);
                        } catch (Exception $ex) {

                            return response()->json([
                                'status'       => 'error',
                                'redirect_url' => $ex->getMessage(),
                            ]);
                        }

                    /*Version 3.6*/
                    case PaymentMethods::TYPE_PAYHERELK:

                        Session::put('payment_method', $paymentMethod->type);
                        $order_id = str_random(10);

                        $checkout = new PayHereLK($credentials->environment);

                        $checkout->param('merchant_id', $credentials->merchant_id);
                        $checkout->param('items', __('locale.sender_id.payment_for_sender_id') . ' ' . $senderid->sender_id);
                        $checkout->param('amount', $senderid->price);
                        $checkout->param('currency', $senderid->currency->code);
                        $checkout->param('order_id', $order_id);
                        $checkout->param('return_url', route('customer.senderid.payment_success', ['senderid' => $senderid->uid, 'payment_method' => $paymentMethod->type]));
                        $checkout->param('cancel_url', route('customer.senderid.payment_cancel', $senderid->uid));
                        $checkout->param('notify_url', route('customer.senderid.payment_cancel', $senderid->uid));
                        $checkout->param('first_name', $input['first_name']);

                        if (isset($input['last_name'])) {
                            $checkout->param('last_name', $input['last_name']);
                        } else {
                            $checkout->param('last_name', $input['first_name']);
                        }

                        $checkout->param('email', $input['email']);
                        $checkout->param('address', $input['address']);
                        $checkout->param('city', $input['city']);
                        $checkout->param('country', $input['country']);
                        $checkout->param('phone', $input['phone']);

                        $checkout->gw_submit();
                        exit();

                    case PaymentMethods::TYPE_MOLLIE:

                        $item_name = __('locale.sender_id.payment_for_sender_id') . ' ' . $senderid->sender_id;

                        $mollie = new MollieApiClient();
                        $mollie->setApiKey($credentials->api_key);
                        $payment = $mollie->payments->create([
                            'amount'      => [
                                'currency' => $senderid->currency->code,
                                'value'    => number_format((float) $senderid->price, 2, '.', ''),
                            ],
                            'description' => $item_name,
                            'redirectUrl' => route('customer.senderid.payment_success', $senderid->uid),
                            'metadata'    => [
                                'user'           => \auth()->user()->id,
                                'senderid'       => $senderid->uid,
                                'payment_method' => $paymentMethod->uid,
                            ],
                        ]);

                        Session::put('payment_method', $paymentMethod->type);
                        Session::put('payment_id', $payment->id);

                        return response()->json([
                            'status'       => 'success',
                            'redirect_url' => $payment->getCheckoutUrl(),
                        ]);

                    case PaymentMethods::TYPE_EASYPAY:

                        $item_name = __('locale.sender_id.payment_for_sender_id') . ' ' . $senderid->sender_id;

                        $body = [
                            'type'    => ['single'],
                            'payment' => [
                                'methods'         => ['cc', 'mb', 'mbw', 'dd', 'vi', 'uf', 'sc'],
                                'type'            => 'sale',
                                'capture'         => [
                                    'descriptive' => $item_name,
                                ],
                                'currency'        => $senderid->currency->code,
                                'expiration_time' => null,
                            ],
                            'order'   => [
                                'key'   => $senderid->uid,
                                'value' => floatval($senderid->price),
                                'items' => [
                                    [
                                        'key'         => $senderid->uid,
                                        'description' => $item_name,
                                        'value'       => floatval($senderid->price),
                                        'quantity'    => 1,
                                    ],
                                ],
                            ],
                        ];

                        $headers = [
                            'AccountId: ' . $credentials->account_id,
                            'ApiKey: ' . $credentials->api_key,
                            'Content-Type: application/json',
                        ];

                        $curlOpts = [
                            CURLOPT_URL            => $credentials->payment_url,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POST           => 1,
                            CURLOPT_TIMEOUT        => 60,
                            CURLOPT_POSTFIELDS     => json_encode($body),
                            CURLOPT_HTTPHEADER     => $headers,
                        ];

                        $curl = curl_init();
                        curl_setopt_array($curl, $curlOpts);
                        $response = curl_exec($curl);
                        curl_close($curl);

                        return response()->json([
                            'status' => 'success',
                            'data'   => $response,
                        ]);

                    case PaymentMethods::TYPE_FEDAPAY:
                        return response()->json([
                            'status'     => 'success',
                            'public_key' => $credentials->public_key,
                            'sender_id'  => $senderid,
                        ]);

                    /*Version 3.8*/

                    case PaymentMethods::TYPE_SELCOMMOBILE:
                        Session::put('payment_method', $paymentMethod->type);
                        $item_name = __('locale.sender_id.payment_for_sender_id') . ' ' . $senderid->sender_id;

                        $orderMinArray = [
                            'vendor'       => $credentials->vendor,
                            'order_id'     => $senderid->uid,
                            'buyer_email'  => $input['email'],
                            'buyer_name'   => $input['first_name'] . ' ' . $input['last_name'],
                            'buyer_phone'  => $input['phone'],
                            'amount'       => $senderid->price,
                            'currency'     => $senderid->currency->code,
                            'redirect_url' => base64_encode(route('customer.senderid.payment_success', $senderid->uid)),
                            'cancel_url'   => base64_encode(route('customer.senderid.payment_cancel', $senderid->uid)),
                            'webhook'      => base64_encode(route('customer.senderid.payment_cancel', $senderid->uid)),

                            'billing.firstname'         => $input['first_name'],
                            'billing.lastname'          => $input['last_name'],
                            'billing.address_1'         => $input['address'],
                            'billing.city'              => $input['city'],
                            'billing.state_or_region'   => $input['city'],
                            'billing.postcode_or_pobox' => $input['postcode'],
                            'billing.country'           => $input['country'],
                            'billing.phone'             => $input['phone'],
                            'buyer_remarks'             => $item_name,
                            'merchant_remarks'          => $item_name,
                            'payment_methods'           => 'ALL',
                            'no_of_items'               => 1,
                        ];

                        $client = new Client($credentials->payment_url, $credentials->api_key, $credentials->api_secret);

                        // path relative to base url
                        $orderMinPath = '/checkout/create-order';

                        // create order minimal
                        try {
                            $response = $client->postFunc($orderMinPath, $orderMinArray);

                            if (isset($response) && is_array($response) && array_key_exists('data', $response) && array_key_exists('result', $response)) {
                                if ($response['result'] == 'SUCCESS') {
                                    return response()->json([
                                        'status'       => 'success',
                                        'message'      => $response['message'],
                                        'redirect_url' => base64_decode($response['data'][0]['payment_gateway_url']),
                                    ]);
                                } else {
                                    return response()->json([
                                        'status'  => 'error',
                                        'message' => $response['message'],
                                    ]);
                                }
                            }

                            return response()->json([
                                'status'  => 'error',
                                'message' => $response,
                            ]);

                        } catch (Exception $exception) {
                            return response()->json([
                                'status'  => 'error',
                                'message' => $exception->getMessage(),
                            ]);
                        }

                    /*Version 3.9*/
                    case PaymentMethods::TYPE_MPGS:
                        $config = [
                            'payment_url'             => $credentials->payment_url,
                            'api_version'             => $credentials->api_version,
                            'merchant_id'             => $credentials->merchant_id,
                            'authentication_password' => $credentials->authentication_password,
                        ];

                        if (isset($credentials->merchant_name)) {
                            $config['merchant_name'] = $credentials->merchant_name;
                        }

                        if (isset($credentials->merchant_address)) {
                            $config['merchant_address'] = $credentials->merchant_address;
                        }

                        $order_id = uniqid();

                        $paymentData = [
                            'user_id'     => Auth::user()->id,
                            'order_id'    => $order_id,
                            'amount'      => $senderid->price,
                            'currency'    => $senderid->currency->code,
                            'first_name'  => $input['first_name'],
                            'last_name'   => $input['last_name'],
                            'phone'       => $input['phone'],
                            'email'       => $input['email'],
                            'address'     => $input['address'],
                            'city'        => $input['city'],
                            'country'     => $input['country'],
                            'post_code'   => $input['postcode'],
                            'description' => __('locale.sender_id.payment_for_sender_id') . ' ' . $senderid->sender_id,
                            'cancel_url'  => route('customer.senderid.payment_cancel', $senderid->uid),
                            'return_url'  => route('customer.senderid.payment_success', [
                                'senderid'       => $senderid->uid,
                                'payment_method' => PaymentMethods::TYPE_MPGS,
                                'order_id'       => $order_id,
                            ]),
                        ];

                        $mpgs      = new MPGS($config, $paymentData);
                        $getResult = $mpgs->submit();

                        if (isset($getResult->getData()->status) && $getResult->getData()->status == 'error') {
                            return response()->json([
                                'status'  => 'error',
                                'message' => $getResult->getData()->message,
                            ]);
                        }

                        exit();

                    case PaymentMethods::TYPE_0XPROCESSING:

                        $billing_id = uniqid();

                        Session::put('payment_method', $paymentMethod->type);
                        Session::put('order_id', $billing_id);

                        $payload = [
                            'MerchantId' => $credentials->merchant_id,
                            'ClientId'   => auth()->user()->id,
                            'AmountUsd'  => $senderid->price,
                            'Currency'   => $senderid->currency->code,
                            'BillingId'  => $billing_id,
                            'Email'      => $input['email'],
                            'FirstName'  => $input['first_name'],
                            'SuccessUrl' => route('customer.senderid.payment_success', [
                                'senderid'       => $senderid->uid,
                                'payment_method' => PaymentMethods::TYPE_0XPROCESSING,
                                'order_id'       => $billing_id,
                            ]),
                            'CancelUrl'  => route('customer.senderid.payment_cancel', $senderid->uid),
                            'AutoReturn' => true,
                            'ReturnUrl'  => true,
                        ];

                        if ($credentials->environment == 'sandbox') {
                            $payload['Test'] = true;
                        } else {
                            $payload['Test'] = false;
                        }

                        if (isset($input['last_name'])) {
                            $payload['LastName'] = $input['last_name'];
                        }

                        $sending_url = 'https://app.0xprocessing.com/payment/create/?' . http_build_query($payload);

                        return response()->json([
                            'status'       => 'success',
                            'redirect_url' => $sending_url,
                        ]);

                    case PaymentMethods::TYPE_CASH:

                        return response()->json([
                            'status' => 'success',
                            'data'   => $credentials,
                        ]);

                }

                return response()->json([
                    'status'  => 'error',
                    'message' => __('locale.payment_gateways.not_found'),
                ]);
            }

            return response()->json(['status'  => 'error',
                                     'message' => __('locale.payment_gateways.not_found'),]);

        }

    }
