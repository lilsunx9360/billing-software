<?php
session_start();
include_once 'connectdb.php'; // Include the database connection

try {
    // Get form data from repay.php
    $phone = $_POST['phone'];
    $amount = floatval($_POST['amount']);
    $customer_name = $_POST['customer_name'];

    // Validate input
    if (empty($phone) || $amount <= 0) {
        $_SESSION['message'] = "Error: Invalid phone number or amount.";
        header('Location: repay.php');
        exit();
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Fetch customer details from tbl_customer to validate phone
    $select_customer = $pdo->prepare("SELECT customer_id, name FROM tbl_customer WHERE phone = :phone");
    $select_customer->bindParam(':phone', $phone);
    $select_customer->execute();
    $customer = $select_customer->fetch(PDO::FETCH_OBJ);

    if (!$customer) {
        $_SESSION['message'] = "Error: Customer not found.";
        $pdo->rollBack();
        header('Location: repay.php');
        exit();
    }

    // Fetch total due for the customer from tbl_invoice, matching credit.php logic
    $select_invoice = $pdo->prepare("
        SELECT SUM(i.due) as total_due
        FROM tbl_invoice i
        INNER JOIN customer_ids c ON i.invoice_id = c.invoice_id
        WHERE i.phone = :phone AND i.payment_type = 'CREDIT'
    ");
    $select_invoice->bindParam(':phone', $phone);
    $select_invoice->execute();
    $invoice = $select_invoice->fetch(PDO::FETCH_OBJ);
    $total_due = $invoice->total_due ?? 0;

    if ($total_due < $amount) {
        $_SESSION['message'] = "Error: Repayment amount exceeds total due.";
        $pdo->rollBack();
        header('Location: repay.php');
        exit();
    }

    // Distribute repayment across tbl_invoice records (CREDIT invoices)
    $remaining_repayment = $amount;
    $select_invoices = $pdo->prepare("
        SELECT i.invoice_id, i.due
        FROM tbl_invoice i
        INNER JOIN customer_ids c ON i.invoice_id = c.invoice_id
        WHERE i.phone = :phone AND i.payment_type = 'CREDIT' AND i.due > 0
        ORDER BY i.invoice_id
    ");
    $select_invoices->bindParam(':phone', $phone);
    $select_invoices->execute();
    $invoices = $select_invoices->fetchAll(PDO::FETCH_OBJ);

    foreach ($invoices as $inv) {
        if ($remaining_repayment <= 0) break;

        $repay_amount = min($inv->due, $remaining_repayment);
        $new_due = $inv->due - $repay_amount;

        // Update tbl_invoice
        $update_invoice = $pdo->prepare("
            UPDATE tbl_invoice
            SET due = :new_due, 
                paid = paid + :repay_amount, 
                repay = repay + :repay_amount
            WHERE invoice_id = :invoice_id
        ");
        $update_invoice->bindParam(':new_due', $new_due);
        $update_invoice->bindParam(':repay_amount', $repay_amount);
        $update_invoice->bindParam(':invoice_id', $inv->invoice_id);
        $update_invoice->execute();

        // Update customer_ids due amount
        $update_customer_ids = $pdo->prepare("
            UPDATE customer_ids
            SET due = :new_due
            WHERE invoice_id = :invoice_id
        ");
        $update_customer_ids->bindParam(':new_due', $new_due);
        $update_customer_ids->bindParam(':invoice_id', $inv->invoice_id);
        $update_customer_ids->execute();

        $remaining_repayment -= $repay_amount;
    }

    // Update or insert into sales table
    $select_sales = $pdo->prepare("SELECT id, paid, repay, due FROM sales WHERE phone = :phone");
    $select_sales->bindParam(':phone', $phone);
    $select_sales->execute();
    $sales_record = $select_sales->fetch(PDO::FETCH_OBJ);

    if ($sales_record) {
        // Update existing sales record
        $new_paid = $sales_record->paid + $amount;
        $new_repay = $sales_record->repay + $amount;
        $new_due = max(0, $sales_record->due - $amount);

        $update_sales = $pdo->prepare("
            UPDATE sales
            SET paid = :paid, repay = :repay, due = :due
            WHERE id = :id
        ");
        $update_sales->bindParam(':paid', $new_paid);
        $update_sales->bindParam(':repay', $new_repay);
        $update_sales->bindParam(':due', $new_due);
        $update_sales->bindParam(':id', $sales_record->id);
        $update_sales->execute();
    } else {
        // Insert new sales record
        $insert_sales = $pdo->prepare("
            INSERT INTO sales (phone, paid, repay, due, customer_id)
            VALUES (:phone, :paid, :repay, :due, :customer_id)
        ");
        $insert_sales->bindParam(':phone', $phone);
        $insert_sales->bindParam(':paid', $amount);
        $insert_sales->bindParam(':repay', $amount);
        $due = max(0, $total_due - $amount);
        $insert_sales->bindParam(':due', $due);
        $insert_sales->bindParam(':customer_id', $customer->customer_id);
        $insert_sales->execute();
    }

    // Commit transaction
    $pdo->commit();

    // Set success message
    $_SESSION['message'] = "Repayment of ₹$amount recorded successfully for $customer_name.";

    // Redirect to credit.php
    header('Location: credit.php');
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    $_SESSION['message'] = "Error: " . $e->getMessage();
    header('Location: repay.php');
    exit();
}
?>