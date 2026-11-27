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

	$return_vals = ['clone' => false, 'newcid' => 0, 'mid' => 0, 'carea' => 0];
	if (isset($_REQUEST['cid'])):
		$itemcid = intval($_REQUEST['cid']);
		// get information from element
		$clang = '';
		$clang_sql = "SELECT `content_lang`, `mid`, `content_area` FROM `content` WHERE `cid` = " . $itemcid;
		$clang_res = doSQL($clang_sql);
		if ($clang_res['num']>0) { 
			$return_vals['mid'] = $clang_res['set'][0]['mid'];
			$return_vals['carea'] = $clang_res['set'][0]['content_area'];
			$clang = trim($clang_res['set'][0]['content_lang']); 
			$olditemmid = intval($clang_res['set'][0]['mid']); 
			$carea = intval($clang_res['set'][0]['content_area']); 
			// get all elements from target area except dragged element
			$cid_sql = "SELECT `cid`, `position` FROM `content` WHERE `mid` = " . intval($olditemmid)." AND `content_area` = ".intval($carea)." AND `content_lang` = '".$clang."' AND `trash` = 0 ORDER BY `position` ASC";
			$cid_res = doSQL($cid_sql);
			if ($cid_res['num']>0):
				$itemx = 1;
				$doPos = false;
				foreach ($cid_res['set'] AS $cpresk => $cpresv) {
					// fix updating all positions
					if ($doPos || $cpresv['position'] ?? 0 != $itemx) {
						$doPos = true;
						doSQL("UPDATE `content` SET `position` = " . $itemx . " WHERE `cid` = " . intval($cpresv['cid']));
					}
					$itemx++;
				}
			endif;
			// insert duplicated content
			$sql = "INSERT INTO `content` (`mid`, `uid`, `sid`, `globalcontent_id`, `connected`, `content_area`, `content_lang`, `visibility`, `showday`, `showtime`, `container`, `containerclass`, `valuefields`, `lastchange`, `interpreter_guid`) (SELECT `mid`, `uid`, `sid`, `globalcontent_id`, `connected`, `content_area`, `content_lang`, `visibility`, `showday`, `showtime`, `container`, `containerclass`, `valuefields`, `lastchange`, `interpreter_guid` FROM `content` WHERE `cid` = " . $itemcid . ")";
			$res = doSQL($sql);
			$newcid = intval($res['inf']);
			// late update of additional information
			doSQL("UPDATE `content` SET `uid` = " . intval($_SESSION['wspvars']['userid']) . " WHERE `cid` = " . $newcid);
			doSQL("UPDATE `content` SET `position` = " . $itemx . " WHERE `cid` = " . $newcid);
			doSQL("UPDATE `content` SET `lastchange` = " . time() . " WHERE `cid` = " . $newcid);
			$return_vals['clone'] = true;
			$return_vals['newcid'] = $newcid;
			// updating contentchanged
			doSQL("UPDATE `menu` SET `contentchanged` = 2 WHERE `mid` = ".intval($olditemmid));
			// return values to jquery
			echo json_encode($return_vals);
		} else {
			$return_vals = array('clone' => false, 'msg' => 'Could not find content to clone');
		}
	endif;

endif;
