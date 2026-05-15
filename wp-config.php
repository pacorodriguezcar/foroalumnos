<?php
define( 'WP_CACHE', true );



/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings ** //
$is_ddev = getenv( 'IS_DDEV_PROJECT' ) === 'true';

if ( $is_ddev ) {
	define( 'DB_NAME',     'db' );
	define( 'DB_USER',     'db' );
	define( 'DB_PASSWORD', 'db' );
	define( 'DB_HOST',     'db' );
	define( 'WP_SITEURL',  'https://foroalumnos.ddev.site' );
	define( 'WP_HOME',     'https://foroalumnos.ddev.site' );
	define( 'WP_DEBUG', true );
	if ( ! defined( 'WP_DEBUG_LOG' ) )     define( 'WP_DEBUG_LOG',     true );
	if ( ! defined( 'WP_DEBUG_DISPLAY' ) ) define( 'WP_DEBUG_DISPLAY', false );
} else {
	define( 'DB_NAME',     getenv('PROD_DB_NAME')     ?: '' );
	define( 'DB_USER',     getenv('PROD_DB_USER')     ?: '' );
	define( 'DB_PASSWORD', getenv('PROD_DB_PASSWORD') ?: '' );
	define( 'DB_HOST',     getenv('PROD_DB_HOST')     ?: 'localhost' );
	define( 'WP_DEBUG',    false );
}

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          '7ELv[AR4_:Xld6__tUDV`]BJpMtWwE-]x*yq^E*G66~{@6O`zGG]Zy<$7p;:9|vS' );
define( 'SECURE_AUTH_KEY',   '&RV{0i!6_L<T0wMs`h/<Dc2w</mrx#@xZ?F@` IUAC2>0K0:qO(z8K?%TQVqN[t5' );
define( 'LOGGED_IN_KEY',     'tyR5a2ma3escbqw>H2m3R[Kh4pc|#Y41E<n[f`Y66>kxQ$i-cQj.Ep>f|@EzDFG]' );
define( 'NONCE_KEY',         'J?>9o-!JNIYN-ib2R}R*TZHuSTqDP]{(CthQ!)hu7!7Ut:1)[D-.^AvpcJT]_|U(' );
define( 'AUTH_SALT',         'cXiR=-g99RLhPI([nx.AP<UzMxkzM0CiTrq+S52a:Ktx^F&@#T|r]il4-C-q_quD' );
define( 'SECURE_AUTH_SALT',  '/deT.1_7RIPx)9jz38#Q!;{@2iOdA[Eu,;]a4lgjTILcA6vgWa`{EQMn46 O^HG_' );
define( 'LOGGED_IN_SALT',    ')o2 >0#%N*V49t6Sk%g E4#eoD$,a|3,^3H2L#*Z&|!fiYpW2.|Dybqlk0|ZCL-|' );
define( 'NONCE_SALT',        'j4tjVbZfy7abUlaD(6yK=).lO(F=J= S[Vgv+Sn@O/=F-MZ|iX^rOBB4)]FLPRwJ' );
define( 'WP_CACHE_KEY_SALT', 'NGmw|s76E,^)kVBdt#icq$pyP:p8G6$Ah5(&3kjoYgmC7_4YE&5YY5|wvob@mn8 ' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'FS_METHOD', 'direct' );
define( 'COOKIEHASH', 'bde2a647843ef179ee1201765bfeb2c0' );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
if ( ! defined( 'WP_DEBUG_LOG' ) )     define( 'WP_DEBUG_LOG',     false );
if ( ! defined( 'WP_DEBUG_DISPLAY' ) ) define( 'WP_DEBUG_DISPLAY', false );
define('WPFC_CLEAR_CACHE_AFTER_THEME_UPDATE', true);
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
