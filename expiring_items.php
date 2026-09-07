<?php
require_once 'config.php';
require_once 'includes/auth.php';
$pageTitle = "Expiring & Expired Items";

// status filter: '' = all (expired + nearly expiring), 'nearly' = only nearly expiring, 'expired' = only expired
$status = $_GET['status'] ?? '';
$days = isset($_GET['days']) && $_GET['days'] !== '' ? (int)$_GET['days'] : 60;
if ($days <= 0) $days = 60;

$sql = "SELECT m.*, c.name as category_name FROM medicines m
        JOIN categories c ON m.category_id = c.id
        WHERE m.is_active = 1 AND m.expiry_date IS NOT NULL";

if ($status === 'expired') {
    $sql .= " AND m.expiry_date < CURDATE()";
} elseif ($status === 'nearly') {
    $sql .= " AND m.expiry_date >= CURDATE() AND m.expiry_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)";
} else {
    // All: expired OR nearly expiring within the chosen window
    $sql .= " AND m.expiry_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)";
}
$sql .= " ORDER BY m.expiry_date ASC";

$stmt = $pdo->prepare($sql);
if ($status !== 'expired') {
    $stmt->bindValue(':days', $days, PDO::PARAM_INT);
}
$stmt->execute();
$items = $stmt->fetchAll();

$totalStockValue = 0;
foreach ($items as $it) $totalStockValue += $it['stock_quantity'] * $it['purchase_price'];

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <h4 class="mb-0"><i class="bi bi-exclamation-triangle text-warning"></i> Expiring & Expired Items</h4>
  <div>
    <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print List</button>
    <a href="index.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
  </div>
</div>

<div class="card mb-3 no-print">
  <div class="card-body">
    <form class="row g-2" method="get">
      <div class="col-md-4">
        <label class="form-label">Filter</label>
        <select name="status" class="form-select">
          <option value="" <?= $status===''?'selected':'' ?>>All (Nearly Expiring + Expired)</option>
          <option value="nearly" <?= $status==='nearly'?'selected':'' ?>>Nearly Expiring Only</option>
          <option value="expired" <?= $status==='expired'?'selected':'' ?>>Expired Only</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Within (days)</label>
        <input type="number" min="1" name="days" class="form-control" value="<?= $days ?>" <?= $status==='expired' ? 'disabled' : '' ?>>
        <div class="form-text">Applies to "Nearly Expiring" window. Ignored for "Expired Only".</div>
      </div>
      <div class="col-md-4 d-flex align-items-end">
        <button class="btn btn-outline-primary w-100"><i class="bi bi-funnel"></i> Apply Filter</button>
      </div>
    </form>
  </div>
</div>

<!-- Printable header (only shows when printing) -->
<div class="d-none d-print-block memo-header mb-3">
  <h4 class="mb-0"><?= htmlspecialchars(APP_NAME) ?></h4>
  <p class="mb-0">Expiring &amp; Expired Items Report</p>
  <p class="mb-0 small">
    Filter: <?= $status==='expired' ? 'Expired Only' : ($status==='nearly' ? "Nearly Expiring (within $days days)" : "All (Expired + within $days days)") ?>
    &nbsp;|&nbsp; Generated: <?= date('d M Y, h:i A') ?>
  </p>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr>
          <th>Medicine</th><th>Category</th><th>Batch No.</th><th>Expiry Date</th><th>Status</th>
          <th>Stock Remaining</th><th>Unit</th><th class="text-end">Purchase Price</th>
          <th class="text-end">Selling Price</th><th class="text-end">Stock Value (cost)</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$items): ?>
        <tr><td colspan="10" class="text-center text-muted py-4">No items match this filter. 🎉</td></tr>
      <?php endif; ?>
      <?php foreach ($items as $it):
          $daysLeft = (int)((strtotime($it['expiry_date']) - strtotime(date('Y-m-d'))) / 86400);
          $isExpired = $daysLeft < 0;
          $rowClass = $isExpired ? 'out-of-stock' : 'expiring-soon';
          $stockValue = $it['stock_quantity'] * $it['purchase_price'];
      ?>
        <tr class="<?= $rowClass ?>">
          <td class="fw-semibold"><?= htmlspecialchars($it['name']) ?></td>
          <td><?= htmlspecialchars($it['category_name']) ?></td>
          <td><?= htmlspecialchars($it['batch_no']) ?: '-' ?></td>
          <td><?= date('d M Y', strtotime($it['expiry_date'])) ?></td>
          <td>
            <?php if ($isExpired): ?>
              <span class="badge bg-danger">Expired (<?= abs($daysLeft) ?> days ago)</span>
            <?php else: ?>
              <span class="badge bg-warning text-dark">Expires in <?= $daysLeft ?> days</span>
            <?php endif; ?>
          </td>
          <td><strong><?= $it['stock_quantity'] ?></strong></td>
          <td><?= htmlspecialchars($it['unit']) ?></td>
          <td class="text-end"><?= CURRENCY . number_format($it['purchase_price'],2) ?></td>
          <td class="text-end"><?= CURRENCY . number_format($it['selling_price'],2) ?></td>
          <td class="text-end"><?= CURRENCY . number_format($stockValue,2) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <?php if ($items): ?>
      <tfoot>
        <tr><th colspan="9" class="text-end">Total Stock Value at Risk (cost basis)</th><th class="text-end"><?= CURRENCY . number_format($totalStockValue,2) ?></th></tr>
      </tfoot>
      <?php endif; ?>
    </table>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
