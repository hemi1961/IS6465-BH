# HW3 Test Report

| Test ID | Test | Test steps and input | Expected result | Actual result | Pass/Fail |
|---|---|---|---|---|---|
| T01 | Validation test harness | Navigate to tests/validation-tests.php | 5 tests display "PASS" | All 5 tests output "PASS" | PASS |
| T02 | View Gift Card List | Navigate to card-list.php | 20 seeded records display | 20 seeded records display | PASS |
| T03 | View Gift Card Details | Click any card name link | Read-only details view loads for that specific ID | Details load correctly | PASS |
| T04 | Add Gift Card | Submit "Test Card", "Retail", 10.00, 100 on card-add.php | Redirects to list, new card is visible | Card added successfully | PASS |
| T05 | Update Gift Card | Change points from 100 to 200 on card-update.php | Redirects to list, updated points reflected | Points updated successfully | PASS |
| T06 | Delete Gift Card | Click Delete, submit confirmation form | Record is removed, redirects to list | Card removed successfully | PASS |
| T07 | Invalid/altered input | Submit negative Card Value (-50) on card-add.php | Form halts insertion, displays error msg | Error msg shown, no insertion | PASS |
| T08 | Invalid identifier handling | Manually change URL to card-details.php?cardId=XYZ | Script halts with "Invalid ID" | Script halts safely | PASS |