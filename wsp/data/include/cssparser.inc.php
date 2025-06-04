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

if (!(function_exists('publishCSS'))) {
    function publishCSS($cssid, bool $ftp = false, bool $usedirect = false) {
        $returnstat = $tmpfile = false;
        
        $css_sql = 'SELECT * FROM `stylesheets` WHERE `id` = ' . intval($cssid);
        $css_res = doSQL($css_sql);
        if ($css_res['num']>0) {
            $tmppath = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/"));

            $cfolder = intval($css_res['set'][0]['cfolder']);
            $lastchange = intval($css_res['set'][0]['lastchange']);
            $cfile = trim($css_res['set'][0]['file'] ?? '');

            if ($cfolder == $lastchange) {
                // single css
                $tmpfile = tempnam($tmppath, '');
                $fh = fopen($tmpfile, "r+");
                fwrite($fh, stripslashes($css_res['set'][0]['stylesheet']));
                fclose($fh);
            } else if ($cfolder != $lastchange && empty($cfile)) {
                // css folder hasn't to be published
                doSQL("UPDATE `stylesheets` SET `lastpublish` = " . time() . " WHERE `id` = ".intval($cssid));
                return true;
            }
        }

        if (is_file($tmpfile) && $ftp===true) {
            $ftppath = $_SESSION['wspvars']['ftpbasedir']."/media/layout/".trim($css_res['set'][0]['file']).'.css';
            if (!ftp_put($ftp, $ftppath, $tmpfile, FTP_BINARY)) {
                addWSPMsg('errormsg', "Could not upload file <strong>".trim($css_res['set'][0]['file']).".css</strong> by FTP.");
            } else {
                doSQL("UPDATE `stylesheets` SET `lastpublish` = ".time()." WHERE `id` = ".intval($cssid));
                $returnstat = true;
            }
            unlink($tmpfile);
        } else if (is_file($tmpfile) && $usedirect===true) {
            $directpath = str_replace("//" , "/" , $_SERVER['DOCUMENT_ROOT'] . "/" . $_SESSION['wspvars']['wspbasediradd'] . "/media/layout/" . trim($css_res['set'][0]['file']) . '.css');
            if (!(copy($tmpfile, $directpath))) {
                addWSPMsg('errormsg', "<p>" . returnIntLang('css publisher could not be written directly', false) . "</p>");
                $returnstat = false;
            } else {
                doSQL("UPDATE `stylesheets` SET `lastpublish` = ".time()." WHERE `id` = ".intval($cssid));
                $returnstat = true;
            }
        } else {
            addWSPMsg('errormsg', 'css publisher could not connect');
        }

        return $returnstat;
    }	// publishCSS()
}
