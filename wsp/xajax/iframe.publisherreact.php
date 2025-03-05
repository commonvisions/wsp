<?php
/**
 * publisher post
 * @author stefan@covi.de
 * @since 7.0
 * @version 7.0
 * @lastchange 2020-03-10
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("../data/include/usestat.inc.php");
require ("../data/include/globalvars.inc.php");
// define actual system position - not required for ajax and iframe
// second includes ---------------------------
require ("../data/include/checkuser.inc.php");
require ("../data/include/errorhandler.inc.php");
require ("../data/include/siteinfo.inc.php");
/* page specific includes */

if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='') {
    if (isset($_POST) && is_array($_POST) && isset($_POST['priojobid'])) {
        doSQL("UPDATE `wspqueue` SET `timeout` = 0, `priority` = (`priority`+1) WHERE `id` = ".intval($_POST['priojobid']));
        echo "<script> parent.cT = 1000; </script>";
        echo "<script> parent.addPMsg('priorised item ".intval($_POST['priojobid'])."'); </script>\n";
    } else if (isset($_POST) && is_array($_POST) && isset($_POST['bp']) && $_POST['bp']=='true') {
        $publish_sql = "SELECT `id`, `action`, `param`, `lang` FROM `wspqueue` WHERE `done` = 0 AND `timeout` < ".time()." ORDER BY `priority` DESC, `set` ASC LIMIT 0, 5";
        $publish_res = doSQL($publish_sql);
        if ($publish_res['num']>0) {
            
            $con = false;
            if (isset($_SESSION['wspvars']['ftp']) && $_SESSION['wspvars']['ftp']!==false) {
                $con = true;
            } else if (isset($_SESSION['wspvars']['srv']) && $_SESSION['wspvars']['srv']!==false) {
                $con = true;
            }

            if ($con===true) {
                foreach ($publish_res['set'] AS $presk => $presv) {
				    // update end_menu-table if less than 5 entries are given
                    $newendmenu = ($publish_res['num']<5)?true:false;
                    
                    // updating timeout to prevent endless loops with the same file
                    doSQL("UPDATE `wspqueue` SET `timeout` = ".(time()+60).", `priority` = 0 WHERE `id` = ".intval($publish_res['set'][0]['id']));
                    // logging?
                    if (isset($_POST['log']) && $_POST['log']=='true') {
                        echo "<script> parent.addPMsg('".$publish_res['set'][0]['action']." ID: ".$publish_res['set'][0]['id']." | PARAM: ".$publish_res['set'][0]['param']." | LANG: ".$publish_res['set'][0]['lang']."'); </script>\n";
                    }
                    
                    if ($presv['action']=='publishitem') {
                        // include base cls class definition
                        require ("../data/include/fileparser.inc.php");
                        require ("../data/include/menuparser.inc.php");
                        require ("../data/include/clsinterpreter.inc.php");
                        // call publisher function
                        $returnpublish = publishSites(intval($presv['param']), 'publish', trim($presv['lang']), $newendmenu, true);
                        if ($returnpublish===true) {
                            doSQL("UPDATE `wspqueue` SET `done` = ".time().", `priority` = 0 WHERE `id` = ".intval($presv['id']));
                            doSQL("UPDATE `menu` SET `contentchanged` = 0, `structurechanged` = 0, `lastpublish` = ".time()." WHERE `mid` = ".intval($presv['param']));
                        }
                    }
                    else if ($presv['action']=='publishcontent') {
                        // include base cls class definition
                        require ("../data/include/fileparser.inc.php");
                        require ("../data/include/menuparser.inc.php");
                        require ("../data/include/clsinterpreter.inc.php");
                        // call publisher function
                        $returnpublish = publishSites(intval($presv['param']), 'publish', trim($presv['lang']), $newendmenu, true);
                        if ($returnpublish===true) {
                            doSQL("UPDATE `wspqueue` SET `done` = ".time().", `priority` = 0 WHERE `id` = ".intval($presv['id']));
                            doSQL("UPDATE `menu` SET `contentchanged` = 0, `lastpublish` = ".time()." WHERE `mid` = ".intval($presv['param']));
                        }
                    }
                    else if ($presv['action']=='publishstructure') {
                        // include base cls class definition
                        require ("../data/include/fileparser.inc.php");
                        require ("../data/include/menuparser.inc.php");
                        require ("../data/include/clsinterpreter.inc.php");
                        // call publisher function
                        $returnpublish = publishMenu(intval($presv['param']), 'publish', trim($presv['lang']), $newendmenu, false);
                        if ($returnpublish===true) {
                            doSQL("UPDATE `wspqueue` SET `done` = ".time().", `priority` = 0 WHERE `id` = ".intval($presv['id']));
                            doSQL("UPDATE `menu` SET `structurechanged` = 0, `lastpublish` = ".time()." WHERE `mid` = ".intval($presv['param']));
                        }
                    }
                    else if ($presv['action']=='renamestructure') {
                        // include base cls class definition
                        require ("../data/include/fileparser.inc.php");
                        require ("../data/include/menuparser.inc.php");
                        require ("../data/include/clsinterpreter.inc.php");
                        // call publisher function
                        $returnpublish = publishMenu(intval($presv['param']), 'publish', trim($presv['lang']), $newendmenu, true);
                        if ($returnpublish===true) {
                            doSQL("UPDATE `wspqueue` SET `done` = ".time().", `priority` = 0 WHERE `id` = ".intval($presv['id']));
                        }
                    }
                    else if ($presv['action']=='publishcss') {
                        require_once ("../data/include/cssparser.inc.php");
                        $returnpublish = publishCSS(intval($presv['param']), true);
                        if ($returnpublish===true) {
                            doSQL("UPDATE `wspqueue` SET `done` = ".time().", `priority` = 0 WHERE `id` = ".intval($presv['id']));
                        }
                    }
                    else if ($presv['action']=='publishjs') {
                        require_once ("../data/include/jsparser.inc.php");
                        $returnpublish = publishJS(intval($presv['param']), true);
                        if ($returnpublish===true) {
                            doSQL("UPDATE `wspqueue` SET `done` = ".time().", `priority` = 0 WHERE `id` = ".intval($presv['id']));
                        }
                    }
                    else if ($presv['action']=='publishrss') {
                        // call publisher function
                        require ("../data/parser/rssparser.inc.php");
                        $returnpublish = publishRSS(intval($presv['param']), true);
                        if ($returnpublish===true) {
                            doSQL("UPDATE `wspqueue` SET `done` = ".time().", `priority` = 0 WHERE `id` = ".intval($presv['id']));
                        }
                    }
                    else {
                        echo "<script> parent.cT = 60000; </script>";
                    }
                    
                    $qnum = intval(getWSPqueue());
                    echo "<script> parent.updateQueue(".$qnum."); </script>\n";
                    echo "<script> parent.removeQueue(".intval($presv['id'])."); </script>\n";
                    if ($qnum>0) {
                        if (isset($_POST['log']) && $_POST['log']=='true') {
                            echo "<script> parent.addPMsg('".$qnum." items left in queue'); </script>\n";
                        }
                        echo "<script> parent.cT = 5000; </script>";
                    } else {
                        echo "<script> parent.cT = 604800000; </script>";
                    }
                }
            }
            else {
                echo "<script> parent.addPMsg('publishing connection could not be established'); </script>\n";
                echo "<script> parent.cT = 60000; </script>";
            }
        } else {
            echo "<script> parent.cT = 60000; </script>";
        }
    } else {
        echo "<pre>error fetching data</pre>";
        echo "<script> parent.cT = 604800000; </script>";
    }
} else {
    echo "<pre>no direct access allowed</pre>";
}

// EOF