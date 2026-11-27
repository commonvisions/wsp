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

	if (isset($_REQUEST['cid'])):
		$cid = intval($_REQUEST['cid']);
		$sql = "UPDATE `content` SET `visibility` = 0, `trash` = 1 WHERE `cid` = ".intval($cid);
		$res = doSQL($sql);
		if ($res['res']):
			$c_sql = "SELECT `mid` FROM `content` WHERE `cid` = ".intval($cid);
			$c_res = doSQL($c_sql);
			if ($c_res['num']>0):
				doSQL("UPDATE `menu` SET `contentchanged` = ".contentChangeStat(intval($c_res['set'][0]['mid']),'content')." WHERE `mid` = ".intval($c_res['set'][0]['mid']));
			endif;
			echo "#cli_".$cid;
		endif;
	endif;
endif;
