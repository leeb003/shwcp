# WP Contacts

![wpcontacts header](assets/images/header.jpg)

---

### Description

WP Contacts is an excellent WordPress Plugin that allows you to manage your
leads and contacts in WordPress.  Easy to install, and easy to set up, WP
Contacts gives you excellent tools for organizing your leads.  With WP Contacts you can set
images for your leads, create your own custom fields, upload files to
associate with leads, view statistics, add notes, export and import leads, set
permissions for who can access your leads, automatic updates, along with many other features!  WP Contacts is translation ready and built to be responsive for using all of your devices!

---

### Requirements

- WordPress version 5+
- PHP 7.x

---

### Quick Start - Install

Installing "WP Contacts" can be done by using the following steps:
- Download the plugin as a zip
- Upload the ZIP file (shwcp.zip) through the 'Plugins > Add New > Upload' screen in your WordPress dashboard
- Activate the plugin through the 'Plugins' menu in WordPress

[Installing WP Contacts Video](assets/InstallingWPContacts.mp4)

---

### Frequently Asked Questions

#### What does this plugin do?
This plugin is for tracking and managing your leads and contacts.  Built as a
plugin for Wordpress, installation is easy and painless.  WP Contacts gives you
the control and flexibility to customize all sorts of aspects of your lead and contact
management.

#### I am unable to interact with WP Contacts Frontend


Although rare, you may come across logging into the front end of WP Contacts after setting up the backend only to find you are unable to Add new entries, edit or save anything.  Or, all of the styles are not what’s shown in the demo.  These issues are indicative of a plugin or theme adding javascript that is throwing errors on the page, or injecting styles on the page.

To verify, you can use Firebug or Chrome Inspector to see if there are any Javascript errors on page load.  You can also try switching themes (to something like twentysixteen) and disabling other plugins to see if it clears up.

Themes and Plugins these days are supposed to follow certain standards for WordPress and the cause of this issue every time has been someone not ‘enqueuing’ their scripts and styles the correct way.  Take a look at the following articles for more information

- http://www.wpbeginner.com/wp-tutorials/how-to-properly-add-javascripts-and-styles-in-wordpress/
- https://premium.wpmudev.org/blog/adding-scripts-and-styles-wordpress-enqueueing/

The good news is most theme and plugin authors want to follow WordPress standards and will take care of that if they are made aware of the problem.

If in the case you must use a theme or plugin that is conflicting and you cannot resolve it any other way, here are some things you can do:

Some of our users have reported using the Multiple Themes plugin successfully to switch themes for the Contacts page https://wordpress.org/plugins/jonradio-multiple-themes/

If you have a plugin that you know is the cause of a conflict (by testing disabling them one by one mentioned above) and you MUST use this plugin…you can try using the Plugin Organizer plugin to disable the conflicting plugin only on the WP Contacts page -> https://wordpress.org/plugins/plugin-organizer

We include the troubleshooting backend setting (at the bottom of your Default database -> Main Settings tab) that will accomplish the below adjustment for you.  All you need to do is set it to true and save.

Working around other plugins / theme javascript errors

We enqueue our scripts in the footer to optimize page loading, you can change all scripts to load in the header to avoid conflicts as a last resort.

#### I am getting Error 200 on file imports


This message is typically related to PHP limitations on your server (upload size, memory limit etc.) or the permissions in the WordPress upload directory, possibly even a missing PHP dependency.  Since it is a generic response from the server, you need to see what the underlying issue is that needs to be changed.

- First of all check your server error log.  This will tell you what the problem is.  You can also go to the WP Contacts Site Information tab in the backend settings to see what some of the current PHP values are set to for a reference as well.
- Make sure your WordPress upload directory has the correct permissions to write files – a quick test for this is to try adding an image to your Library through the WordPress admin.  If you can’t upload an image, you’ll need to make changes to your WordPress installation to allow access.

#### I am getting the login window with undefined buttons

This indicates that you have some form of caching enabled (Either in a plugin or a server setting).  You will want to disable caching for WP Contacts to prevent this behavior.  You’ll know if by reloading the page after login (ctrl + R or refresh button) and the page shows up correctly. 

