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

	$return_vals = array('clone' => false, 'newcid' => 0);
	if (isset($_REQUEST['cid'])):
		$itemcid = intval($_REQUEST['cid']);
		// get information from element
		$clang = '';
		$clang_sql = "SELECT `content_lang`, `mid`, `content_area` FROM `content` WHERE `cid` = ".$itemcid;
		$clang_res = doSQL($clang_sql);
		if ($clang_res['num']>0): $clang = trim($clang_res['set'][0]['content_lang']); $olditemmid = intval($clang_res['set'][0]['mid']); $carea = intval($clang_res['set'][0]['content_area']); endif;
		// get all elements from target area except dragged element
		$cid_sql = "SELECT `cid`, `position` FROM `content` WHERE `mid` = ".intval($olditemmid)." AND `content_area` = ".intval($carea)." AND `content_lang` = '".$clang."' AND `trash` = 0 ORDER BY `position` ASC";
		$return_vals['sql'] = $cid_sql;
		$cid_res = doSQL($cid_sql);
		if ($cid_res['num']>0):
			$itemx = 1;
			foreach ($cid_res['set'] AS $cpresk => $cpresv) {
				// updating all positions
				if (intval($cpresv['cid'])==$itemcid): $itemx = 3; endif;
				doSQL("UPDATE `content` SET `position` = ".intval($cpresk+$itemx)." WHERE `cid` = ".intval($cpresv['cid']));
			}
		endif;
		// insert duplicated content
		$sql = "INSERT INTO `content` (`mid`, `globalcontent_id`, `connected`, `content_area`, `content_lang`, `position`, `visibility`, `showday`, `showtime`, `container`, `containerclass`, `sid`, `valuefields`, `lastchange`, `interpreter_guid`, `xajaxfunc`, `xajaxfuncnames`) (SELECT `mid`,`globalcontent_id`, `connected`, `content_area`, `content_lang`, (`position`+1), `visibility`, `showday`, `showtime`, `container`, `containerclass`, `sid`, `valuefields`, `lastchange`, `interpreter_guid`, `xajaxfunc`, `xajaxfuncnames` FROM `content` WHERE cid = ".$itemcid.")";
		$res = doSQL($sql);
		$newcid = intval($res['inf']);
		$return_vals = array('clone' => true, 'newcid' => $newcid);
		// updating contentchanged
		doSQL("UPDATE `menu` SET `contentchanged` = 2 WHERE `mid` = ".intval($olditemmid));
		// return values to jquery
		echo json_encode($return_vals);
	endif;

endif;
