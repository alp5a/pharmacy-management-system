<?php
require_once 'config.php';
require_once 'includes/auth.php';
$pageTitle = "Suppliers";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact_person, phone, address) VALUES (?,?,?,?)");
    $stmt->execute([trim($_POST['name']), trim($_POST['contact_person']), trim($_POST['phone']), trim($_POST['address'])]);
    $_SESSION['flash'] = ['type'=>'success','msg'=>"Supplier added."];
    header('Location: suppliers.php'); exit;
}
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $used = $pdo->prepare("SELECT COUNT(*) c FROM purchases WHERE supplier_id=?");
    $used->execute([$id]);
    if ($used->fetch()['c'] > 0) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>"Cannot delete - supplier has purchase records."];
    } else {
        $pdo->prepare("DELETE FROM suppliers WHERE id=?")->execute([$id]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>"Supplier deleted."];
    }
    header('Location: suppliers.php'); exit;
}

$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll();
include 'includes/header.php';
?>

<div class="row g-3">
  <div class="col-md-4">
    <div class="card">
      <div class="card-header fw-bold"><i class="bi bi-plus-circle"></i> Add Supplier</div>
      <div class="card-body">
        <form method="post">
          <div class="mb-2"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" required></div>
          <div class="mb-2"><label class="form-label">Contact Person</label><input type="text" name="contact_person" class="form-control"></div>
          <div class="mb-2"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
          <div class="mb-3"><label class="form-label">Address</label><input type="text" name="address" class="form-control"></div>
          <button class="btn btn-primary w-100" name="add">Add Supplier</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card">
      <div class="card-header fw-bold"><i class="bi bi-truck"></i> Supplier List</div>
      <div class="card-body p-0">
        <table class="table mb-0">
          <thead><tr><th>Name</th><th>Contact</th><th>Phone</th><th>Address</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($suppliers as $s): ?>
            <tr>
              <td><?= htmlspecialchars($s['name']) ?></td>
              <td><?= htmlspecialchars($s['contact_person']) ?></td>
              <td><?= htmlspecialchars($s['phone']) ?></td>
              <td><?= htmlspecialchars($s['address']) ?></td>
              <td><a href="?delete=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
