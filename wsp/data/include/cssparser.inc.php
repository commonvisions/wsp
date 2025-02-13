<?php
/**
 * stylesheet parser-functions
 * @author stefan@covi.de
 * @since 6.0
 * @version 6.8.1
 * @lastchange 2019-01-25
 */

if (!(function_exists('publishCSS'))) {
    function publishCSS($cssid, $ftp = false) {
        $returnstat = false;
        if ($ftp!==false) {
            $css_sql = 'SELECT * FROM `stylesheets` WHERE `id` = '.intval($cssid);
            $css_res = doSQL($css_sql);
            if ($css_res['num']>0) {
                $tmppath = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/"));
                if (intval($css_res['set'][0]['cfolder'])==intval($css_res['set'][0]['lastchange'])) {
                    // single css
                    $tmpfile = tempnam($tmppath, '');
                    $fh = fopen($tmpfile, "r+");
                    fwrite($fh, stripslashes($css_res['set'][0]['stylesheet']));
                    fclose($fh);
                    if (!ftp_put($ftp, $_SESSION['wspvars']['ftpbasedir']."/media/layout/".trim($css_res['set'][0]['file']).'.css', $tmpfile, FTP_BINARY)):
                        $returnstat = false;
                        addWSPMsg('errormsg', "Kann erzeugte Datei <strong>".trim($css_res['set'][0]['file']).".css</strong> nicht hochladen. (Put)");
                    else:
                        $returnstat = true;
                        doSQL("UPDATE `stylesheets` SET `lastpublish` = ".time()." WHERE `id` = ".intval($cssid));
                    endif;
                    unlink($tmpfile);
                }
                else if (intval($css_res['set'][0]['cfolder'])!=intval($css_res['set'][0]['lastchange']) && trim($css_res['set'][0]['file'])=='') {
                    // css folder hasn't to be published
                    doSQL("UPDATE `stylesheets` SET `lastpublish` = ".time()." WHERE `id` = ".intval($cssid));
                    $returnstat = true;
                }
            }
        } else {
            addWSPMsg('errormsg', 'publisher css could not connect');
        }
        return $returnstat;
    }	// publishCSS()
}

// EOF ?>