<?php
/**
 * @author stefan@covi.de
 * @since 6.0
 * @version GIT
 */

 if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!=''):
	session_start();
	$wspdir = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".($_SESSION['wspvars']['wspbasediradd'] ?? "")."/".($_SESSION['wspvars']['wspbasedir'] ?? "")));
	include $wspdir.'/data/include/globalvars.inc.php';
	require $wspdir.'/data/include/wsplang.inc.php';
	require $wspdir."/data/include/dbaccess.inc.php";
	if (file_exists($wspdir."/data/include/ftpaccess.inc.php")) require $wspdir."/data/include/ftpaccess.inc.php";
	require $wspdir."/data/include/funcs.inc.php";
	require $wspdir."/data/include/filesystemfuncs.inc.php";
	include $wspdir."/data/include/errorhandler.inc.php";
	include $wspdir."/data/include/siteinfo.inc.php";

	$result = array('success' => false, 'msg' => 'could not handle request');

	if (trim($_POST['fileid'] ?? '') != '') {
		$fileid = trim($_POST['fileid']);
	}

	if($fileid != "") {
		$f_sql = "SELECT * FROM `wspmedia` WHERE `filekey` = '".escapeSQL(trim($fileid))."'";
		$f_res = doSQL($f_sql);
		if ($f_res['num']>0) {
			$finaldir = str_replace("//", "/", str_replace("//", "/", trim($f_res['set'][0]['mediafolder'])));
			$file = trim($f_res['set'][0]['filename']);
			$ftp = doFTP();
			// check connection
			if ($ftp) {
				if (@ftp_delete($ftp, str_replace("//", "/", str_replace("//", "/", ($_SESSION['wspvars']['ftpbasedir'] ?? '')."/".$finaldir."/".$file)))) {
					doSQL("DELETE FROM `mediadesc` WHERE `mediafile` = '".str_replace("//", "/", str_replace("//", "/", $finaldir."/".$file))."'");
					doSQL("DELETE FROM `wspmedia` WHERE `filekey` = '".trim($fileid)."'");
					$result = array('success' => true, 'removedfile' => $fileid, 'msg' => 'removed');
				}
				else {
					$result = array('success' => false, 'msg' => 'deletefile could not remove file');
				}
				ftp_close($ftp);
			} else {
				if (unlink(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT'] . '/' . ($_SESSION['wspvars']['wspbasediradd'] ?? '') . '/' . $finaldir . "/" . $file)))) {
					doSQL("DELETE FROM `mediadesc` WHERE `mediafile` = '".str_replace("//", "/", str_replace("//", "/", $finaldir."/".$file))."'");
					doSQL("DELETE FROM `wspmedia` WHERE `filekey` = '".trim($fileid)."'");
					$result = array('success' => true, 'removedfile' => $fileid, 'msg' => 'removed');
				} else {
					$result = array('success' => false, 'msg' => 'deletefile did not work');
				}
			}
		}
		else {
			$result = array('success' => false, 'msg' => 'deletefile could not find file in database');
		}
	}

	echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);

endif;
