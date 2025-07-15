=== WP Control ===
Tags: log, error log, debug, cron, transients
Requires at least: 6.0
Tested up to: 6.8.1
Requires PHP: 7.4
Stable tag: 2.4.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.txt

**WP Control** is a WordPress plugin which purpose is to manage all kinds on error logs. It has build in fully functional **Cron** and **Transients** management system and plugin version switcher.

== Description ==

**WP Control** is a powerful plugin designed for WordPress sites with extensive error logs. It allows administrators to:
- Read and analyze error logs even if the error log is gigabytes of size. (Only up to last 100 records are shown) Filtering by type is supported. The limitation is necessary for two reasons - first if there are more errors they are either not relevant or repeating ones, and second - that way your server will continue operate without any interruptions even if your log is few gigs of data.
- Enable or disable error logging directly from the WordPress dashboard.
- Manage large log files without performance degradation.
- **Cron manager** at the tip of your fingers (edit / delete / run).
- **Transient manager** - all (stored in the DB) transients (edit / delete).
- **SQL tables manager** - From here you can see and delete records from all the tables currently present in the Database.
- **Environment type** - There is the notification in the admin bar which tells you what is the selected type of the current environment you are on (can change it (the env type) from the settings or completely disable it)
- **Plugin version switcher** - Now you can change the plugin version directly from the admin Plugins page of your WordPress. Shows the path where main plugin file is located.
- Easily see where exactly error is thrown (where detected)

This plugin is ideal for developers and administrators who need robust tools for troubleshooting and maintenance.

You can see it in action [here](https://wordpress.org/plugins/0-day-analytics/?preview=1&networking=yes "WP Playground") or use the "Live Preview" button on the WordPress plugin page.

**Key Features**:
- Handles gigabyte-sized error logs seamlessly.
- Option to enable or disable logging via the admin interface.
- Optimized for high-performance even with large log files.
- Provides insights into logged errors for efficient troubleshooting.
- Built-in fully functional Cron manager.
- Built-in fully functional Transients manager.
- Built-in Table manager.
- Built-in badge that shows you current environment type.
- Option to randomize the name of the error log file (security).
- Easily plugin version switch (the ones from official WP marketstore).
- Built-in dark mode.

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

= Why only last 100 error logs? =
Plugin is designed to be as fast as possible and work with enormously large log files, consuming as less resources as possible, no other plugin could provide something even close to that, thats why it comes with this limitation. And one single error could contain more than 30K sub-rows. But 100 is more than enough - errors before that are either too old, no longer related or repeating ones, in fact is best to regularly truncate your log file and check for only the last error. And last but not least - this are 100 errors not 100 lines of the error log. You can increase that up-to 999 using screen options menu but do that on your wn risk.

= Why there is no pagination for error logs? =
That is once again related to the nature of the error log - one single reload could generate tens of new errors, so paginating this would probably never come handy, and in order to paginate, the entire log must be read every time - which is extremely bad idea resource-wise.

= How do I enable or disable error logging? =  
Go to "WP Control > Settings" in your WordPress dashboard and toggle the logging option as needed.

= Can this plugin handle large error logs? =  
Yes, Error Log Manager is optimized for gigabyte-sized log files, ensuring smooth performance even with extensive logs.

= Where are the error logs are stored? =  
The plugin autodetects default error log location, usually WordPress defines that in `wp-config.php`. You can customize this path if needed - this is strongly recommended for security reasons, and don't worry - you can do it with one click from plugin settings.

= Note =
Because of its extremely poor implementation and interfering with the proper WordPress workflow (debug log set, constant pollution of the log file, improper JS implementation etc.), *Log-IQ* plugin is automatically deactivated.

== Screenshots ==

1. **Error Log Overview** - Displays a summary of recent errors logged.
2. **Settings Page** - Toggle logging options and configure advanced settings.
3. **Setting reset / import / export** - You can upload or reset plugin settings from here.
4. **Cron manager** - Built-in is very powerful cron manager.
5. **Transients manager** - Built-in is very powerful transients manager.
6. **Plugin Version Switcher** - Built-in plugin version switcher.
7. **Table manager** - Built-in is very powerful SQL table manager.
8. **Table manager operations** - Current table more detailed information and truncate and delete operations.

== Changelog ==

= 2.4.1 =
Removed messages when WP_DEBUG_DISPLAY is enabled as it produces "headers already sent" notification. Tables view now supports Truncate operation (for all tables) and Drop operation (for non wp core tables).

= 2.4.0 =
Code and UI improvements. JS fixes.

= 2.3.0 =
Bug fixes. Added single rows delete confirmation. Table information included.

= 2.2.2 =
Bug fixes. Added confirmation dialog when delete DB table record from the quick menu. Logic improvements.

= 2.2.1 =
Fixed "doing_it_wrong" error.

= 2.2.0 =
Now supporting all tables in the give DataBase. Bug fixes and optimizations.

= 2.1.3 =
PHP Warnings fix.

= 2.1.2 =
Warnings fix removed and added table size.

= 2.1.1 =
WP Screen not set error fix.

= 2.1.0 =
Code optimizations and SQL Table Viewer.

= 2.0.0 =
Code improvements - mostly JS responses and better interaction with UI. Errors coming from mail function (WP_Error) catching. Text selected in the console-like window (bottom of the error log viewer) is automatically copied into the clipboard.

= 1.9.8.2 =
Very small code updates and proper version settings.

= 1.9.8.1 =
'Headers already sent' in settings error fix.

= 1.9.8 =
Automatically deactivates Log-IQ plugin. Lots of code optimizations, added Cron add functionality, fixed errors.

= 1.9.7 =
Extended default admin Plugins page - gives thee option to switch to older version directly from the page and shows information about the plugin main file location.

= 1.9.6 =
Lots of UI changes both light and dark skin. Filtering the severities directly from the error log list view.

= 1.9.5.1 =
Fixed warning message about missing setting. Small code optimizations.

= 1.9.5 =
Added option to monitor wp_die when it is called with parameters - enabled by default.

= 1.9.4.1 =
Bug fixes and UI changes.

= 1.9.4 =
Code and UI improvements. Added push notifications option.

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
