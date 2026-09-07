<?php
require_once 'config.php';
require_once 'includes/auth.php';
$pageTitle = "Sales History";

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$search = trim($_GET['q'] ?? '');

$sql = "SELECT s.*, COALESCE(c.name,'Walk-in Customer') as customer_name
        FROM sales s LEFT JOIN customers c ON s.customer_id=c.id WHERE 1=1";
$params = [];
if ($from) { $sql .= " AND DATE(s.sale_date) >= ?"; $params[] = $from; }
if ($to)   { $sql .= " AND DATE(s.sale_date) <= ?"; $params[] = $to; }
if ($search) { $sql .= " AND s.memo_no LIKE ?"; $params[] = "%$search%"; }
$sql .= " ORDER BY s.sale_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sales = $stmt->fetchAll();

$totalSales = array_sum(array_column($sales, 'payable_amount'));
$totalDue = array_sum(array_column($sales, 'due_amount'));

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0"><i class="bi bi-receipt"></i> Sales History</h4>
  <a href="sales.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> New Sale</a>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form class="row g-2" method="get">
      <div class="col-md-3"><label class="form-label">From</label><input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>"></div>
      <div class="col-md-3"><label class="form-label">To</label><input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>"></div>
      <div class="col-md-3"><label class="form-label">Memo No.</label><input type="text" name="q" class="form-control" value="<?= htmlspecialchars($search) ?>"></div>
      <div class="col-md-3 d-flex align-items-end"><button class="btn btn-outline-primary w-100"><i class="bi bi-funnel"></i> Filter</button></div>
    </form>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-6"><div class="stat-card bg-success"><p>Total Sales (filtered)</p><h3><?= CURRENCY . number_format($totalSales,2) ?></h3></div></div>
  <div class="col-md-6"><div class="stat-card bg-danger"><p>Total Due (filtered)</p><h3><?= CURRENCY . number_format($totalDue,2) ?></h3></div></div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Memo No.</th><th>Customer</th><th>Date</th><th class="text-end">Total</th><th class="text-end">Discount</th><th class="text-end">Payable</th><th class="text-end">Paid</th><th class="text-end">Due</th><th></th></tr></thead>
      <tbody>
      <?php if (!$sales): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">No sales found.</td></tr>
      <?php endif; ?>
      <?php foreach ($sales as $s): ?>
        <tr class="<?= $s['due_amount'] > 0 ? 'low-stock' : '' ?>">
          <td><?= htmlspecialchars($s['memo_no']) ?></td>
          <td><?= htmlspecialchars($s['customer_name']) ?></td>
          <td><?= date('d M Y, h:i A', strtotime($s['sale_date'])) ?></td>
          <td class="text-end"><?= CURRENCY . number_format($s['total_amount'],2) ?></td>
          <td class="text-end"><?= CURRENCY . number_format($s['discount'],2) ?></td>
          <td class="text-end"><?= CURRENCY . number_format($s['payable_amount'],2) ?></td>
          <td class="text-end"><?= CURRENCY . number_format($s['paid_amount'],2) ?></td>
          <td class="text-end <?= $s['due_amount']>0 ? 'text-danger fw-bold' : '' ?>"><?= CURRENCY . number_format($s['due_amount'],2) ?></td>
          <td><a href="memo_print.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer"></i> Memo</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
