# Upgrading Guide

Follow these guidelines when upgrading the package versions.

## Semantic Versioning

The package adheres to Semantic Versioning (`vMAJOR.MINOR.PATCH`).
- **Major versions** may introduce breaking changes to database schemas or public APIs.
- **Minor versions** add backward-compatible features.
- **Patch versions** deliver bug fixes.

---

## Upgrade Steps

When a new package version is released:

1. **Update Composer Dependency**
   Update your `composer.json` or run:
   ```bash
   composer update richnessagency/rich-whatsapp
   ```

2. **Publish Assets (Force Overwrite)**
   If a new version contains dashboard interface updates, republish assets:
   ```bash
   php artisan vendor:publish --tag=rich-whatsapp-assets --force
   ```

3. **Run Migrations**
   Run database migrations in case schema changes were introduced:
   ```bash
   php artisan migrate
   ```

4. **Run Diagnostics Test**
   Confirm that everything behaves as expected:
   ```bash
   php artisan rich-whatsapp:test
   ```
