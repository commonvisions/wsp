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

if (isset($_REQUEST['pagetime']) && intval($_REQUEST['pagetime'])<time()):
	
	$qin_sql = "SELECT * FROM `wspqueue` WHERE `set` > ".(intval($_REQUEST['pagetime'])-300);
	$qin_res = doSQL($qin_sql);
	
	$qdone_sql = "SELECT * FROM `wspqueue` WHERE `set` > ".(intval($_REQUEST['pagetime'])-300)." AND (`done` >= ".(intval($_REQUEST['pagetime'])-300)." AND `done` <= ".(intval($_REQUEST['calltime'])+120).")";
	$qdone_res = doSQL($qdone_sql);
	
	$result = array(
		'pagetime' => date("Y-m-d H:i:s", $_REQUEST['pagetime']),
		'calltime' => date("Y-m-d H:i:s", $_REQUEST['calltime']),
		'inqueue' => intval($qin_res['num']),
		'queuedone' => intval($qdone_res['num']),
		'id' => array(),
		'request' => serialize($_REQUEST)
		);
	if (intval($qdone_res['num'])>0):
        foreach ($qdone_res['set'] AS $qdsk => $qdsv)
			$result['id'][] = intval($qdsv['id']);
		endforeach;
		$result['id'] = array_unique($result['id']);
	endif;
else:
	$result = array('request' => serialize($_REQUEST));
endif;
echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
endif;

// EOF ?>