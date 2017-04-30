=== Restrict Content Pro - CSV User Import ===
Author URI: http://pippinsplugins.com
Contributors: mordauk, chriscoyier
Author: Pippin Williamson
Donate link: http://pippinsplugins.com/support-the-site
Tags: Restrict Content Pro, premium content, memberships, subscriptions, csv, user import, csv to users, Pippin Williamson, Pippin's Plugins
Requires at least 3.3
Tested up to 4.3
Stable tag: 1.1.3

A plugin for importing a CSV of user accounts into Restrict Content Pro

== Description ==

This plugin is an add-on for [Restrict Content Pro](http://pippinsplugins.com/restrict-content-pro-premium-content-plugin/), a complete subscription and premium content manager plugin for WordPress.

Once activated, this plugin will provide a new menu item under the Restrict menu called "CSV Import".

In order to import correctly, you must preformat your CSV to match the requirements of the plugin. You CSV should have the following columns:

**user_email, first_name, last_name, user_login**

A sample CSV is included in the plugin's folder that you can use for reference.

The user's email address is the only column that requires a value. If user_login is left blank, the user's email address will be used for their login name.

When importing, every user has their password auto generated, so each user will need to go through the recover a lost password process.

**Note:** this plugin should be able to handle the importation of a few thousands users at a time, but if you have more than 5,000 (or if you are having problems with server timeouts), you will want to consider creating a custom shell script to import the users in batches.

== Installation ==

1. Upload rcp-user-import to wp-content/plugins
2. Click "Activate" in the WordPress plugins menu
3. Go to Restrict > CSV Import and follow directions

 == Screenshots ==

1. CSV user import page.

== Changelog ==

= 1.1.3 =

* Fixed an issue with updating existing members

= 1.1.2 =

* User update_user_meta() instead of add_user_meta()

= 1.1.1 =

* Properly esc the select element

= 1.1 =

* Improved the CSV import process to make it far more reliable.

= 1.0.3 =

* Added support for updating existing user accounts by passing the user ID to an "ID" column

= 1.0.2 =

* Added a new rcp_user_import_user_added hook that runs after each user is created

= 1.0.1 =

* Added support for subscription user roles

= 1.0 =

* Initial Release
