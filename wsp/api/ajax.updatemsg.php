<?php
/**
 * @author stefan@covi.de
 * @since 6.0
 * @version GIT
 */

session_start();
$wspdir = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".($_SESSION['wspvars']['wspbasediradd'] ?? "")."/".($_SESSION['wspvars']['wspbasedir'] ?? "")));
require $wspdir.'/data/include/globalvars.inc.php';
require $wspdir.'/data/include/funcs.inc.php';
require $wspdir.'/data/include/dbaccess.inc.php';

$msg_sql = "SELECT `id`, `message` FROM `wspmsg` WHERE `targetuid` = ".intval($_POST['uid'])." AND `read` = 0 ORDER BY `id` DESC LIMIT 0, 3";
$msg_res = doSQL($msg_sql);
$msgbarcontent = "";
if ($msg_res['num']>0):
	foreach ($msg_res['set'] AS $msgk => $msgv) {
		$msgbarcontent.= "<fieldset class=\"msgbox\" id=\"wspmsg".intval($msgv['id'])."\" style=\"opacity: 0.8; background: white; -moz-box-shadow: 0px 0px 10px #000; -webkit-box-shadow: 0px 0px 10px #000; box-shadow: 0px 0px 10px #000;\">";
		$msgbarcontent.= "<p class=\"msglegend\" style=\"text-align: right; margin: 0px; padding: 0px;\"><a onclick=\"closeMsg(".intval($msgv['id']).")\"><span class=\"bubblemessage red\">CLOSE</span></a></p>";
		$msgbarcontent.= "<p>".setUTF8($msgv['message'])."</p>";
		$msgbarcontent.= "</fieldset>";
	}
endif;
if (trim($msgbarcontent!="")) { echo $msgbarcontent; }
