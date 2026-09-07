<?php
require_once 'config.php';
require_once 'includes/auth.php';
$pageTitle = "Categories";

// Handle add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    if ($name !== '') {
        $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?,?)");
        try {
            $stmt->execute([$name, $desc]);
            $_SESSION['flash'] = ['type'=>'success','msg'=>"Category '$name' added."];
        } catch (PDOException $e) {
            $_SESSION['flash'] = ['type'=>'danger','msg'=>"Could not add - it may already exist."];
        }
    }
    header('Location: categories.php'); exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $inUse = $pdo->prepare("SELECT COUNT(*) c FROM medicines WHERE category_id=?");
    $inUse->execute([$id]);
    if ($inUse->fetch()['c'] > 0) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>"Cannot delete - medicines are still using this category."];
    } else {
        $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>"Category deleted."];
    }
    header('Location: categories.php'); exit;
}

$categories = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM medicines m WHERE m.category_id=c.id) as med_count
                            FROM categories c ORDER BY c.name")->fetchAll();
include 'includes/header.php';
?>

<div class="row g-3">
  <div class="col-md-4">
    <div class="card">
      <div class="card-header fw-bold"><i class="bi bi-plus-circle"></i> Add Classification</div>
      <div class="card-body">
        <form method="post">
          <div class="mb-3">
            <label class="form-label">Type Name (e.g. Syrup, Tablet, Drops)</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Description (optional)</label>
            <input type="text" name="description" class="form-control">
          </div>
          <button class="btn btn-primary w-100" name="add_category">Add Category</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-8">
    <div class="card">
      <div class="card-header fw-bold"><i class="bi bi-tags"></i> Medicine Classifications</div>
      <div class="card-body p-0">
        <table class="table mb-0">
          <thead><tr><th>Name</th><th>Description</th><th>Medicines</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($categories as $c): ?>
            <tr>
              <td><span class="badge bg-primary"><?= htmlspecialchars($c['name']) ?></span></td>
              <td class="text-muted small"><?= htmlspecialchars($c['description']) ?></td>
              <td><?= $c['med_count'] ?></td>
              <td>
                <a href="?delete=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Delete this category?')"><i class="bi bi-trash"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
