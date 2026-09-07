<?php
require_once 'config.php';
require_once 'includes/auth.php';
$pageTitle = "New Sale";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = $_POST['customer_id'] ?: null;
    $discount = (float)($_POST['discount'] ?? 0);
    $paidAmount = (float)($_POST['paid_amount'] ?? 0);
    $medIds = $_POST['medicine_id'] ?? [];
    $qtys = $_POST['quantity'] ?? [];
    $prices = $_POST['unit_price'] ?? [];

    if (empty($medIds)) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>"Please add at least one item to the sale."];
        header('Location: sales.php'); exit;
    }

    // Validate stock availability first
    foreach ($medIds as $i => $mid) {
        $qty = (int)$qtys[$i];
        $stmt = $pdo->prepare("SELECT name, stock_quantity FROM medicines WHERE id=?");
        $stmt->execute([$mid]);
        $med = $stmt->fetch();
        if (!$med || $med['stock_quantity'] < $qty) {
            $_SESSION['flash'] = ['type'=>'danger','msg'=>"Not enough stock for '{$med['name']}'. Available: {$med['stock_quantity']}"];
            header('Location: sales.php'); exit;
        }
    }

    $pdo->beginTransaction();
    try {
        $total = 0;
        foreach ($medIds as $i => $mid) {
            $total += (float)$qtys[$i] * (float)$prices[$i];
        }
        $payable = max($total - $discount, 0);
        $due = max($payable - $paidAmount, 0);
        $memoNo = generateMemoNo($pdo);

        $stmt = $pdo->prepare("INSERT INTO sales (memo_no, customer_id, total_amount, discount, payable_amount, paid_amount, due_amount, created_by)
                                VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$memoNo, $customerId, $total, $discount, $payable, $paidAmount, $due, $_SESSION['user_id']]);
        $saleId = $pdo->lastInsertId();

        foreach ($medIds as $i => $mid) {
            $qty = (int)$qtys[$i];
            $price = (float)$prices[$i];
            if ($qty <= 0) continue;
            $subtotal = $qty * $price;
            $pdo->prepare("INSERT INTO sale_items (sale_id, medicine_id, quantity, unit_price, subtotal)
                            VALUES (?,?,?,?,?)")->execute([$saleId, $mid, $qty, $price, $subtotal]);
        }
        $pdo->commit();

        // Deduct stock + log history for each item
        foreach ($medIds as $i => $mid) {
            $qty = (int)$qtys[$i];
            if ($qty <= 0) continue;
            adjustStock($pdo, $mid, -$qty, 'sale', $saleId, 'sale', "Sale memo: $memoNo");
        }

        header('Location: memo_print.php?id=' . $saleId); exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash'] = ['type'=>'danger','msg'=>"Error: " . $e->getMessage()];
        header('Location: sales.php'); exit;
    }
}

$customers = $pdo->query("SELECT * FROM customers ORDER BY name")->fetchAll();
$medicines = $pdo->query("SELECT id, name, unit, selling_price, stock_quantity FROM medicines WHERE is_active=1 AND stock_quantity > 0 ORDER BY name")->fetchAll();
include 'includes/header.php';
?>

<h4 class="mb-3"><i class="bi bi-cart-check"></i> New Sale (Point of Sale)</h4>

