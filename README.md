# WP Engine Environment Switch #
* Contributors: agent-codesmith
* Tags: admin bar, adminbar, admin, developer, development, staging, environment
* Requires at least: 4.0
* Tested up to: 4.6.1
* Stable tag: 0.1.0
* License: GNU General Public License v2 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily switch between staging and production environments on your WP Engine installs.

## Description ##

Easily switch between staging and production environments on your WP Engine installs.
Participate in development through [GitHub](https://github.com/theWEBDOGS/wp-engine-environment-switch)!

## Installation ##

1. Upload the plugin files to the `/wp-content/plugins/wp-engine-environment-switch` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the 'WP Engine Environment Switch' plugin through the 'Plugins' menu in WordPress
	
## Frequently Asked Questions ##

### Can I help to develop this plugin? ###
Just fork it! Submit your pull requests from the [GitHub Repository](https://github.com/theWEBDOGS/wp-engine-environment-switch).

### Is it possible to change the quicklink title? ###
Yes, the menu title is customizable by providing a format string to be applied using [sprintf()](http://php.net/manual/en/function.sprintf.php). Filter the title's format string by configuring the following example somewhere to the functions.php of your theme

```php
add_filter( 'wpees-quicklink-format', 'filter_wpees_format' );

function filter_wpees_format( $format ) {
    $format = 'View this page on $1s%';
    return $format;
}
```

### Can I change the quicklink icon? ###
Yes, you can use the filter ``add_filter( 'wpees-quicklink-icon', 'filter_wpees_icon' );`` and return a valid [Dashicon](https://developer.wordpress.org/resource/dashicons/) class name.

### Can I change the default capability needed to access the quicklinks? ###
Yes, use ``add_filter( 'wpees-quicklink-capability', 'filter_wpees_capability' );`` and return the desired capability.

## Changelog ##

### 0.1.0 ###
* Initial Release
