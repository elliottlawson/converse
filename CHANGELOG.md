# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added
- UUID support for messages - Messages now have a `uuid` column that is automatically generated
- `converse:upgrade` command for existing installations to add UUID column to messages table

### Changed
- **BREAKING**: Messages table now includes a `uuid` column by default. If you have an existing installation, run `php artisan converse:upgrade` to add the UUID column to your messages table.

### Upgrade Guide

If you have an existing installation with the messages table already created:

```bash
php artisan converse:upgrade
```

This command will add the UUID column to your existing messages table. Note that existing messages will not have UUIDs - only new messages will have UUIDs generated automatically.

The upgrade command will be removed in a future version once most users have migrated.