<?php
require_once 'config.php';
require_once 'includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT s.*, c.name as customer_name, c.phone as customer_phone, u.full_name as sold_by
                        FROM sales s
                        LEFT JOIN customers c ON s.customer_id = c.id
                        LEFT JOIN users u ON s.created_by = u.id
                        WHERE s.id = ?");
$stmt->execute([$id]);
$sale = $stmt->fetch();
if (!$sale) { die("Sale not found."); }

$items = $pdo->prepare("SELECT si.*, m.name as medicine_name, m.unit FROM sale_items si
                         JOIN medicines m ON si.medicine_id = m.id WHERE si.sale_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Memo <?= htmlspecialchars($sale['memo_no']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4 no-print">
  <a href="sales.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> New Sale</a>
  <a href="sales_history.php" class="btn btn-outline-secondary btn-sm">Sales History</a>
  <button class="btn btn-primary btn-sm float-end" onclick="window.print()"><i class="bi bi-printer"></i> Print Memo</button>
</div>

<div class="memo-box shadow-sm">
  <div class="memo-header">
    <h3 class="mb-0">💊 <?= htmlspecialchars(APP_NAME) ?></h3>
    <p class="mb-0 text-muted small">Address line here · Phone: 000-000000</p>
    <h5 class="mt-2 mb-0">SALES MEMO</h5>
  </div>

  <div class="row mb-3">
    <div class="col-6">
      <strong>Memo No:</strong> <?= htmlspecialchars($sale['memo_no']) ?><br>
      <strong>Date:</strong> <?= date('d M Y, h:i A', strtotime($sale['sale_date'])) ?>
    </div>
    <div class="col-6 text-end">
      <strong>Customer:</strong> <?= htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer') ?><br>
      <?php if ($sale['customer_phone']): ?><strong>Phone:</strong> <?= htmlspecialchars($sale['customer_phone']) ?><br><?php endif; ?>
    </div>
  </div>

  <table class="table table-bordered memo-table">
    <thead>
      <tr><th>#</th><th>Medicine</th><th>Qty</th><th>Unit Price</th><th class="text-end">Subtotal</th></tr>
    </thead>
    <tbody>
    <?php foreach ($items as $i => $it): ?>
      <tr>
        <td><?= $i+1 ?></td>
        <td><?= htmlspecialchars($it['medicine_name']) ?></td>
        <td><?= $it['quantity'] ?> <?= htmlspecialchars($it['unit']) ?></td>
        <td><?= CURRENCY . number_format($it['unit_price'],2) ?></td>
        <td class="text-end"><?= CURRENCY . number_format($it['subtotal'],2) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <table class="table memo-table" style="max-width:320px; margin-left:auto;">
    <tr><td>Total:</td><td class="text-end"><?= CURRENCY . number_format($sale['total_amount'],2) ?></td></tr>
    <tr><td>Discount:</td><td class="text-end"><?= CURRENCY . number_format($sale['discount'],2) ?></td></tr>
    <tr class="fw-bold"><td>Payable:</td><td class="text-end"><?= CURRENCY . number_format($sale['payable_amount'],2) ?></td></tr>
    <tr><td>Paid:</td><td class="text-end"><?= CURRENCY . number_format($sale['paid_amount'],2) ?></td></tr>
    <tr class="text-danger fw-bold"><td>Due:</td><td class="text-end"><?= CURRENCY . number_format($sale['due_amount'],2) ?></td></tr>
  </table>

  <div class="mt-4 d-flex justify-content-between">
    <div>Served by: <?= htmlspecialchars($sale['sold_by'] ?? '-') ?></div>
    <div class="text-end">___________________<br>Authorized Signature</div>
  </div>

  <p class="text-center text-muted small mt-4 mb-0">Thank you for your purchase! Get well soon.<br>Medicines once sold are not returnable unless defective.</p>
</div>

</body>
</html>
