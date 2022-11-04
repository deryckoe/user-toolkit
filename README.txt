=== User Tools ===
Contributors: deryck
Donate link: http://deryckoe.com/user-toolkit
Tags: user, user-tools, user-toolkit, last-login, registered-date, user-id, disable-users
Requires at least: 5.9.5
Tested up to: 6.1
Requires PHP: 7.3
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The missing user tools and activity data that you need and don't have by default.

== Description ==

User Tools adds missing features to user management, such as basic user activities, including last login and registration dates. You can deactivate users without deleting them, allowing you to maintain your ownership of past user activity and content.

== Installation ==

1. Upload `user-toolkit` to the `/wp-content/plugins/` directory or install directly through the plugin installer.
2. Activate the plugin through the 'Plugins' menu in WordPress or by using the link provided by the plugin installer.
3. Disable any extra column you don't want, using Screen Options in the Users screen.
4. Enable or disable any user login access in the Users screen or in the User Edit screen.

== Frequently Asked Questions ==

= Will this plugin deactivate all users login by default? =

No. All users will remain active by default. You select what users do you want to deactivate.

= Last Login dates will be displayed as soon as I activate User Tools? =

No. WordPress does not have that information. It's introduced with the plugin so will be tracked as soon as you enable it. However, we are working to have the last activity of the user available as soon as the plugin is activated, even if the user has not logged in yet.

== Screenshots ==

1. Login activation/deactivation, Registration date, Last Login date and ID columns.
2. Filter by login status.
3. Login status, registration and last login dates in user profile.

== Changelog ==

= 1.0.0 =
* Initial Release

= 1.0.1 =
* Downgraded to support PHP 7.3

== Upgrade Notice ==

= 1.0.0 =
Last Login, Registration Date and disable user login without deleting him.

= 1.0.1 =
Downgraded to support PHP 7.3
