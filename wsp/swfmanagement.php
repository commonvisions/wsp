<?php
/**
 * Verwaltung von Mediadateien
 * @author s.haendler@covi.de
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
 * @since 3.1
 * @version 6.8
 * @lastchange 2019-01-23
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
$_SESSION['wspvars']['mgroup'] = 6;
$_SESSION['wspvars']['lockstat'] = 'flash';
$_SESSION['wspvars']['fposition'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
/* second includes --------------------------- */
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/checkuser.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
$mediafolder = "flash";
$mediadesc = "Flash & Video";
// define upload based vars
$_SESSION['wspvars']['upload']['basetarget'] = 'flash';
$_SESSION['wspvars']['upload']['extensions'] = 'swf;fla;flv;mp4;mp3;ogg';
$_SESSION['wspvars']['upload']['scale'] = false;
$_SESSION['wspvars']['upload']['thumbs'] = false;
$_SESSION['wspvars']['upload']['preview'] = false;

// define required folders to handle that page
$requiredstructure = array("media","/media/video","/media/video/flash");

include ("filemanagement.php");

// EOF ?>