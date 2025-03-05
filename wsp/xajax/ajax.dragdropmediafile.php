<?php
/**
 * ...
 * @author stefan@covi.de
 * @copyright (c) 2018, Common Visions Media.Agentur (COVI)
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

if (isset($_POST) && array_key_exists('fkey', $_POST) && array_key_exists('xajaxmedialist', $_SESSION) && array_key_exists($_POST['fkey'], $_SESSION['xajaxmedialist'])):
	$ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])));
    if ($ftp!==false) {if (!ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass'])) { $ftp = false; }}
    if (isset($_SESSION['wspvars']['ftppasv'])) { ftp_pasv($ftp, $_SESSION['wspvars']['ftppasv']); }
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