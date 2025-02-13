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
	require $wspdir."/data/include/funcs.inc.php";
	require $wspdir."/data/include/filesystemfuncs.inc.php";
	require $wspdir."/data/include/errorhandler.inc.php";
	require $wspdir."/data/include/siteinfo.inc.php";

	if (isset($_REQUEST['newname']) && trim($_REQUEST['newname'])!=''):
		$filename_sql = "SELECT `id` FROM `menu` WHERE `filename` LIKE '".escapeSQL(trim($_REQUEST['newname']))."'";
		$filename_res = doSQL($filename_sql);
		if ($filename_res['num']>0) echo returnIntLang('message filename already exists', false);
	endif;
endif;
