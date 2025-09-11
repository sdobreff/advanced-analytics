=== WP Control ===
Tags: error log, debug, cron, transients, requests, mail log
Requires at least: 6.0
Tested up to: 6.8.2
Requires PHP: 7.4
Stable tag: 3.6.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.txt

**WP Control** will give you control over your WP like never before. 

== Description ==

**WP Control** is a powerful plugin designed for WordPress sites with extensive error logs. But it is not limited to that, it will also provide you with:
- Read and analyze error logs even if the error log is gigabytes of size. (Only up to last 999 records are shown).  The limitation is necessary for two reasons - first if there are more errors they are either not relevant or repeating ones, and second - that way your server will continue operate without any interruptions even if your log is few gigs of data.Filtering by type is supported. You can also filter by plugin name - **WP Control** is the first and only with such functionality. There is also a search by string option.
- Enable or disable error logging directly from the WordPress dashboard.
- Manage large log files without performance irruptions.
- Easily see where exactly error is thrown (built-in code viewer)
- **Cron manager** at the tip of your fingers (edit / delete / run).
- **Transient manager** - all (stored in the DB) transients (edit / delete).
- **Requests manager** - all requests which your WP install is making (edit / delete).
- **Mail logger** - all mails which your WP sends are recorded and accessible from here (edit / delete).
- You can even compose your emails directly from the plugin.
- **SMTP** mail settings are built-in - you can setup you provider now.
- **SQL tables manager** - From here you can see and delete records from all the tables currently present in the Database.
- **Environment type** - There is the notification in the admin bar which tells you what is the selected type of the current environment you are on (can change it (the env type) from the settings or completely disable it)
- **Plugin version switcher** - Now you can change the plugin version directly from the admin Plugins page of your WordPress. Shows the path where main plugin file is located.

**Important:** Description below does not apply for multisites, as this feature is not implemented by the WordPress core team for multisites yet!
**Recovery Mode** link in the notification channels (Slack and Telegram if set) is added along with the fatal error message. WP core not always kicks in on fatality errors and this makes sure that you still have access to your website.

**How this feature works:**
If fatal error is detected from the plugin, it sends notifications to Slack or Telegram channels (if set), and provides recovery link along with the error message. When used, that link allows admin to login and suppresses all the plugins and active theme (except the **WP Control** plugin). You can observe the error log using the plugin screen and see where, when and what caused the error and take measures. You can completely disable the errored plugin or theme, switch to another version or just fix the error (if possible). Once done - just exit recovery mode and everything should continue working normally.
Note: Every time fatal is thrown, for security reasons new link is generated, every single one of them should work, but on busy sites that could lead to tens of generated links.

This plugin is ideal for developers and administrators who need robust tools for troubleshooting and maintenance.

You can completely disable individual modules (if you are not using them) or enable them only when needed.

