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

	if (isset($_REQUEST['mid'])):
		$mid = intval(str_replace("li_", "", $_REQUEST['mid']));
		if (intval($mid)>0):
			if (isset($_REQUEST['listorder'])):
				$listelements = explode("=",$_REQUEST['listorder']);
				$orderedlist = array();
				foreach ($listelements AS $lk => $lv):
					if (intval($lv)>0):
						$orderedlist[] = intval($lv);
					endif;
				endforeach;
				$checkmove_sql = "SELECT MIN(`level`) AS `minl`, MAX(`level`) AS `maxl` FROM `menu` WHERE `mid` IN (".implode(",", $orderedlist).")";
				$checkmove_res = doSQL($checkmove_sql);
				if ($checkmove_res['num']>0): 
					if (intval($checkmove_res['set'][0]['minl'])==intval($checkmove_res['set'][0]['maxl'])):
						foreach ($orderedlist AS $ok => $ov):
							doSQL("UPDATE `menu` SET `position` = ".(intval($ok)+1)." WHERE `mid` = ".intval($ov));
						endforeach;
					endif;
				endif;
			endif;
			doSQL("UPDATE `menu` SET `contentchanged` = 4 WHERE `mid` = ".intval($mid));
		endif;
	endif;
endif;
