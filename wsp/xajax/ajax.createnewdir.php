<?php
/**
 * creating media directories
 * @author stefan@covi.de
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 7.0
 * @lastchange 2019-01-27
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

if (isset($_POST) && isset($_POST['subdirto']) && isset($_POST['newdirname'])):
	$subdirto = str_replace("//", "/", str_replace("//", "/", str_replace(".", "", $_POST['subdirto'])));
	$newdirname = str_replace("//", "/", str_replace("//", "/", $subdirto."/".urltext(str_replace(".", "", $_POST['newdirname']))));
	$mediatype = trim($_POST['mediatype']);
endif;

if($newdirname!=""):
	$ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport']))); if ($ftp!==false) {if (!ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass'])) { $ftp = false; }} if (isset($_SESSION['wspvars']['ftppasv']) && $ftp!==false) { ftp_pasv($ftp, $_SESSION['wspvars']['ftppasv']); }
	if ($ftp!==false) {
		if (ftp_mkdir($ftp, str_replace("//", "/", str_replace("//", "/", $_SESSION['wspvars']['ftpbasedir']."/".$newdirname)))):
			$sql = "INSERT INTO `wspmedia` SET `mediatype` = '".$mediatype."', `mediafolder` = '".str_replace("//", "/", str_replace("//", "/", $newdirname."/"))."', `filefolder` = '".str_replace("//", "/", str_replace("//", "/", trim("/".str_replace("/media/".$mediatype."/", "/", $newdirname)."/")))."', lastchange = ".time();
			doSQL($sql);
			echo true;
		else:
			echo false;
		endif;
    ftp_close($ftp);
	}
endif;

endif;

// EOF ?>