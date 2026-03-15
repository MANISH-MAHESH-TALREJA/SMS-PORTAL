<?php
// 1. CONFIGURATION
$merchantId = "MAKEMEONLINE";
$apiKey = "22e07b88-e03f-4f4b-91e9-a18947ad9824";
$keyIndex = 1;
$mode = "PROD"; 
$apiHost = "https://api.phonepe.com/apis/hermes/pg/v1/pay";

// 2. CAPTURE DATA
$amount   = (int)($_GET['amount'] * 100); // Amount in Paise
$mobile   = $_GET['mobile'];
$transactionId = "TXN" . time();

// 3. PREPARE PAYLOAD
$payload = [
    "merchantId" => $merchantId,
    "merchantTransactionId" => $transactionId,
    "merchantUserId" => "MUID" . substr($mobile, -4),
    "amount" => $amount,
    "redirectUrl" => "https://makememessage.com/callback.php",
    "redirectMode" => "POST",
    "callbackUrl" => "https://makememessage.com/callback.php",
    "mobileNumber" => $mobile,
    "paymentInstrument" => ["type" => "PAY_PAGE"]
];

$base64Payload = base64_encode(json_encode($payload));
$checksum = hash('sha256', $base64Payload . "/pg/v1/pay" . $apiKey) . '###' . $keyIndex;

// 4. API CALL TO GET REDIRECT URL
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $apiHost,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode(['request' => $base64Payload]),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "X-VERIFY: $checksum",
        "accept: application/json"
    ],
]);

$response = json_decode(curl_exec($curl), true);
curl_close($curl);

// The 'tokenUrl' is the redirectUrl from the API response
$tokenUrl = $response['data']['instrumentResponse']['redirectInfo']['url'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>PhonePe Mercury Pay</title>
    <script src="https://mercury.phonepe.com/web/bundle/checkout.js"></script>
</head>
<body style="text-align:center; padding:50px; font-family: sans-serif;">

    <?php if ($tokenUrl): ?>
        <h2>Ready to Pay ₹<?php echo $_GET['amount']; ?></h2>
        <button id="payButton" style="padding:15px 35px; background:#5f259f; color:white; border:none; border-radius:8px; cursor:pointer;">
            Pay Now
        </button>
    <?php else: ?>
        <p style="color:red;">Error: Could not generate payment session. Check API logs.</p>
        <pre><?php print_r($response); ?></pre>
    <?php endif; ?>

    <script>
        document.getElementById('payButton').addEventListener('click', function() {
            if (window.PhonePeCheckout && window.PhonePeCheckout.transact) {
                window.PhonePeCheckout.transact({
                    tokenUrl: "<?php echo $tokenUrl; ?>",
                    type: "IFRAME", // Use "REDIRECT" if you prefer a new page
                    callback: function(response) {
                        if (response === 'CONCLUDED') {
                            window.location.href = "success.php";
                        } else if (response === 'USER_CANCEL') {
                            alert("Payment Cancelled by User");
                        }
                    }
                });
            } else {
                alert("SDK not loaded yet. Check your connection or Ad-blocker.");
            }
        });
    </script>
</body>
</html>