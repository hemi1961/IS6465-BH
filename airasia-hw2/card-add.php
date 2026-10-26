<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AirAsia Rewards - Add Card</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <h2>Add New Gift Card</h2>
        
        <form action="card-list.php" method="GET">
            <label for="card_name">Card Name:</label>
            <input type="text" id="card_name" name="card_name" required>
            
            <label for="card_type">Card Type:</label>
            <select id="card_type" name="card_type" required>
                <option value="Travel">Travel</option>
                <option value="Dining">Dining</option>
                <option value="Retail">Retail</option>
            </select>
            
            <label for="card_value">Card Value ($):</label>
            <input type="number" id="card_value" name="card_value" step="0.01" required>
            
            <label for="points_required">Points Required:</label>
            <input type="number" id="points_required" name="points_required" required>
            
            <button type="submit">Add Gift Card</button>
        </form>

        <div class="nav-links">
            <a href="card-list.php" class="button-link">Cancel</a>
        </div>
    </div>
</body>
</html>