<?php
require_once 'config.php';
require_once 'includes/auth.php';
$pageTitle = "Customers";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO customers (name, phone, address) VALUES (?,?,?)");
    $stmt->execute([trim($_POST['name']) ?: 'Walk-in Customer', trim($_POST['phone']), trim($_POST['address'])]);
    $_SESSION['flash'] = ['type'=>'success','msg'=>"Customer added."];
    header('Location: customers.php'); exit;
}
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $used = $pdo->prepare("SELECT COUNT(*) c FROM sales WHERE customer_id=?");
    $used->execute([$id]);
    if ($used->fetch()['c'] > 0) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>"Cannot delete - customer has sales records."];
    } else {
        $pdo->prepare("DELETE FROM customers WHERE id=?")->execute([$id]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>"Customer deleted."];
    }
    header('Location: customers.php'); exit;
}

$customers = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM sales s WHERE s.customer_id=c.id) as sale_count
                           FROM customers c ORDER BY c.name")->fetchAll();
include 'includes/header.php';
?>

<div class="row g-3">
  <div class="col-md-4">
    <div class="card">
      <div class="card-header fw-bold"><i class="bi bi-plus-circle"></i> Add Customer</div>
      <div class="card-body">
        <form method="post">
          <div class="mb-2"><label class="form-label">Name</label><input type="text" name="name" class="form-control" placeholder="Walk-in Customer"></div>
          <div class="mb-2"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
          <div class="mb-3"><label class="form-label">Address</label><input type="text" name="address" class="form-control"></div>
          <button class="btn btn-primary w-100" name="add">Add Customer</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card">
      <div class="card-header fw-bold"><i class="bi bi-people"></i> Customer List</div>
      <div class="card-body p-0">
        <table class="table mb-0">
          <thead><tr><th>Name</th><th>Phone</th><th>Address</th><th>Total Purchases</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($customers as $c): ?>
            <tr>
              <td><?= htmlspecialchars($c['name']) ?></td>
              <td><?= htmlspecialchars($c['phone']) ?></td>
              <td><?= htmlspecialchars($c['address']) ?></td>
              <td><?= $c['sale_count'] ?></td>
              <td><a href="?delete=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