#### Full Documentation located at https://www.scripthat.com/wpcontacts/

---

### Changelog

 3.3.4  
- PHP 8x support
- Wordpress 6.1.1 support

 3.3.3  
- Fix for fullcalendar, update to version v3.10.2 from v2.5.0 for WordPress 5.6.2 

 3.3.1 
- Added Automatic role selection for new users
- Event notes editor and some styling fixes for events

 3.3.0  
- Added print page for individual entries option
- Added notes capabilities - links and image insertion as well as quite a few other formatting options
- Changed some icons and logos

 3.2.9  
- Fix for resizing thumbnails routine on non-default database
- Fix for some ui bugs
- Added some Gravity Forms enhancements

 3.2.8 
- Fix for displaying multi-select fields in view only mode on the individual entry page
- Gutenberg Database selection Classic editor metabox interference
- New feature - Individual field overrides for custom roles. You can now choose which fields are accessible for custom roles that you create!
- Code editor for custom css and js fields
- Backend ui enhancements

 3.2.7 
- Cloning Databases functionality.  Now you can easily clone an existing database with settings to another new one in your backend settings.

 3.2.6  
- New feature Multiselection fields!  Now you can create custom multiselection field types in your WP Contacts.
- Set name for export files.

 3.2.5  
- Users now have the option to download database backup zip files through the backend Database settings tab

 3.2.4  
- Custom thumbnail sizes are now allowed and can be set in the backend settings.  
- Existing thumbnails can be regenerated through Settings -> Manage Front Page on the frontend

 3.2.3 
- Uninstall routine correctly removes tables and options upon uninstall

 3.2.2  
- Gutenberg sidebar implemented for database selection on WP Contacts page when WP Contacts page template is selected

 3.2.1 
- Automatic updates are now handled by the Envato Market plugin, users will be given the option to install it for managing updates now.

 3.2.0 
- Added insert notes capabilities to the REST api so that notes can now be created this way as well

 3.1.9 
- Added update entries functionality to spreadsheet imports to allow data with an ID column to update existing
- entries with the same id instead of importing new ones
- Fixed php 7.2+ null count warning message

 3.1.8  
- Added detail logging on Entry detail modification, something we've overlooked in the past

 3.1.7  
- Added duplicate entries functionality to frontend
- PHP Notification fixes

 3.1.6  
- Fixed bug when adding new entry could not go directly to entry to edit
- Changed add/edit modal to disallow outside clicks when open

 3.1.5 
- Fixed bug where database ordering for form integration was not sending
- results to the correct database when non-default used.

 3.1.4 
- Greek language file added - Thanks to George Konstantinidis!

 3.1.3  
- Bug fix for custom roles small screens adding entries

 3.1.2 
- Bug fix for ie 11 with javascript error thrown for no matching parenthesis

 3.1.1  
- Bug fix for add new entries permission where radio allowed both being
- checked and not saving to db

 3.1.0 
- Bug fix choice selection for form integrations when selection exists for
- other dropdown
- Contact Form 7 version 4.8 update fix tag array to object check

 3.0.9  
- Bug fix dropdown sort ordering on frontpage *

 3.0.8 
- Added RTL CSS file for RTL language support *

 3.0.7  
- Bug fix for read only users viewing individual entries
- Bug fix for undefined warning on front view for custom roles

 3.0.6  
- New feature! Dynamic permissions
- SST migrated to dropdowns, all can be managed under Field Management section
- New feature! Individual page layout management
- New feature! Dynamic statistics match dropdowns created
- Reorganized field edit page to match field capabilities
- Initial default settings saved on install for quick access
- Export "current view" added to front page for easily exporting filtered views
- Date only field type added
- Print css added to individual page for better printing
- Contact Form 7 file upload mapping to WP Contacts now available
- Be sure to make a backup before updating just in case!

 3.0.5  
- Dynamic filters on front page, now you can use dropdowns for filtering as well
- Filtering exports capability now included
- File preview on front page capability added
- UI enhancements

 3.0.4 
- API Action hooks small fix to send all fields and not just front page ones
- on insert and update

 3.0.3 
- Fix for CF7 deprectated notice
- Fix for php version 7 and break command on excel export
- Fix for custom javascript applying to correct database
- Year dropdown selector for datetime field types added.

 3.0.1 
