<?php
/**
 * stylesheet parser-functions
 * @author stefan@covi.de
 * @since 6.0
 * @version 7.0.1
 * @lastchange 2021-09-22
 */

if (!(function_exists('publishCSS'))) {
function publishCSS($cssid, $con = false) {
	$returnstat = false;
	if ($con!==false) {
		$css_sql = 'SELECT * FROM `stylesheets` WHERE `id` = '.intval($cssid);
		$css_res = doSQL($css_sql);
		if ($css_res['num']>0) {
			$tmppath = cleanPath(DOCUMENT_ROOT."/".WSP_DIR."/tmp/".$_SESSION['wspvars']['usevar']."/");
			$tmpfile = tempnam($tmppath, '');
			$fh = fopen($tmpfile, 'r+');
			fwrite($fh, stripslashes($css_res['set'][0]['stylesheet']));
			fclose($fh);
			if (!copyFile($tmpfile, '/media/layout/'.$css_res['set'][0]['file'].'.css')) {
				$returnstat = false;
				addWSPMsg('errormsg', returnIntLang('css could not be uploaded1').' <strong>'.$css_res['set'][0]['file'].'.css</strong> '.returnIntLang('css could not be uploaded2'));
			} else {
				doSQL("UPDATE `stylesheets` SET `lastpublish` = ".time()." WHERE `id` = ".intval($cssid));
				$returnstat = true;
			}
			// unlinking is only nessessary, if the file was copied by ftp
			// otherwise it was already moved bei srv part of copy function
			@unlink($tmpfile);
		}
	}
	return $returnstat;
	}	// publishCSS()
}

?>