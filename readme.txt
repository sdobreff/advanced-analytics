=== WP Control ===
Tags: log, error log, analytics, cron, crons, transients
Requires at least: 6.0
Tested up to: 6.8.1
Requires PHP: 7.4
Stable tag: 1.9.3
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.txt

**WP Control** is a WordPress plugin which purpose is to manage all kinds on error logs (large or small), providing options to enable or disable logging and optimized for gigabyte-sized log files. It is specially designed and optimized to work on production sites without slowing them. It has build in fully functional **Cron** and **Transients** management system, so everything you need to get best inner insight of your site is one plugin built for speed.

== Description ==

**WP Control** is a powerful plugin designed for WordPress sites with extensive error logs. It allows administrators to:
- Read and analyze error logs even if the error log is gigabytes of size. (Only up to last 100 records are shown) Filtering by type is supported. The limitation is necessary for two reasons - first if there are more errors they are either not relevant or repeating ones, and second - that way your server will continue operate without any interruptions even if your log is few gigs of data.
- Enable or disable error logging directly from the WordPress dashboard.
- Manage large log files without performance degradation.
- **Cron manager** at the tip of your fingers (edit / delete / run)
- **Transient manager** - all (stored in the DB) transients (edit / delete)
- **Environment type** - There is the notification in the admin bar which tells you what is the selected type of the current environment you are on (can change it (the env type) from the settings or completely disable it)

This plugin is ideal for developers and administrators who need robust tools for troubleshooting and maintenance.

You can see it in action [here](https://wordpress.org/plugins/0-day-analytics/?preview=1&networking=yes "WP Playground") or use the "Live Preview" button on the WordPress plugin page.

**Key Features**:
- Handles gigabyte-sized error logs seamlessly.
- Option to enable or disable logging via the admin interface.
- Optimized for high-performance even with large log files.
- Provides insights into logged errors for efficient troubleshooting.
- Build-in fully functional Cron manager
- Build-in fully functional Transients manager
- Build-in badge that shows you current environment type
- Option to randomize the name of the error log file (security)

== Installation ==

1. Download the plugin from the WordPress Plugin Directory.
2. Upload the `0-day-analytics` folder to the `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. You will see new menu in admin bar (left hand) called `WP Control`.

Voila! It's ready to go.

## Technical specification...

* Designed for both single and multi-site installations
* PHP8 fully compatible

== Frequently Asked Questions ==

= How do I enable or disable error logging? =  
Go to "WP Control > Settings" in your WordPress dashboard and toggle the logging option as needed.

= Can this plugin handle large error logs? =  
Yes, Error Log Manager is optimized for gigabyte-sized log files, ensuring smooth performance even with extensive logs.

= Where are the error logs are stored? =  
The plugin autodetects default error log location, usually WordPress defines that in `wp-config.php`. You can customize this path if needed - this is strongly recommended for security reasons, and don't worry - you can do it with one click from plugin settings.

== Screenshots ==

1. **Error Log Overview** - Displays a summary of recent errors logged.
2. **Settings Page** - Toggle logging options and configure advanced settings.
3. **Setting reset / import / export** - You can upload or reset plugin settings from here.
4. **Cron manager** - Build-in is very powerful cron manager.
5. **Transients manager** - Build-in is very powerful transients manager.

== Changelog ==

= 1.9.3 =
Added option for logging errors from REST API (can be disabled from the settings). Code improvements and bug fixes.

= 1.9.2.1 =
Bug fix when plugin is activated WP Screen is not set

= 1.9.2 =  
* Option to create transient. Option to truncate file but keep selected amount of last records. UI fixes and code optimizations. Late initialize to save resources.

= 1.9.1 =  
* Silencing warnings coming from \is_file where restrictions / permissions are in place.

= 1.9.0 =  
* Code optimizations. UI improvements. Bug fixes. Better source reporting.

= 1.8.6 =  
* Providing editing option for Crons and Transients, code optimizations.

= 1.8.5 =  
* Bug fixes and code optimizations. Telegram notifications support.

= 1.8.4.1 =  
* Fixed problem with init hook called too early.

= 1.8.4 =  
* Added control for more WP core constants as WP_DEVELOPMENT_MODE, SCRIPT_DEBUG, SAVEQUERIES etc. Added evnironment type show in the Admin bar (WP_ENVIRONMENT_TYPE). Added code viewer optins in the details section of error log. UI fixes and code optimizations.

= 1.8.3 =  
* Lots of UI fixes and code showing optimizations.

= 1.8.2 =  
* UI fixes related to the severity colors, added option to enable / disable the Admin bar live notifications, small code optimizations.

= 1.8.1 =  
* Source view button in error log, PHP 7 problem fix.

= 1.8.0 =  
* Logic improvements, menu name change, Slack notifications for fatal errors, speed optimizations.

= 1.7.5 =  
* Lots of UI fixes and dark theme optimizations, small code fixes.

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
