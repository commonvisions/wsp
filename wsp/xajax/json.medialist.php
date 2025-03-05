<?php
/**
 * ...
 * @author stefan@covi.de
 * @since 6.0
 * @version 7.0
 * @lastchange 2021-06-02
 */

if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!=''):
    session_start();
    require("../data/include/globalvars.inc.php");
    require("../data/include/errorhandler.inc.php");
    require("../data/include/siteinfo.inc.php");

    header('Cache-Control: no-cache');
    header('Content-Type: application/json');

    echo json_encode(mediaJSON('/media/images/', '/media/images/', false, array(), false, 20));

else:
    echo json_encode('error');
endif;

// EOF