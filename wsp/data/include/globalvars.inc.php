<?php
/**
 * global WSP variables 
 * @author cms@covi.de
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
$wspvars['sitetitle'] = "WSP 6.25.8";
$wspvars['wspbasedir'] = "wsp";
$tmpbase = __DIR__; $tmpbase = trim(substr($tmpbase,0,-13)); $tmpbase = explode("/", $tmpbase); $tmpbase = trim(@array_pop($tmpbase));
if ($tmpbase!=$wspvars['wspbasedir']): $wspvars['wspbasedir'] = str_replace("/", "", $tmpbase); endif;
$wspvars['wspversion'] = "6.25.8";
$wspvars['wsplongname'] = "WebSitePreview 6.25.8";
$wspvars['wspshortname'] = "WSP 6.25.8";
$wspvars['wspurl'] = "https://www.wsp3.de";
$wspvars['updatekey'] = 'ahfsa9r278rtSNDKJaou387zrfsdfchizqrw';
$wspvars['updateuri'] = 'https://update.wsp-server.info/wsp';
$wspvars['updatefiles'] = 'https://update.wsp-server.info/wsp';
$wspvars['updatedatabase'] = 'https://update.wsp-server.info/updater';
$wspvars['reportingmail'] = 'cmserror@covi.de';
//
// moegliche rechtebereiche, die verwaltet werden koennen
//
$wspvars['rightabilities'] = array(
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
$wspvars['rightpossibilities'] = array(1, 1, 2, 1, 2, 1, 2);
//
// possible modes: 
// 1 = yes/no create and edit
// 2 = choose from structure and create and edit
// 3 = yes/no edit existing facts
// 4 = extends 2 - choose from structure and edit existing
// 5 = 
// 
$wspvars['rightabilityarray'] = array(
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
$wspvars['allowdir'] = array("data/menu","data/modules","media/fonts","media/video","media/images","media/screen","media/layout","media/download","[wsp]/data/interpreter","[wsp]/data/lang","[wsp]/data/modsetup","[wsp]/data/modules","[wsp]/media/javascript","[wsp]/media/screen");

$wspvars['forbiddentables'] = array("reference");

$wspvars['files'] = array(
	'[wsp]/analytics.php',
	'[wsp]/cleanup.php',
	// '[wsp]/contentclean.php',
	// '[wsp]/contentconvert.php',
	// '[wsp]/contentdump.php',
	'[wsp]/contentedit.php',
	'[wsp]/contentstructure.php',
	'[wsp]/designedit.php',
	'[wsp]/dev.php',
	'[wsp]/documentmanagement.php',
	'[wsp]/editcon.php',
	'[wsp]/editorprefs.php',
	// '[wsp]/fbtools.php',
	'[wsp]/filemanagement.php',
	'[wsp]/fontmanagement.php',
	// '[wsp]/getcontents.php',
	'[wsp]/globalcontent.php',
	'[wsp]/globalcontentedit.php',
	// '[wsp]/googletools.php',
	'[wsp]/headerprefs.php',
	// '[wsp]/iconset.php',
	'[wsp]/imagemanagement.php',
	'[wsp]/index.php',
	'[wsp]/languagetools.php',
	'[wsp]/logout.php',
	'[wsp]/mediadetails.php',
	'[wsp]/mediadownload.php',
	// '[wsp]/mediafile.php',
	// '[wsp]/mediamanagement.php',
	// '[wsp]/mediaupload.php',
	// '[wsp]/mediaviewer.php',
	'[wsp]/menuedit.php',
	'[wsp]/menueditdetails.php',
	'[wsp]/menutemplate.php',
	'[wsp]/modgoto.php',
	'[wsp]/modinstall.php',
	'[wsp]/modinterpreter.php',
	'[wsp]/modules.php',
	// '[wsp]/pageitems.php',
	// '[wsp]/preview.php',
	'[wsp]/privacy.php',
	'[wsp]/publisher.php',
	'[wsp]/publishqueue.php',
	'[wsp]/rssedit.php',
	'[wsp]/rssentry.php',
	'[wsp]/rssfeed.php',
	'[wsp]/rssparser.php',
	'[wsp]/screenmanagement.php',
	'[wsp]/scriptedit.php',
	'[wsp]/selfvarsedit.php',
	'[wsp]/semanagement.php',
	'[wsp]/showpreview.php',
	// '[wsp]/sitelang.php',
	'[wsp]/siteprefs.php',
	'[wsp]/sites.php',
	// '[wsp]/sitestats.php',
	// '[wsp]/stats.php',
	'[wsp]/swfmanagement.php',
	'[wsp]/system.php',
	'[wsp]/templates.php',
	'[wsp]/trash.php',
	'[wsp]/uploadmedia.php',
	'[wsp]/useredit.php',
	'[wsp]/userhistory.php',
	'[wsp]/usermanagement.php',
	'[wsp]/usernotice.php',
	'[wsp]/usershow.php',
	'[wsp]/videomanagement.php',

	'[wsp]/data/include/autocomplete.inc.php',
	'[wsp]/data/include/checkuser.inc.php',
	'[wsp]/data/include/clsinterpreter.inc.php',
	'[wsp]/data/include/clssetup.inc.php',
	'[wsp]/data/include/cssparser.inc.php',
	'[wsp]/data/include/errorhandler.inc.php',
	'[wsp]/data/include/export.inc.php',
	'[wsp]/data/include/fileparser.inc.php',
	'[wsp]/data/include/filesystemfuncs.inc.php',
	'[wsp]/data/include/footer.inc.php',
	'[wsp]/data/include/footerempty.inc.php',
	'[wsp]/data/include/funcs.inc.php',
	'[wsp]/data/include/globalvars.inc.php',
	'[wsp]/data/include/header.inc.php',
	'[wsp]/data/include/headerempty.inc.php',
	'[wsp]/data/include/import.inc.php',
	'[wsp]/data/include/jsparser.inc.php',
	// '[wsp]/data/include/lib.xml.inc.php',
	'[wsp]/data/include/lib5.xml.inc.php',
	'[wsp]/data/include/mediafuncs.inc.php',
	'[wsp]/data/include/menuparser.inc.php',
	'[wsp]/data/include/modmenu.inc.php',
	'[wsp]/data/include/msgheader.inc.php',
	// '[wsp]/data/include/offmenu.inc.php',
	// '[wsp]/data/include/popupheader.inc.php',
	'[wsp]/data/include/rssparser.inc.php',
	'[wsp]/data/include/siteinfo.inc.php',
	'[wsp]/data/include/sysrun.inc.php',
	'[wsp]/data/include/usestat.inc.php',
	'[wsp]/data/include/wspcore.inc.php',
	'[wsp]/data/include/wsplang.inc.php',
	'[wsp]/data/include/wspmenu.inc.php',
	'[wsp]/data/include/blowfish/blowfish.box.php',
	'[wsp]/data/include/blowfish/blowfish.class.php',
	// '[wsp]/data/include/fpdf/fpdf.php',
	// '[wsp]/data/include/fpdf/font/courier.php',
	// '[wsp]/data/include/fpdf/font/helvetica.php',
	// '[wsp]/data/include/fpdf/font/helveticab.php',
	// '[wsp]/data/include/fpdf/font/helveticabi.php',
	// '[wsp]/data/include/fpdf/font/helveticai.php',
	// '[wsp]/data/include/fpdf/font/symbol.php',
	// '[wsp]/data/include/fpdf/font/times.php',
	// '[wsp]/data/include/fpdf/font/timesb.php',
	// '[wsp]/data/include/fpdf/font/timesbi.php',
	// '[wsp]/data/include/fpdf/font/timesi.php',
	// '[wsp]/data/include/fpdf/font/zapfdingbats.php',
	'[wsp]/data/include/googleapi/gapi.class.php',
	'[wsp]/data/include/xtea/xtea.class.php',
	'[wsp]/data/include/peararchive/PEAR.php',
	'[wsp]/data/include/peararchive/PEAR5.php',
	'[wsp]/data/include/peararchive/tar.php',
	
	// '[wsp]/data/parser/framevars.inc.php',
	// '[wsp]/data/parser/menuparser.inc.php',
	// '[wsp]/data/parser/parserfuncs.inc.php',
	// '[wsp]/data/parser/phpvars.inc.php',
	// '[wsp]/data/parser/rssparser.inc.php',
	
	'[wsp]/data/script/basescript.js.php',
	
	'[wsp]/data/script/tinymce/plugins/image/imagelist.json.php',
	'[wsp]/data/script/tinymce/plugins/image/tinyupload.php',
	'[wsp]/data/script/tinymce/plugins/link/classlist.json.php',
	'[wsp]/data/script/tinymce/plugins/link/medialist.json.php',
	'[wsp]/data/script/tinymce/plugins/link/pagelist.json.php',
	'[wsp]/data/script/tinymce/plugins/link/tinyupload.php',
	'[wsp]/data/script/tinymce/plugins/table/tableclasslist.json.php',
    
	'[wsp]/data/lang/de-utf8.inc.php',
	'[wsp]/data/lang/en-utf8.inc.php',
	
	'[wsp]/media/layout/flexible.css.php',
	'[wsp]/media/layout/wsp.css.php',
	'[wsp]/media/layout/print.css.php',
	'[wsp]/media/layout/vertical.css.php',
	'[wsp]/media/layout/horizontal.css.php',
	
	'[wsp]/xajax/ajax.addcontent.php',
	'[wsp]/xajax/ajax.addcontentonpage.php',
	'[wsp]/xajax/ajax.backgroundpublish.php',
	'[wsp]/xajax/ajax.changevisibilitystructure.php',
	'[wsp]/xajax/ajax.checkfornewfilename.php',
	'[wsp]/xajax/ajax.checkfornewshortcut.php',
	'[wsp]/xajax/ajax.checkforpublished.php',
	'[wsp]/xajax/ajax.checkforusedfilename.php',
	'[wsp]/xajax/ajax.checkforusedshortcut.php',
	'[wsp]/xajax/ajax.checksmtpconnection.php',
	'[wsp]/xajax/ajax.checkdbconnection.php',
	'[wsp]/xajax/ajax.checkftpconnection.php',
	'[wsp]/xajax/ajax.checkmediafilename.php',
	'[wsp]/xajax/ajax.clonecontent.php',
	'[wsp]/xajax/ajax.createnewdir.php',
	'[wsp]/xajax/ajax.deletecontent.php',
	'[wsp]/xajax/ajax.deletedir.php',
	'[wsp]/xajax/ajax.deletefile.php',
	'[wsp]/xajax/ajax.deletestructure.php',
	'[wsp]/xajax/ajax.dragdropcontent.php',
	'[wsp]/xajax/ajax.dragdropmediafile.php',
	'[wsp]/xajax/ajax.findcontent.php',
	'[wsp]/xajax/ajax.movestructure.php',
	'[wsp]/xajax/ajax.neststructure.php',
	'[wsp]/xajax/ajax.returnfolderprops.php',
	'[wsp]/xajax/ajax.returnmediafilelist.php',
	'[wsp]/xajax/ajax.returnmediasearch.php',
	'[wsp]/xajax/ajax.setnewfiledesc.php',
	'[wsp]/xajax/ajax.setopenmediafilelist.php',
	'[wsp]/xajax/ajax.setopentab.php',
	'[wsp]/xajax/ajax.setwspsite.php',
	'[wsp]/xajax/ajax.showcontent.php',
	'[wsp]/xajax/ajax.showhidevisibility.php',
	'[wsp]/xajax/ajax.showinnermsg.php',
	'[wsp]/xajax/ajax.showpublisher.php',
	'[wsp]/xajax/ajax.showstructure.php',
	'[wsp]/xajax/ajax.togglecontentview.php',
	'[wsp]/xajax/ajax.updatelogstat.php',
	'[wsp]/xajax/ajax.updatemsg.php',
	'[wsp]/xajax/ajax.updatemsgclose.php',
	
	// '[wsp]/xajax/xajax.basefuncs.php',
	// '[wsp]/xajax/xajax.bildwahl.php',
	// '[wsp]/xajax/xajax.cleanup.php',
	// '[wsp]/xajax/xajax.contentclean.php',
	// '[wsp]/xajax/xajax.contentconvert.php',
	// '[wsp]/xajax/xajax.contentdump.php',
	// '[wsp]/xajax/xajax.contentedit.php',
	// '[wsp]/xajax/xajax.contentmove.php',
	// '[wsp]/xajax/xajax.contentstructure.php',
	// '[wsp]/xajax/xajax.cookie.php',
	// '[wsp]/xajax/xajax.designedit.php',
	// '[wsp]/xajax/xajax.dev.php',
	// '[wsp]/xajax/xajax.editcon.php',
	// '[wsp]/xajax/xajax.editorprefs.php',
	// '[wsp]/xajax/xajax.filemanagement.php',
	// '[wsp]/xajax/xajax.getcontents.php',
	// '[wsp]/xajax/xajax.globcalcontent.php',
	// '[wsp]/xajax/xajax.globcalcontentedit.php',
	// '[wsp]/xajax/xajax.globcalcontentloaddata.php',
	// '[wsp]/xajax/xajax.googletools.php',
	// '[wsp]/xajax/xajax.headerprefs.php',
	// '[wsp]/xajax/xajax.iconset.php',
	// '[wsp]/xajax/xajax.imagemanagement.php',
	// '[wsp]/xajax/xajax.index.php',
	// '[wsp]/xajax/xajax.languagetools.php',
	// '[wsp]/xajax/xajax.mediadetails.php',
	// '[wsp]/xajax/xajax.mediadownload.php',
	// '[wsp]/xajax/xajax.mediafile.php',
	// '[wsp]/xajax/xajax.mediamanagement.php',
	// '[wsp]/xajax/xajax.mediaviewer.php',
	// '[wsp]/xajax/xajax.menuedit.php',
	// '[wsp]/xajax/xajax.menueditdetails.php',
	// '[wsp]/xajax/xajax.menutemplate.php',
	// '[wsp]/xajax/xajax.modinstall.php',
	// '[wsp]/xajax/xajax.modinterpreter.php',
	// '[wsp]/xajax/xajax.modules.php',
	// '[wsp]/xajax/xajax.pageitems.php',
	// '[wsp]/xajax/xajax.preview.php',
	// '[wsp]/xajax/xajax.publisher.php',
	// '[wsp]/xajax/xajax.rssedit.php',
	// '[wsp]/xajax/xajax.rssentry.php',
	// '[wsp]/xajax/xajax.rssfeed.php',
	// '[wsp]/xajax/xajax.rssparser.php',
	// '[wsp]/xajax/xajax.screen.php',
	// '[wsp]/xajax/xajax.screenmanagement.php',
	// '[wsp]/xajax/xajax.scriptedit.php',
	// '[wsp]/xajax/xajax.selfvarsedit.php',
	// '[wsp]/xajax/xajax.semanagement.php',
	// '[wsp]/xajax/xajax.setupbuilder.php',
	// '[wsp]/xajax/xajax.showpreview.php',
	// '[wsp]/xajax/xajax.sitelang.php',
	// '[wsp]/xajax/xajax.siteprefs.php',
	// '[wsp]/xajax/xajax.sitestats.php',
	// '[wsp]/xajax/xajax.stats.php',
	// '[wsp]/xajax/xajax.swfmanagement.php',
	// '[wsp]/xajax/xajax.system.php',
	// '[wsp]/xajax/xajax.templatesedit.php',
	// '[wsp]/xajax/xajax.templateviewer.php',
	// '[wsp]/xajax/xajax.useredit.php',
	// '[wsp]/xajax/xajax.userhistory.php',
	// '[wsp]/xajax/xajax.usermanagement.php',
	// '[wsp]/xajax/xajax.usernotice.php',
	// '[wsp]/xajax/xajax.usershow.php',
	
	// 'data/include/browserdetection.inc.php',
	// 'data/include/checksession.inc.php',
	'data/include/checkuser.inc.php',
	'data/include/funcs.inc.php',
	'data/include/global.inc.php',
	'data/include/doprepareprivacy.inc.php',
	// 'data/include/shopfuncs.inc.php',
	// 'data/include/systeminfo.inc.php',
	// 'data/include/tracking.inc.php'
	);

$wspvars['tables']=array(
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

if (isset($_SESSION) && array_key_exists('usevar', $_SESSION) && trim($_SESSION['usevar'])!=''):
	$usevar = trim($_SESSION['usevar']);
elseif (isset($_COOKIE) && array_key_exists('usevar', $_COOKIE) && trim($_COOKIE['usevar'])!=''):
	$usevar = trim($_COOKIE['usevar']);
elseif (isset($_POST) && array_key_exists('usevar', $_POST) && trim($_POST['usevar'])!=''):
	$usevar = trim($_POST['usevar']);
elseif (isset($_GET) && array_key_exists('usevar', $_GET) && trim($_GET['usevar'])!=''):
	$usevar = trim($_GET['usevar']);
else:
	$usevar = '';
endif;

/* handle fake-subdomains used (for EXAMPLE) at strato hosting services */
$buildfilename = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SERVER['SCRIPT_NAME']));
if ($buildfilename!=$_SERVER['SCRIPT_FILENAME']):
	$tmpwspbaseadd = str_replace($_SERVER['DOCUMENT_ROOT'], "", $_SERVER['SCRIPT_FILENAME']);
	$tmpwspbaseadd = str_replace($_SERVER['SCRIPT_NAME'], "", $tmpwspbaseadd);
	$wspvars['wspbasediradd'] = str_replace("//", "/", $tmpwspbaseadd);
