# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.2] - 2026-03-04
### Fixed
- Changed table action namespaces from `Filament\Tables\Actions` to `Filament\Actions` (Filament v5 compatibility).
- Added `filament-custom-forms::` prefix to all translation calls for package context.
- Added missing translation keys (`access_denied`, `upgrade_required`) and `tenant.php` (later removed).
- Removed hardcoded `\App\Enums\Currency` dependency; money fields now default to USD.

### Removed
- Removed multi-tenancy (tenant) logic and feature checks.
- Deleted `src/Enums/Currency.php`.

## [1.0.0] - 2026-03-04
### Added
- Initial release of Chanthoeun Custom Forms plugin.
- Support for dynamic form building and entry management in Filament.
- Khmer and English translation support.
- Published migrations as stubs for fresh project integration.
