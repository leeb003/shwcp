=== WordPress Plugin Template ===
Contributors: sh-themes
Tags: wordpress, plugin, leads, contacts
Requires at least: 4.0
Tested up to: 4.6.1
Stable tag: 4.6.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Leads and Contacts manager used as a WordPress plugin

== Description ==

WP Contacts is an excellent WordPress Plugin that allows you to manage your
leads and contacts in WordPress.  Easy to install, and easy to set up, WP
Contacts gives you excellent tools for organizing your leads.  With WP Contacts you can set
images for your leads, create your own custom fields, upload files to
associate with leads, view statistics, add notes, export and import leads, set
permissions for who can access your leads, automatic updates, along with many other features!  WP Contacts is translation ready and built to be responsive for using all of your devices!

== Installation ==

Installing "WP Contacts" can be done by using the following steps:
1. Download the plugin after purchase
1. Upload the ZIP file (shwcp.zip) through the 'Plugins > Add New > Upload' screen in your WordPress dashboard
1. Activate the plugin through the 'Plugins' menu in WordPress
== Frequently Asked Questions ==
= What does this plugin do? =
This plugin is for tracking and managing your leads and contacts.  Built as a
plugin for Wordpress, installation is easy and painless.  WP Contacts gives you
the control to customize all sorts of aspects of your lead and contact
management.

== Changelog ==

= 3.0.9 = 
* Bug fix dropdown sort ordering on frontpage *

= 3.0.8 =
* Added RTL CSS file for RTL language support *

= 3.0.7 = 
* Bug fix for read only users viewing individual entries
* Bug fix for undefined warning on front view for custom roles

= 3.0.6 = 
* New feature! Dynamic permissions
* SST migrated to dropdowns, all can be managed under Field Management section
* New feature! Individual page layout management
* New feature! Dynamic statistics match dropdowns created
* Reorganized field edit page to match field capabilities
* Initial default settings saved on install for quick access
* Export "current view" added to front page for easily exporting filtered views
* Date only field type added
* Print css added to individual page for better printing
* Contact Form 7 file upload mapping to WP Contacts now available
* Be sure to make a backup before updating just in case!

= 3.0.5 = 
* Dynamic filters on front page, now you can use dropdowns for filtering as well
* Filtering exports capability now included
* File preview on front page capability added
* UI enhancements

= 3.0.4 =
* API Action hooks small fix to send all fields and not just front page ones
* on insert and update

= 3.0.3 =
* Fix for CF7 deprectated notice
* Fix for php version 7 and break command on excel export
* Fix for custom javascript applying to correct database
* Year dropdown selector for datetime field types added.

= 3.0.1 =
* WP Contacts RESTful API Integration!

= 2.1.2 =
* Fix for individual lead display for Publicly accessible set up and
* non-logged-in users

= 2.1.1 =
* Updates for WordPress version 4.7 - fixes template selection for page in
* this version.

= 2.1.0 =
* Changed a template generating class name that was conflicting with an automotive theme
* Added uri encoding to search for special character search support

= 2.0.9 =
* Bug Fix - Multiple custom events not showing up on same day
* Changed search feature to paginated results instead of displaying all at
* once for main results and logs

= 2.0.8 = 
* Added custom filter hooks to adjust output for all frontend pages and
* sections
* Some minor styling fixes added

= 2.0.7 =
* Added support for Ninja Forms v3+ since they've updated some of their hooks
* and processes

= 2.0.6 =
* Fixed a bug that affected multiple select field types with the same
* selections on import

= 2.0.5 =
* Fix for multisite and notes not being displayed on sub sites for individual
* contacts

= 2.0.4 =
* Fixed bug with database selection if using default db and assigning it
* Added user id to body class for Custom CSS targetting 
* Several minor improvements
* New! Dropdown fields added as a new field type

= 2.0.3 = 
* Fixed file upload url reporting incorrect path for non-default database
* Added jpeg extension support for main image upload
* New Spanish translation files - Thanks to Ruben Villar

= 2.0.2 =
* Fixed non-default database reset folder recreation
* Fixed small bug on mobile search overlapping first entry
* Fixed several small styling issues
* Added move our scripts to header capability for troubleshooting

= 2.0.1 =
* Fields can now be set to be required inputs in forms
* Date fields can now be set to use WordPress custom formats
* Search from individual entry page
* Speed improvements and db versioning
* Fix to daily stats graph to go through current day
* Many other minor improvements

= 2.0.0 =
* Many new features and improvements!
* Unlimited number of separate databases per installation - each with their own access and settings.
* Ninja Forms Integration
* Gravity Forms Integration
* Calendaring, Events, and Notifications functionality now included
* Mailchimp API export direct 
* Custom JS now available
* Custom Links for Frontend menu
* New Fields - star rating, date time, checkbox, Grouping Titles
* Edit Notes capability
* Search All Fields ability
* Sticky quick edit bar
* Many styling enhancements and layout fixes (Big thanks to Roman!)
* Select and delete multiple entries at a time

= 1.2.6 =
* Modification for Contact Form 7 radio and checkbox field types to be saved

= 1.2.5 = 
* WordPress 4.4 compatability

= 1.2.4 =
* Added Contact Form 7 integration to insert contact form submissions into
* database - be sure to read the docs on Contact Form 7 usage

= 1.2.3 =
* Added Back end setting to set default sort field and direction for Front Page view

= 1.2.2 = 
* Added Google Map Link field type
* Fixed Front page sorting issue which could be triggered when managing fields

= 1.2.1 =
* Fixed styling overflows on large menus
* New feature! Added field types for further customizing fields

= 1.1.9 = 
* Fix - Added missing translation strings
* WP Contacts version notice in backend

= 1.1.8 = 
* 2015-08-24-2015
* Fix - Adding a new contact now defaults to the current user in dropdown
* Added capability to disable the ability to transfer contacts for users who
* manage their own leads

= 1.1.7 =
* 2015-08-07-2015
* Using WP Contacts as Front page fix search parameters
* Fix search & sorting combo views
* Default view set to latest entries first

= 1.1.6 = 
* 2015-07-29
* Multisite Ready
* CSS Class selectors added to front page for targetting custom css
* No images used bug fix for add contact

= 1.1.5 =
* 2015-07-26
* Added Translation to Spanish
* Fixed some SST & Fields saving bugs

= 1.1.4 = 
* 2015-07-26
* Added Database Operations Tab to backend settings
* New Capabilities - Backup, Restore, Manage Backups, Reset Database
* Cleaned up some quoting output

= 1.1.3 =
* 2015-07-02
* Added clear all logging capability
* Styling fixes

= 1.1.2 =
* 2015-06-30
* Some style fixes
* Added some new statistic views for entries

= 1.1.1 =
* 2015-06-19
* Fixed some slash single quote issues on display

= 1.1.0 =
* 2015-06-19
* Initial release

== Upgrade Notice ==
= 2.0.0 = 
* Many new features and improvements!

= 1.2.6 =
* Added functionality for Contact Form 7 radio and checkbox fields

= 1.1.0 =
* Initial release

