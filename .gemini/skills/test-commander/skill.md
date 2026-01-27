---
name: Test Commander
description: Automates the creation of strict PHPUnit tests. It specializes in mocking Repositories/Configs using `Mockery` and validating `App\Core\ServiceResponse` objects. It ensures no feature is considered "complete" without a test. Use this skill when a new **Service Method** is created or a bug is reported.
---
# Procedural Guidance
### 1. The Mockery Standard
*   **Base Class:** All tests must extend `Tests\Unit\TestCase`.
*   **Config Mocking:** Use `$this->mockConfig->shouldReceive('get')->with('key')->andReturn(...)`.
*   **Repo Mocking:** Mock repositories to return Entities, never raw arrays (unless it's a legacy repo).

### 2. The Assertion Protocol
*   **ServiceResponse:** Use helper methods `$this->assertServiceSuccess($response)` and `$this->assertServiceFailure($response)`.
*   **Transactions:** Ensure the Service test mocks `$db->beginTransaction()`, `commit()`, and `rollBack()`.

### 3. Integration vs Unit
*   **Unit:** Default. Mock everything. Place in `tests/Unit/Services/`.
*   **Integration:** Only if DB interactions are complex. Must use the `try { $db->beginTransaction(); ... } finally { $db->rollBack(); }` pattern found in `tests/Integration/`.

### 4. Output Format
Provide the full, drop-in PHP file ending in `Test.php`.