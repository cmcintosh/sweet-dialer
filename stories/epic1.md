# Epic 1 — Package Foundation & Module Scaffolding
**Total Points:** 30 | **Stories:** S-001 through S-011

## S-001: Create Directory Structure (3 pts)
**Description:** Create complete directory hierarchy for Sweet-Dialer package

### Acceptance Criteria
- [ ] `manifest.php` exists at package root with name, version, author, dependencies
- [ ] `ModuleInstall/` directory created for Studio-safe installs
- [ ] `custom/modules/` subdirectory exists for all 6 modules: CTISettings, CTICalls, Voicemail, Recordings, PhoneNumbers, OptedOut
- [ ] `custom/include/TwilioDialer/` created with subdirectories: Services, Webhooks, Helpers, Models
- [ ] All directories have appropriate `.htaccess` files for security
- [ ] Directory structure passes SuiteCRM module loader validation

### Edge Cases
- Directory creation fails on read-only filesystem (log error, abort install)
- Existing partially-installed files detected (prompt for cleanup vs abort)
- Windows server path resolution (use SuiteCRM's `SugarConfig` for paths)
- Conflicting package name already exists in `moduleList`

### Dependencies
- SuiteCRM 7.12+ or 8.x file system permissions
- PHP write permissions to `custom/` directory

## S-002: Write install.php with DB Migration Triggers (3 pts)
**Description:** Create installer that handles fresh installs and upgrades

### Acceptance Criteria
- [ ] `install.php` implements `pre_install()` hook
- [ ] `post_install()` triggers database migrations
- [ ] Install checks SuiteCRM version compatibility (7.12+ or 8.x)
- [ ] Install checks PHP version (>= 8.0 preferred, 7.4+ minimum)
- [ ] Install checks for required PHP extensions (openssl, json, mbstring, curl)
- [ ] Install logs version and timestamp to `config_override.php` entry
- [ ] Upgrade path detection: compares installed version vs package version

### Edge Cases
- Database user lacks CREATE TABLE privileges (graceful error with instructions)
- Partial previous install detected (offer cleanup + reinstall)
- SuiteCRM in maintenance mode (defer install, queue for next load)

## S-003: Write uninstall.php with Cleanup (3 pts)
**Description:** Clean uninstall that removes all module artifacts

### Acceptance Criteria
- [ ] `pre_uninstall()` archives call data to CSV (configurable)
- [ ] Database tables dropped in dependency-safe order
- [ ] Scheduler jobs disabled and removed
- [ ] Webhook endpoints return 410 status during uninstall
- [ ] Cache directories cleared

### Edge Cases
- Database has foreign key constraints blocking DROP
- Active calls in progress during uninstall (warn user, option to force)
- Multi-user uninstall coordination

## S-004 to S-008: Create 11 Database Tables (17 pts)

### Tables: twilio_settings, twilio_calls, twilio_voicemail, twilio_recordings, twilio_opted_out, twilio_dual_ringtone, twilio_hold_ringtone, twilio_common_settings, twilio_error_logs, twilio_logger, twilio_phone_numbers

### Acceptance Criteria
- [ ] All 11 tables created with utf8mb4 charset
- [ ] Foreign key constraints defined where applicable
- [ ] Indexes on frequently queried columns
- [ ] Soft delete on all tables except logs
- [ ] Audit fields (date_entered, date_modified, created_by) on all tables

### Edge Cases
- Table already exists from partial install
- Collation mismatch between tables
- InnoDB foreign key errors during creation

## S-009 to S-011: Scaffold SuiteCRM Module Metadata (9 pts)
**Description:** vardefs, language files, viewdefs, menus for all 6 modules

### Acceptance Criteria Per Module
- [ ] `vardefs.php` with all field definitions
- [ ] `en_us.lang.php` with all labels
- [ ] `metadata/editviewdefs.php`, `metadata/detailviewdefs.php`, `metadata/listviewdefs.php`
- [ ] Module icon created
- [ ] Studio integration enabled

### Edge Cases
- Language overrides in `custom/modules/` (preserve on upgrade)
- Studio customizations exist (merge vs overwrite)
