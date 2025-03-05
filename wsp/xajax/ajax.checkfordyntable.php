<?php
/**
 * ...
 * @author stefan@covi.de
 * @since 6.0
 * @version 7.0
 * @lastchange 2019-07-24
 */
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='') {
    session_start();
    require("../data/include/globalvars.inc.php");
    require("../data/include/errorhandler.inc.php");
    require("../data/include/siteinfo.inc.php");
//    if (isset($_SESSION['userid']) && intval($_SESSION['userid'])>0 && isset($_SESSION['usertype']) && intval($_SESSION['usertype'])>0) {
        // if request is not empty
        
        /*
        // generic reading of table
        if (isset($_REQUEST['table']) && trim($_REQUEST['table'])!='') {
            $tablefields = array();
            $tf_sql = "DESCRIBE `".escapeSQL(trim($_REQUEST['table']))."`";
            $tf_res = doSQL($tf_sql);
            if ($tf_res['num']>0) { 
                foreach ($tf_res['set'] AS $tfk => $tfv) {
                    $tablefields[] = $tfv['Field'];
                }
            }
            echo json_encode($tablefields);
        }
        */
    
        // get dynamic fields by module configuration
        if (isset($_REQUEST['table']) && trim($_REQUEST['table'])!='') {
            $tablefields = array();
            
            $ts_sql = "SELECT `id`, `name`, `dynamiccontent` FROM `modules` WHERE `dynamiccontent` LIKE '%".escapeSQL(trim($_REQUEST['table']))."%'";
            $ts_res = doSQL($ts_sql);
            if ($ts_res['num']>0) {
                foreach ($ts_res['set'] AS $tsk => $tsv) {
                    $dynamic = unserializeBroken($tsv['dynamiccontent']);
                    foreach ($dynamic AS $dck => $dcv) {
                        if ($dck==trim($_REQUEST['table'])) {
                            $tablefields = $dcv;
                        }
                    }
                }
            }
            echo json_encode($tablefields);
        }
    
//    }
}
?>