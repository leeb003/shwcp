# Changelog
All notable changes to WP Contacts will be documented in this file starting with version 3.1.0.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## 3.3.3 - 2021-3-2
### Fixed
- Fullcalendar (Events page) version update for WordPress 5.6.2

## 3.3.1 - 2020-5-1
### Fixed
- Event notes editor and some styling fixes for events

## 3.3.0 - 2020-2-24
### Added 
- Print page for individual entries option
- Notes capabilities - links and image insertion as well as quite a few other formatting options
### Changed 
- Some icons and logos used in WP Contacts

## [3.2.9] - 2020-1-15
### Fixed
- Resizing thumbnails routine on non-default database
- UI bugs

### Added 
- Gravity Forms enhancements

## [3.2.8] - 2019-12-27
### Fixed
- Displaying multi-select fields in view only mode on the individual entry page now displays correctly.
- Gutenberg Database selection Classic editor metabox interference

### Added
- New feature, Individual field overrides for custom roles. You can now choose which fields are accessible for custom roles that you create!
- Code editor for custom css and js fields
- Backend ui enhancements

## [3.2.7] - 2019-12-7
_____
### Added
- Cloning Databases functionality.  Now you can easily clone an existing database with settings to another new one in your backend settings.

## [3.2.6] - 2019-12-6
_____
### Added
- New feature Multiselection fields!  Now you can create custom multiselection field types in your WP Contacts.
- Set name for export files.

## [3.2.5] - 2019-12-2 
_____
### Added
- Users now have the option to download database backup zip files through the backend Database settings tab

## [3.2.4] - 2019-11-30 
_____
- Custom thumbnail sizes are now allowed and can be set in the backend settings.  
- Existing thumbnails can be regenerated through Settings -> Manage Front Page on the frontend

## [3.2.3] - 2019-11-29
_____
- Uninstall routine correctly removes tables and options upon uninstall

## [3.2.2] - 2019-11-27
_____
- Gutenberg sidebar implemented for database selection on WP Contacts page when WP Contacts page template is selected

## [3.2.1] - 2019-11-22
_____
- Automatic updates are now handled by the Envato Market plugin, users will be given the option to install it for managing updates now.

## [3.2.0] - 2019-9-6
_____
- Added insert notes capabilities to the REST api so that notes can now be created this way as well

## [3.1.9] - 2019-8-6
_____
- Added update entries functionality to spreadsheet imports to allow data with an ID column to update existing
- entries with the same id instead of importing new ones
- Fixed php 7.2+ null count warning message

## [3.1.8] - 2019-6-27 
_____
- Added detail logging on Entry detail modification, something we have overlooked in the past

## [3.1.7] - 2019-3-13 
_____
- Added duplicate entries functionality to frontend
- PHP Notification fixes

## [3.1.6] - 2018-8-31 
_____
- Fixed bug when adding new entry could not go directly to entry to edit
- Changed add/edit modal to disallow outside clicks when open

## [3.1.5] - 2017-11-22
_____
- Fixed bug where database ordering for form integration was not sending
- results to the correct database when non-default used.

## [3.1.4] - 2017-9-29
_____
- Greek language file added.  Thanks to George Konstantinidis!

## [3.1.3] - 2017-9-26
_____
- Bug fix for custom roles small screens adding entries

## [3.1.2] - 2017-9-1
_____
- Bug fix for ie 11 with javascript error thrown for no matching parenthesis

## [3.1.1] - 2017-8-28
_____
- Bug fix for add new entries permission where radio allowed both being
- checked and not saving to db

## [3.1.0] - 2017-7-21
_____
- Bug fix choice selection for form integrations when selection exists for other dropdown
- Contact Form 7 version 4.8 update fix tag array to object check

