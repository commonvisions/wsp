<?php
/**
 * ...
 * @author s.haendler@covi.de
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 6.8
 * @lastchange 2019-01-22
 */

session_start(); 
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='' && isset($_SESSION['wspvars'])):
include $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/globalvars.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/wsplang.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/dbaccess.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/ftpaccess.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/funcs.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/filesystemfuncs.inc.php";
include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php";
include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php";

if (isset($_REQUEST['newfilename']) && trim($_REQUEST['newfilename'])!='' && isset($_REQUEST['orgfilename']) && trim($_REQUEST['orgfilename'])!=''):
	if (trim($_REQUEST['newfilename'])!=trim($_REQUEST['orgfilename'])):
		$newfilename = strtolower(removeSpecialChar(trim($_REQUEST['newfilename'])));
		$f_sql = "SELECT `mid` FROM `wspmedia` WHERE `mediafolder` = '".escapeSQL(trim($_REQUEST['filefolder']))."' AND `filename` = '".escapeSQL($newfilename)."'";
		$f_res = doSQL($f_sql);
		if ($f_res['num']>0):
			$newfilename = $newfilename."-".time();
		endif;
		echo $newfilename;
	endif;
endif;
endif;

// EOF ?>