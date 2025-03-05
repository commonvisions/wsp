<?php
/**
 * updating the widget show properties
 * @author stefan@covi.de
 * @since 6.0
 * @version 7.0
 * @lastchange 2019-03-08
 */
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='') {
    session_start();
    require ("../data/include/usestat.inc.php");
    require ("../data/include/globalvars.inc.php");
    $val = array('true'=>1,'false'=>0);
    doSQL("DELETE FROM `wspproperties` WHERE `varname` = 'widget_".escapeSQL(trim($_REQUEST['widget']))."'");
    $res = doSQL("INSERT INTO `wspproperties` SET `varname` = 'widget_".escapeSQL(trim($_REQUEST['widget']))."', `varvalue` = '".intval($val[$_REQUEST['val']])."'");
    return var_export($res);
}
?>