# Implementation Plan: GLPI 11 Upgrade

## Overview

This implementation plan outlines the tasks for upgrading the GLPI Costs plugin from version 3.0.6 to 3.1.0, adding support for GLPI 11.x while maintaining backward compatibility with GLPI 10.x. The upgrade is primarily a compatibility update with minimal code changes, focusing on version constraint updates and comprehensive testing.

## Tasks

- [x] 1. Update version constants and plugin metadata
  - Update PLUGIN_COSTS_VERSION from "3.0.6" to "3.1.0" in setup.php
  - Update PLUGIN_COSTS_MAX_GLPI from "11.0" to "12.0" in setup.php
  - Verify PLUGIN_COSTS_MIN_GLPI remains "10.0"
  - _Requirements: 1.1, 1.2, 1.3_

- [ ]* 1.1 Write unit test for version constants
  - Test that PLUGIN_COSTS_VERSION equals "3.1.0"
  - Test that PLUGIN_COSTS_MIN_GLPI equals "10.0"
  - Test that PLUGIN_COSTS_MAX_GLPI equals "12.0"
  - **Property 1: Version Constants Configuration**
  - **Validates: Requirements 1.1, 1.2, 1.3**

- [x] 2. Set up GLPI 11 testing environment
  - Install GLPI 11.0.x in a test environment
  - Configure database and basic GLPI settings
  - Document environment setup for future testing
  - _Requirements: 12.1_

- [-] 3. Test plugin installation on GLPI 11
  - [x] 3.1 Install plugin on GLPI 11 test instance
    - Run plugin installation process
    - Verify all database tables are created
    - Check for any installation errors or warnings
    - _Requirements: 10.1, 10.3, 10.4, 12.1_

  - [ ]* 3.2 Write property test for plugin installation
    - **Property 3: Plugin Installation Success**
    - Test that plugin_costs_install() executes without exceptions
    - Test that all expected tables exist after installation
    - **Validates: Requirements 10.1, 10.3, 10.4, 12.1**

  - [ ]* 3.3 Write property test for database table creation
    - **Property 2: Database Table Creation**
    - Test that tables use DBConnection::getDefaultCharset()
    - Test that tables use DBConnection::getDefaultCollation()
    - Test that tables use DBConnection::getDefaultPrimaryKeySignOption()
    - **Validates: Requirements 2.2, 2.3, 2.4**

- [x] 4. Test plugin uninstallation on GLPI 11
  - [x] 4.1 Test uninstallation process
    - Run plugin uninstallation
    - Verify tables are removed
    - Check for any uninstallation errors
    - _Requirements: 10.2_

  - [ ]* 4.2 Write property test for plugin uninstallation
    - **Property 4: Plugin Uninstallation Success**
    - Test that plugin_costs_uninstall() executes without exceptions
    - **Validates: Requirements 10.2**

- [x] 5. Test core plugin functionality on GLPI 11
  - [x] 5.1 Test entity cost configuration
    - Create entity cost configurations
    - Test fixed cost and time cost settings
    - Test inheritance configuration
    - Verify configuration persistence
    - _Requirements: 12.2_

  - [ ]* 5.2 Write property test for cost configuration round trip
    - **Property 7: Cost Configuration Round Trip**
    - Test that saving and retrieving configuration produces equivalent data
    - **Validates: Requirements 12.2**

  - [x] 5.3 Test ticket cost generation
    - Create tickets in billable entities
    - Verify cost entries are generated
    - Test billable vs non-billable tickets
    - _Requirements: 12.3_

  - [ ]* 5.4 Write property test for ticket cost generation
    - **Property 8: Ticket Cost Generation**
    - Test that tickets in billable entities generate cost entries
    - **Validates: Requirements 12.3**

  - [x] 5.5 Test task cost calculation
    - Add tasks to billable tickets
    - Verify time-based costs are calculated correctly
    - Test with different time durations
    - _Requirements: 12.4_

  - [ ]* 5.6 Write property test for task cost calculation
    - **Property 9: Task Cost Calculation**
    - Test that tasks generate correct time-based costs
    - **Validates: Requirements 12.4**

- [x] 6. Checkpoint - Verify core functionality works on GLPI 11
  - Ensure all tests pass, ask the user if questions arise.

- [x] 7. Test UI components on GLPI 11
  - [x] 7.1 Test template rendering
    - Load config page and verify template renders
    - Load entity costs tab and verify template renders
    - Check for any Twig template errors
    - _Requirements: 4.3, 12.5, 12.6_

  - [ ]* 7.2 Write property test for template rendering
    - **Property 5: Template Rendering**
    - Test that all templates render without exceptions
    - **Validates: Requirements 4.3**

  - [x] 7.3 Test form generation
    - Test entity cost configuration form
    - Test billable dropdown on ticket form
    - Verify forms submit correctly
    - _Requirements: 9.3_

  - [ ]* 7.4 Write property test for form rendering
    - **Property 6: Form Rendering**
    - Test that all forms render without exceptions
    - **Validates: Requirements 9.3**

