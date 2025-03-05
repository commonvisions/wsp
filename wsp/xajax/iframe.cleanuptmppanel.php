<?php
/**
 * ...
 * @author stefan@covi.de
 * @since 6.0
 * @version 7.0
 * @lastchange 2019-03-25
 */

// if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!=''):
session_start();
require("../data/include/globalvars.inc.php");
require("../data/include/errorhandler.inc.php");
require("../data/include/siteinfo.inc.php");
require("../data/include/headertiny.inc.php");

var_export(WSP_DEV);

echo "<div style='position: fixed; top: 0px; left: 0px; width: 100%; height: 100%; text-align: center;'><i class='fa fa-circle-o-notch fa-spin fa-3x fa-fw' style='position: absolute; top: 50%; margin-top: -1em; font-size: 2em;'></i></div>";

$tempdirs = scandirs("/".WSP_DIR."/tmp", false);
if (count($tempdirs)>1):
    foreach ($tempdirs AS $key => $value):
        $stat = stat(cleanPath(DOCUMENT_ROOT."/".WSP_DIR."/tmp/".$value));
        $tempdirs[$key] = str_replace("//", "/", str_replace("//", "/", $value));
        if (intval($stat[9])>=intval(time()-1209600)):
            $tempdirs[$key] = "";
            unset($tempdirs[$key]);
        elseif (strchr($value, 'previewtmp')):
            unset($tempdirs[$key]);
        endif;
    endforeach;
endif;

echo "<pre>";
var_export($tempdirs);
foreach ($tempdirs AS $tv) {
    var_export(deleteFolder('/'.WSP_DIR.'/tmp/'.$tv, false));
    echo "<hr />";
}
echo "</pre>";

echo "</body></html>";

// EOF