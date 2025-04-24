<?php
include_once 'connectdb.php';

header('Content-Type: application/json');

if (isset($_POST['phone'])) {
    $phone = $_POST['phone'];

    // Query to fetch customer details and total due for credit orders
    $query = "
        SELECT 
            MAX(i.name) as name, 
            SUM(i.due) as total_due
        FROM tbl_invoice i
        INNER JOIN customer_ids c ON i.invoice_id = c.invoice_id
        WHERE i.payment_type = 'CREDIT' AND i.phone = :phone
        GROUP BY i.phone
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute(['phone' => $phone]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode([
            'success' => true,
            'name' => $result['name'],
            'total_due' => number_format($result['total_due'], 2)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No credit orders found for this phone number.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Phone number not provided.'
    ]);
}
?>