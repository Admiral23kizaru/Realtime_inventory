<?php
require_once 'config.php';
require_once 'auth.php';

// Fetch sales summary data
$sales_summary = $pdo->query("SELECT * FROM sales_summary ORDER BY sale_day DESC LIMIT 30")->fetchAll();

// Fetch product performance data
$product_performance = $pdo->query("SELECT * FROM product_performance ORDER BY revenue_generated DESC LIMIT 10")->fetchAll();

// Fetch customer purchase history
$customer_history = $pdo->query("SELECT * FROM customer_purchase_history ORDER BY total_spent DESC LIMIT 10")->fetchAll();

// Fetch low stock alerts
$low_stock = $pdo->query("SELECT * FROM low_stock_alert")->fetchAll();

// Prepare data for charts
$sales_chart_data = array_reverse($sales_summary);
$chart_labels = [];
$chart_revenue = [];
$chart_quantity = [];

foreach ($sales_chart_data as $data) {
    $chart_labels[] = $data['sale_day'];
    $chart_revenue[] = $data['total_revenue'];
    $chart_quantity[] = $data['total_quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Inventory System</title>
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
        <a class="nav-link" href="sales.php"><i class="fas fa-cash-register"></i> Sales</a>
        <a class="nav-link active" href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
        <a class="nav-link text-danger mt-4" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    <div class="header">Reports & Analytics</div>
    <div class="main-content fade-in">
        <!-- Sales Summary Card -->
        <div class="card p-4 slide-in mb-4">
            <h4 class="mb-3"><i class="fas fa-chart-line text-primary"></i> Sales Summary</h4>
            <canvas id="salesChart" height="100"></canvas>
        </div>

        <div class="row">
            <!-- Top Products Card -->
            <div class="col-md-6 mb-4">
                <div class="card p-4 slide-in h-100">
                    <h4 class="mb-3"><i class="fas fa-box text-success"></i> Top Products</h4>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Stock</th>
                                    <th>Sold</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($product_performance as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo $product['current_stock']; ?></td>
                                    <td><?php echo $product['units_sold']; ?></td>
                                    <td>$<?php echo number_format($product['revenue_generated'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Top Customers Card -->
            <div class="col-md-6 mb-4">
                <div class="card p-4 slide-in h-100">
                    <h4 class="mb-3"><i class="fas fa-users text-info"></i> Top Customers</h4>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Type</th>
                                    <th>Purchases</th>
                                    <th>Total Spent</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customer_history as $customer): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                    <td><?php echo ucfirst($customer['customer_type']); ?></td>
                                    <td><?php echo $customer['total_purchases']; ?></td>
                                    <td>$<?php echo number_format($customer['total_spent'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Alerts Card -->
        <div class="card p-4 slide-in">
            <h4 class="mb-3"><i class="fas fa-exclamation-triangle text-warning"></i> Low Stock Alerts</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Current Stock</th>
                            <th>Min Level</th>
                            <th>Supplier</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($low_stock as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo $item['sku']; ?></td>
                            <td class="text-danger"><?php echo $item['quantity']; ?></td>
                            <td><?php echo $item['min_stock_level']; ?></td>
                            <td><?php echo htmlspecialchars($item['supplier']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Sales Chart
    var ctx = document.getElementById('salesChart').getContext('2d');
    var salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Revenue',
                data: <?php echo json_encode($chart_revenue); ?>,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37,99,235,0.1)',
                fill: true,
                tension: 0.3
            }, {
                label: 'Quantity Sold',
                data: <?php echo json_encode($chart_quantity); ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,0.1)',
                fill: true,
                tension: 0.3,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Revenue ($)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Quantity'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
    </script>
</body>
</html> 