<?php
require_once __DIR__ . '/../includes/validation.php';
echo "<h2>Validation Test Harness</h2>\n<ul>\n";

// Test 1: Valid Data
echo "<li>Test 1 (Valid Gift Card data): " . (validateCardName("Voucher") && validateCardType("Travel") && validateCardValue(50.00) && validatePoints(5000) ? "PASS" : "FAIL") . "</li>\n";

// Test 2: Blank Card Name
echo "<li>Test 2 (Blank Card Name): " . (!validateCardName("   ") ? "PASS" : "FAIL") . "</li>\n";

// Test 3: Altered or invalid Card Type
echo "<li>Test 3 (Altered/invalid Card Type): " . (!validateCardType("Grocery") ? "PASS" : "FAIL") . "</li>\n";

// Test 4: Zero or negative Card Value
echo "<li>Test 4 (Zero/negative Card Value): " . (!validateCardValue(-10) ? "PASS" : "FAIL") . "</li>\n";

// Test 5: Invalid Points Required
echo "<li>Test 5 (Invalid Points Required): " . (!validatePoints(20.5) ? "PASS" : "FAIL") . "</li>\n";
echo "</ul>\n";
?>