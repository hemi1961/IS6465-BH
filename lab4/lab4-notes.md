# Lab 4 Notes

## Part 1: Run the application
**What happens after submitting the form:**
[Insert your observation here after testing in Codespaces. Example: "After submitting a valid tutoring request, the application processes the data and redirects to a confirmation page displaying the submitted details."]

**Screenshot:**
![Confirmation Screenshot](./path-to-your-screenshot.png) 

## Part 2: Trace the templates
1. **What content does header.php provide to every page?** 
   It provides the opening HTML structure, including the `<!DOCTYPE html>` declaration, the `<head>` section with metadata, the page title, and the linked stylesheet[cite: 2].
2. **What content does nav.php provide?** 
   It provides the main navigation menu/links so users can move between the different sections of the Peer Tutoring Hub application[cite: 2].
3. **What content does footer.php provide?** 
   It provides the closing HTML tags (like `</body>` and `</html>`), and typically includes copyright information or shared footer text[cite: 2].
4. **Why is require_once appropriate for these files?** 
   It ensures that structural files like the header and footer are only included exactly one time per page load[cite: 2]. If `require_once` fails to find the file, it halts the script entirely, which is safer than rendering a broken page[cite: 2].
5. **Where is the shared CSS file linked?** 
   The `css/styles.css` file is linked inside `includes/header.php`[cite: 1, 2].

## Part 3: Observe the form controls
1. **reason:** 
   This is a text control (likely a text input or textarea) where the user types why they need tutoring[cite: 2]. PHP receives it as a single string.
2. **topics[]:** 
   The square brackets indicate this is an array of checkboxes or a multi-select dropdown[cite: 2]. PHP receives it as an indexed array of strings containing all selected topics.
3. **preferredtime:** 
   This is a time input or dropdown for selecting the appointment time[cite: 2]. PHP receives it as a single string.
4. **preferreddate:** 
   This is a date input for the appointment day[cite: 2]. PHP receives it as a single string (usually formatted as YYYY-MM-DD).
5. **form name:** 
   This is typically a hidden input or the name attribute of the submit button, used to verify that the specific form was actually submitted[cite: 2]. PHP receives it as a single string.

## Part 4: Test validation behavior
| Test | Values Entered | Accepted/Rejected | Message Displayed | Business Rule Checked |
| :--- | :--- | :--- | :--- | :--- |
| **Valid request** | [Your specific test data] | Accepted | [Your result] | Validates that all required fields are present, at least one topic is selected, and the date is not in the past. |
| **Missing/invalid value** | [Your specific test data] | Rejected | [Your result] | Validates that required fields cannot be empty or null. |
| **Past date** | [Your specific test data] | Rejected | [Your result] | Validates that the `preferreddate` is greater than or equal to the current date. |

## Part 5: Review testing and CRUD pages
1. **Result shown for validation-tests.php:** 
   [Run tests/validation-tests.php in Codespaces and paste the output here][cite: 3].

2. **CRUD Action Mapping:**
   * **Create:** `request-form.php`[cite: 1, 3]
   * **Read one request:** `request-details.php`[cite: 1, 3]
   * **Read all requests:** `requests.php`[cite: 1, 3]
   * **Update:** `edit-request.php`[cite: 1, 3]
   * **Delete:** `delete-request.php`[cite: 1, 3]

3. **What query parameter appears in the request details URL?** 
   It will use an identifier parameter, usually `?id=` followed by the unique ID number of the request[cite: 3].
4. **Why is a GET query parameter appropriate for viewing one request?** 
   Viewing a record is an idempotent "Read" action that doesn't modify data on the server[cite: 3]. Using a GET parameter allows the URL to be safely bookmarked, refreshed, or shared.
5. **Why should an actual update or delete later use POST rather than GET?** 
   Update and delete are destructive actions that alter server state[cite: 3]. If GET were used, web crawlers, browser pre-fetching, or accidental link clicks could unintentionally modify or delete database records[cite: 3]. POST ensures the action requires an intentional form submission[cite: 3].