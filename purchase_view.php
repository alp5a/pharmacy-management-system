<?php
require_once 'config.php';
require_once 'includes/auth.php';
$pageTitle = "Purchase Details";

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT p.*, s.name as supplier_name, s.phone as supplier_phone
                        FROM purchases p LEFT JOIN suppliers s ON p.supplier_id = s.id WHERE p.id=?");
$stmt->execute([$id]);
$purchase = $stmt->fetch();
if (!$purchase) { die("Purchase not found."); }

$items = $pdo->prepare("SELECT pi.*, m.name as medicine_name, m.unit FROM purchase_items pi
                         JOIN medicines m ON pi.medicine_id = m.id WHERE pi.purchase_id=?");
$items->execute([$id]);
$items = $items->fetchAll();

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0"><i class="bi bi-truck"></i> Purchase #<?= $purchase['id'] ?></h4>
  <a href="purchase_history.php" class="btn btn-outline-secondary btn-sm">Back to History</a>
</div>

<div class="card mb-3">
  <div class="card-body row">
    <div class="col-md-4"><strong>Invoice No:</strong> <?= htmlspecialchars($purchase['invoice_no']) ?: '-' ?></div>
    <div class="col-md-4"><strong>Supplier:</strong> <?= htmlspecialchars($purchase['supplier_name']) ?: '-' ?></div>
    <div class="col-md-4"><strong>Date:</strong> <?= date('d M Y', strtotime($purchase['purchase_date'])) ?></div>
    <div class="col-md-12 mt-2"><strong>Notes:</strong> <?= htmlspecialchars($purchase['notes']) ?: '-' ?></div>
  </div>
</div>

<div class="card">
  <div class="card-header fw-bold">Items Purchased</div>
  <div class="card-body p-0">
    <table class="table mb-0">
      <thead><tr><th>Medicine</th><th>Quantity</th><th>Unit Price</th><th class="text-end">Subtotal</th></tr></thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= htmlspecialchars($it['medicine_name']) ?></td>
          <td><?= $it['quantity'] ?> <?= htmlspecialchars($it['unit']) ?></td>
          <td><?= CURRENCY . number_format($it['purchase_price'],2) ?></td>
          <td class="text-end"><?= CURRENCY . number_format($it['subtotal'],2) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr><th colspan="3" class="text-end">Grand Total</th><th class="text-end"><?= CURRENCY . number_format($purchase['total_amount'],2) ?></th></tr>
      </tfoot>
    </table>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
