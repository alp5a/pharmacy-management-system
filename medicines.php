<?php
require_once 'config.php';
require_once 'includes/auth.php';
$pageTitle = "Medicines";

// Delete (soft delete - just deactivate, keeps history intact)
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("UPDATE medicines SET is_active=0 WHERE id=?")->execute([$id]);
    $_SESSION['flash'] = ['type'=>'success','msg'=>"Medicine removed from active list."];
    header('Location: medicines.php'); exit;
}

$search = trim($_GET['q'] ?? '');
$catFilter = $_GET['category'] ?? '';
$expiryFilter = $_GET['expiry'] ?? ''; // '', 'nearly', 'expired'

$sql = "SELECT m.*, c.name as category_name FROM medicines m
        JOIN categories c ON m.category_id = c.id WHERE m.is_active=1";
$params = [];
if ($search !== '') {
    $sql .= " AND (m.name LIKE ? OR m.generic_name LIKE ? OR m.manufacturer LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($catFilter !== '') {
    $sql .= " AND m.category_id = ?";
    $params[] = $catFilter;
}
if ($expiryFilter === 'nearly') {
    $sql .= " AND m.expiry_date IS NOT NULL AND m.expiry_date >= CURDATE() AND m.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)";
} elseif ($expiryFilter === 'expired') {
    $sql .= " AND m.expiry_date IS NOT NULL AND m.expiry_date < CURDATE()";
}
$sql .= " ORDER BY m.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$medicines = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0"><i class="bi bi-capsule"></i> Medicines</h4>
  <a href="medicine_form.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add New Medicine</a>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form class="row g-2" method="get">
      <div class="col-md-6">
        <input type="text" name="q" class="form-control" placeholder="Search by name, generic name, or manufacturer..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="col-md-3">
        <select name="category" class="form-select">
          <option value="">All Categories</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $catFilter == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="expiry" class="form-select">
          <option value="">All (Expiry)</option>
          <option value="nearly" <?= $expiryFilter==='nearly' ? 'selected' : '' ?>>Nearly Expired</option>
          <option value="expired" <?= $expiryFilter==='expired' ? 'selected' : '' ?>>Expired</option>
        </select>
      </div>
      <div class="col-md-1">
        <button class="btn btn-outline-primary w-100"><i class="bi bi-search"></i></button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr>
          <th>Name</th><th>Generic Name</th><th>Category</th><th>Manufacturer</th>
          <th>Unit</th><th>Purchase Price</th><th>Selling Price</th><th>Stock</th><th>Expiry</th><th></th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$medicines): ?>
        <tr><td colspan="10" class="text-center text-muted py-4">No medicines found.</td></tr>
      <?php endif; ?>
      <?php foreach ($medicines as $m):
          $rowClass = '';
          if ($m['stock_quantity'] == 0) $rowClass = 'out-of-stock';
          elseif ($m['stock_quantity'] <= $m['reorder_level']) $rowClass = 'low-stock';
          $expSoon = $m['expiry_date'] && strtotime($m['expiry_date']) <= strtotime('+60 days');
      ?>
        <tr class="<?= $rowClass ?>">
          <td class="fw-semibold"><?= htmlspecialchars($m['name']) ?></td>
          <td class="text-muted small"><?= htmlspecialchars($m['generic_name']) ?></td>
          <td><span class="badge bg-primary"><?= htmlspecialchars($m['category_name']) ?></span></td>
          <td><?= htmlspecialchars($m['manufacturer']) ?></td>
          <td><?= htmlspecialchars($m['unit']) ?></td>
          <td><?= CURRENCY . number_format($m['purchase_price'],2) ?></td>
          <td><?= CURRENCY . number_format($m['selling_price'],2) ?></td>
          <td><strong><?= $m['stock_quantity'] ?></strong></td>
          <td class="<?= $expSoon ? 'text-danger fw-bold' : '' ?>"><?= $m['expiry_date'] ? date('d M Y', strtotime($m['expiry_date'])) : '-' ?></td>
          <td class="text-nowrap">
            <a href="medicine_form.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
            <a href="inventory.php?highlight=<?= $m['id'] ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-box-seam"></i></a>
            <a href="?delete=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this medicine?')"><i class="bi bi-trash"></i></a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
