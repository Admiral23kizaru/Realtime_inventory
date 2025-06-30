<?php
require_once 'config.php';
require_once 'auth.php';

$added = false;
$edited = false;
$deleted = false;
// Fetch all products and customers for dropdowns
$stmt = $pdo->query("SELECT id, name FROM products");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT id, name FROM customers");
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_sale'])) {
    $product_id = $_POST['product_id'];
    $customer_id = $_POST['customer_id'];
    $quantity = $_POST['quantity'];
    $stmt = $pdo->prepare("INSERT INTO sales (product_id, customer_id, quantity) VALUES (?, ?, ?)");
    $stmt->execute([$product_id, $customer_id, $quantity]);
    $added = true;
}
// Handle edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_sale'])) {
    $id = $_POST['edit_id'];
    $product_id = $_POST['edit_product_id'];
    $customer_id = $_POST['edit_customer_id'];
    $quantity = $_POST['edit_quantity'];
    $stmt = $pdo->prepare("UPDATE sales SET product_id=?, customer_id=?, quantity=? WHERE id=?");
    $stmt->execute([$product_id, $customer_id, $quantity, $id]);
    $edited = true;
}
// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_sale'])) {
    $id = $_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM sales WHERE id=?");
    $stmt->execute([$id]);
    $deleted = true;
}

// Fetch all sales
$stmt = $pdo->query("SELECT s.*, p.name as product_name, c.name as customer_name FROM sales s JOIN products p ON s.product_id = p.id JOIN customers c ON s.customer_id = c.id");
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Management</title>
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
        <a class="nav-link" href="customers.php"><i class="fas fa-users"></i> Customers</a>
        <a class="nav-link active" href="sales.php"><i class="fas fa-cash-register"></i> Sales</a>
        <a class="nav-link" href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
        <a class="nav-link text-danger mt-4" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    <div class="header">Sales Management</div>
    <div class="main-content fade-in">
        <div class="card p-4 slide-in mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0"><i class="fas fa-cash-register text-primary"></i> Record Sale</h3>
                <button class="btn btn-primary" data-toggle="modal" data-target="#addSaleModal"><i class="fas fa-plus"></i> New Sale</button>
            </div>
        </div>
        <div class="card p-4 slide-in">
            <h4 class="mb-3"><i class="fas fa-list"></i> Sales List</h4>
            <input class="form-control mb-3" id="searchInput" type="text" placeholder="Search sales...">
            <div class="table-responsive">
                <table class="table table-bordered" id="salesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Customer</th>
                            <th>Quantity</th>
                            <th>Sale Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales as $sale): ?>
                        <tr class="fade-in">
                            <td><?php echo $sale['id']; ?></td>
                            <td><?php echo $sale['product_name']; ?></td>
                            <td><?php echo $sale['customer_name']; ?></td>
                            <td><?php echo $sale['quantity']; ?></td>
                            <td><?php echo $sale['sale_date']; ?></td>
                            <td>
                                <button class="btn btn-sm btn-info editBtn" 
                                    data-id="<?php echo $sale['id']; ?>" 
                                    data-product_id="<?php echo $sale['product_id']; ?>" 
                                    data-customer_id="<?php echo $sale['customer_id']; ?>" 
                                    data-quantity="<?php echo $sale['quantity']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger deleteBtn" 
                                    data-id="<?php echo $sale['id']; ?>">
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
    <!-- Add Sale Modal -->
    <div class="modal fade" id="addSaleModal" tabindex="-1" role="dialog" aria-labelledby="addSaleModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST">
            <input type="hidden" name="add_sale" value="1">
            <div class="modal-header">
              <h5 class="modal-title" id="addSaleModalLabel"><i class="fas fa-plus"></i> Record New Sale</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="product_id">Product</label>
                    <select class="form-control" id="product_id" name="product_id" required>
                        <option value="">Select Product</option>
                        <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>"><?php echo $product['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="customer_id">Customer</label>
                    <select class="form-control" id="customer_id" name="customer_id" required>
                        <option value="">Select Customer</option>
                        <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo $customer['id']; ?>"><?php echo $customer['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" class="form-control" id="quantity" name="quantity" required>
                </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Record Sale</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- Edit Sale Modal -->
    <div class="modal fade" id="editSaleModal" tabindex="-1" role="dialog" aria-labelledby="editSaleModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST">
            <input type="hidden" name="edit_sale" value="1">
            <input type="hidden" id="edit_id" name="edit_id">
            <div class="modal-header">
              <h5 class="modal-title" id="editSaleModalLabel"><i class="fas fa-edit"></i> Edit Sale</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_product_id">Product</label>
                    <select class="form-control" id="edit_product_id" name="edit_product_id" required>
                        <option value="">Select Product</option>
                        <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>"><?php echo $product['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_customer_id">Customer</label>
                    <select class="form-control" id="edit_customer_id" name="edit_customer_id" required>
                        <option value="">Select Customer</option>
                        <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo $customer['id']; ?>"><?php echo $customer['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_quantity">Quantity</label>
                    <input type="number" class="form-control" id="edit_quantity" name="edit_quantity" required>
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
    <!-- Delete Sale Modal -->
    <div class="modal fade" id="deleteSaleModal" tabindex="-1" role="dialog" aria-labelledby="deleteSaleModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST">
            <input type="hidden" name="delete_sale" value="1">
            <input type="hidden" id="delete_id" name="delete_id">
            <div class="modal-header">
              <h5 class="modal-title" id="deleteSaleModalLabel"><i class="fas fa-trash"></i> Delete Sale</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this sale?</p>
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
        $("#salesTable tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
    // Edit modal
    $(".editBtn").on("click", function() {
        $("#edit_id").val($(this).data('id'));
        $("#edit_product_id").val($(this).data('product_id'));
        $("#edit_customer_id").val($(this).data('customer_id'));
        $("#edit_quantity").val($(this).data('quantity'));
        $("#editSaleModal").modal('show');
    });
    // Delete modal
    $(".deleteBtn").on("click", function() {
        $("#delete_id").val($(this).data('id'));
        $("#deleteSaleModal").modal('show');
    });
    </script>
    <?php if ($added): ?>
    <script>$(function(){ showToast('Sale recorded successfully!'); });</script>
    <?php endif; ?>
    <?php if ($edited): ?>
    <script>$(function(){ showToast('Sale updated successfully!'); });</script>
    <?php endif; ?>
    <?php if ($deleted): ?>
    <script>$(function(){ showToast('Sale deleted successfully!'); });</script>
    <?php endif; ?>
</body>
</html> 