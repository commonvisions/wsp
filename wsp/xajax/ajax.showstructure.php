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
	require $wspdir."/data/include/funcs.inc.php";
	require $wspdir."/data/include/filesystemfuncs.inc.php";
	require $wspdir."/data/include/errorhandler.inc.php";
	require $wspdir."/data/include/siteinfo.inc.php";

	if (isset($_POST) && array_key_exists('type', $_POST) && $_POST['type']=='contents'):
		$showtype = 'contents';
	else:
		$showtype = 'structure';
	endif;

	// getjMenuStructure(mid des menuepunktes, $aSelectIDs, $op, $showmidpath, $outputtype, $showlang);
	$openmenu = getjMenuStructure(intval($_POST['mid']), '', '', '', $showtype, $_SESSION['wspvars']['workspacelang']);

	if (trim($openmenu)!=''):
		echo $openmenu;
	else:
		echo "<li class=\"structurelistspacer\"></li>";
	endif;
endif;
