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
	require $wspdir."/data/include/ftpaccess.inc.php";
	require $wspdir."/data/include/funcs.inc.php";
	require $wspdir."/data/include/filesystemfuncs.inc.php";
	require $wspdir."/data/include/errorhandler.inc.php";
	require $wspdir."/data/include/siteinfo.inc.php";

if (isset($_POST) && array_key_exists('fkey', $_POST) && array_key_exists('xajaxmedialist', $_SESSION) && array_key_exists($_POST['fkey'], $_SESSION['xajaxmedialist'])):
	$ftp = doFTP();
	if ($ftp):
		$ftptrgt = str_replace("//", "/", str_replace("//", "/", $_SESSION['wspvars']['ftpbasedir']."/".$_POST['target']."/".$_SESSION['xajaxmedialist'][$_POST['fkey']]['file']));
		if ($_POST['copykey']=='copy'):		
			$ftphome = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['xajaxmedialist'][$_POST['fkey']]['directory']."/".$_SESSION['xajaxmedialist'][$_POST['fkey']]['file']));
			if (@ftp_put($ftp, $ftptrgt, $ftphome, FTP_BINARY)):
				if (trim($_SESSION['xajaxmediastructure'][$_SESSION['xajaxmedialist'][$_POST['fkey']]['directory']][$_SESSION['xajaxmedialist'][$_POST['fkey']]['file']]['thumbnail'])!=''):
					// thumbnail exists
					$ftptmbtrgt = str_replace("//", "/", str_replace("//", "/", str_replace($_POST['base'], $_POST['base']."/thumbs/", $GLOBALS['wspvars']['ftpbasedir']."/".$_POST['target']."/".$_SESSION['xajaxmedialist'][$_POST['fkey']]['file'])));
					$ftptmbhome = str_replace("//", "/", str_replace("//", "/", str_replace($_POST['base'], $_POST['base']."/thumbs/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['xajaxmedialist'][$_POST['fkey']]['directory']."/".$_SESSION['xajaxmedialist'][$_POST['fkey']]['file'])));
					if (@ftp_put($ftp, $ftptmbtrgt, $ftptmbhome, FTP_BINARY)):
//						echo "thmb:copy\n";
					endif;
				endif;
			endif;
			// add copied file to session var
			$_SESSION['xajaxmedialist'][md5("/".$_POST['target']."/".$_SESSION['xajaxmedialist'][$_POST['fkey']]['file'])] = array(
				'directory' => $_POST['target'],
				'file' => $_SESSION['xajaxmedialist'][$_POST['fkey']]['file']
				);
			$_SESSION['xajaxmediastructure'][$_POST['target']][$_SESSION['xajaxmedialist'][$_POST['fkey']]['file']] = $_SESSION['xajaxmediastructure'][$_SESSION['xajaxmedialist'][$_POST['fkey']]['directory']][$_SESSION['xajaxmedialist'][$_POST['fkey']]['file']];
		else:
			$ftphome = str_replace("//", "/", str_replace("//", "/", $_SESSION['wspvars']['ftpbasedir']."/".$_SESSION['xajaxmedialist'][$_POST['fkey']]['directory']."/".$_SESSION['xajaxmedialist'][$_POST['fkey']]['file']));
			if (@ftp_rename($ftp, $ftphome, $ftptrgt)):
//				echo "file:move";
				if (trim($_SESSION['xajaxmediastructure'][$_SESSION['xajaxmedialist'][$_POST['fkey']]['directory']][$_SESSION['xajaxmedialist'][$_POST['fkey']]['file']]['thumbnail'])!=''):
					$ftptmbtrgt = str_replace("//", "/", str_replace("//", "/", str_replace($_POST['base'], $_POST['base']."/thumbs/", $_SESSION['wspvars']['ftpbasedir']."/".$_POST['target']."/".$_SESSION['xajaxmedialist'][$_POST['fkey']]['file'])));
					$ftptmbhome = str_replace("//", "/", str_replace("//", "/", str_replace($_POST['base'], $_POST['base']."/thumbs/", $_SESSION['wspvars']['ftpbasedir']."/".$_SESSION['xajaxmedialist'][$_POST['fkey']]['directory']."/".$_SESSION['xajaxmedialist'][$_POST['fkey']]['file'])));
					if (@ftp_rename($ftp, $ftptmbhome, $ftptmbtrgt)):
//						echo "thmb:move\n";
					endif;
				endif;
			endif;
			// add moved file to session var
			$_SESSION['xajaxmedialist'][md5("/".$_POST['target']."/".$_SESSION['xajaxmedialist'][$_POST['fkey']]['file'])] = array(
				'directory' => $_POST['target'],
				'file' => $_SESSION['xajaxmedialist'][$_POST['fkey']]['file']
				);
			$_SESSION['xajaxmediastructure'][$_POST['target']][$_SESSION['xajaxmedialist'][$_POST['fkey']]['file']] = $_SESSION['xajaxmediastructure'][$_SESSION['xajaxmedialist'][$_POST['fkey']]['directory']][$_SESSION['xajaxmedialist'][$_POST['fkey']]['file']];
			// delete from moved places
			unset($_SESSION['xajaxmediastructure'][$_SESSION['xajaxmedialist'][$_POST['fkey']]['directory']][$_SESSION['xajaxmedialist'][$_POST['fkey']]['file']]);
			unset($_SESSION['xajaxmedialist'][$_POST['fkey']]);
		endif;
        ftp_close($ftp);
    endif;
endif;
endif;

// EOF ?>