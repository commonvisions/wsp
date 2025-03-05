<?php
/**
 * ...
 * @author stefan@covi.de
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 7.0
 * @lastchange 2019-01-22
 */
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!=''):
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/globalvars.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/wsplang.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/dbaccess.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/ftpaccess.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/funcs.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/filesystemfuncs.inc.php";
include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php";
include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php";

if (isset($_POST['conhost']) && trim($_POST['conhost'])!='' && isset($_POST['conlocation']) && trim($_POST['conlocation'])!='' && isset($_POST['conuser']) && trim($_POST['conuser'])!='' && isset($_POST['conpass']) && trim($_POST['conpass'])!=''):
	/*
    $ftpAttempt = 2;
	$counterOld = $ftpAttempt;
	$ftp = false;
	$tmpfile = str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/ftptest.txt");
	file_put_contents($tmpfile, time());
	while (!$ftp && ($ftpAttempt > 0)):
		if ($counterOld != $ftpAttempt):
			$counterOld = $ftpAttempt;
			sleep(1);
		endif;
		$ftp = @ftp_connect($_POST['conhost'], $_POST['conport']);
		$ftpAttempt--;
	endwhile;
	if ($ftp === false):
		echo 1;
	elseif (!(@ftp_login($ftp, trim($_POST['conuser']), trim($_POST['conpass'])))):
		echo 2;
	elseif (@ftp_size($ftp, str_replace("//","/",$_POST['conlocation']."/data/include/global.inc.php"))<0):
		echo 3;
	elseif (!(ftp_put($ftp, str_replace("//","/",$_POST['conlocation']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/ftpdone.txt"), $tmpfile, FTP_BINARY))):
		echo 4;
	else:
		unlink($tmpfile);
		ftp_delete($ftp, str_replace("//","/",$_POST['conlocation']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/ftpdone.txt"));
		ftp_close($ftp);
		echo 99;
	endif;
    */

    // deprecated since 7.0
    echo 1;

endif;

endif;

// EOF ?>