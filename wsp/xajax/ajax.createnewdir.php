<?php
/**
 * creating media directories
 * @author s.haendler@covi.de
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 6.8.1
 * @lastchange 2019-01-27
 */
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!=''):
	session_start();
	$wspdir = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".($_SESSION['wspvars']['wspbasediradd'] ?? "")."/".($_SESSION['wspvars']['wspbasedir'] ?? "")));
	include $wspdir.'/data/include/globalvars.inc.php';
	require $wspdir.'/data/include/wsplang.inc.php';
	require $wspdir."/data/include/dbaccess.inc.php";
	if (file_exists($wspdir."/data/include/ftpaccess.inc.php")) require $wspdir."/data/include/ftpaccess.inc.php";
	require $wspdir."/data/include/funcs.inc.php";
	require $wspdir."/data/include/filesystemfuncs.inc.php";
	include $wspdir."/data/include/errorhandler.inc.php";
	include $wspdir."/data/include/siteinfo.inc.php";

	if (isset($_POST) && isset($_POST['subdirto']) && isset($_POST['newdirname'])) {
		$subdirto = str_replace("//", "/", str_replace("//", "/", str_replace(".", "", $_POST['subdirto'])));
		$newdirname = trim(str_replace("//", "/", str_replace("//", "/", $subdirto."/".urltext(str_replace(".", "", $_POST['newdirname'])))));
		$mediatype = trim($_POST['mediatype']);
	}

	if ($newdirname != "") {
		$ftp = doFTP();
		if ($ftp) {
			if (ftp_mkdir($ftp, str_replace("//", "/", str_replace("//", "/", $_SESSION['wspvars']['ftpbasedir']."/".$newdirname)))):
				$sql = "INSERT INTO `wspmedia` SET `mediatype` = '".$mediatype."', `mediafolder` = '".str_replace("//", "/", str_replace("//", "/", $newdirname."/"))."', `filefolder` = '".str_replace("//", "/", str_replace("//", "/", trim("/".str_replace("/media/".$mediatype."/", "/", $newdirname)."/")))."', lastchange = ".time();
				doSQL($sql);
				echo true;
			else:
				echo false;
			endif;
			ftp_close($ftp);
		} else {
			if (mkdir($_SERVER['DOCUMENT_ROOT'] . '/' . ($_SESSION['wspvars']['wspbasediradd'] ?? '') . '/' . $newdirname, 0755, true)) {
				$sql = "INSERT INTO `wspmedia` SET `mediatype` = '".$mediatype."', `mediafolder` = '".str_replace("//", "/", str_replace("//", "/", $newdirname."/"))."', `filefolder` = '".str_replace("//", "/", str_replace("//", "/", trim("/".str_replace("/media/".$mediatype."/", "/", $newdirname)."/")))."', lastchange = ".time();
				doSQL($sql);
				echo true;
			} else {
				echo false;
			}
		}
	}

endif;

// EOF ?>