- WP Contacts RESTful API Integration!

 2.1.2 
- Fix for individual lead display for Publicly accessible set up and
- non-logged-in users

 2.1.1 
- Updates for WordPress version 4.7 - fixes template selection for page in
- this version.

 2.1.0 
- Changed a template generating class name that was conflicting with an automotive theme
- Added uri encoding to search for special character search support

 2.0.9 
- Bug Fix - Multiple custom events not showing up on same day
- Changed search feature to paginated results instead of displaying all at
- once for main results and logs

 2.0.8  
- Added custom filter hooks to adjust output for all frontend pages and
- sections
- Some minor styling fixes added

 2.0.7 
- Added support for Ninja Forms v3+ since they've updated some of their hooks
- and processes

 2.0.6 
- Fixed a bug that affected multiple select field types with the same
- selections on import

 2.0.5 
- Fix for multisite and notes not being displayed on sub sites for individual
- contacts

 2.0.4 
- Fixed bug with database selection if using default db and assigning it
- Added user id to body class for Custom CSS targetting 
- Several minor improvements
- New! Dropdown fields added as a new field type

 2.0.3  
- Fixed file upload url reporting incorrect path for non-default database
- Added jpeg extension support for main image upload
- New Spanish translation files - Thanks to Ruben Villar

 2.0.2 
- Fixed non-default database reset folder recreation
- Fixed small bug on mobile search overlapping first entry
- Fixed several small styling issues
- Added move our scripts to header capability for troubleshooting

 2.0.1 
- Fields can now be set to be required inputs in forms
- Date fields can now be set to use WordPress custom formats
- Search from individual entry page
- Speed improvements and db versioning
- Fix to daily stats graph to go through current day
- Many other minor improvements

 2.0.0 
- Many new features and improvements!
- Unlimited number of separate databases per installation - each with their own access and settings.
- Ninja Forms Integration
- Gravity Forms Integration
- Calendaring, Events, and Notifications functionality now included
- Mailchimp API export direct 
- Custom JS now available
- Custom Links for Frontend menu
- New Fields - star rating, date time, checkbox, Grouping Titles
- Edit Notes capability
- Search All Fields ability
- Sticky quick edit bar
- Many styling enhancements and layout fixes (Big thanks to Roman!)
- Select and delete multiple entries at a time

 1.2.6 
- Modification for Contact Form 7 radio and checkbox field types to be saved

 1.2.5  
- WordPress 4.4 compatability

 1.2.4 
- Added Contact Form 7 integration to insert contact form submissions into
- database - be sure to read the docs on Contact Form 7 usage

 1.2.3 
- Added Back end setting to set default sort field and direction for Front Page view

 1.2.2  
- Added Google Map Link field type
- Fixed Front page sorting issue which could be triggered when managing fields

 1.2.1 
- Fixed styling overflows on large menus
- New feature! Added field types for further customizing fields

 1.1.9  
- Fix - Added missing translation strings
- WP Contacts version notice in backend

 1.1.8  
- 2015-08-24-2015
- Fix - Adding a new contact now defaults to the current user in dropdown
- Added capability to disable the ability to transfer contacts for users who
- manage their own leads

 1.1.7 
- 2015-08-07-2015
- Using WP Contacts as Front page fix search parameters
- Fix search & sorting combo views
- Default view set to latest entries first

 1.1.6  
- 2015-07-29
- Multisite Ready
- CSS Class selectors added to front page for targetting custom css
- No images used bug fix for add contact

 1.1.5 
- 2015-07-26
- Added Translation to Spanish
- Fixed some SST & Fields saving bugs

 1.1.4  
- 2015-07-26
- Added Database Operations Tab to backend settings
- New Capabilities - Backup, Restore, Manage Backups, Reset Database
- Cleaned up some quoting output

 1.1.3 
- 2015-07-02
- Added clear all logging capability
- Styling fixes

 1.1.2 
- 2015-06-30
- Some style fixes
- Added some new statistic views for entries

 1.1.1 
- 2015-06-19
- Fixed some slash single quote issues on display

 1.1.0 
- 2015-06-19
- Initial release

