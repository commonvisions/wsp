<?php
/**
 * WSP conf file
 * Rename 'sample' to 'inc' for usage
 * @since 7.0
 */

// define('DB_HOST', 'localhost' ); // optional
define('DB_NAME', 'database name' );
define('DB_USER', 'database user' );
define('DB_PASS', 'database password' ); 
// define('DB_PORT', 3306 ); // optional

// FTP usage is optional and only required, if direct writing to host is disabled
// define('FTP_HOST', 'localhost' ); // optional
// define('FTP_BASE', '/basedir/' ); // base-directory relative to your ftp root directory you are logging in
// define('FTP_USER', 'ftp user' );
// define('FTP_PASS', 'ftp password' );
// define('FTP_PORT', 21 ); // optional
// define('FTP_SSL', false ); // optional » if true, the system will try to establish a secure connection

// SMTP usage is optional and only required, if mail() isn't working properly
// define('SMTP_HOST', 'smtp host' );
// define('SMTP_USER', 'smtp user' );
// define('SMTP_PASS', 'smtp password' ); 
// define('SMTP_PORT', 587 ); // optional
// define('SMTP_SSL', true ); // optional » if true, SSL encryption is used (as normally required)

define('ROOTPHRASE', 'string with 32 chars' );

// define('WSP_LANG', 'de' ); // optional » base language wsp will startup with
// define('WSP_DIR', 'wsp' ); // optional » only needed if wsp can't detect its directory by itself
// define('WSP_SPACE', 256 ); // optional » size in MB to calculate space left on system 

// define('WSP_DEV', false ); // optional
// define('WSP_MULTI', false ); // optional » if true, 'projects' can access multiple locations from THIS backend 

// define('WSP_UPDKEY', 'ahfsa9r278rtSNDKJaou387zrfsdfchizqrw' ); optional » only for paid system services
// define('WSP_UPDSRV', 'update.wsp-server.info' ); // optional » update-server location for inline updates

// define('BASEMAIL', 'mail address' ); // optional » this mail can get every notification even if no user is set

