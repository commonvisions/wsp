<?php
/**
 * @author stefan@covi.de
 * @since 3.1
 * @version 6.11
 */

/*
@header_remove('X-Powered-By');
@header('X-Content-Type-Options: nosniff');
@header('X-Frame-Options: ALLOWALL');
@header('Referrer-Policy: strict-origin-when-cross-origin');
@header('Content-Security-Policy: default-src * \'unsafe-inline\';');
@header('Permissions-Policy: accelerometer=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()');
*/

/* define vars */
global $indexlogin;
global $docookielogin;
global $loginindex;
global $styleinclude;

/* wsp infovars */
if (!defined('WSPVERSION')) define('WSPVERSION', '6.26.11');
if (!defined('WSPLONGNAME')) define('WSPLONGNAME', 'WebSitePreview ' . WSPVERSION);
if (!defined('WSPSHORTNAME')) define('WSPSHORTNAME', 'WSP ' . WSPVERSION);

/* check for installed directory and define basedir */
$tmpbase = basename(dirname(dirname(__DIR__)));
if (!defined('WSPBASEDIR')) define('WSPBASEDIR', ($tmpbase!='wsp') ? $tmpbase : 'wsp');
$_SESSION['wspvars']['wspbasedir'] = WSPBASEDIR;

$_SESSION['wspvars']['wspurl'] = "https://www.wsp3.de";
//
// moegliche rechtebereiche, die verwaltet werden koennen
//
$_SESSION['wspvars']['rightabilities'] = array(
	"siteprops", // einstellungen zu meta-tags etc
	"sitestructure", // menuestruktur
	"contents", // inhalte
	"rss",
	"publisher", // publisher
	"design", // design
	"menufacts", // menuestruktur
	"imagesfolder",
	"downloadfolder",
	"flashfolder",
	"videofolder",
	"fontsfolder",
);
// possible modes: 
// 1 = yes/no create and edit
// 2 = choose from structure and create and edit
// 3 = yes/no edit existing facts
// 4 = extends 2 - choose from structure and edit existing
// 5 = 
// 
$_SESSION['wspvars']['rightabilityarray'] = array(
	'siteprops' => array( 'mode' => 1, 'desc' => array( 'de' => 'Siteeinstellungen' ) ), // meta-tags, etc.
	'sitestructure' => array( 'mode' => 7, 'desc' => array( 'de' => 'Sitestruktur' ) ), // site structure
	'contents' => array( 'mode' => 15, 'desc' => array( 'de' => 'Inhalte' ) ), // contents
	'publisher' => array( 'mode' => 12, 'desc' => array( 'de' => 'Publisher' ) ), // publisher
	'rss' => array( 'mode' => 1, 'desc' => array( 'de' => 'RSS-Editor' ) ), // rss-feature
	'design' => array( 'mode' => 1, 'desc' => 'rights design'), // css, screen images, templates, variables
	'imagesfolder' => array( 'mode' => 6, 'basefolder' => '/media/images/', 'desc' => 'rights filesystem images'),
	'downloadfolder' => array( 'mode' => 6, 'basefolder' => '/media/download/', 'desc' => 'rights filesystem docs'),
	'flashfolder' => array( 'mode' => 6, 'basefolder' => '/media/flash/' , 'desc' => 'rights filesystem swf'),
	'videofolder' => array( 'mode' => 6, 'basefolder' => '/media/video/' , 'desc' => 'rights filesystem video'),
	'fontsfolder' => array( 'mode' => 6, 'basefolder' => '/media/fonts/' , 'desc' => 'rights filesystem fonts')
);

$usevar = '';
foreach ([$_SESSION, $_COOKIE, $_POST, $_REQUEST] as $Source) {
    if (($Value = trim($Source['usevar'] ?? '')) !== '') {
        $usevar = $Value;
        break;
    }
}

/* handle fake-subdomains used (for EXAMPLE) at strato hosting services */
$buildfilename = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SERVER['SCRIPT_NAME']));
if ($buildfilename!=$_SERVER['SCRIPT_FILENAME']):
	$tmpwspbaseadd = str_replace($_SERVER['DOCUMENT_ROOT'], "", $_SERVER['SCRIPT_FILENAME']);
	$tmpwspbaseadd = str_replace($_SERVER['SCRIPT_NAME'], "", $tmpwspbaseadd);
    $_SESSION['wspvars']['wspbasediradd'] = str_replace("//", "/", $tmpwspbaseadd);
else:
    $_SESSION['wspvars']['wspbasediradd'] = '';
endif;

$_SESSION['wspvars']['plugin'] = "";

// replacing deprecated mysql_query()
if (!(function_exists('mysql_connect'))) {
    function mysql_connect($host, $user, $pass) {
        if (!(defined('DB_HOST'))) { define('DB_HOST', $host ); }
        if (!(defined('DB_USER'))) { define('DB_USER', $user ); }
        if (!(defined('DB_PASS'))) { define('DB_PASS', $pass ); }
        return array('host' => $host, 'user' => $user, 'pass' => $pass);
	}
}

if (!(function_exists('mysql_select_db'))) {
    function mysql_select_db($db, $connect) {
        if (!(defined('DB_HOST'))) { define('DB_HOST', $connect['host'] ); }
        if (!(defined('DB_NAME'))) { define('DB_NAME', $db ); }
        if (!(defined('DB_USER'))) { define('DB_USER', $connect['user'] ); }
        if (!(defined('DB_PASS'))) { define('DB_PASS', $connect['pass'] ); }
        $_SESSION['wspvars']['db'] = new mysqli($connect['host'],$connect['user'],$connect['pass'],$db);
        return $_SESSION['wspvars']['db'];
    }
}
