<?php
/**
 * ...
 * @author stefan@covi.de
 * @since 6.0
 * @version 7.0
 * @lastchange 2021-06-02
 */

 session_start();

if (isset($_REQUEST['panel']) && trim(filter_var($_REQUEST['panel'], FILTER_SANITIZE_URL))!='') {
    if (isset($_REQUEST['stat']) && intval($_REQUEST['stat'])>0) {
        $_SESSION['wspvars']['panelstat'][trim(filter_var($_REQUEST['panel'], FILTER_SANITIZE_URL))] = 1;
    } else {
        $_SESSION['wspvars']['panelstat'][trim(filter_var($_REQUEST['panel'], FILTER_SANITIZE_URL))] = 0;
    }
}
