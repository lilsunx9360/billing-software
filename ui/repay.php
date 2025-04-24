<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Record Repayment</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-blue-100 min-h-screen flex items-center justify-center">
  <?php
  session_start();
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
  <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-xl w-full max-w-md border border-gray-200">
    <!-- Icon -->
    <div class="w-12 h-12 mx-auto flex items-center justify-center rounded-full bg-purple-100 mb-4">
      <i class="fas fa-credit-card text-purple-600 text-xl"></i>
    </div>

    <!-- Title -->
    <h2 class="text-2xl font-bold text-center text-gray-800">Record Repayment</h2>
    <p class="text-sm text-center text-gray-500 mb-6">Log when someone repays you</p>

    <form class="space-y-4" action="process_repayment.php" method="POST">
      <!-- Phone Number Search -->
      <div>
        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number <span class="text-red-500">*</span></label>
        <input type="text" id="phone" name="phone" class="w-full border border-gray-300 rounded-md p-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Enter phone number" required />
      </div>

      <!-- Customer Name (Read-Only) -->
      <div>
        <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-2">Customer Name</label>
        <input type="text" id="customer_name" name="customer_name" class="w-full border border-gray-300 rounded-md p-2 text-sm bg-gray-100" readonly />
       
      <div>
        <label for="total_due" class="block text-sm font-medium text-gray-700 mb-2 mt-2">Total Due (₹)</label>
        <input type="text" id="total_due" name="total_due" class="w-full border border-gray-300 rounded-md p-2 text-sm bg-gray-100" readonly />
      </div>

      <!-- Amount Field -->
      <div>
        <div class="flex justify-between items-center mb-2 mt-2">
          <label for="amount" class="block text-sm font-medium text-gray-700">Amount <span class="text-red-500">*</span></label>
          <label for="full-repay" class="text-sm text-gray-500 flex items-center space-x-2">
            <span>Full Repay</span>
            <input type="checkbox" id="full-repay" class="toggle-checkbox hidden" />
            <div class="toggle-slot relative w-10 h-5 bg-gray-300 rounded-full shadow-inner cursor-pointer">
              <div class="toggle-button absolute w-5 h-5 bg-white rounded-full shadow left-0 transition-transform duration-300 transform" style="transform: translateX(0);"></div>
            </div>
          </label>
        </div>
        <input type="number" id="amount" name="amount" step="0.01" class="w-full border border-gray-300 rounded-md p-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" value="0.00" required />
      </div>

      <!-- Submit Button -->
      <button type="submit" id="submitbtn"  class="w-full mt-2 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md transition duration-200">
        Record Repayment
      </button>
    </form>
  </div>

  <script>
    $(document).ready(function () {
      // Handle phone number input
      $('#phone').on('input', function () {
        var phone = $(this).val();
        if (phone.length >= 10) {
          $.ajax({
            url: 'fetch_customer.php',
            type: 'POST',
            data: { phone: phone },
            dataType: 'json',
            success: function (response) {
              if (response.success) {
                $('#customer_name').val(response.name);
                $('#total_due').val(response.total_due);
                $('#amount').val('0.00'); // Reset amount
                $('#full-repay').prop('checked', false); // Reset full repay checkbox
              } else {
                $('#customer_name').val('');
                $('#total_due').val('');
                $('#amount').val('0.00');
                alert(response.message);
              }
            },
            error: function () {
              $('#customer_name').val('');
              $('#total_due').val('');
              $('#amount').val('0.00');
              alert('Error fetching customer details.');
            }
          });
        } else {
          $('#customer_name').val('');
          $('#total_due').val('');
          $('#amount').val('0.00');
        }
      });

      // Handle full repay checkbox
      $('#full-repay').on('change', function () {
        if ($(this).is(':checked')) {
          var totalDue = $('#total_due').val();
          $('#amount').val(totalDue);
        } else {
          $('#amount').val('0.00');
        }
      });

      // Toggle switch styling
      $('#full-repay').on('change', function () {
        var toggleButton = $('.toggle-button');
        if ($(this).is(':checked')) {
          toggleButton.css('transform', 'translateX(100%)');
        } else {
          toggleButton.css('transform', 'translateX(0)');
        }
      });

      // Confirm form submission
      $('form').on('submit', function (e) {
        if (!confirm('Are you sure you want to record this repayment?')) {
          e.preventDefault();
        }
      });
    });
  </script>
</body>
</html>