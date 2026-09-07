<?php
require_once 'config.php';
require_once 'includes/auth.php';
$pageTitle = "Dashboard";

$totalMedicines = $pdo->query("SELECT COUNT(*) c FROM medicines WHERE is_active=1")->fetch()['c'];
$lowStock       = $pdo->query("SELECT COUNT(*) c FROM medicines WHERE stock_quantity <= reorder_level AND stock_quantity > 0 AND is_active=1")->fetch()['c'];
$outOfStock     = $pdo->query("SELECT COUNT(*) c FROM medicines WHERE stock_quantity = 0 AND is_active=1")->fetch()['c'];
$expiringSoon   = $pdo->query("SELECT COUNT(*) c FROM medicines WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) AND is_active=1")->fetch()['c'];

$todaySales = $pdo->query("SELECT COALESCE(SUM(payable_amount),0) s, COUNT(*) c FROM sales WHERE DATE(sale_date) = CURDATE()")->fetch();
$monthSales = $pdo->query("SELECT COALESCE(SUM(payable_amount),0) s FROM sales WHERE MONTH(sale_date)=MONTH(CURDATE()) AND YEAR(sale_date)=YEAR(CURDATE())")->fetch()['s'];
$monthPurchases = $pdo->query("SELECT COALESCE(SUM(total_amount),0) s FROM purchases WHERE MONTH(purchase_date)=MONTH(CURDATE()) AND YEAR(purchase_date)=YEAR(CURDATE())")->fetch()['s'];

$lowStockList = $pdo->query("SELECT m.name, m.stock_quantity, m.reorder_level, c.name as category
    FROM medicines m JOIN categories c ON m.category_id=c.id
    WHERE m.stock_quantity <= m.reorder_level AND m.is_active=1
    ORDER BY m.stock_quantity ASC LIMIT 8")->fetchAll();

$recentSales = $pdo->query("SELECT s.memo_no, s.sale_date, s.payable_amount, COALESCE(c.name,'Walk-in') as cust
    FROM sales s LEFT JOIN customers c ON s.customer_id=c.id
    ORDER BY s.id DESC LIMIT 6")->fetchAll();

include 'includes/header.php';
?>

<div class="row g-3 mb-2">
  <div class="col-md-3"><div class="stat-card bg-primary"><p>Total Medicines</p><h3><?= $totalMedicines ?></h3></div></div>
  <div class="col-md-3"><div class="stat-card bg-warning text-dark"><p>Low Stock Items</p><h3><?= $lowStock ?></h3></div></div>
  <div class="col-md-3"><div class="stat-card bg-danger"><p>Out of Stock</p><h3><?= $outOfStock ?></h3></div></div>
  <div class="col-md-3"><a href="expiring_items.php" class="text-decoration-none"><div class="stat-card bg-purple" style="cursor:pointer"><p>Expiring in 60 Days <i class="bi bi-box-arrow-up-right small"></i></p><h3><?= $expiringSoon ?></h3></div></a></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="stat-card bg-success"><p>Today's Sales (<?= $todaySales['c'] ?> memos)</p><h3><?= CURRENCY . number_format($todaySales['s'],2) ?></h3></div></div>
  <div class="col-md-4"><div class="stat-card bg-info text-dark"><p>This Month's Sales</p><h3><?= CURRENCY . number_format($monthSales,2) ?></h3></div></div>
  <div class="col-md-4"><div class="stat-card bg-secondary"><p>This Month's Purchases</p><h3><?= CURRENCY . number_format($monthPurchases,2) ?></h3></div></div>
</div>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header fw-bold"><i class="bi bi-exclamation-triangle text-warning"></i> Low / Out of Stock</div>
      <div class="card-body p-0">
        <table class="table mb-0">
          <thead><tr><th>Medicine</th><th>Category</th><th>Stock</th><th>Reorder Level</th></tr></thead>
          <tbody>
          <?php if (!$lowStockList): ?>
            <tr><td colspan="4" class="text-center text-muted py-3">All stock levels look healthy 🎉</td></tr>
          <?php endif; ?>
          <?php foreach ($lowStockList as $m): ?>
            <tr class="<?= $m['stock_quantity']==0 ? 'out-of-stock' : 'low-stock' ?>">
              <td><?= htmlspecialchars($m['name']) ?></td>
              <td><?= htmlspecialchars($m['category']) ?></td>
              <td><?= $m['stock_quantity'] ?></td>
              <td><?= $m['reorder_level'] ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card">
      <div class="card-header fw-bold"><i class="bi bi-receipt"></i> Recent Sales</div>
      <div class="card-body p-0">
        <table class="table mb-0">
          <thead><tr><th>Memo No</th><th>Customer</th><th>Date</th><th class="text-end">Amount</th></tr></thead>
          <tbody>
          <?php if (!$recentSales): ?>
            <tr><td colspan="4" class="text-center text-muted py-3">No sales yet</td></tr>
          <?php endif; ?>
          <?php foreach ($recentSales as $s): ?>
            <tr>
              <td><?= htmlspecialchars($s['memo_no']) ?></td>
              <td><?= htmlspecialchars($s['cust']) ?></td>
              <td><?= date('d M Y, h:i A', strtotime($s['sale_date'])) ?></td>
              <td class="text-end"><?= CURRENCY . number_format($s['payable_amount'],2) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
