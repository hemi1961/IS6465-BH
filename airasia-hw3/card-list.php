<?php
require_once 'includes/db.php';
require_once 'includes/header.php';
require_once 'includes/nav.php';

$stmt = $pdo->query("SELECT * FROM GIFTCARD");
$cards = $stmt->fetchAll();
?>
<h2>Available Gift Cards</h2>
<?php foreach ($cards as $card): ?>
    <div class="card-item">
        <a href="card-details.php?cardId=<?= urlencode($card['cardId']) ?>">
            <img src="images/gift-card.png" alt="<?= htmlspecialchars($card['cardName']) ?>">
        </a>
        <div>
            <h3><a href="card-details.php?cardId=<?= urlencode($card['cardId']) ?>"><?= htmlspecialchars($card['cardName']) ?></a></h3>
            <p>Points Required: <?= htmlspecialchars($card['points']) ?></p>
        </div>
    </div>
<?php endforeach; ?>
<?php require_once 'includes/footer.php'; ?>