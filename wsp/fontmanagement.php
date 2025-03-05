<?php
/**
 * Verwaltung von Bildern
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-10-02
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* define actual system position ------------- */
$_SESSION['wspvars']['mgroup'] = 4;
$_SESSION['wspvars']['lockstat'] = 'fonts';
$_SESSION['wspvars']['pagedesc'] = array('fa fa-paint-brush',returnIntLang('menu design'),returnIntLang('menu design fonts'));
$_SESSION['wspvars']['fposition'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
$mediafolder = "fonts";
$mediadesc = "Schriftartdateien";
// define upload based vars
$_SESSION['wspvars']['upload']['basetarget'] = '/media/fonts/';
$_SESSION['wspvars']['upload']['hiddendir'] = array('thumbs','preview');
$_SESSION['wspvars']['upload']['extensions'] = 'woff;otf;ps;eot;svg;ttf;woff2';
$_SESSION['wspvars']['upload']['scale'] = false;
$_SESSION['wspvars']['upload']['thumbs'] = false;
$_SESSION['wspvars']['upload']['preview'] = false;

include ("filemanagement.php");
?>