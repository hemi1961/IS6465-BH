<?php
require_once 'includes/db.php';
require_once 'includes/validation.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['cardName'] ?? '';
    $type = $_POST['cardType'] ?? '';
    $value = $_POST['cardValue'] ?? '';
    $points = $_POST['points'] ?? '';

    if (!validateCardName($name) || !validateCardType($type) || !validateCardValue($value) || !validatePoints($points)) {
        $error = "Validation failed. Please check your inputs.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO GIFTCARD (cardName, cardType, cardValue, points) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $type, $value, $points]);
        header("Location: card-list.php");
        exit;
    }
}
require_once 'includes/header.php';
require_once 'includes/nav.php';
?>
<h2>Add New Gift Card</h2>
<?php if ($error): ?><p style="color:red;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form action="card-add.php" method="POST">
    <label for="cardName">Card Name:</label>
    <input type="text" id="cardName" name="cardName" required>
    
    <label for="cardType">Card Type:</label>
    <select id="cardType" name="cardType" required>
        <option value="Travel">Travel</option>
        <option value="Dining">Dining</option>
        <option value="Retail">Retail</option>
    </select>
    
    <label for="cardValue">Card Value ($):</label>
    <input type="number" id="cardValue" name="cardValue" step="0.01" required>
    
    <label for="points">Points Required:</label>
    <input type="number" id="points" name="points" required>
    
    <button type="submit">Add Gift Card</button>
</form>
<?php require_once 'includes/footer.php'; ?>