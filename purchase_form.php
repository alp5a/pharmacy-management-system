<?php
require_once 'config.php';
require_once 'includes/auth.php';
$pageTitle = "New Purchase";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplierId = $_POST['supplier_id'] ?: null;
    $invoiceNo = trim($_POST['invoice_no']);
    $purchaseDate = $_POST['purchase_date'];
    $notes = trim($_POST['notes']);
    $medIds = $_POST['medicine_id'] ?? [];
    $qtys = $_POST['quantity'] ?? [];
    $prices = $_POST['unit_price'] ?? [];

    if (empty($medIds)) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>"Please add at least one item."];
        header('Location: purchase_form.php'); exit;
    }

    $pdo->beginTransaction();
    try {
        $total = 0;
        foreach ($medIds as $i => $mid) {
            $total += (float)$qtys[$i] * (float)$prices[$i];
        }
        $stmt = $pdo->prepare("INSERT INTO purchases (invoice_no, supplier_id, purchase_date, total_amount, notes, created_by)
                                VALUES (?,?,?,?,?,?)");
        $stmt->execute([$invoiceNo, $supplierId, $purchaseDate, $total, $notes, $_SESSION['user_id']]);
        $purchaseId = $pdo->lastInsertId();

        foreach ($medIds as $i => $mid) {
            $qty = (int)$qtys[$i];
            $price = (float)$prices[$i];
            if ($qty <= 0) continue;
            $subtotal = $qty * $price;

            $pdo->prepare("INSERT INTO purchase_items (purchase_id, medicine_id, quantity, purchase_price, subtotal)
                            VALUES (?,?,?,?,?)")->execute([$purchaseId, $mid, $qty, $price, $subtotal]);

            // update the medicine's purchase price to the latest cost too
            $pdo->prepare("UPDATE medicines SET purchase_price=? WHERE id=?")->execute([$price, $mid]);
        }
        $pdo->commit();

        // Now adjust stock + log history for each item (outside the main transaction since adjustStock manages its own)
        foreach ($medIds as $i => $mid) {
            $qty = (int)$qtys[$i];
            if ($qty <= 0) continue;
            adjustStock($pdo, $mid, $qty, 'purchase', $purchaseId, 'purchase', "Purchase invoice: $invoiceNo");
        }

        $_SESSION['flash'] = ['type'=>'success','msg'=>"Purchase recorded successfully. Stock updated."];
        header('Location: purchase_view.php?id=' . $purchaseId); exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash'] = ['type'=>'danger','msg'=>"Error: " . $e->getMessage()];
        header('Location: purchase_form.php'); exit;
    }
}

$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll();
$medicines = $pdo->query("SELECT id, name, unit, purchase_price FROM medicines WHERE is_active=1 ORDER BY name")->fetchAll();
include 'includes/header.php';
?>

<h4 class="mb-3"><i class="bi bi-truck"></i> New Purchase (Buy Stock)</h4>

<form method="post" id="purchaseForm">
<div class="card mb-3">
  <div class="card-body row g-3">
    <div class="col-md-4">
      <label class="form-label">Supplier</label>
      <select name="supplier_id" class="form-select">
        <option value="">-- Select Supplier --</option>
        <?php foreach ($suppliers as $s): ?>
          <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Invoice / Reference No.</label>
      <input type="text" name="invoice_no" class="form-control">
    </div>
    <div class="col-md-4">
      <label class="form-label">Purchase Date *</label>
      <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
    </div>
    <div class="col-12">
      <label class="form-label">Notes</label>
      <input type="text" name="notes" class="form-control">
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header fw-bold d-flex justify-content-between">
    <span><i class="bi bi-list-ul"></i> Items</span>
    <button type="button" class="btn btn-sm btn-success" onclick="addRow()"><i class="bi bi-plus"></i> Add Item</button>
  </div>
  <div class="card-body p-0">
    <table class="table mb-0" id="itemsTable">
      <thead><tr><th>Medicine</th><th>Quantity</th><th>Unit Price</th><th>Subtotal</th><th></th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
  <div class="card-footer text-end">
    <strong>Grand Total: <span id="grandTotal"><?= CURRENCY ?>0.00</span></strong>
  </div>
</div>

<div class="mt-3">
  <button class="btn btn-primary" type="submit"><i class="bi bi-check-circle"></i> Save Purchase</button>
  <a href="purchase_history.php" class="btn btn-outline-secondary">Cancel</a>
</div>
</form>

<script>
const medicines = <?= json_encode($medicines) ?>;
let rowIndex = 0;

function addRow() {
    const tbody = document.querySelector('#itemsTable tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td style="min-width:220px">
            <select name="medicine_id[]" class="form-select med-select" required onchange="fillPrice(this)">
                <option value="">-- Select medicine --</option>
                ${medicines.map(m => `<option value="${m.id}" data-price="${m.purchase_price}">${m.name} (${m.unit})</option>`).join('')}
            </select>
        </td>
        <td style="width:120px"><input type="number" min="1" name="quantity[]" class="form-control qty" value="1" oninput="calcRow(this)" required></td>
        <td style="width:150px"><input type="number" step="0.01" min="0" name="unit_price[]" class="form-control price" oninput="calcRow(this)" required></td>
        <td style="width:130px" class="subtotal fw-bold">${"<?= CURRENCY ?>"}0.00</td>
        <td style="width:50px"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove(); calcGrandTotal();"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
}

function fillPrice(sel) {
    const opt = sel.options[sel.selectedIndex];
    const price = opt.getAttribute('data-price') || 0;
    const row = sel.closest('tr');
    row.querySelector('.price').value = parseFloat(price).toFixed(2);
    calcRow(sel);
}

function calcRow(el) {
    const row = el.closest('tr');
    const qty = parseFloat(row.querySelector('.qty').value) || 0;
    const price = parseFloat(row.querySelector('.price').value) || 0;
    const subtotal = qty * price;
    row.querySelector('.subtotal').textContent = "<?= CURRENCY ?>" + subtotal.toFixed(2);
    calcGrandTotal();
}

function calcGrandTotal() {
    let total = 0;
    document.querySelectorAll('#itemsTable tbody tr').forEach(row => {
        const qty = parseFloat(row.querySelector('.qty')?.value) || 0;
        const price = parseFloat(row.querySelector('.price')?.value) || 0;
        total += qty * price;
    });
    document.getElementById('grandTotal').textContent = "<?= CURRENCY ?>" + total.toFixed(2);
}

addRow(); // start with one row
</script>

<?php include 'includes/footer.php'; ?>
