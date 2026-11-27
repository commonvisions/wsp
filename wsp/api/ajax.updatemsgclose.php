<?php
/**
 * @author stefan@covi.de
 * @since 6.0
 * @version 6.8
 */

session_start();
$wspdir = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".($_SESSION['wspvars']['wspbasediradd'] ?? "")."/".($_SESSION['wspvars']['wspbasedir'] ?? "")));

require $wspdir.'/data/include/globalvars.inc.php';
require $wspdir.'/data/include/wsplang.inc.php';
require $wspdir.'/data/include/funcs.inc.php';
require $wspdir.'/data/include/dbaccess.inc.php';

$msg_sql = "UPDATE `wspmsg` SET `read` = 1 WHERE `id` = ".intval($_POST['msgid']);
$msg_res = doSQL($msg_sql);
