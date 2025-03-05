<?php
/**
 * Verwaltung von Bildern
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-07-31
 */

// start session -----------------------------
session_start();
// base includes -----------------------------
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
// define actual system position -------------
$_SESSION['wspvars']['mgroup'] = 6;
$_SESSION['wspvars']['lockstat'] = 'documents';
$_SESSION['wspvars']['pagedesc'] = array('far fa-copy',returnIntLang('menu files'),returnIntLang('menu files docs'));
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['menuposition'] = 'download'; // ?? is dieser Eintrag richtig?
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
$mediafolder = "download";
$mediadesc = returnIntLang('str files');
// define upload based vars
$_SESSION['wspvars']['upload']['basetarget'] = '/media/download/';
$_SESSION['wspvars']['upload']['hiddendir'] = array('thumbs','preview');
$_SESSION['wspvars']['upload']['extensions'] = ''; // leer, da jegliche Dateien hochgeladen werden können
$_SESSION['wspvars']['upload']['scale'] = false;
$_SESSION['wspvars']['upload']['thumbs'] = true;
$_SESSION['wspvars']['upload']['preview'] = true;

include ("filemanagement.php");
?>