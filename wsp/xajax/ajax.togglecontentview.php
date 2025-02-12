<?php
/**
 * ...
 * @author s.haendler@covi.de
 * @copyright (c) 2018, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 6.8
 * @lastchange 2019-01-22
 */
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!=''):
session_start();
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/globalvars.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/wsplang.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/dbaccess.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/funcs.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/filesystemfuncs.inc.php";
include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php";
include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php";

if (isset($_REQUEST['cid'])):
	$cid_sql = "SELECT `visibility`, `mid` FROM `content` WHERE `cid` = ".intval($_REQUEST['cid']);
	$cid_res = doSQL($cid_sql);
	$vis = 0; if ($cid_res['num']>0): $vis = intval($cid_res['set'][0]['visibility']); $mid = intval($cid_res['set'][0]['mid']); endif;
	if ($vis==0):
		$vis_sql = "UPDATE `content` SET `visibility` = 1, `lastchange` = ".time()." WHERE `cid` = ".intval($_REQUEST['cid']);
		doSQL($vis_sql);
		echo "show";
	else:
		$vis_sql = "UPDATE `content` SET `visibility` = 0, `lastchange` = ".time()." WHERE `cid` = ".intval($_REQUEST['cid']);
		doSQL($vis_sql);
		echo "hide";
	endif;
	
	// updating menu for changed content
	if($mid>0):
        $minfo_sql = "SELECT `contentchanged` FROM `menu` WHERE `mid` = ".intval($mid);
        $minfo_res = doSQL($minfo_sql);
        $ccres = 0; if ($minfo_res['num']>0): $ccres = intval($minfo_res['set'][0]['contentchanged']); endif;
        $nccres = 0; if ($ccres==0): $nccres = 2;
        elseif ($ccres==1): $nccres = 3;
        elseif ($ccres==2): $nccres = 2;
        elseif ($ccres==3): $nccres = 3;
        elseif ($ccres==4): $nccres = 5;
        elseif ($ccres==5): $nccres = 5;
        endif;
        $minfo_sql = "UPDATE `menu` SET `contentchanged` = ".intval($nccres)." WHERE `mid` = ".intval($mid);
        doSQL($minfo_sql);
	endif;
	
endif;
endif;
