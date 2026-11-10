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
$_SESSION['wspvars']['sitetitle'] = "WSP 6.26.1";
// check for installed directory
$tmpbase = basename(dirname(dirname(__DIR__)));
$_SESSION['wspvars']['wspbasedir'] = ($tmpbase!='wsp') ? $tmpbase : 'wsp';
$_SESSION['wspvars']['wspversion'] = "6.26.11#5";
$_SESSION['wspvars']['wsplongname'] = "WebSitePreview 6.26.1";
$_SESSION['wspvars']['wspshortname'] = "WSP 6.26.1";
$_SESSION['wspvars']['wspurl'] = "https://www.wsp3.de";
$_SESSION['wspvars']['updatekey'] = 'ahfsa9r278rtSNDKJaou387zrfsdfchizqrw';
$_SESSION['wspvars']['updateuri'] = 'https://update.wsp-server.info/wsp';
$_SESSION['wspvars']['updatefiles'] = 'https://update.wsp-server.info/wsp';
$_SESSION['wspvars']['updatedatabase'] = 'https://update.wsp-server.info/updater';
$_SESSION['wspvars']['reportingmail'] = 'cmserror@covi.de';
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
//
// rechtemoeglichkeiten, die die rechtebereiche annehmen koennen
// yes|no|auswahl, mit yes = 1, no = 0 und auswahl = 2
//
$_SESSION['wspvars']['rightpossibilities'] = array(1, 1, 2, 1, 2, 1, 2);
//
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
// directories to accept writing while installing modules
$_SESSION['wspvars']['allowdir'] = array("data/menu","data/modules","media/fonts","media/video","media/images","media/screen","media/layout","media/download","[wsp]/data/interpreter","[wsp]/data/lang","[wsp]/data/modsetup","[wsp]/data/modules","[wsp]/media/javascript","[wsp]/media/screen");

$_SESSION['wspvars']['forbiddentables'] = array("reference");

$_SESSION['wspvars']['tables']=array(
	'content',
	'content_backup',
	'content_parsefile',
	'globalcontent',
	'interpreter',
	'javascript',
	'mediadesc',
	'menu',
	'modules',
	'options',
	'pageproperties',
	'restrictions',
	'rssdata',
	'rssentries',
	'r_temp_jscript',
	'r_temp_rss',
	'r_temp_styles',
	'security',
	'securitylog',
	'selfvars',
	'siteproperties',
	'site_tracking',
	'stylesheets',
	'templates',
	'templates_menu',
	'tracking_files',
	'tracking_info',
	'usercontrol',
	'wspaccess',
	'wspmedia',
	'wspmenu',
	'wspmsg',
	'wspplugins',
	'wspproperties',
	'wspqueue',
	'wsprights'
	);

// define usevar
foreach ([$_SESSION, $_COOKIE, $_POST, $_REQUEST] as $Source) {
    if (($Value = trim($Source['usevar'] ?? '')) !== '' && empty(trim((string) $_SESSION['wspvars']['usevar']))) {
        $_SESSION['wspvars']['usevar'] = $Value;
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
