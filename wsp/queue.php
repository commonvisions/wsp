<?php
/**
 * queue
 * @author s.haendler@covi.de
 * @copyright (c) 2012, Common Visions Media.Agentur (COVI)
 * @since 3.1
 * @version 6.7.1
 * @lastchange 2018-12-21
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("data/include/usestat.inc.php");
require ("data/include/globalvars.inc.php");
/* first includes ---------------------------- */
if (is_file($_SERVER['DOCUMENT_ROOT']."/".$wspvars['wspbasedir']."/data/javascript/xajax/xajax_core/xajax.inc.php")):
	require ($_SERVER['DOCUMENT_ROOT']."/".$wspvars['wspbasedir']."/data/javascript/xajax/xajax_core/xajax.inc.php");
endif;
require ($_SERVER['DOCUMENT_ROOT']."/".$wspvars['wspbasedir']."/data/include/wsplang.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$wspvars['wspbasedir']."/data/include/dbaccess.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$wspvars['wspbasedir']."/data/include/ftpaccess.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$wspvars['wspbasedir']."/data/include/funcs.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$wspvars['wspbasedir']."/data/include/filesystemfuncs.inc.php");
/* checkParamVar ----------------------------- */


require ($_SERVER['DOCUMENT_ROOT']."/".$wspvars['wspbasedir']."/data/include/checkuser.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$wspvars['wspbasedir']."/data/include/errorhandler.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$wspvars['wspbasedir']."/data/include/siteinfo.inc.php");

$tmppath = $_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['wspvars']['wspbasedir']."/tmp/".$GLOBALS['wspvars']['usevar']."/";
$tmpfile = tempnam($tmppath, 'wsp');

$tmpbuf = "testinhalt fuer ".$_GET['id']." ".mktime();

$fh = fopen($tmpfile, "r+");
fwrite($fh, $tmpbuf);
fclose($fh);

$ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])));
$login = ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass']);

ftp_put($ftp, str_replace("//","/",$_SESSION['wspvars']['ftpbasedir'].$ftppath.'/test'.mktime().'.php'), $tmpfile, FTP_BINARY);

//	@unlink($tmpfile);

ftp_close($ftp);

?>

