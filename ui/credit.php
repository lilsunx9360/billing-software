<?php
include_once 'connectdb.php';
session_start();

if ($_SESSION['useremail'] == "" || $_SESSION['role'] == "") {
    header('location:../index.php');
}

if ($_SESSION['role'] == "Admin") {
    include_once 'header.php';
} else {
    include_once 'headeruser.php';
}
?>

<!-- Add custom CSS for sidebar scroll and layout -->
<style>
/* Ensure the sidebar is scrollable */
.main-sidebar {
    height: 100vh;
    position: fixed;
    overflow-y: hidden; 
    top: 0;
    bottom: 0;
    
}

/* Adjust content wrapper to prevent overlap with sidebar */
.content-wrapper {
    margin-left: 250px; /* Adjust based on your sidebar width */
    min-height: 100vh;
    overflow-y: scroll; /* Allow scrolling for content */
    padding-bottom: 60px; /* Ensure space for footer */
}

/* Ensure footer stays at the bottom */
.main-footer {
    position: fixed;
    bottom: 0;
    width: calc(100% - 250px); /* Adjust based on sidebar width */
    margin-left: 250px; /* Align with content wrapper */
    background: #fff;
    border-top: 1px solid #dee2e6;
    z-index: 1000;
}

/* Ensure table responsiveness */
.table-responsive {
    overflow-x: auto;
}

/* Fix table layout for small screens */
@media (max-width: 768px) {
    .content-wrapper, .main-footer {
        margin-left: 0;
        width: 100%;
    }
    .main-sidebar {
        position: absolute;
    }
}
</style>

<div class="content-wrapper ">
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h5 class="m-0">Credit Orders</h5>
                            <div class="float-right">
                           <button id="btnRepay" class="btn btn-info" style="margin-right: 10px;">
                           <span class="fa fa-reply" style="color:#ffffff"></span>
                           Repay
                           </button>
                           <button id="btnNotifyAll" class="btn btn-info">
                                    <span class="fa fa-bell" style="color:#ffffff"></span> Send Notifications
                           </button>
                           </div>
                             <div style="clear: both;"></div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="table_orderlist">
                                    <thead>
                                        <tr>
                                          
                                            <th>Name</th>
                                            <th>Order Date</th>
                                            <th>Total</th>
                                            <th>Paid</th>
                                            <th>Due</th>
                                            <th>phone</th>
                                            <th>Payment Type</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
$select = $pdo->prepare("
    SELECT 
        MIN(i.invoice_id) as invoice_id, 
        MAX(i.name) as name, 
        MAX(i.order_date) as order_date, 
        SUM(i.total) as total, 
        SUM(i.paid) as total_paid, 
        SUM(i.due) as total_due, 
        i.phone, 
        i.payment_type
    FROM tbl_invoice i
    INNER JOIN customer_ids c ON i.invoice_id = c.invoice_id
    WHERE i.payment_type = 'CREDIT'
    GROUP BY i.phone
    ORDER BY MIN(i.invoice_id) DESC
");
$select->execute();

while ($row = $select->fetch(PDO::FETCH_OBJ)) {
    echo '
    <tr data-invoice-id="'.$row->invoice_id.'">
        <td>'.$row->name.'</td>
        <td>'.$row->order_date.'</td>
        <td>'.$row->total.'</td>
        <td>'.$row->total_paid.'</td>
        <td>'.$row->total_due.'</td>
        <td>'.$row->phone.'</td>
        <td><span class="badge badge-danger">'.$row->payment_type.'</span></td>
        <td>
            <button class="btn btn-danger btn-sm btndelete" data-id="'.$row->invoice_id.'">
                <i class="fa fa-trash"></i>
            </button>
        </td>
    </tr>';
}
?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <h3 class="m-2 p-2">Credit Payment History</h3>
            <div class="row">
                <div class="col-lg-12">
                    <div class="card card-primary card-outline">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="table_history">
                                    <thead>
                                        <tr>
                                           
                                            <th>Name</th>
                                
                                            <th>Order Date</th>
                                            <th>Total</th>
                                            <th>Paid</th>
                                            <th>Due</th>
                                            <th>phone</th>
                                            <th>Payment Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
$select_history = $pdo->prepare("
    SELECT 
        MAX(i.name) as name, 
        MAX(i.order_date) as order_date, 
        SUM(i.total) as total, 
        SUM(i.paid) as total_paid, 
        SUM(i.due) as total_due, 
        i.phone, 
        i.payment_type
    FROM tbl_invoice_history i
    WHERE i.payment_type = 'CREDIT'
    GROUP BY i.phone
    ORDER BY MAX(i.invoice_id) DESC
");
$select_history->execute();

while ($row = $select_history->fetch(PDO::FETCH_OBJ)) {
    echo '
    <tr>
        <td>'.$row->name.'</td>
        <td>'.$row->order_date.'</td>
        <td>'.$row->total.'</td>
        <td>'.$row->total_paid.'</td>
        <td>'.$row->total_due.'</td>
        <td>'.$row->phone.'</td>
        <td><span class="badge badge-danger">'.$row->payment_type.'</span></td>
    </tr>';
}
?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
<?php include_once "footer.php"; ?>
<?php
if (isset($_SESSION['message'])) {
    $message_type = strpos($_SESSION['message'], 'Error') === false ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700';
    echo '<div class="' . $message_type . ' border px-4 py-3 rounded relative mb-4" role="alert">';
    echo '<span class="block sm:inline">' . $_SESSION['message'] . '</span>';
    echo '<button type="button" class="absolute top-0 right-0 px-4 py-3" onclick="this.parentElement.remove()">';
    echo '<span>×</span>';
    echo '</button>';
    echo '</div>';
    unset($_SESSION['message']);
}
?>
</div>

<script>
    document.getElementById('btnRepay').addEventListener('click', function() {
      window.location.href = 'repay.php';
    });
  </script>
<script>
$(document).ready(function () {
    $('#table_orderlist').DataTable({
        "order": [[0, "desc"]]
    });
    
    $('#table_history').DataTable({
        "order": [[0, "desc"]]
    });

    $('#btnNotifyAll').click(function() {
        if (confirm('Send notifications for all credit payments?')) {
            $.ajax({
                url: 'run_batch_file.php',
                type: 'POST',
                success: function(response) {
                    alert('Notifications sent successfully');
                },
                error: function() {
                    alert('Error sending notifications');
                }
            });
        }
    });

    $('#table_orderlist').on('click', '.btndelete', function() {
        var invoiceId = $(this).data('id');
        var row = $(this).closest('tr');
        
        if (confirm('Remove this invoice from notifications?')) {
            $.ajax({
                url: 'delete_invoice.php',
                type: 'POST',
                data: { invoice_id: invoiceId },
                success: function(response) {
                    var res = JSON.parse(response);
                    if (res.success) {
                        row.remove();
                        alert(res.message);
                    } else {
                        alert(res.message);
                    }
                }
            });
        }
    });
});
</script>