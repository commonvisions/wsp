<?php
/**
* @author stefan@covi.de
* @copyright (c) 2019, Common Visions Media.Agentur (COVI)
* @since 3.1
* @version 6.9.2
* @lastchange 2021-01-20
*/

if (!function_exists('apache_response_headers')) {
    function apache_response_headers () {
        $arh = array();
        flush();
        $headers = headers_list();
        foreach ($headers as $header) {
            $header = explode(":", $header);
            $arh[array_shift($header)] = trim(implode(":", $header));
        }
        return $arh;
    }
}
// manipulate headers
@header_remove('X-Powered-By');
// if ($_SERVER['HTTPS']=='on' && !array_key_exists('X-Content-Type-Options', apache_response_headers ())) { @header('Strict-Transport-Security: max-age=31536000; includeSubDomains'); }
if (!array_key_exists('X-Content-Type-Options', apache_response_headers ())) { @header('X-Content-Type-Options: nosniff'); }
if (!array_key_exists('X-XSS-Protection', apache_response_headers ())) { @header('X-XSS-Protection: 1; mode=block'); }
if (!array_key_exists('X-Frame-Options', apache_response_headers ())) { @header('X-Frame-Options: ALLOWALL'); }
if (!array_key_exists('Referrer-Policy', apache_response_headers ())) { @header('Referrer-Policy: strict-origin-when-cross-origin'); }
// if (!array_key_exists('Content-Security-Policy', apache_response_headers ())) { @header('Content-Security-Policy: default-src * \'unsafe-inline\';'); }
if (!array_key_exists('Permissions-Policy', apache_response_headers ())) { @header('Permissions-Policy: accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()'); }

if (phpversion()>5): date_default_timezone_set('Europe/Berlin'); endif;
// get information about root directory and setup param
$buildsysfile = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SERVER['SCRIPT_NAME']));
if ($buildsysfile!=$_SERVER['SCRIPT_FILENAME']):
	define('DOCUMENT_ROOT', str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".str_replace($_SERVER['SCRIPT_NAME'], "", str_replace($_SERVER['DOCUMENT_ROOT'], "", $_SERVER['SCRIPT_FILENAME']))));
else:
	define('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);
endif;
// define
$_SESSION['wsppage']['usesessionvar'] = false;
$_SESSION['wsppage']['usecookies'] = false;
$_SESSION['wsppage']['trackuser'] = false;
// include page funcs
if (is_file(DOCUMENT_ROOT.'/data/include/funcs.inc.php')): include DOCUMENT_ROOT.'/data/include/funcs.inc.php'; endif;
// load db-access-data
if (is_file(DOCUMENT_ROOT.'/data/include/dbaccess.inc.php')): include DOCUMENT_ROOT.'/data/include/dbaccess.inc.php'; endif;
// create db connect
if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
    if (function_exists('mysqli_get_client_info')) {
        $_SESSION['wspvars']['db'] = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);
    } else {
        $_SESSION['wspvars']['db'] = mysql_select_db(DB_NAME, mysql_connect(DB_HOST,DB_USER,DB_PASS));
    }
} else {
    // db connect is not set up properly
    $_SESSION['wspvars']['db'] = false;
    echo "<!-- DBCON NOT SETUP PROPERLY -->";
}
// visitor control
if (is_file(DOCUMENT_ROOT.'/data/include/checkuser.inc.php')): include DOCUMENT_ROOT.'/data/include/checkuser.inc.php'; endif;
// EOF ?>