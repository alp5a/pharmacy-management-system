<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$user = currentUser();
function navActive($page, $current) { return $page === $current ? 'active' : ''; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?><?= htmlspecialchars(APP_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top no-print">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="index.php">💊 <?= htmlspecialchars(APP_NAME) ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link <?= navActive('index.php',$currentPage) ?>" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link <?= navActive('medicines.php',$currentPage) ?>" href="medicines.php"><i class="bi bi-capsule"></i> Medicines</a></li>
        <li class="nav-item"><a class="nav-link <?= navActive('categories.php',$currentPage) ?>" href="categories.php"><i class="bi bi-tags"></i> Categories</a></li>
        <li class="nav-item"><a class="nav-link <?= navActive('inventory.php',$currentPage) ?>" href="inventory.php"><i class="bi bi-box-seam"></i> Inventory</a></li>
        <li class="nav-item"><a class="nav-link <?= navActive('inventory_history.php',$currentPage) ?>" href="inventory_history.php"><i class="bi bi-clock-history"></i> Stock History</a></li>
        <li class="nav-item"><a class="nav-link <?= navActive('expiring_items.php',$currentPage) ?>" href="expiring_items.php"><i class="bi bi-exclamation-triangle"></i> Expiry Alerts</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-cart-check"></i> Sales</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="sales.php">New Sale (POS)</a></li>
            <li><a class="dropdown-item" href="sales_history.php">Sales History</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-truck"></i> Purchases</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="purchase_form.php">New Purchase</a></li>
            <li><a class="dropdown-item" href="purchase_history.php">Purchase History</a></li>
            <li><a class="dropdown-item" href="suppliers.php">Suppliers</a></li>
          </ul>
        </li>
        <li class="nav-item"><a class="nav-link <?= navActive('customers.php',$currentPage) ?>" href="customers.php"><i class="bi bi-people"></i> Customers</a></li>
      </ul>
      <span class="navbar-text text-white me-3"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['role']) ?>)</span>
      <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
  </div>
</nav>
<div class="container-fluid py-4">
<?php if (!empty($_SESSION['flash'])): ?>
    <div class="alert alert-<?= $_SESSION['flash']['type'] ?> alert-dismissible fade show no-print">
        <?= htmlspecialchars($_SESSION['flash']['msg']) ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
