<?php
/**
 * javascript parser-functions
 * @author stefan@covi.de
 * @since 6.0
 * @version 7.0.1
 * @lastchange 2021-09-22
 */

if (!(function_exists('publishJS'))) {
    function publishJS($jsid, $con = false) {
        $returnstat = false;
        if ($con!==false) { 
            $js_sql = 'SELECT `id`, `file`, `scriptcode` FROM `javascript` WHERE `id` = '.intval($jsid);
            $js_res = doSQL($js_sql);
            if ($js_res['num']>0) {
                $tmppath = cleanPath(DOCUMENT_ROOT."/".WSP_DIR."/tmp/".$_SESSION['wspvars']['usevar']."/");
                $tmpfile = tempnam($tmppath, '');
                $fh = fopen($tmpfile, "r+");
                fwrite($fh, stripslashes(trim($js_res['set'][0]['scriptcode'])));
                fclose($fh);
                if (!copyFile($tmpfile, '/data/script/'.trim($js_res['set'][0]['file']).'.css')) {
                    addWSPMsg('errormsg', returnIntLang('js could not be uploaded1').' <strong>'.$js_res['set'][0]['file'].'.js</strong> '.returnIntLang('js could not be uploaded2'));
                    $returnstat = false;
                } else {
                    doSQL("UPDATE `javascript` SET `lastpublish` = ".time()." WHERE `id` = ".intval($cssid));
                    $returnstat = true;
                }
                // unlinking is only nessessary, if the file was copied by ftp
			    // otherwise it was already moved bei srv part of copy function
                unlink($tmpfile);
            }
            return $returnstat;
        }
	}
}

?>