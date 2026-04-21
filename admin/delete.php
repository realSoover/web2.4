<?php
require_once '../config.php';

$id = $_GET['id'] ?? 0;

if ($id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
    } catch (Exception $e) {
        error_log("Delete error: " . $e->getMessage());
    }
}

header('Location: index.php');
exit;
?>