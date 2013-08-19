<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'monarch');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'password');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'i[S D2c(p!7/)|r+&V#ZRr[MU.b&Z(^<FR}PEAl]EO+xwJyFr/ko%>_M>-~8|{|A');
define('SECURE_AUTH_KEY',  'I+A($}5k1`!8RelWC/0yXe,<5H)P$-Hx[H@*yD+5o/b6c8V7)iQ-C@o=tR@E&V-o');
define('LOGGED_IN_KEY',    'L_|H_-f~G{DYe;%[VSb>f$o D/%p  J33CjHR1:Ej]A6h?K,r6}cONOhVNG@|R]7');
define('NONCE_KEY',        '.H{vo>G$oFWBiVM9DoYhSuJhv 6!B]h}Pw>SmC!UEEKIt3bu.rx0kWkK1/O_Dl74');
define('AUTH_SALT',        '#m.-lB12t|D{N+ShBiz0{]9PWao?a46si!3k x++-lpGV3H?5^pp 7QcO|`Zt)XI');
define('SECURE_AUTH_SALT', '~yiGM:(+~;jf5:{KzvW6~k|XAZc-gW>E#>Nm-d.jM7-a<a^T+p[~|#(=(v2oCHFs');
define('LOGGED_IN_SALT',   'q|j+IPyxm*iDz;8wBM|/-91uT97PW}iZt9T5X}!{#E8}$z>GPC--U-rdBfUyZ*Ad');
define('NONCE_SALT',       'v@{y*3-]_jEYh,0g)P!DDI%@B9zdh4gS@ql)DQre=h?uQYZbd%>Qnc:JNJ,{vQiX');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