- [x] 8. Test search functionality on GLPI 11
  - [x] 8.1 Test ticket search with billable field
    - Perform ticket searches including billable field
    - Verify search results display correctly
    - Test filtering by billable status
    - _Requirements: 8.3_

  - [ ]* 8.2 Write property test for search options integration
    - **Property 10: Search Options Integration**
    - Test that searches with billable field execute without errors
    - **Validates: Requirements 8.3**

- [x] 9. Test hook execution on GLPI 11
  - [x] 9.1 Test all plugin hooks
    - Test POST_ITEM_FORM hook on ticket form
    - Test PRE_ITEM_UPDATE hook on ticket update
    - Test ITEM_ADD hooks for tickets and tasks
    - Test ITEM_PURGE hook for task deletion
    - _Requirements: 3.7_

  - [ ]* 9.2 Write property test for hook execution
    - **Property 11: Hook Execution**
    - Test that all hooks execute without errors
    - **Validates: Requirements 3.7**

- [x] 10. Test rights management on GLPI 11
  - [x] 10.1 Test permission checks
    - Test with users having entity UPDATE rights
    - Test with users having config READ/UPDATE rights
    - Test with users lacking required rights
    - _Requirements: 5.4_

  - [ ]* 10.2 Write property test for rights management
    - **Property 12: Rights Management**
    - Test that access control works correctly based on user rights
    - **Validates: Requirements 5.4**

- [x] 11. Test migration functionality on GLPI 11
  - [x] 11.1 Test plugin upgrade scenario
    - Simulate upgrade from previous version
    - Verify migration executes correctly
    - Check database schema after migration
    - _Requirements: 7.7_

  - [ ]* 11.2 Write property test for migration execution
    - **Property 13: Migration Execution**
    - Test that migrations execute without errors
    - **Validates: Requirements 7.7**

- [x] 12. Checkpoint - Verify all GLPI 11 tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 13. Test backward compatibility with GLPI 10
  - [x] 13.1 Run full test suite on GLPI 10.0.x
    - Install plugin on GLPI 10.0.x
    - Run all functional tests
    - Verify no regressions
    - _Requirements: 11.1, 11.2_

  - [x] 13.2 Run full test suite on GLPI 10.0.latest
    - Install plugin on latest GLPI 10 release
    - Run all functional tests
    - Verify no regressions
    - _Requirements: 11.2_

  - [ ]* 13.3 Write property test for cross-version compatibility
    - **Property 14: Cross-Version Compatibility**
    - Test that core features function identically on GLPI 10 and 11
    - **Validates: Requirements 11.2, 11.3, 11.4**

- [x] 14. Update documentation
  - [x] 14.1 Update CHANGELOG.md
    - Add entry for version 3.1.0
    - Document GLPI 11 support
    - List any changes or fixes
    - _Requirements: 1.3_

  - [x] 14.2 Update README.md if needed
    - Update supported GLPI versions if mentioned
    - Add any GLPI 11 specific notes
    - _Requirements: 1.1, 1.2_

- [x] 15. Final validation and release preparation
  - [x] 15.1 Run complete test suite on all GLPI versions
    - Test on GLPI 10.0.x
    - Test on GLPI 10.0.latest
    - Test on GLPI 11.0.x
    - Test on GLPI 11.0.latest
    - _Requirements: 11.4, 12.1-12.6_

  - [x] 15.2 Perform manual testing checklist
    - Install plugin on fresh GLPI 11 instance
    - Configure entity costs
    - Create billable and non-billable tickets
    - Add tasks and verify cost generation
    - Test search functionality
    - Test entity inheritance
    - Verify all UI pages
    - Test uninstallation
    - _Requirements: 12.1-12.6_

  - [x] 15.3 Code review
    - Review all changes
    - Verify no deprecated API usage
    - Check for any missed compatibility issues
    - Ensure code quality standards
    - _Requirements: 2.1-2.6, 3.1-3.6, 4.1-4.4, 5.1-5.3, 6.1-6.5_

- [x] 16. Final checkpoint - Ready for release
  - Ensure all tests pass, documentation is updated, and code review is complete.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties
- Unit tests validate specific examples and edge cases
- The upgrade is primarily a compatibility update with minimal code changes
- Testing on multiple GLPI versions is critical for ensuring compatibility