<form method="post" id="saleForm">
<div class="card mb-3">
  <div class="card-body row g-3">
    <div class="col-md-6">
      <label class="form-label">Customer</label>
      <select name="customer_id" class="form-select">
        <option value="">Walk-in Customer</option>
        <?php foreach ($customers as $c): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> <?= $c['phone'] ? '('.$c['phone'].')' : '' ?></option>
        <?php endforeach; ?>
      </select>
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
      <thead><tr><th>Medicine</th><th>Available</th><th>Quantity</th><th>Unit Price</th><th>Subtotal</th><th></th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
  <div class="card-footer">
    <div class="row justify-content-end">
      <div class="col-md-4">
        <div class="d-flex justify-content-between"><span>Total:</span><strong id="totalAmt"><?= CURRENCY ?>0.00</strong></div>
        <div class="d-flex justify-content-between align-items-center my-1">
          <span>Discount:</span>
          <input type="number" step="0.01" min="0" name="discount" id="discount" value="0" class="form-control form-control-sm w-50" oninput="calcGrandTotal()">
        </div>
        <div class="d-flex justify-content-between"><span>Payable:</span><strong id="payableAmt" class="text-primary fs-5"><?= CURRENCY ?>0.00</strong></div>
        <div class="d-flex justify-content-between align-items-center my-1">
          <span>Paid Amount:</span>
          <input type="number" step="0.01" min="0" name="paid_amount" id="paidAmount" value="0" class="form-control form-control-sm w-50" oninput="calcGrandTotal()">
        </div>
        <div class="d-flex justify-content-between"><span>Due:</span><strong id="dueAmt" class="text-danger"><?= CURRENCY ?>0.00</strong></div>
      </div>
    </div>
  </div>
</div>

<div class="mt-3">
  <button class="btn btn-primary btn-lg" type="submit"><i class="bi bi-printer"></i> Complete Sale & Print Memo</button>
  <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
</div>
</form>

<script>
const medicines = <?= json_encode($medicines) ?>;

function addRow() {
    const tbody = document.querySelector('#itemsTable tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td style="min-width:220px">
            <select name="medicine_id[]" class="form-select med-select" required onchange="fillMed(this)">
                <option value="">-- Select medicine --</option>
                ${medicines.map(m => `<option value="${m.id}" data-price="${m.selling_price}" data-stock="${m.stock_quantity}">${m.name} (${m.unit})</option>`).join('')}
            </select>
        </td>
        <td style="width:90px" class="avail text-center">-</td>
        <td style="width:110px"><input type="number" min="1" name="quantity[]" class="form-control qty" value="1" oninput="calcRow(this)" required></td>
        <td style="width:140px"><input type="number" step="0.01" min="0" name="unit_price[]" class="form-control price" oninput="calcRow(this)" required></td>
        <td style="width:120px" class="subtotal fw-bold">${"<?= CURRENCY ?>"}0.00</td>
        <td style="width:50px"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove(); calcGrandTotal();"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
}

function fillMed(sel) {
    const opt = sel.options[sel.selectedIndex];
    const price = opt.getAttribute('data-price') || 0;
    const stock = opt.getAttribute('data-stock') || 0;
    const row = sel.closest('tr');
    row.querySelector('.price').value = parseFloat(price).toFixed(2);
    row.querySelector('.qty').max = stock;
    row.querySelector('.avail').textContent = stock;
    calcRow(sel);
}

function calcRow(el) {
    const row = el.closest('tr');
    const qty = parseFloat(row.querySelector('.qty').value) || 0;
    const price = parseFloat(row.querySelector('.price').value) || 0;
    row.querySelector('.subtotal').textContent = "<?= CURRENCY ?>" + (qty*price).toFixed(2);
    calcGrandTotal();
}

function calcGrandTotal() {
    let total = 0;
    document.querySelectorAll('#itemsTable tbody tr').forEach(row => {
        const qty = parseFloat(row.querySelector('.qty')?.value) || 0;
        const price = parseFloat(row.querySelector('.price')?.value) || 0;
        total += qty * price;
    });
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const payable = Math.max(total - discount, 0);
    const paid = parseFloat(document.getElementById('paidAmount').value) || 0;
    const due = Math.max(payable - paid, 0);

    document.getElementById('totalAmt').textContent = "<?= CURRENCY ?>" + total.toFixed(2);
    document.getElementById('payableAmt').textContent = "<?= CURRENCY ?>" + payable.toFixed(2);
    document.getElementById('dueAmt').textContent = "<?= CURRENCY ?>" + due.toFixed(2);
}

addRow();
</script>

<?php include 'includes/footer.php'; ?>
