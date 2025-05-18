=== Advanced Analytics ===
Tags: log, error log, 0-day, analytics, cron, crons, transients
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.7.4
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.txt

**Advanced Analytics** is a WordPress plugin which purpose is to manage all kinds on error logs (large or small), providing options to enable or disable logging and optimized for gigabyte-sized log files. It is specially designed and optimized to work on production sites without slowing them. It has build in fully functional **Cron** and **Transients** management system, so everything you need to get best inner insight of your site is one plugin built for speed.

== Description ==

Advanced Analytics is a powerful plugin designed for WordPress sites with extensive error logs. It allows administrators to:
- Read and analyze gigabytes of error logs efficiently.
- Enable or disable error logging directly from the WordPress dashboard.
- Manage large log files without performance degradation.
- **Cron manager** at the tip of your fingers
- **Transient manager** - all (strored in the DB) transients

This plugin is ideal for developers and administrators who need robust tools for troubleshooting and maintenance.

You can see it in action [here](https://wordpress.org/plugins/0-day-analytics/?preview=1&networking=yes "WP Playground") or use the "Live Preview" button on the WordPress plugin page.

**Key Features**:
- Handles gigabyte-sized error logs seamlessly.
- Option to enable or disable logging via the admin interface.
- Optimized for high-performance even with large log files.
- Provides insights into logged errors for efficient troubleshooting.
- Build-id fully functional Cron manager
- Build-id fully functional Transients manager

== Installation ==

1. Download the plugin from the WordPress Plugin Directory.
2. Upload the `0-day-analytics` folder to the `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. You will see new menu in adminbar (left hand) called Analyse.

Voila! It's ready to go.

## Technical specification...

* Designed for both single and multi-site installations
* PHP8 fully compatible

== Frequently Asked Questions ==

= How do I enable or disable error logging? =  
Go to "Analyze > Settings" in your WordPress dashboard and toggle the logging option as needed.

= Can this plugin handle large error logs? =  
Yes, Error Log Manager is optimized for gigabyte-sized log files, ensuring smooth performance even with extensive logs.

= Where are the error logs stored? =  
The plugin autodetects default error log location, usually WordPress defines that in `wp-config.php`. You can customize this path if needed.

== Screenshots ==

1. **Error Log Overview** - Displays a summary of recent errors logged.
2. **Settings Page** - Toggle logging options and configure advanced settings.
3. **Setting reset / import / export** - You can upload or reset plugin settings from here.
4. **Cron manager** - Build-in is very powerful cron manager.

== Changelog ==

= 1.7.4 =  
* UI / UX improvements and link fixes.

= 1.7.3 =  
* Lots of bug fixes and UI / UX improvements.

= 1.7.3 =  
* Lots of bug fixes and UI / UX improvements.

= 1.7.2 =  
* Deprecation error fix.

= 1.7.1 =  
* UI improvements. Fixed class reflection and method extraction in crons.

= 1.7.0 =  
* Bug fixes and UI improvements. Transients manager added.

= 1.6.1 =  
* Fixed bugs with error reader and improved memory management.

= 1.6.0 =  
* Code and memory optimizations, new functionalities, features and UI changes in Cron manager.

= 1.5.1 =  
* Fatal error on delete crons fixed.

= 1.5.0 =  
* Lots of code optimizations and improvements. Cron list extending and optimizing. UI changes. Bug and functionality fixes.

= 1.4.0 =  
* Code optimizations and functionality enhancements. Cron list extending and optimizing

= 1.3.0 =  
* Code optimizations and functionality enhancements. More of WP deprecated events are now support. First version of Cron manager introduced (multisite is not fully supported yet and functionalities are limited)

= 1.2.0 =  
* Code optimizations and functionality enhancements. Console now shows lines the way PHP error log stores them (not in reverse order). The new errors (count) is now shown next to the admin menu item. Admin bar size is reduced.

= 1.1.1 =  
* :) .

= 1.1.0 =  
* Fixed lots of problems, code optimizations and functionality enhancements.

= 1.0.1 =  
* Small fixes and improvements.

= 1.0.0 =  
* Initial release of Error Log Manager.