else:
	$wspvars['wspbasediradd'] = '';
endif;

// setup all wspvars to SESSION['wspvars']
if (!(isset($_SESSION['wspvars']))) $_SESSION['wspvars'] = array();
if (is_array($wspvars)):
	foreach ($wspvars AS $wk => $wv):
		$_SESSION['wspvars'][$wk] = $wv;
	endforeach;
endif;

$_SESSION['wspvars']['plugin'] = "";

// replacing deprecated mysql_query()
if (!(function_exists('mysql_connect'))):
function mysql_connect($host, $user, $pass) { 
    if (!(defined('DB_HOST'))) { define('DB_HOST', $host ); }
    if (!(defined('DB_USER'))) { define('DB_USER', $user ); }
    if (!(defined('DB_PASS'))) { define('DB_PASS', $pass ); }
    return array('host' => $host, 'user' => $user, 'pass' => $pass);
	}
endif;

if (!(function_exists('mysql_select_db'))):
function mysql_select_db($db, $connect) { 
    if (!(defined('DB_HOST'))) { define('DB_HOST', $connect['host'] ); }
    if (!(defined('DB_NAME'))) { define('DB_NAME', $db ); }
    if (!(defined('DB_USER'))) { define('DB_USER', $connect['user'] ); }
    if (!(defined('DB_PASS'))) { define('DB_PASS', $connect['pass'] ); }
    $_SESSION['wspvars']['db'] = new mysqli($connect['host'],$connect['user'],$connect['pass'],$db);
    return $_SESSION['wspvars']['db'];
}
endif;

// EOF ?>