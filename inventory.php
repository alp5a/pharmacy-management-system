<?php
require_once 'config.php';
require_once 'includes/auth.php';
$pageTitle = "Inventory";

// Handle manual stock adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust'])) {
    $medId = (int)$_POST['medicine_id'];
    $action = $_POST['action']; // add or remove
    $qty = (int)$_POST['quantity'];
    $reason = trim($_POST['reason']);

    if ($qty > 0) {
        $change = $action === 'add' ? $qty : -$qty;
        $type = $action === 'add' ? 'manual_add' : 'manual_remove';
        try {
            adjustStock($pdo, $medId, $change, $type, null, null, $reason ?: null);
            $_SESSION['flash'] = ['type'=>'success','msg'=>"Inventory updated successfully."];
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type'=>'danger','msg'=>$e->getMessage()];
        }
    }
    header('Location: inventory.php'); exit;
}

$search = trim($_GET['q'] ?? '');
$sql = "SELECT m.*, c.name as category_name FROM medicines m
        JOIN categories c ON m.category_id=c.id WHERE m.is_active=1";
$params = [];
if ($search !== '') {
    $sql .= " AND m.name LIKE ?";
    $params[] = "%$search%";
}
$sql .= " ORDER BY m.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$medicines = $stmt->fetchAll();

$highlight = $_GET['highlight'] ?? null;
include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0"><i class="bi bi-box-seam"></i> Inventory</h4>
  <form class="d-flex" method="get">
    <input type="text" name="q" class="form-control me-2" placeholder="Search medicine..." value="<?= htmlspecialchars($search) ?>">
    <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
  </form>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr><th>Medicine</th><th>Category</th><th>Unit</th><th>Current Stock</th><th>Reorder Level</th><th>Status</th><th>Quick Update</th></tr>
      </thead>
      <tbody>
      <?php foreach ($medicines as $m):
          $rowClass = '';
          $status = '<span class="badge bg-success">OK</span>';
          if ($m['stock_quantity'] == 0) { $rowClass = 'out-of-stock'; $status = '<span class="badge bg-danger">Out of Stock</span>'; }
          elseif ($m['stock_quantity'] <= $m['reorder_level']) { $rowClass = 'low-stock'; $status = '<span class="badge bg-warning text-dark">Low Stock</span>'; }
          if ($highlight == $m['id']) $rowClass .= ' table-active';
      ?>
        <tr class="<?= $rowClass ?>">
          <td class="fw-semibold"><?= htmlspecialchars($m['name']) ?></td>
          <td><?= htmlspecialchars($m['category_name']) ?></td>
          <td><?= htmlspecialchars($m['unit']) ?></td>
          <td><strong class="fs-5"><?= $m['stock_quantity'] ?></strong></td>
          <td><?= $m['reorder_level'] ?></td>
          <td><?= $status ?></td>
          <td>
            <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal"
                    data-bs-target="#adjustModal" data-id="<?= $m['id'] ?>" data-name="<?= htmlspecialchars($m['name']) ?>" data-action="add">
              <i class="bi bi-plus"></i> Add
            </button>
            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                    data-bs-target="#adjustModal" data-id="<?= $m['id'] ?>" data-name="<?= htmlspecialchars($m['name']) ?>" data-action="remove">
              <i class="bi bi-dash"></i> Remove
            </button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<!-- Adjustment Modal -->
<div class="modal fade" id="adjustModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">Update Stock</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="medicine_id" id="modalMedId">
          <input type="hidden" name="action" id="modalAction">
          <p>Medicine: <strong id="modalMedName"></strong></p>
          <div class="mb-3">
            <label class="form-label">Quantity</label>
            <input type="number" min="1" name="quantity" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Reason (optional)</label>
            <input type="text" name="reason" class="form-control" placeholder="e.g. damaged, stock correction, returned">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="adjust" class="btn btn-primary">Confirm Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('adjustModal').addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    document.getElementById('modalMedId').value = button.getAttribute('data-id');
    document.getElementById('modalMedName').textContent = button.getAttribute('data-name');
    const action = button.getAttribute('data-action');
    document.getElementById('modalAction').value = action;
    document.getElementById('modalTitle').textContent = action === 'add' ? 'Add Stock' : 'Remove Stock';
});
</script>

<?php include 'includes/footer.php'; ?>
