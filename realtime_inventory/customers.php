<?php
require_once 'config.php';
require_once 'auth.php';

$added = false;
$edited = false;
$deleted = false;
// Handle add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email) VALUES (?, ?, ?)");
    $stmt->execute([$name, $phone, $email]);
    $added = true;
}
// Handle edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_customer'])) {
    $id = $_POST['edit_id'];
    $name = $_POST['edit_name'];
    $phone = $_POST['edit_phone'];
    $email = $_POST['edit_email'];
    $stmt = $pdo->prepare("UPDATE customers SET name=?, phone=?, email=? WHERE id=?");
    $stmt->execute([$name, $phone, $email, $id]);
    $edited = true;
}
// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_customer'])) {
    $id = $_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id=?");
    $stmt->execute([$id]);
    $deleted = true;
}
// Fetch all customers
$stmt = $pdo->query("SELECT * FROM customers");
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="text-center mb-4">
            <img src="https://img.icons8.com/fluency/48/000000/warehouse.png" alt="Logo" style="width:48px;">
        </div>
        <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Dashboard</a>
        <a class="nav-link" href="products.php"><i class="fas fa-box"></i> Products</a>
        <a class="nav-link active" href="customers.php"><i class="fas fa-users"></i> Customers</a>
        <a class="nav-link" href="sales.php"><i class="fas fa-cash-register"></i> Sales</a>
        <a class="nav-link" href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
        <a class="nav-link text-danger mt-4" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    <div class="header">Customer Management</div>
    <div class="main-content fade-in">
        <div class="card p-4 slide-in mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0"><i class="fas fa-users text-primary"></i> Add Customer</h3>
                <button class="btn btn-primary" data-toggle="modal" data-target="#addCustomerModal"><i class="fas fa-plus"></i> New Customer</button>
            </div>
        </div>
        <div class="card p-4 slide-in">
            <h4 class="mb-3"><i class="fas fa-list"></i> Customers List</h4>
            <input class="form-control mb-3" id="searchInput" type="text" placeholder="Search customers...">
            <div class="table-responsive">
                <table class="table table-bordered" id="customersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                        <tr class="fade-in">
                            <td><?php echo $customer['id']; ?></td>
                            <td><?php echo $customer['name']; ?></td>
                            <td><?php echo $customer['phone']; ?></td>
                            <td><?php echo $customer['email']; ?></td>
                            <td>
                                <button class="btn btn-sm btn-info editBtn" 
                                    data-id="<?php echo $customer['id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($customer['name']); ?>" 
                                    data-phone="<?php echo htmlspecialchars($customer['phone']); ?>" 
                                    data-email="<?php echo htmlspecialchars($customer['email']); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger deleteBtn" 
                                    data-id="<?php echo $customer['id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1" role="dialog" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST">
            <input type="hidden" name="add_customer" value="1">
            <div class="modal-header">
              <h5 class="modal-title" id="addCustomerModalLabel"><i class="fas fa-plus"></i> Add New Customer</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="name">Customer Name</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email">
                </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Add Customer</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- Edit Customer Modal -->
    <div class="modal fade" id="editCustomerModal" tabindex="-1" role="dialog" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST">
            <input type="hidden" name="edit_customer" value="1">
            <input type="hidden" id="edit_id" name="edit_id">
            <div class="modal-header">
              <h5 class="modal-title" id="editCustomerModalLabel"><i class="fas fa-edit"></i> Edit Customer</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_name">Customer Name</label>
                    <input type="text" class="form-control" id="edit_name" name="edit_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_phone">Phone</label>
                    <input type="text" class="form-control" id="edit_phone" name="edit_phone">
                </div>
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" class="form-control" id="edit_email" name="edit_email">
                </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- Delete Customer Modal -->
    <div class="modal fade" id="deleteCustomerModal" tabindex="-1" role="dialog" aria-labelledby="deleteCustomerModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST">
            <input type="hidden" name="delete_customer" value="1">
            <input type="hidden" id="delete_id" name="delete_id">
            <div class="modal-header">
              <h5 class="modal-title" id="deleteCustomerModalLabel"><i class="fas fa-trash"></i> Delete Customer</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this customer?</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-danger">Delete</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    // Search filter
    $("#searchInput").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#customersTable tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
    // Edit modal
    $(".editBtn").on("click", function() {
        $("#edit_id").val($(this).data('id'));
        $("#edit_name").val($(this).data('name'));
        $("#edit_phone").val($(this).data('phone'));
        $("#edit_email").val($(this).data('email'));
        $("#editCustomerModal").modal('show');
    });
    // Delete modal
    $(".deleteBtn").on("click", function() {
        $("#delete_id").val($(this).data('id'));
        $("#deleteCustomerModal").modal('show');
    });
    </script>
    <?php if ($added): ?>
    <script>$(function(){ showToast('Customer added successfully!'); });</script>
    <?php endif; ?>
    <?php if ($edited): ?>
    <script>$(function(){ showToast('Customer updated successfully!'); });</script>
    <?php endif; ?>
    <?php if ($deleted): ?>
    <script>$(function(){ showToast('Customer deleted successfully!'); });</script>
    <?php endif; ?>
</body>
</html> 