<?php
require_once 'includes/db.php';
require_once 'includes/validation.php';

if (!isset($_REQUEST['cardId']) || !validateId($_REQUEST['cardId'])) { die("Invalid ID."); }
$cardId = $_REQUEST['cardId'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("DELETE FROM GIFTCARD WHERE cardId = ?");
    $stmt->execute([$cardId]);
    header("Location: card-list.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM GIFTCARD WHERE cardId = ?");
$stmt->execute([$cardId]);
$card = $stmt->fetch();
if (!$card) { die("Gift Card not found."); }

require_once 'includes/header.php';
require_once 'includes/nav.php';
?>
<h2>Delete Gift Card</h2>
<p>Are you sure you want to delete the gift card: <strong><?= htmlspecialchars($card['cardName']) ?></strong>?</p>
<form action="card-delete.php" method="POST">
    <input type="hidden" name="cardId" value="<?= htmlspecialchars($card['cardId']) ?>">
    <button type="submit" style="background-color: #555;">Confirm Deletion</button>
</form>
<?php require_once 'includes/footer.php'; ?>