<?php
require_once 'includes/db.php';
require_once 'includes/validation.php';

if (!isset($_GET['cardId']) || !validateId($_GET['cardId'])) {
    die("Invalid or missing Gift Card ID.");
}

$stmt = $pdo->prepare("SELECT * FROM GIFTCARD WHERE cardId = ?");
$stmt->execute([$_GET['cardId']]);
$card = $stmt->fetch();

if (!$card) { die("Gift Card not found."); }

require_once 'includes/header.php';
require_once 'includes/nav.php';
?>
<h2>Gift Card Details</h2>
<div class="card-details">
    <img src="images/gift-card.png" alt="<?= htmlspecialchars($card['cardName']) ?>">
    <div>
        <h3><?= htmlspecialchars($card['cardName']) ?></h3>
        <p><strong>Card Type:</strong> <?= htmlspecialchars($card['cardType']) ?></p>
        <p><strong>Card Value:</strong> $<?= htmlspecialchars(number_format($card['cardValue'], 2)) ?></p>
        <p><strong>Points Required:</strong> <?= htmlspecialchars($card['points']) ?></p>
    </div>
</div>
<div class="nav-links">
    <a href="card-update.php?cardId=<?= urlencode($card['cardId']) ?>" class="button-link">Update Gift Card</a>
    <a href="card-delete.php?cardId=<?= urlencode($card['cardId']) ?>" class="button-link">Delete Gift Card</a>
</div>
<?php require_once 'includes/footer.php'; ?>