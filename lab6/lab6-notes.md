# Lab 6: PHP, PDO, and SQLite Integration Notes

**GitHub Codespaces Link:** [Insert your GitHub Codespaces link here]

---

## Part 1: Set Up the Database

**Command used to set up the database:**
```bash
[Insert command here, e.g., sqlite3 database/tutoring.db < database/setup-database.sql]
```

**Confirmation:**

![Commands used to create and verify](./Part1.JPG)

---

## Part 2: Run the Application

**1. URL opened in the browser:**

https://friendly-memory-v94q7q44p9h9r6-8000.app.github.dev/php/request-form.php

**2. Request Confirmation:**

Once filled out with valid data the confrimation page loaded displaying the inputed request.

**3. Screenshot of the request detail page:**

![Request Detail Page Screenshot](./Part2.JPG)

---

## Part 3: Test the Application Workflows

| Test | What I Did | Expected Result | Actual Result | Pass or Fail |
| :--- | :--- | :--- | :--- | :--- |
| Submit a valid request | [Describe your action] | A new tutoring request is created with status Open. | [Describe what actually happened] | [Pass/Fail] |
| View all requests | [Describe your action] | The new request appears in the request list. | [Describe what actually happened] | [Pass/Fail] |
| Filter requests | [Describe your action] | Filtering by course or status shows matching requests. | [Describe what actually happened] | [Pass/Fail] |
| Edit an Open request | [Describe your action] | The revised values are displayed. | [Describe what actually happened] | [Pass/Fail] |
| Cancel an Open request | [Describe your action] | The request status changes to Cancelled. | [Describe what actually happened] | [Pass/Fail] |
| Try to edit or cancel a cancelled request | [Describe your action] | The application does not allow the change. | [Describe what actually happened] | [Pass/Fail] |

---

## Part 4: Trace the Request Submission Workflow

**1. Which file displays the tutoring request form?**
[Your answer]

**2. Which file receives the submitted form data?**
[Your answer]

**3. Which file opens the PDO connection to SQLite?**
[Your answer]

**4. Which file coordinates validation and the database transaction?**
[Your answer]

**5. Which files contain the SQL statements?**
[Your answer]

**6. Which table stores student information?**
[Your answer]

**7. Which table stores tutoring requests?**
[Your answer]

**8. How does a tutoring request connect to the student who submitted it?**
[Your answer]

**Explanation of SQL in Repository Files:**
[Write two to four sentences explaining why SQL statements belong in repository files rather than directly in the form page.]

---

## Part 5: Run the Integration Tests

**Integration Test Output:**
```text
[Paste the complete terminal output here, ending with "All integration checks passed"]
```

---

## Part 6: Use the AI Skills Pack Process

### Application Builder

**My Prompt:**
Explain how a tutoring request moves from php/request-form.php through PHP, PDO, and SQLite in this project. Identify the responsibility of each file.

**AI Response:**
> [Paste the AI's response here]

**Evaluation:**
[Identify one accurate statement from the AI's response and explain how you verified it by checking the actual code.]

### Traceability Auditor

**My Prompt:**
Trace the requirement "a student can submit a tutoring request" through the PHP files, service, repositories, SQLite tables, and integration tests in this project.

**AI Response:**
> [Paste the AI's response here]

**Traceability Table:**

| Requirement | Application Page | Service or Validation | Repository or SQL | Database Table | Test Evidence |
| :--- | :--- | :--- | :--- | :--- | :--- |
| Student submits a tutoring request | [Your answer] | [Your answer] | [Your answer] | [Your answer] | [Your answer] |

### Test Harness Generator

**My Prompt:**
Suggest one useful test case for the Peer Tutoring Hub application. The test must relate to request creation, filtering, editing, cancellation, validation, or database integrity.

**AI Response:**
> [Paste the AI's response here]

**Evaluation:**
1. **Is it already covered by tests/integration-tests.php?** [Your answer]
2. **How to test manually or add to integration test file:** [Your answer]
3. **Is the AI generated test appropriate for this application?** [Your answer]

### Skills Pack Reflection

**Reflection:**
[Write three to five sentences explaining how the AI tool helped you understand, trace, or test the application. Explain why developers must verify AI generated outputs against actual code and requirements.]

---

## Part 7: Inspect the Database

**Explanation of the SQL JOIN:**
[Explain what the `JOIN students AS s ON r.student_id = s.student_id` clause does in the provided query.]