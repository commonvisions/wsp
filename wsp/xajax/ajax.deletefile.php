<?php
/**
 * ...
 * @author stefan@covi.de
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 7.0
 * @lastchange 2019-10-17
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

$result = array('success' => false, 'msg' => 'msg');

if (isset($_POST) && isset($_POST['fileid'])):
	$fileid = $_POST['fileid'];
	$basedir = $_SESSION['wspvars']['ftpbasedir'];
endif;

if($fileid!="" && $basedir!='') {
	$f_sql = "SELECT * FROM `wspmedia` WHERE `filekey` = '".escapeSQL(trim($fileid))."'";
	$f_res = doSQL($f_sql);
	if ($f_res['num']>0) {
		$finaldir = str_replace("//", "/", str_replace("//", "/", trim($f_res['set'][0]['mediafolder'])));
		$file = trim($f_res['set'][0]['filename']);
		$ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport']))); if ($ftp!==false) {if (!ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass'])) { $ftp = false; }} if (isset($_SESSION['wspvars']['ftppasv']) && $ftp!==false) { ftp_pasv($ftp, $_SESSION['wspvars']['ftppasv']); }
		// check connection
		if ($ftp) {
			if (@ftp_delete($ftp, str_replace("//", "/", str_replace("//", "/", $basedir."/".$finaldir."/".$file)))) {
                doSQL("DELETE FROM `mediadesc` WHERE `mediafile` = '".str_replace("//", "/", str_replace("//", "/", $finaldir."/".$file))."'");
                doSQL("DELETE FROM `wspmedia` WHERE `filekey` = '".trim($fileid)."'");
                $result = array('success' => true, 'removedfile' => $fileid, 'msg' => 'removed');
            }
            else {
                $result = array('success' => false, 'msg' => 'deletefile could not remove file');
            }
			ftp_close($ftp);
		} else {
            $result = array('success' => false, 'msg' => 'deletefile could not connect to ftp');
        }
	}
    else {
        $result = array('success' => false, 'msg' => 'deletefile could not find file in database');
    }
}

echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);

endif;

// EOF ?>