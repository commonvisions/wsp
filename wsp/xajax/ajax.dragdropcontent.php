<?php
/**
 * content drag drop content
 * @author stefan@covi.de
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 7.0
 * @lastchange 2019-01-22
 */
session_start();

if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!=''):
	if (isset($_SESSION['wspvars'])):
        require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/globalvars.inc.php';
		require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/wsplang.inc.php';
		require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/dbaccess.inc.php";
		require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/ftpaccess.inc.php";
		require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/funcs.inc.php";
		require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/filesystemfuncs.inc.php";
		include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php";
		include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php";

		if (isset($_POST) && array_key_exists('dropmid', $_POST)):

			/* sort 
			[action] => sort
			[itempos] => 1
			[dropid] => cli_541
			[droparea] => droplist droponempty carea_1 ui-sortable
			[dropmid] => li_57
			[copy] => false/true
			*/

			/* move
			[action] => dragdrop
			[itempos] => 0
			[dropid] => cli_552
			[sentarea] => carea_1 careaholder
			[droparea] => droplist droponempty carea_2 ui-sortable
			[dropmid] => li_57
			[copy] => false/true
			*/

			$itemcopy = false;
			$itemmid = intval(str_replace("li_", "", $_POST['dropmid']));
			$itemcid = intval(str_replace("cli_", "", $_POST['dropid']));
			$newitempos = intval($_POST['itempos'])+1;
			$tmpitemarea = explode(" ", $_POST['droparea']);
			if (is_array($tmpitemarea)):
				foreach($tmpitemarea AS $tiak => $tiav):
					if (strchr($tiav, 'carea_')):
						$newitemarea = intval(str_replace("carea_", "", $tiav));
						break;
					else:
						$newitemarea = 1;
					endif;
				endforeach;
			else:
				$newitemarea = 1;
			endif;
			$return_vals = array('copy' => 'no', 'thenewid' => 0);
			if ($_POST['copy']=='true'): $itemcopy = true; endif;
			// get information from element
			$clang = '';
			$clang_sql = "SELECT `content_lang`, `mid` FROM `content` WHERE `cid` = ".intval($itemcid);
			$clang_res = doSQL($clang_sql);
			if ($clang_res['num']>0): $clang = trim($clang_res['set'][0]['content_lang']); $olditemmid = intval($clang_res['set'][0]['mid']); endif;
			// get all elements from target area except dragged element
			$cid_sql = "SELECT `cid`, `position` FROM `content` WHERE `mid` = ".intval($itemmid)." AND `cid` != ".intval($itemcid)." AND `content_area` = ".intval($newitemarea)." AND `content_lang` = '".escapeSQL($clang)."' AND `trash` = 0 ORDER BY `position` ASC";
			$cid_res = doSQL($cid_sql);
			if ($cid_res['num']>0):
				$itemx = 0;
				foreach ($cid_res['set'] AS $cpresk => $cpresv) {
					// updating all positions
					if (intval($cpresv['position'])<$newitempos): $itemx = -1; else: $itemx = 2; endif;
					doSQL("UPDATE `content` SET `position` = ".intval($cpresk+$itemx)." WHERE `cid` = ".intval($cpresv['cid']));
				}
			endif;
			// move or copy item
			if (!($itemcopy)):
				// move
				doSQL("UPDATE `content` SET `mid` = ".intval($itemmid).", `content_area` = ".$newitemarea.", `position` = ".intval($newitempos)." WHERE `cid` = ".intval($itemcid));
				echo json_encode($return_vals);
			else:
				$sql = "INSERT INTO `content` (`mid`, `globalcontent_id`, `connected`, `content_area`, `content_lang`, `position`, `visibility`, `showday`, `showtime`, `container`, `containerclass`, `sid`, `valuefields`, `lastchange`, `interpreter_guid`, `xajaxfunc`, `xajaxfuncnames`) (SELECT '" . $itemmid . "',`globalcontent_id`, `connected`, '".$newitemarea."', `content_lang`, ".intval($newitempos).", `visibility`, `showday`, `showtime`, `container`, `containerclass`, `sid`, `valuefields`, `lastchange`, `interpreter_guid`, `xajaxfunc`, `xajaxfuncnames` FROM `content` WHERE cid = ".intval($itemcid).")";
				$res = doSQL($sql);
				$thenewid = intval($res['inf']);
				$return_vals['copy'] = "copy";
				$return_vals['thenewid'] = $thenewid;
				echo json_encode($return_vals);
			endif;
			// resort all items positions finally
			$cid_sql = "SELECT `cid`, `position` FROM `content` WHERE `mid` = ".intval($itemmid)." AND `content_area` = ".intval($newitemarea)." AND `content_lang` = '".escapeSQL($clang)."' AND `trash` = 0 ORDER BY `position` ASC";
			$cid_res = doSQL($cid_sql);
			if ($cid_res['num']>0):
				foreach ($cid_res['set'] AS $cpresk => $cpresv) {
					// updating all positions
					doSQL("UPDATE `content` SET `position` = ".intval(intval($cpresk)+1)." WHERE `cid` = ".intval($cpresv['cid']));
                }
			endif;

			/* updating new menu for changed content */
			if($itemmid>0):
				$minfo_sql = "SELECT `contentchanged` FROM `menu` WHERE `mid` = ".intval($itemmid);
				$minfo_res = intval(doResultSQL($minfo_sql));
				$nccres = 0; if ($minfo_res==0): $nccres = 2;
				elseif ($minfo_res==1): $nccres = 3;
				elseif ($minfo_res==2): $nccres = 2;
				elseif ($minfo_res==3): $nccres = 3;
				elseif ($minfo_res==4): $nccres = 5;
				elseif ($minfo_res==5): $nccres = 5;
				endif;
				doSQL("UPDATE `menu` SET `contentchanged` = ".intval($nccres)." WHERE `mid` = ".intval($itemmid));
			endif;
            /* updating old menu for changed content */
			if (!($itemcopy) && ($itemmid!=$olditemmid)):
				if($olditemmid>0):
                    $minfo_sql = "SELECT `contentchanged` FROM `menu` WHERE `mid` = ".intval($olditemmid);
                    $minfo_res = intval(doResultSQL($minfo_sql));
                    $nccres = 0; if ($minfo_res==0): $nccres = 2;
                    elseif ($minfo_res==1): $nccres = 3;
                    elseif ($minfo_res==2): $nccres = 2;
                    elseif ($minfo_res==3): $nccres = 3;
                    elseif ($minfo_res==4): $nccres = 5;
                    elseif ($minfo_res==5): $nccres = 5;
                    endif;
                    doSQL("UPDATE `menu` SET `contentchanged` = ".intval($nccres)." WHERE `mid` = ".intval($olditemmid));
				endif;
			endif;
		endif;
	endif;
endif;

// EOF ?>