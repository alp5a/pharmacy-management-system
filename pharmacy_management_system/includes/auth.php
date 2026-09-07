<?php
// Include this at the top of every protected page (after config.php)
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

function currentUser() {
    return [
        'id'   => $_SESSION['user_id']   ?? null,
        'name' => $_SESSION['full_name'] ?? '',
        'role' => $_SESSION['role']      ?? 'staff',
    ];
}

/** Generates the next memo/invoice number like PH-20260905-0001 */
function generateMemoNo(PDO $pdo) {
    $prefix = 'PH-' . date('Ymd') . '-';
    $stmt = $pdo->prepare("SELECT memo_no FROM sales WHERE memo_no LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetchColumn();
    $next = $last ? ((int)substr($last, -4)) + 1 : 1;
    return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
}

/** Logs a stock change into inventory_history and updates medicines.stock_quantity */
function adjustStock(PDO $pdo, $medicineId, $qtyChange, $type, $referenceId = null, $referenceType = null, $remarks = null) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT stock_quantity FROM medicines WHERE id = ? FOR UPDATE");
        $stmt->execute([$medicineId]);
        $row = $stmt->fetch();
        if (!$row) throw new Exception("Medicine not found");

        $previous = (int)$row['stock_quantity'];
        $new = $previous + $qtyChange;
        if ($new < 0) throw new Exception("Not enough stock available.");

        $upd = $pdo->prepare("UPDATE medicines SET stock_quantity = ? WHERE id = ?");
        $upd->execute([$new, $medicineId]);

        $hist = $pdo->prepare("INSERT INTO inventory_history
            (medicine_id, change_type, quantity_changed, previous_qty, new_qty, reference_id, reference_type, remarks, created_by)
            VALUES (?,?,?,?,?,?,?,?,?)");
        $hist->execute([$medicineId, $type, $qtyChange, $previous, $new, $referenceId, $referenceType, $remarks, $_SESSION['user_id'] ?? null]);

        $pdo->commit();
        return $new;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
