<?php
/**
 * @author stefan@covi.de
 * @since 6.0
 * @version GIT
 * 
 * 2025-06-04
 * introduced direct publishing
 * 
 */

if (!(function_exists('publishJS'))):
	function publishJS(int $jsid, bool $ftp = false, bool $usedirect = false) {
		$returnstat = $tmpfile = false;
    
		$js_sql = 'SELECT `id`, `file`, `scriptcode` FROM `javascript` WHERE `id` = ' . intval($jsid);
		$js_res = doSQL($js_sql);
		if ($js_res['num']>0) {
			$tmppath = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/"));

            $cfile = trim($js_res['set'][0]['file'] ?? 'script' . intval($jsid));
			$tmpfile = tempnam($tmppath, '');
			$fh = fopen($tmpfile, "r+");
			fwrite($fh, stripslashes($js_res['set'][0]['scriptcode']));
			fclose($fh);
		}

		if (is_file($tmpfile) && $ftp===true) {
            $ftppath = $_SESSION['wspvars']['ftpbasedir'] . "/data/script/" . $cfile . '.js';
            if (!ftp_put($ftp, $ftppath, $tmpfile, FTP_BINARY)) {
                addWSPMsg('errormsg', "Could not upload file <strong>". $cfile .".js</strong> by FTP.");
            } else {
                doSQL("UPDATE `javascript` SET `lastpublish` = " . time() . " WHERE `id` = " . intval($jsid));
                $returnstat = true;
            }
            unlink($tmpfile);
        } else if (is_file($tmpfile) && $usedirect===true) {
            $directpath = str_replace("//" , "/" , $_SERVER['DOCUMENT_ROOT'] . "/" . $_SESSION['wspvars']['wspbasediradd'] . "/data/script/" . $cfile . '.js');
            if (!(copy($tmpfile, $directpath))) {
                addWSPMsg('errormsg', "<p>" . returnIntLang('js publisher could not be written directly', false) . "</p>");
                $returnstat = false;
            } else {
                doSQL("UPDATE `javascript` SET `lastpublish` = " . time() . " WHERE `id` = " . intval($jsid));
                $returnstat = true;
            }
        } else {
            addWSPMsg('errormsg', 'js publisher could not connect');
        }
		return $returnstat;
	}	// publishJS()
endif;
