<?php
require_once 'config.php';
require_once 'includes/auth.php';
$pageTitle = "Purchase History";

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$sql = "SELECT p.*, s.name as supplier_name FROM purchases p LEFT JOIN suppliers s ON p.supplier_id=s.id WHERE 1=1";
$params = [];
if ($from) { $sql .= " AND p.purchase_date >= ?"; $params[] = $from; }
if ($to)   { $sql .= " AND p.purchase_date <= ?"; $params[] = $to; }
$sql .= " ORDER BY p.purchase_date DESC, p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$purchases = $stmt->fetchAll();
$total = array_sum(array_column($purchases, 'total_amount'));

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0"><i class="bi bi-clock-history"></i> Purchase History</h4>
  <a href="purchase_form.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> New Purchase</a>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form class="row g-2" method="get">
      <div class="col-md-4"><label class="form-label">From</label><input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>"></div>
      <div class="col-md-4"><label class="form-label">To</label><input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>"></div>
      <div class="col-md-4 d-flex align-items-end"><button class="btn btn-outline-primary w-100"><i class="bi bi-funnel"></i> Filter</button></div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead><tr><th>Date</th><th>Invoice No.</th><th>Supplier</th><th>Notes</th><th class="text-end">Total</th><th></th></tr></thead>
      <tbody>
      <?php if (!$purchases): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No purchases recorded yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($purchases as $p): ?>
        <tr>
          <td><?= date('d M Y', strtotime($p['purchase_date'])) ?></td>
          <td><?= htmlspecialchars($p['invoice_no']) ?: '-' ?></td>
          <td><?= htmlspecialchars($p['supplier_name']) ?: '-' ?></td>
          <td class="text-muted small"><?= htmlspecialchars($p['notes']) ?></td>
          <td class="text-end"><?= CURRENCY . number_format($p['total_amount'],2) ?></td>
          <td><a href="purchase_view.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> View</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <?php if ($purchases): ?>
      <tfoot><tr><th colspan="4" class="text-end">Total</th><th class="text-end"><?= CURRENCY . number_format($total,2) ?></th><th></th></tr></tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
