<?php
/**
 * Verwaltung von Bildern
 * @author s.haendler@covi.de
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
 * @since 3.1
 * @version 6.11
 * @lastchange 2022-10-16
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* first includes ---------------------------- */
require ("./data/include/wsplang.inc.php");
require ("./data/include/dbaccess.inc.php");
if (file_exists("./data/include/ftpaccess.inc.php")) require ("./data/include/ftpaccess.inc.php");
require ("./data/include/funcs.inc.php");
require ("./data/include/filesystemfuncs.inc.php");
/* checkParamVar ----------------------------- */
$op = checkParamVar('op', '');
$extern = checkParamVar('extern', 0);
/* define actual system position ------------- */
$_SESSION['wspvars']['mgroup'] = 4;
$_SESSION['wspvars']['lockstat'] = 'design';
$_SESSION['wspvars']['fposition'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = true;
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
$mediafolder = "screen";
$mediadesc = "Bilder";
// define upload based vars
$_SESSION['wspvars']['upload']['basetarget'] = 'screen';
$_SESSION['wspvars']['upload']['extensions'] = 'jpg;png;gif';

$_SESSION['wspvars']['upload']['scale'] = true;
$_SESSION['wspvars']['upload']['thumbs'] = true;
$_SESSION['wspvars']['upload']['preview'] = false;

// workaround for filemanagement rights request
$_SESSION['wspvars']['rights']['screenfolder'] = 1;

// define required folders to handle that page
$requiredstructure = array("/media","/media/screen","/media/screen/thumbs");

include ($_SERVER['DOCUMENT_ROOT']."/".$wspvars['wspbasedir']."/filemanagement.php");

// EOF ?>