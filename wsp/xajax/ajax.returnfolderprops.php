<?php
/**
 * @author stefan@covi.de
 * @since 6.0
 * @version 6.8
 */

if (!empty($_SERVER['HTTP_REFERER'] ?? null)):
	session_start();
	$wspdir = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".($_SESSION['wspvars']['wspbasediradd'] ?? "")."/".($_SESSION['wspvars']['wspbasedir'] ?? "")));

	require $wspdir.'/data/include/globalvars.inc.php';
	require $wspdir.'/data/include/wsplang.inc.php';
	require $wspdir."/data/include/dbaccess.inc.php";
	require $wspdir.'/data/include/funcs.inc.php';
	require $wspdir.'/data/include/filesystemfuncs.inc.php';
	require $wspdir."/data/include/errorhandler.inc.php";
	require $wspdir."/data/include/siteinfo.inc.php";

	$usefolder = '';
	foreach (($_SESSION['wspvars']['xajaxactmediastructure'] ?? []) AS $fk => $fv):
		if (urltext(str_replace("/", "-", $fv))==$_POST['fread']):
			$usefolder = $fv;
		endif;
	endforeach;

	$filelist = array();
	$uselist = array();
	$subdir = false;
	if ($usefolder!=''):
		$fsysfolder = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$usefolder));
		$folderstat = opendir($fsysfolder);
		$foldercount = readdir($folderstat);
		$countfiles = 0;
		while ($file = readdir ( $folderstat )):
			if (substr($file,0,1)!='.' && is_file($fsysfolder."/".$file)):
				$filelist[] = trim($file);
				if (fileinuse($usefolder, trim($file))):
					$uselist[] = trim($file);
				endif;
			endif;
			if (substr($file,0,1)!='.' && is_dir($fsysfolder."/".$file)):
				$subdir = true;
			endif;
		endwhile;
		closedir($folderstat);
	endif;

	if (count($uselist)>0 || $subdir):
		if (count($filelist)==count($uselist) || $subdir):
			// all files are in use
			echo "2";
		else:
			// some files are in use
			echo "1";
		endif;
	else:
		echo "0";
	endif;

endif;
