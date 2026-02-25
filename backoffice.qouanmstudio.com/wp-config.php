<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'qounam_db' );

/** Database username */
define( 'DB_USER', 'qounam_user' );

/** Database password */
define( 'DB_PASSWORD', 'vkQMFbfP8hBVm2v' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

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
define( 'AUTH_KEY',         '3teSs4EzEXq4uWsRa|PKz)M Wg:h(t?|,m-j[0@l8*9Hj9ps6,2k0nK2t`;,q)Ez' );
define( 'SECURE_AUTH_KEY',  'V%h>HSeR9-><SetnAXLGG~~=<(6y-Ge?/=gn&:m{Gkk`9}7!YH1U[3M1%$T7(:HB' );
define( 'LOGGED_IN_KEY',    '?GV^34!S0,qs=Lk&sdTycQi,r$B[ooP?O&QMVHG=EgmcQ^Kk+sKfF}.[#0mE`6N ' );
define( 'NONCE_KEY',        '^iHte$O/v]Z([tRW^IIyP[(&I:h)1GvMDfp9SlT-* >N&`CxW903oQCpwT@>,3@P' );
define( 'AUTH_SALT',        '9^:|t/2<w{6(0NHg(-{n<h1???[4pW%UN[6I8tc<IeU4mosXMHW`h`yio7E4a&~+' );
define( 'SECURE_AUTH_SALT', '-KEz_#LtxX}ieO.P9aFeufIYIa<=SZviUgLGgu+8>Dnc7Epm,jEDUreIox^3ji<G' );
define( 'LOGGED_IN_SALT',   'gtQ|`VSHJW2f$?$LbpI[[S+Rm|9cb7`Ay}4|mx/{Z(f3H55.1(QAlu j0J;:t0b~' );
define( 'NONCE_SALT',       '5+o%D4*5G=`CDn=;:UYt/?;jyR1awtygI8^(fdc]8,FtCD/h5!;W@Nw;v,(u)avG' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
