<?php

session_start();
if (!isset($_SESSION['useremail'])) {
    die('Error: User is not logged in.');
}

//call the FPDF library
require('fpdf/fpdf.php');

include_once 'connectdb.php';

$id = $_GET["id"];

// Fetch invoice details
$select = $pdo->prepare("SELECT * FROM tbl_invoice WHERE invoice_id = :id");
$select->bindParam(':id', $id);
$select->execute();
$row = $select->fetch(PDO::FETCH_OBJ);

// Fetch user details based on the logged-in user's email
$useremail = $_SESSION['useremail'];

$userQuery = $pdo->prepare("SELECT company_name,website, phone, address FROM tbl_user WHERE useremail = :email");
$userQuery->bindParam(':email', $useremail);
$userQuery->execute();
$userDetails = $userQuery->fetch(PDO::FETCH_OBJ);

if (!$userDetails) {
    die('Error: User details not found. Please check the database or session.');
}

$companyName = $userDetails->company_name ?? 'N/A';
$website = $userDetails->website ?? 'N/A';
$phone = $userDetails->phone ?? 'N/A';
$address = $userDetails->address ?? 'N/A';

// Create PDF object
$pdf = new FPDF('P', 'mm', array(80, 200));

// Add new page
$pdf->AddPage();

// Add company name
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetTextColor(0, 128, 0); // Set text color to Green (RGB: 0,128,0)
$pdf->Cell(60, 8, strtoupper($companyName), 1, 1, 'C');
$pdf->SetTextColor(0, 0, 0); // Reset text color to Black (default)

// Add phone website 
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(60, 5, 'Webstie: ' . $website, 0, 1, 'C');

// Add phone number
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(60, 5, 'PHONE NUMBER: ' . $phone, 0, 1, 'C');


// Line separator
$pdf->Line(7, 28, 72, 28);
$pdf->Ln(1);

// Add invoice details
$pdf->SetFont('Arial', 'BI', 8);
$pdf->Cell(20, 4, 'Bill No:', 0, 0, '');
$pdf->SetFont('Courier', 'BI', 8);
$pdf->Cell(40, 4, $row->invoice_id, 0, 1, '');

$pdf->SetFont('Arial', 'BI', 8);
$pdf->Cell(20, 4, 'Date:', 0, 0, '');
$pdf->SetFont('Courier', 'BI', 8);
$pdf->Cell(40, 4, $row->order_date, 0, 1, '');

// Add product details
$pdf->SetX(7);
$pdf->SetFont('Courier', 'B', 8);
$pdf->Cell(34, 5, 'PRODUCT', 1, 0, 'C');
$pdf->Cell(7, 5, 'QTY', 1, 0, 'C');
$pdf->Cell(12, 5, 'PRC', 1, 0, 'C');
$pdf->Cell(12, 5, 'TOTAL', 1, 1, 'C');

$select = $pdo->prepare("SELECT * FROM tbl_invoice_details WHERE invoice_id = :id");
$select->bindParam(':id', $id);
$select->execute();

while ($product = $select->fetch(PDO::FETCH_OBJ)) {
    $pdf->SetX(7);
    $pdf->SetFont('Helvetica', 'B', 8);
    $pdf->Cell(34, 5, $product->product_name, 1, 0, 'L');
    $pdf->Cell(7, 5, $product->qty, 1, 0, 'C');
    $pdf->Cell(12, 5, $product->rate, 1, 0, 'C');
    $pdf->Cell(12, 5, $product->rate * $product->qty, 1, 1, 'C');
}

// Add totals and other details
$pdf->SetX(7);
$pdf->SetFont('courier', 'B', 8);
$pdf->Cell(0.1, 5, '', 0, 0, 'L');
$pdf->Cell(34, 5, 'SUBTOTAL(Rs)', 1, 0, 'C');
$pdf->Cell(31, 5, $row->subtotal, 1, 1, 'C');

