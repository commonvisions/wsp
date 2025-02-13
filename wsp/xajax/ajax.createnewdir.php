<?php
/**
 * @author stefan@covi.de
 * @since 6.0
 * @version GIT
 */

if (!empty($_SERVER['HTTP_REFERER'] ?? null)):
	session_start();
	$wspdir = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".($_SESSION['wspvars']['wspbasediradd'] ?? "")."/".($_SESSION['wspvars']['wspbasedir'] ?? "")));
	
	require $wspdir.'/data/include/globalvars.inc.php';
	require $wspdir.'/data/include/wsplang.inc.php';
	require $wspdir."/data/include/dbaccess.inc.php";
	if (file_exists($wspdir."/data/include/ftpaccess.inc.php")) require $wspdir."/data/include/ftpaccess.inc.php";
	require $wspdir."/data/include/funcs.inc.php";
	require $wspdir."/data/include/filesystemfuncs.inc.php";
	require $wspdir."/data/include/errorhandler.inc.php";
	require $wspdir."/data/include/siteinfo.inc.php";

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