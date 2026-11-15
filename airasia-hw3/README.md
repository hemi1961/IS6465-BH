## Use Case Traceability
| Use case | PHP page(s) | Database table | Test evidence |
|----------|-------------|----------------|---------------|
| View a List of Gift Cards | card-list.php | GIFTCARD | Loaded the page and verified the 20 seeded database records display correctly with images and points. |
| View Details of One Gift Card | card-details.php | GIFTCARD | Clicked a card link and verified the read-only details pull correctly using GET cardId. |
| Add a Gift Card | card-add.php | GIFTCARD | Submitted valid Gift Card data and confirmed the new record appeared on card-list.php. |
| Update a Gift Card | card-update.php, card-details.php | GIFTCARD | Accessed update via details page, changed the card value, and confirmed the change persisted. |
| Delete a Gift Card | card-delete.php, card-details.php | GIFTCARD | Accessed delete from details, submitted POST confirmation, and confirmed removal on card-list.php. |