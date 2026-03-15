<?php
// Retrieve the response
$response = $_POST;

if (isset($response['code']) && $response['code'] == 'PAYMENT_SUCCESS') {
    // 1. SUCCESS LOGIC
    // Update your database, send an email, etc.
    echo "<h1>Payment Successful!</h1>";
    echo "Transaction ID: " . $response['transactionId'];
} else {
    // 2. FAILURE LOGIC
    echo "<h1>Payment Failed or Pending</h1>";
    // Log the error for debugging
}
?>