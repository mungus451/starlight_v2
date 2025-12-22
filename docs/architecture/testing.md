# Testing & Compliance

Verification scripts and tests that enforce the architecture and game rules:

- MVC compliance and strict layering
- DI resolution and container wiring
- Session decoupling
- Feature-specific tests (e.g., alliance bonuses)

See the `tests/` directory for:
- `VerifySystemIntegrity.php`
- `VerifySessionDecoupling.php`
- `AllianceStructureBonusTest.php`
- `GameLoopSimulationTest.php`