$pdf->SetX(7);
$pdf->SetFont('courier', 'B', 8);
$pdf->Cell(0.1, 5, '', 0, 0, 'L');
$pdf->Cell(34, 5, 'DISCOUNT %', 1, 0, 'C');
$pdf->Cell(31, 5, $row->discount, 1, 1, 'C');

$discount_rs = $row->discount / 100 * $row->subtotal;

$pdf->SetX(7);
$pdf->SetFont('courier', 'B', 8);
$pdf->Cell(0.1, 5, '', 0, 0, 'L');
$pdf->Cell(34, 5, 'DISCOUNT (Rs)', 1, 0, 'C');
$pdf->Cell(31, 5, $discount_rs, 1, 1, 'C');

$pdf->SetX(7);
$pdf->SetFont('courier', 'B', 8);
$pdf->Cell(0.1, 5, '', 0, 0, 'L');
$pdf->Cell(34, 5, 'SGST %', 1, 0, 'C');
$pdf->Cell(31, 5, $row->sgst, 1, 1, 'C');

$pdf->SetX(7);
$pdf->SetFont('courier', 'B', 8);
$pdf->Cell(0.1, 5, '', 0, 0, 'L');
$pdf->Cell(34, 5, 'CGST %', 1, 0, 'C');
$pdf->Cell(31, 5, $row->cgst, 1, 1, 'C');

$sgst_rs = $row->sgst / 100 * $row->subtotal;
$cgst_rs = $row->cgst / 100 * $row->subtotal;

$pdf->SetX(7);
$pdf->SetFont('courier', 'B', 8);
$pdf->Cell(0.1, 5, '', 0, 0, 'L');
$pdf->Cell(34, 5, 'SGST(Rs)', 1, 0, 'C');
$pdf->Cell(31, 5, $sgst_rs, 1, 1, 'C');

$pdf->SetX(7);
$pdf->SetFont('courier', 'B', 8);
$pdf->Cell(0.1, 5, '', 0, 0, 'L');
$pdf->Cell(34, 5, 'CGST(Rs)', 1, 0, 'C');
$pdf->Cell(31, 5, $cgst_rs, 1, 1, 'C');

$pdf->SetX(7);
$pdf->SetFont('courier', 'B', 10);
$pdf->Cell(0.1, 5, '', 0, 0, 'L');
$pdf->Cell(34, 5, 'G-TOTAL(Rs)', 1, 0, 'C');
$pdf->Cell(31, 5, $row->total, 1, 1, 'C');

$pdf->SetX(7);
$pdf->SetFont('courier', 'B', 8);
$pdf->Cell(0.1, 5, '', 0, 0, 'L');
$pdf->Cell(34, 5, 'PAID(Rs)', 1, 0, 'C');
$pdf->Cell(31, 5, $row->paid, 1, 1, 'C');

$pdf->SetX(7);
$pdf->SetFont('courier', 'B', 8);
$pdf->Cell(0.1, 5, '', 0, 0, 'L');
$pdf->Cell(34, 5, 'DUE(Rs)', 1, 0, 'C');
$pdf->Cell(31, 5, $row->due, 1, 1, 'C');

// Add footer
$pdf->SetX(7);
$pdf->SetFont('Courier', 'B', 8);
$pdf->Cell(25, 5, 'Important Notice:', 0, 1, '');
$pdf->SetX(7);
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(75, 5, 'No Product Will Be Replaced Or Refunded If You Dont Have Bill With You', 0, 2, '');
$pdf->SetX(7);
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(75, 5, 'You Can Refund Within 2 Days Of Purchase', 0, 2, '');

// Add address
$pdf->SetFont('Courier', 'B', 8);
$pdf->Cell(25, 5, 'Address:', 0, 1, '');
$pdf->SetFont('Arial', '', 7);
$pdf->MultiCell(60, 5, $address, 0, 'L');

$pdf->Output();
?>