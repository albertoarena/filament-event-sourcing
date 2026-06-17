# Contributing

Thanks for considering a contribution.

## Workflow

1. Fork the repository and create a branch from `main`.
2. Write a failing test first, then make it pass. Every change is test driven.
3. Keep the public API stable. Anything in `src/` that is not marked `@internal` is a published
   contract.
4. Run the checks below before opening a pull request.

## Checks

```bash
composer test      # Pest test suite
composer analyse   # PHPStan at level 6
composer format    # Pint, Laravel preset
```

The test suite uses an in-memory SQLite database and Orchestra Testbench, so no external
services are needed. Coverage of new code is expected to stay at or above 90 percent.

## Conventions

- Strict types in every file.
- Final classes by default.
- One behaviour per test, with descriptive names.
- Commit messages in the imperative mood, scoped to a single logical change.

## Reporting bugs

Open an issue with a minimal reproduction. A failing test against the fixture `Post` domain is
the most helpful form a report can take.
