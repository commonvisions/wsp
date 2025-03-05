<?php
/**
 * 
 * @author stefan@covi.de
 * @since 7.0
 * @version 7.0
 * @lastchange 2019-03-14
 */
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='') {
    session_start();
    require("../data/include/globalvars.inc.php");
    require("../data/include/errorhandler.inc.php");
    require("../data/include/siteinfo.inc.php");
    if (isset($_REQUEST['structure']) && trim($_REQUEST['structure'])!='') {
        setStructure(json_decode(trim($_REQUEST['structure']), true));
    }
}
else {
    echo "<pre>no direct access allowed</pre>";
}

// EOF ?>