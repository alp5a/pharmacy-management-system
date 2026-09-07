<?php
require_once 'config.php';
require_once 'includes/auth.php';
$pageTitle = "Stock History";

$medFilter = $_GET['medicine_id'] ?? '';
$typeFilter = $_GET['type'] ?? '';

$sql = "SELECT h.*, m.name as medicine_name, u.full_name as user_name
        FROM inventory_history h
        JOIN medicines m ON h.medicine_id = m.id
        LEFT JOIN users u ON h.created_by = u.id
        WHERE 1=1";
$params = [];
if ($medFilter !== '') { $sql .= " AND h.medicine_id = ?"; $params[] = $medFilter; }
if ($typeFilter !== '') { $sql .= " AND h.change_type = ?"; $params[] = $typeFilter; }
$sql .= " ORDER BY h.created_at DESC LIMIT 300";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$history = $stmt->fetchAll();

$medicines = $pdo->query("SELECT id, name FROM medicines ORDER BY name")->fetchAll();

$typeLabels = [
    'purchase' => ['label'=>'Purchase', 'badge'=>'success'],
    'sale' => ['label'=>'Sale', 'badge'=>'primary'],
    'manual_add' => ['label'=>'Manual Add', 'badge'=>'info'],
    'manual_remove' => ['label'=>'Manual Remove', 'badge'=>'warning'],
    'adjustment' => ['label'=>'Adjustment', 'badge'=>'secondary'],
];

include 'includes/header.php';
?>

<h4 class="mb-3"><i class="bi bi-clock-history"></i> Inventory / Stock History</h4>

<div class="card mb-3">
  <div class="card-body">
    <form class="row g-2" method="get">
      <div class="col-md-5">
        <select name="medicine_id" class="form-select">
          <option value="">All Medicines</option>
          <?php foreach ($medicines as $m): ?>
            <option value="<?= $m['id'] ?>" <?= $medFilter==$m['id']?'selected':'' ?>><?= htmlspecialchars($m['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <select name="type" class="form-select">
          <option value="">All Change Types</option>
          <?php foreach ($typeLabels as $key=>$info): ?>
            <option value="<?= $key ?>" <?= $typeFilter==$key?'selected':'' ?>><?= $info['label'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <button class="btn btn-outline-primary w-100"><i class="bi bi-funnel"></i> Filter</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr><th>Date/Time</th><th>Medicine</th><th>Type</th><th>Change</th><th>Before → After</th><th>Remarks</th><th>By</th></tr>
      </thead>
      <tbody>
      <?php if (!$history): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No history records found.</td></tr>
      <?php endif; ?>
      <?php foreach ($history as $h):
          $info = $typeLabels[$h['change_type']] ?? ['label'=>$h['change_type'],'badge'=>'dark'];
          $isPositive = $h['quantity_changed'] > 0;
      ?>
        <tr>
          <td class="text-nowrap"><?= date('d M Y, h:i A', strtotime($h['created_at'])) ?></td>
          <td><?= htmlspecialchars($h['medicine_name']) ?></td>
          <td><span class="badge bg-<?= $info['badge'] ?>"><?= $info['label'] ?></span></td>
          <td class="fw-bold <?= $isPositive ? 'text-success' : 'text-danger' ?>">
              <?= $isPositive ? '+' : '' ?><?= $h['quantity_changed'] ?>
          </td>
          <td><?= $h['previous_qty'] ?> → <?= $h['new_qty'] ?></td>
          <td class="text-muted small"><?= htmlspecialchars($h['remarks']) ?></td>
          <td><?= htmlspecialchars($h['user_name'] ?? '-') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>
<p class="text-muted small mt-2">Showing the latest 300 records.</p>

<?php include 'includes/footer.php'; ?>
