=== User Toolkit ===
Contributors: deryck
Donate link: http://deryckoe.com/user-toolkit
Tags: user profile, last login, disable user, registration date
Requires at least: 5.9.5
Tested up to: 6.1
Requires PHP: 7.3
Stable tag: 1.1.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The missing user tools and activity data that you need and don't have by default.

== Description ==

User Tools adds missing features to user management, such as basic user activities, including last login, registration dates and user switch from the User administration screen. You can deactivate users without deleting them, allowing you to maintain your ownership of past user activity and content.

## SECURITY

**Disabled user**

Your own user or the first created used cannot be disabled. Disabled user will not lost data or be deleted under any circumstances.

**User switching**

Only users with the ability to edit other users can switch user accounts. Unless you create new roles with this capabilities, this is only Administrators on single site installations, and Super Admins on Multisite installations.
Passwords are not (and cannot be) revealed.
Uses the cookie authentication system in WordPress for user switching.
Implements the nonce security system in WordPress, meaning only those who intend to switch users can switch.
Full support for user session validation where appropriate.
Full support for administration over SSL (if applicable).

## USAGE

**Disable user**

1. Visit the Users menu in WordPress and you will see a enable/disable switch in the list of each user.
2. Click on the "Activate" switch to disable (gray) or to enable (blue).
3. Visit every user profile and check/uncheck "Activate user login" to enable/disabled the user.

**Switch user**

1. Visit the Users menu in WordPress and you will see a "Switch to" link in the list of each user.
2. Visit every user profile and click on the "Switch to {user}" to switch to the user.
3. You will be able to switch back using the message that will appear in every admin screen.
4. You will be able to switch back using the "Switch back to {user}" located in the User menu in the admin bar.
5. If the user you switched to does not have access to the admin screens you will be able to switch back using the link located in the right bottom corner of the screen.

**User Columns**

1. Visit the Users menu in WordPress and you will see a "Last Login", "Registered" and "ID" columns by default in the list of each user.
2. Disable all or any column clicking "Screen Options" on the right top corner of the screen.


## PRIVACY STATEMENT

This plugin makes use of browser cookies in order to allow users to switch to another account. Its cookies operate using the same mechanism as the authentication cookies in WordPress core, which means their values contain the user’s user_login field in plain text which should be treated as potentially personally identifiable information (PII) for privacy and regulatory reasons (GDPR, CCPA, etc). . The names of the cookies are:

* wp_usrtk_user_from_{COOKIEHASH}
* wp_usrtk_user_switched_{COOKIEHASH}

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

= 1.1.4 =
* Fixed and issue that prevents user switching if there is no access to wp-login.php

= 1.1.3 =
* Fixed and issue that prevents user to be ordered by Last Login

= 1.1.2 =
* Fixed an issue that allows disabling the first user created.
* Fixed an issue that disabled the user when editing their own profile.

= 1.1 =
* Switch to any user account from the Users screen and be able to switch back to your own user later.
* Fix: First created user cannot be disabled.

= 1.0.4 =
* Same user displays ON/OFF in login status in User List
* Same user cannot disable his own login status in User List

= 1.0.3 =
* Stability and security improvements

= 1.0.2 =
* Ability to migrate last login data from When Last Login and Ultimate Member plugins

= 1.0.1 =
* Downgraded to support PHP 7.3

= 1.0.0 =
* Initial Release

== Upgrade Notice ==

= 1.1.4 =
* Fixed and issue that prevents user switching if there is no access to wp-login.php

= 1.1.3 =
* Fixed and issue that prevents user to be ordered by Last Login

= 1.1.2 =
* Fixed an issue that allows disabling the first user created.
* Fixed an issue that disabled the user when editing their own profile.

= 1.1 =
Switch to any user account from the Users screen and be able to switch back to your own user later.

= 1.0.4 =
Improve user login status and toggle in user list

= 1.0.3 =
Improve security and performance

= 1.0.2 =
Support for switching from plugins like When Last Login and Ultimate Member

= 1.0.1 =
Downgraded to support PHP 7.3

= 1.0.0 =
Last Login, Registration Date and disable user login without deleting him.