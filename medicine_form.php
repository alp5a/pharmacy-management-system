<?php
require_once 'config.php';
require_once 'includes/auth.php';

$editId = $_GET['id'] ?? null;
$medicine = [
    'name'=>'', 'generic_name'=>'', 'category_id'=>'', 'manufacturer'=>'', 'unit'=>'pcs',
    'batch_no'=>'', 'expiry_date'=>'', 'purchase_price'=>0, 'selling_price'=>0,
    'stock_quantity'=>0, 'reorder_level'=>10
];
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM medicines WHERE id=?");
    $stmt->execute([$editId]);
    $found = $stmt->fetch();
    if ($found) $medicine = $found;
}
$pageTitle = $editId ? "Edit Medicine" : "Add Medicine";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim($_POST['name']),
        'generic_name' => trim($_POST['generic_name']),
        'category_id' => $_POST['category_id'],
        'manufacturer' => trim($_POST['manufacturer']),
        'unit' => trim($_POST['unit']),
        'batch_no' => trim($_POST['batch_no']),
        'expiry_date' => $_POST['expiry_date'] ?: null,
        'purchase_price' => (float)$_POST['purchase_price'],
        'selling_price' => (float)$_POST['selling_price'],
        'reorder_level' => (int)$_POST['reorder_level'],
    ];

    if ($editId) {
        $sql = "UPDATE medicines SET name=?, generic_name=?, category_id=?, manufacturer=?, unit=?,
                batch_no=?, expiry_date=?, purchase_price=?, selling_price=?, reorder_level=? WHERE id=?";
        $pdo->prepare($sql)->execute([
            $data['name'],$data['generic_name'],$data['category_id'],$data['manufacturer'],$data['unit'],
            $data['batch_no'],$data['expiry_date'],$data['purchase_price'],$data['selling_price'],$data['reorder_level'],$editId
        ]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>"Medicine updated successfully."];
        header('Location: medicines.php'); exit;
    } else {
        $initialStock = (int)$_POST['stock_quantity'];
        $sql = "INSERT INTO medicines (name, generic_name, category_id, manufacturer, unit, batch_no,
                expiry_date, purchase_price, selling_price, stock_quantity, reorder_level)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)";
        $pdo->prepare($sql)->execute([
            $data['name'],$data['generic_name'],$data['category_id'],$data['manufacturer'],$data['unit'],
            $data['batch_no'],$data['expiry_date'],$data['purchase_price'],$data['selling_price'],
            $initialStock,$data['reorder_level']
        ]);
        $newId = $pdo->lastInsertId();

        // Log the initial stock as history if > 0
        if ($initialStock > 0) {
            $hist = $pdo->prepare("INSERT INTO inventory_history
                (medicine_id, change_type, quantity_changed, previous_qty, new_qty, remarks, created_by)
                VALUES (?, 'manual_add', ?, 0, ?, 'Initial stock on item creation', ?)");
            $hist->execute([$newId, $initialStock, $initialStock, $_SESSION['user_id']]);
        }

        $_SESSION['flash'] = ['type'=>'success','msg'=>"Medicine '{$data['name']}' added successfully."];
        header('Location: medicines.php'); exit;
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
include 'includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header fw-bold">
        <i class="bi bi-capsule"></i> <?= $editId ? "Edit Medicine" : "Add New Medicine" ?>
      </div>
      <div class="card-body">
        <form method="post">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Medicine Name *</label>
              <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($medicine['name']) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Generic Name</label>
              <input type="text" name="generic_name" class="form-control" value="<?= htmlspecialchars($medicine['generic_name']) ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label">Classification / Category *</label>
              <select name="category_id" class="form-select" required>
                <option value="">-- Select --</option>
                <?php foreach ($categories as $c): ?>
                  <option value="<?= $c['id'] ?>" <?= $medicine['category_id']==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Manufacturer</label>
              <input type="text" name="manufacturer" class="form-control" value="<?= htmlspecialchars($medicine['manufacturer']) ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label">Unit</label>
              <select name="unit" class="form-select">
                <?php foreach (['pcs','strip','bottle','box','tube','vial','sachet','ampoule'] as $u): ?>
                  <option value="<?= $u ?>" <?= $medicine['unit']==$u?'selected':'' ?>><?= ucfirst($u) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Batch No.</label>
              <input type="text" name="batch_no" class="form-control" value="<?= htmlspecialchars($medicine['batch_no']) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Expiry Date</label>
              <input type="date" name="expiry_date" class="form-control" value="<?= htmlspecialchars($medicine['expiry_date']) ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label">Purchase Price (per unit) *</label>
              <input type="number" step="0.01" min="0" name="purchase_price" class="form-control" required value="<?= $medicine['purchase_price'] ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Selling Price (per unit) *</label>
              <input type="number" step="0.01" min="0" name="selling_price" class="form-control" required value="<?= $medicine['selling_price'] ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Reorder Level</label>
              <input type="number" min="0" name="reorder_level" class="form-control" value="<?= $medicine['reorder_level'] ?>">
            </div>

            <?php if (!$editId): ?>
            <div class="col-md-4">
              <label class="form-label">Initial Stock Quantity</label>
              <input type="number" min="0" name="stock_quantity" class="form-control" value="0">
            </div>
            <?php else: ?>
              <div class="col-md-4">
                <label class="form-label">Current Stock</label>
                <input type="text" class="form-control" value="<?= $medicine['stock_quantity'] ?> (use Inventory page to change)" disabled>
              </div>
            <?php endif; ?>
          </div>

          <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Medicine</button>
            <a href="medicines.php" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
