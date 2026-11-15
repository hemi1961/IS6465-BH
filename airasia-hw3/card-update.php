<?php
require_once 'includes/db.php';
require_once 'includes/validation.php';

if (!isset($_REQUEST['cardId']) || !validateId($_REQUEST['cardId'])) { die("Invalid ID."); }
$cardId = $_REQUEST['cardId'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['cardName'] ?? '';
    $type = $_POST['cardType'] ?? '';
    $value = $_POST['cardValue'] ?? '';
    $points = $_POST['points'] ?? '';

    if (!validateCardName($name) || !validateCardType($type) || !validateCardValue($value) || !validatePoints($points)) {
        $error = "Validation failed. Please check your inputs.";
    } else {
        $stmt = $pdo->prepare("UPDATE GIFTCARD SET cardName=?, cardType=?, cardValue=?, points=? WHERE cardId=?");
        $stmt->execute([$name, $type, $value, $points, $cardId]);
        header("Location: card-list.php");
        exit;
    }
}

$stmt = $pdo->prepare("SELECT * FROM GIFTCARD WHERE cardId = ?");
$stmt->execute([$cardId]);
$card = $stmt->fetch();
if (!$card) { die("Gift Card not found."); }

require_once 'includes/header.php';
require_once 'includes/nav.php';
?>
<h2>Update Gift Card</h2>
<?php if ($error): ?><p style="color:red;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form action="card-update.php" method="POST">
    <input type="hidden" name="cardId" value="<?= htmlspecialchars($card['cardId']) ?>">
    <label>Card Name:</label>
    <input type="text" name="cardName" value="<?= htmlspecialchars($card['cardName']) ?>" required>
    
    <label>Card Type:</label>
    <select name="cardType" required>
        <option value="Travel" <?= $card['cardType'] == 'Travel' ? 'selected' : '' ?>>Travel</option>
        <option value="Dining" <?= $card['cardType'] == 'Dining' ? 'selected' : '' ?>>Dining</option>
        <option value="Retail" <?= $card['cardType'] == 'Retail' ? 'selected' : '' ?>>Retail</option>
    </select>
    
    <label>Card Value ($):</label>
    <input type="number" name="cardValue" step="0.01" value="<?= htmlspecialchars($card['cardValue']) ?>" required>
    
    <label>Points Required:</label>
    <input type="number" name="points" value="<?= htmlspecialchars($card['points']) ?>" required>
    
    <button type="submit">Update Gift Card</button>
</form>
<?php require_once 'includes/footer.php'; ?>