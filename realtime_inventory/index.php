<?php
require_once 'config.php';
require_once 'auth.php';
// Fetch dashboard stats
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_customers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$total_sales = $pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn();
$total_revenue = $pdo->query("SELECT SUM(s.quantity * p.price) FROM sales s JOIN products p ON s.product_id = p.id")->fetchColumn();
// Fetch sales per day for chart
$chartData = $pdo->query("SELECT DATE(sale_date) as day, SUM(quantity * p.price) as total_sales FROM sales s JOIN products p ON s.product_id = p.id GROUP BY day ORDER BY day ASC")->fetchAll(PDO::FETCH_ASSOC);
$chart_labels = [];
$chart_values = [];
foreach ($chartData as $row) {
    $chart_labels[] = $row['day'];
    $chart_values[] = $row['total_sales'] ? $row['total_sales'] : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory and Sales Tracking System</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="text-center mb-4">
            <img src="https://img.icons8.com/fluency/48/000000/warehouse.png" alt="Logo" style="width:48px;">
        </div>
        <a class="nav-link active" href="index.php"><i class="fas fa-home"></i> Dashboard</a>
        <a class="nav-link" href="products.php"><i class="fas fa-box"></i> Products</a>
        <a class="nav-link" href="customers.php"><i class="fas fa-users"></i> Customers</a>
        <a class="nav-link" href="sales.php"><i class="fas fa-cash-register"></i> Sales</a>
        <a class="nav-link" href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
        <a class="nav-link text-danger mt-4" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    <div class="header">Inventory and Sales Tracking System</div>
    <div class="main-content fade-in">
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card p-3 text-center">
                    <div class="mb-2"><i class="fas fa-box fa-2x text-primary"></i></div>
                    <div class="h4 counter" data-count="<?php echo $total_products; ?>">0</div>
                    <div class="text-muted">Products</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card p-3 text-center">
                    <div class="mb-2"><i class="fas fa-users fa-2x text-success"></i></div>
                    <div class="h4 counter" data-count="<?php echo $total_customers; ?>">0</div>
                    <div class="text-muted">Customers</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card p-3 text-center">
                    <div class="mb-2"><i class="fas fa-cash-register fa-2x text-warning"></i></div>
                    <div class="h4 counter" data-count="<?php echo $total_sales; ?>">0</div>
                    <div class="text-muted">Sales</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card p-3 text-center">
                    <div class="mb-2"><i class="fas fa-dollar-sign fa-2x text-danger"></i></div>
                    <div class="h4 counter" data-count="<?php echo $total_revenue ? $total_revenue : 0; ?>">0</div>
                    <div class="text-muted">Revenue</div>
                </div>
            </div>
        </div>
        <div class="card p-4 slide-in">
            <h4 class="mb-3"><i class="fas fa-chart-line text-primary"></i> Sales Trend</h4>
            <canvas id="salesChart" height="90"></canvas>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Animated counters
    $('.counter').each(function() {
        var $this = $(this), countTo = $this.attr('data-count');
        $({ countNum: $this.text() }).animate({ countNum: countTo }, {
            duration: 1200,
            easing: 'swing',
            step: function() {
                $this.text(Math.floor(this.countNum));
            },
            complete: function() {
                $this.text(countTo);
            }
        });
    });
    // Sales Trend Chart
    var ctx = document.getElementById('salesChart').getContext('2d');
    var salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Total Sales',
                data: <?php echo json_encode($chart_values); ?>,
                backgroundColor: 'rgba(37,99,235,0.12)',
                borderColor: '#2563eb',
                borderWidth: 3,
                pointRadius: 4,
                pointBackgroundColor: '#2563eb',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { color: '#64748b' } },
                x: { ticks: { color: '#64748b' } }
            }
        }
    });
    </script>
</body>
</html> 