<?php
/**
 * javascript parser-functions
 * @author s.haendler@covi.de
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 6.8
 * @lastchange 2019-01-22
 */

if (!(function_exists('publishJS'))):
function publishJS($jsid) {
	$returnstat = false;
    
    $ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport']))); if ($ftp!==false) {if (!ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass'])) { $ftp = false; }} if (isset($_SESSION['wspvars']['ftppasv']) && $ftp!==false) { ftp_pasv($ftp, $_SESSION['wspvars']['ftppasv']); }
    
	if ($ftp):
		$js_sql = 'SELECT `id`, `file`, `scriptcode` FROM `javascript` WHERE `id` = '.intval($jsid);
		$js_res = doSQL($js_sql);
		if ($js_res['num']>0):
			$tmppath = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/"));
			$tmpfile = tempnam($tmppath, '');
			$fh = fopen($tmpfile, "r+");
			fwrite($fh, stripslashes($js_res['set'][0]['scriptcode']));
			fclose($fh);
			if (!ftp_put($ftp, $_SESSION['wspvars']['ftpbasedir']."/data/script/".trim($js_res['set'][0]['file']).'.js', $tmpfile, FTP_BINARY)):
				$returnstat = false;
				addWSPMsg('errormsg', "Kann erzeugte Datei <strong>".trim($js_res['set'][0]['file']).".js</strong> nicht hochladen. (Put)";
			else:
				$returnstat = true;
				doSQL("UPDATE `javascript ` SET `lastpublish` = ".time()." WHERE `id` = ".intval($jsid));
			endif;
			unlink($tmpfile);
		endif;
        ftp_close($ftp);
	endif;
	return $returnstat;
	}	// publishJS()
endif;

// EOF ?>