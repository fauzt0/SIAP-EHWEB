# Task 005: Refactor Controller Database Transactions to Models

## Objective
Remove all direct database connection calls (`\Config\Database::connect()`) and transaction management blocks (`$db->transStart()`, `$db->transComplete()`) from all Controllers, moving them to their respective Models. This aligns with MVC principles and project standards (§9 of `DOCUMENTACION_TECNICA.md`).

---

## Recommended Model
- **Model:** `Sonnet 4.6`
- **Reason:** Requires precise understanding of code flow, refactoring PHP files without altering business logic, and preserving application state.

---

## Refactoring Process

### 1. Identify Target Controllers
Search the codebase for occurrences of:
- `\Config\Database::connect()`
- `transStart()`
- `transComplete()`
- `transRollback()`
- `transCommit()`
within the `app/Controllers/` directory.

### 2. Move Logic to Models
For each occurrence:
- Identify the main Model driving the operation.
- Create a public transaction method within that Model.
- Move the multi-model data insertion/deletion/updating code into this new Model method.
- Use the model's internal DB connection object `$this->db` to start and complete the transaction.
- Example pattern:
  ```php
  // In the Model
  public function executeComplexOperation(array $mainData, array $subData): bool
  {
      $this->db->transStart();
      $this->insert($mainData);
      
      $otherModel = new OtherModel();
      $otherModel->insert($subData);
      
      $this->db->transComplete();
      return $this->db->transStatus();
  }
  ```

### 3. Update the Controller
- Instanciate the main Model (if not already done).
- Call the newly created Model method.
- Base response logic on the boolean return status of the Model transaction.

---

## Verification
- Run existing PHPUnit tests (`./vendor/bin/phpunit`) to ensure no functionality is broken by the refactoring.
