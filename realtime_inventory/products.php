<?php
require_once 'config.php';
require_once 'auth.php';

$added = false;
$edited = false;
$deleted = false;

// Handle add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $stmt = $pdo->prepare("INSERT INTO products (name, price) VALUES (?, ?)");
    $stmt->execute([$name, $price]);
    $product_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("INSERT INTO stock_levels (product_id, quantity) VALUES (?, ?)");
    $stmt->execute([$product_id, $quantity]);
    $added = true;
}
// Handle edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $id = $_POST['edit_id'];
    $name = $_POST['edit_name'];
    $price = $_POST['edit_price'];
    $quantity = $_POST['edit_quantity'];
    $stmt = $pdo->prepare("UPDATE products SET name=?, price=? WHERE id=?");
    $stmt->execute([$name, $price, $id]);
    $stmt = $pdo->prepare("UPDATE stock_levels SET quantity=? WHERE product_id=?");
    $stmt->execute([$quantity, $id]);
    $edited = true;
}
// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $id = $_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
    $stmt->execute([$id]);
    $deleted = true;
}

// Fetch all products
$stmt = $pdo->query("SELECT p.*, s.quantity FROM products p JOIN stock_levels s ON p.id = s.product_id");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
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
        <a class="nav-link active" href="products.php"><i class="fas fa-box"></i> Products</a>
        <a class="nav-link" href="customers.php"><i class="fas fa-users"></i> Customers</a>
        <a class="nav-link" href="sales.php"><i class="fas fa-cash-register"></i> Sales</a>
        <a class="nav-link" href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
        <a class="nav-link text-danger mt-4" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    <div class="header">Product Management</div>
    <div class="main-content fade-in">
        <div class="card p-4 slide-in mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0"><i class="fas fa-box text-primary"></i> Add Product</h3>
                <button class="btn btn-primary" data-toggle="modal" data-target="#addProductModal"><i class="fas fa-plus"></i> New Product</button>
            </div>
        </div>
        <div class="card p-4 slide-in">
            <h4 class="mb-3"><i class="fas fa-list"></i> Products List</h4>
            <input class="form-control mb-3" id="searchInput" type="text" placeholder="Search products...">
            <div class="table-responsive">
                <table class="table table-bordered" id="productsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Stock Quantity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr class="fade-in">
                            <td><?php echo $product['id']; ?></td>
                            <td><?php echo $product['name']; ?></td>
                            <td><?php echo $product['price']; ?></td>
                            <td><?php echo $product['quantity']; ?></td>
                            <td>
                                <button class="btn btn-sm btn-info editBtn" 
                                    data-id="<?php echo $product['id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($product['name']); ?>" 
                                    data-price="<?php echo $product['price']; ?>" 
                                    data-quantity="<?php echo $product['quantity']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger deleteBtn" 
                                    data-id="<?php echo $product['id']; ?>">
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
    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" role="dialog" aria-labelledby="addProductModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST">
            <input type="hidden" name="add_product" value="1">
            <div class="modal-header">
              <h5 class="modal-title" id="addProductModalLabel"><i class="fas fa-plus"></i> Add New Product</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="name">Product Name</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="price">Price</label>
                    <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                </div>
                <div class="form-group">
                    <label for="quantity">Initial Stock Quantity</label>
                    <input type="number" class="form-control" id="quantity" name="quantity" required>
                </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Add Product</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1" role="dialog" aria-labelledby="editProductModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST">
            <input type="hidden" name="edit_product" value="1">
            <input type="hidden" id="edit_id" name="edit_id">
            <div class="modal-header">
              <h5 class="modal-title" id="editProductModalLabel"><i class="fas fa-edit"></i> Edit Product</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_name">Product Name</label>
                    <input type="text" class="form-control" id="edit_name" name="edit_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_price">Price</label>
                    <input type="number" step="0.01" class="form-control" id="edit_price" name="edit_price" required>
                </div>
                <div class="form-group">
                    <label for="edit_quantity">Stock Quantity</label>
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
    <!-- Delete Product Modal -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1" role="dialog" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST">
            <input type="hidden" name="delete_product" value="1">
            <input type="hidden" id="delete_id" name="delete_id">
            <div class="modal-header">
              <h5 class="modal-title" id="deleteProductModalLabel"><i class="fas fa-trash"></i> Delete Product</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this product?</p>
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
        $("#productsTable tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
    // Edit modal
    $(".editBtn").on("click", function() {
        $("#edit_id").val($(this).data('id'));
        $("#edit_name").val($(this).data('name'));
        $("#edit_price").val($(this).data('price'));
        $("#edit_quantity").val($(this).data('quantity'));
        $("#editProductModal").modal('show');
    });
    // Delete modal
    $(".deleteBtn").on("click", function() {
        $("#delete_id").val($(this).data('id'));
        $("#deleteProductModal").modal('show');
    });
    </script>
    <?php if ($added): ?>
    <script>$(function(){ showToast('Product added successfully!'); });</script>
    <?php endif; ?>
    <?php if ($edited): ?>
    <script>$(function(){ showToast('Product updated successfully!'); });</script>
    <?php endif; ?>
    <?php if ($deleted): ?>
    <script>$(function(){ showToast('Product deleted successfully!'); });</script>
    <?php endif; ?>
</body>
</html> 