You can see it in action [here](https://wordpress.org/plugins/0-day-analytics/?preview=1&networking=yes "WP Playground") or use the "Live Preview" button on the WordPress plugin page.

**Key Features**:
- Handles gigabyte-sized error logs seamlessly.
- Option to enable or disable logging via the admin interface.
- Optimized for high-performance even with large log files.
- Provides insights into logged errors for efficient troubleshooting.
- Built-in fully functional Cron manager.
- Built-in fully functional Transients manager.
- Built-in Table manager.
- Built-in Requests viewer.
- Built-in Mail logger.
- Built-in SMTP.
- Built-in mail composer.
- Built-in badge that shows you current environment type.
- Option to randomize the name of the error log file (security).
- Easily plugin version switch (the ones from official WP marketplace).
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

= Why only last 999 error logs? =
Plugin is designed to be as fast as possible and work with enormously large log files, consuming as less resources as possible, no other plugin could provide something even close to that, thats why it comes with this limitation. And one single error could contain more than 30K sub-rows. But 999 is more than enough - errors before that are either too old, no longer related or repeating ones, in fact is best to regularly truncate your log file and check for only the last error. And last but not least - this are 999 errors not 999 lines of the error log. You can increase that up-to 999 using screen options menu but do that on your wn risk.

= Why there is no pagination for error logs? =
That is once again related to the nature of the error log - one single reload could generate tens of new errors, so paginating this would probably never come handy, and in order to paginate, the entire log must be read every time - which is extremely bad idea resource-wise.

= Why I see 2 records for errors which look the same but one is with "Details" button? =
Usually deprecated errors in WP and next to useless when it comes to guess what is causing them - WP Control captures deprecated WP errors as early as possible and even before the check if they should or not trigger error. That means the plugin will log deprecated error even if that given error is set (usually by other plugins) not to trigger error (silenced) and then will return the execution to the WordPress. That is done because this way plugin can provide very detailed information of what caused the error and when (in code) hence - the "Details" button. If the given error is not silenced, it will then trigger normal error which comes after the plugin check - that is the reason for 2 almost the same errors.

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
9. **Requests operations** - All the request made from the given WP install.

== Changelog ==

= 3.6.1 =
Text search introduced in Error Log viewer.

= 3.6.0 =
Various small issues fixes. Added option to show the active plugins first in plugins page. Added cron job for auto truncating the mail log table (clears the entire table) - default to 1 week.

= 3.5.2 =
Adds option to set from email address and option to set from name (for mail SMTP options). Fixes problem with mail list when attachments are set to null.

= 3.5.1 =
Fixes problem with settings can not be saved because of required field.

= 3.5.0 =
Added filtering to the mail log. Added option to send mail directly from the plugin.

= 3.4.1 =
Small fix for doing_it_wrong from this plugin.

= 3.4.0 =
Fixed some errors related to showing the HTML enabled error logs. Added logging for when doing_it_wrong_run is triggered. Added filtering to crons and transients - now with the most comprehensive filtering system for those two.

= 3.3.1 =
Small maintenance update, addressed are mostly UI problems, non HTML mails viewer improvements.

= 3.3.0 =
Added mail SMTP settings - gives option to configure your own SMTP server settings. Added option for test email settings. Code fixes.

= 3.2.0 =
Automatic fallback to AJAX if WP apiFetch function is missing for Cron calls. Inner PHP error logging mechanism if nothing else can be used (wp-config is not writable, and there is nothing else that enables the error logging, that will not enable inner WP logging which has dependency on WP_DEBUG to be set to true). Table view now trims the column values to 100 symbols, and provides View option which displays the content in new window. Mail viewer now handles different types of attachments outside from the media folder.

= 3.1.1 =
Fixed problem with Cron Jobs execution, thanks to @lucianwpwhite

= 3.1.0 =
Added "From" in Mail logger module.

= 3.0.0 =
Mail logger introduced.

= 2.9.0 =
Option to disable browser notifications from settings (in plugin not in the browser). Implemented different sorting options for crons viewer. Code improvements and bug fixes.

= 2.8.2 =
As of this version you can disable individual modules (if you are not using them) or enable them only when needed. Cron job introduced for auto clearing the requests table. Switched cron execution from AJAX to REST API. Filter error log results by Plugin (if there are results). Cron job introduced for auto clearing the error log file (leaving last records based on the selected value from settings). 

= 2.8.1 =
Option to copy and share Request / Response from the request details. Code optimizations. Error log filtering enhancements.

= 2.8.0 =
REST API calls monitoring in the request viewer, logic improvements. Plugin own REST API name endpoint change.

= 2.7.2 =
Solves problem with fatal error "Call to undefined function is_user_logged_in" very thanks to @lucianwpwhite on this one. Fixed problem with bulk actions not working on plugins.php page.

= 2.7.1 =
Small maintenance update - mostly UI problems addressed.

= 2.7.0 =
Introduced Request viewer log. Bug fixes and UI improvements. Code optimizations. Multisite optimizations.

= 2.6.2 =
Multisite improvements and transient edit functionality fixes.

= 2.6.1 =
Recovery mode improvements - Fixes problem with Slack notifications - by default Slack follows links. Added checks for multisite and suppresses logic if one is detected.

= 2.6.0 =
Code logic improvements. Added option to disable all external requests. Added error capturing for when API requests trow WP_Error. Extended error reporting feature. Implemented recovery mode if fatal error occurs and WP Core does not catch it.

= 2.5.0 =
Multisite fixes, trigger_error filter introduction.

= 2.4.2.1 =
Bug fix when database name contains '-'.

= 2.4.2 =
Bug fix with missing variable in error class - special thanks to @lucianwpwhite.

= 2.4.1 =
Removed messages when WP_DEBUG_DISPLAY is enabled as it produces "headers already sent" notification. Tables view now supports Truncate operation (for all tables) and Drop operation (for non wp core tables). Deprecation WP functions improvements and better handling. Code optimizations.

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
