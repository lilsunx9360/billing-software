


<?php
ob_start();
include_once 'connectdb.php';
session_start();

// Get phone number from POST request
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

if (!empty($phone)) {
    // Query the tbl_customer table
    $stmt = $pdo->prepare("SELECT name, gender, city, zipcode, dob, age FROM tbl_customer WHERE phone = ?");
    $stmt->execute([$phone]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($customer) {
        // Return customer data
        echo json_encode(['status' => 'success', 'data' => $customer]);
    } else {
        // No customer found
        echo json_encode(['status' => 'error', 'message' => 'No customer found']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid phone number']);
}
?>