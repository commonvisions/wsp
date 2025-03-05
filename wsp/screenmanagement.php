<?php
/**
 * Verwaltung von Bildern
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-07-30
 */

// start session -----------------------------
session_start();
// base includes -----------------------------
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
// define actual system position -------------
$_SESSION['wspvars']['mgroup'] = 4;
$_SESSION['wspvars']['lockstat'] = 'design';
$_SESSION['wspvars']['pagedesc'] = array('fa fa-paint-brush',returnIntLang('menu design'),returnIntLang('menu design media'));
$_SESSION['wspvars']['fposition'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['addpagecss'] = array();
$_SESSION['wspvars']['addpagejs'] = array();
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
$mediafolder = "screen";
$mediadesc = returnIntLang('str images');
// define upload based vars
$_SESSION['wspvars']['upload']['basetarget'] = '/media/screen/';
$_SESSION['wspvars']['upload']['hiddendir'] = array('thumbs');
$_SESSION['wspvars']['upload']['extensions'] = 'jpg;jpeg;png;gif';
$_SESSION['wspvars']['upload']['scale'] = true;
$_SESSION['wspvars']['upload']['thumbs'] = true;
$_SESSION['wspvars']['upload']['preview'] = false;

include ("filemanagement.php");
?>