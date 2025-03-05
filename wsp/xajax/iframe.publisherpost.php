<?php
/**
 * publisher post
 * @author stefan@covi.de
 * @since 7.0
 * @version 7.0
 * @lastchange 2020-03-11
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("../data/include/usestat.inc.php");
require ("../data/include/globalvars.inc.php");
// define actual system position - not required
// second includes ---------------------------
require ("../data/include/checkuser.inc.php");
require ("../data/include/errorhandler.inc.php");
require ("../data/include/siteinfo.inc.php");
/* page specific includes */

/* define page specific vars ----------------- */
$publisher = array(); // holding all items returned to parent

/* define page specific functions ------------ */
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='' && DEFINED('DOCUMENT_ROOT')) {
    if (isset($_POST) && is_array($_POST) && count($_POST)>0) {
        
        // remove OWN queued files
        if (isset($_POST) && array_key_exists('op', $_POST) && $_POST['op']=="clearqueue") {
            $cpsql = doSQL("DELETE FROM `wspqueue` WHERE `uid` = ".intval($_SESSION['wspvars']['userid'])." AND `done` = 0");
            if ($cpsql['aff']>0) {
                addWSPMsg('resultmsg', returnIntLang('queue cleared', false));
            }
        }
        
        // remove ALL queued files (only for admin accounts)
        else if (isset($_POST) && array_key_exists('op', $_POST) && $_POST['op']=="clearallqueues" && $_SESSION['wspvars']['usertype']==1) {
            $cpsql = doSQL("DELETE FROM `wspqueue` WHERE `done` = 0");
            if ($cpsql['aff']>0) {
                addWSPMsg('resultmsg', returnIntLang('queue all cleared', false));
            }
        };
        
        if (array_key_exists('publishitem', $_POST) && is_array($_POST['publishitem']) && count($_POST['publishitem'])>0) {
            $setuptime = time();
            $pubid = $_POST['publishitem'];
            if ($_POST['publishsubs']==1) {
                $tmppubid = array();
                foreach ($pubid AS $pvalue) {
                    $tmppubid = array_merge($tmppubid, subpMenu($pvalue));
                }
                $pubid = array_merge($pubid, $tmppubid);
                unset($tmppubid);
            }
            array_unique($pubid);

            // find ... if structure is in publishing mode
            if(array_key_exists('op', $_POST) && is_array($_POST['op']) && in_array("structure", $_POST['op'])) {
                $tmppubid = $pubid;
                $struc_pubid = array();
                foreach ($tmppubid AS $pvalue) {		
                    if (getChangeStat($pvalue) == 4 || getChangeStat($pvalue) == 5 || getChangeStat($pvalue) == 7) {
                        // 7 = rename file
                        $emp_tmp = getAffectedMID($pvalue);
                        if(count($emp_tmp)>0) {
                            foreach($emp_tmp AS $emp) {
                                if($emp!="" && $emp!=$pvalue) {
                                    array_push($struc_pubid,$emp);
                                }
                            }
                        }
                    }
                }

                // Alle abhängigen MPs werden in die Warteschlange eingetragen
                if(count($struc_pubid)>0) {
                    foreach ($struc_pubid AS $strucvalue) {
                        if ($strucvalue>0) {
                            if (array_key_exists('publishlang', $_POST) && is_array($_POST['publishlang'])) {
                                foreach ($_POST['publishlang'] AS $plk => $plv) {
                                    $pubsql = "DELETE FROM `wspqueue` WHERE `uid` = ".intval($_SESSION['wspvars']['userid'])." AND `action` = 'publishstructure' AND `param` = '".intval($strucvalue)."' AND `lang` = '".trim($plv)."' AND `done` = 0";
                                    doSQL($pubsql);
                                    $pubsql = "REPLACE INTO `wspqueue` SET `uid` = ".intval($_SESSION['wspvars']['userid']).", `set` = '".$setuptime."', `action` = 'publishstructure', `param` = '".intval($strucvalue)."', `timeout` = 0, `done` = 0, `priority` = 1, `outputuid` = ".intval($_SESSION['wspvars']['userid']).", `lang` = '".trim($plv)."', `output` = ''";
                                    if (!(doSQL($pubsql))) {
                                        addWSPMsg('errormsg', returnIntLang('publisher error setup queue'));
                                    }
                                }
                            }
                            else {
                                $publishlang = unserializeBroken($_SESSION['wspvars']['sitelanguages']);
                                foreach ($publishlang['languages']['shortcut'] AS $sk => $sv) {
                                    $pubsql = "DELETE FROM `wspqueue` WHERE `uid` = ".intval($_SESSION['wspvars']['userid'])." AND `action` = 'publishstructure' AND `param` = '".intval($strucvalue)."' AND `lang` = '".trim($sv)."' AND `done` = 0";
                                    doSQL($pubsql);
                                    $pubsql = "REPLACE INTO `wspqueue` SET `uid` = ".intval($_SESSION['wspvars']['userid']).", `set` = '".$setuptime."', `action` = 'publishstructure', `param` = '".intval($strucvalue)."', `timeout` = 0, `done` = 0, `priority` = 1, `outputuid` = ".intval($_SESSION['wspvars']['userid']).", `lang` = '".trim($sv)."', `output` = ''";
                                    if (!(doSQL($pubsql))) {
                                        addWSPMsg('errormsg', returnIntLang('publisher error setup queue'));
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            // run publisher ids
            foreach ($pubid AS $pvalue) {
                // different publish modes
                if (array_key_exists('op', $_POST) && is_array($_POST['op']) && in_array("structure", $_POST['op']) && !(in_array("content", $_POST['op'])) && !(in_array("force", $_POST['op']))) {
                    // only structure publishing
                    $cccheck_sql = "SELECT `mid` FROM `menu` WHERE `mid` = ".intval($pvalue)." AND `contentchanged` = 7";
                    $cccheck_res = doSQL($cccheck_sql);
                    if ($cccheck_res['num']>0) {
                        $publishaction = 'renamestructure';
                    }
                    else {
                        $publishaction = 'publishstructure';
                    }
                }
                else if (array_key_exists('op', $_POST) && is_array($_POST['op']) && in_array("content", $_POST['op']) && !(in_array("structure", $_POST['op']))) {
                    // remove pages without changed contents
                    $cccheck_sql = "SELECT `mid` FROM `menu` WHERE `mid` = ".intval($pvalue)." AND (`contentchanged` = 2 || `contentchanged` = 3 || `contentchanged` = 5)";
                    $cccheck_res = doSQL($cccheck_sql);
                    if ($cccheck_res['num']>0) {
                        $publishaction = 'publishcontent';
                    }
                    else {
                        $pvalue = 0;
                    }
                }
                else if (array_key_exists('op', $_POST) && is_array($_POST['op']) && in_array("force", $_POST['op']) && !(in_array("structure", $_POST['op']))) {
                    $publishaction = 'publishcontent';
                }
                else if (array_key_exists('op', $_POST) && is_array($_POST['op']) && in_array("structure", $_POST['op']) && in_array("content", $_POST['op'])) {
                    // remove pages without changed contents 
                    $cccheck_sql = "SELECT `mid` FROM `menu` WHERE `mid` = ".intval($pvalue)." AND (`contentchanged` = 2 || `contentchanged` = 3 || `contentchanged` = 5)";
                    $cccheck_res = doSQL($cccheck_sql);
                    if ($cccheck_res['num']>0) {
                        $publishaction = 'publishitem';
                    }
                    else {
                        $publishaction = 'publishstructure';
                    }
                }
                elseif (array_key_exists('op', $_POST) && is_array($_POST['op']) && in_array("structure", $_POST['op']) && in_array("force", $_POST['op'])) {
                    $publishaction = 'publishitem';
                }
                
                addWSPMsg('devmsg', 'publisher was setup to '.$publishaction);

                if ($pvalue>0) {
                    // setup queue
                    if (array_key_exists('publishlang', $_POST) && is_array($_POST['publishlang'])) {
                        foreach ($_POST['publishlang'] AS $plk => $plv) {
                            $pucnum = getNumSQL("SELECT `id` FROM `wspqueue` WHERE `action` = '".$publishaction."', `param` = '".intval($pvalue)."' AND `lang` = '".trim($plv)."' AND `done` = 0");
                            if (intval($pucnum)==0) {
                                $pubsql = doSQL("INSERT INTO `wspqueue` SET `uid` = ".intval($_SESSION['wspvars']['userid']).", `set` = '".$setuptime."', `action` = '".$publishaction."', `param` = '".intval($pvalue)."', `timeout` = 0, `done` = 0, `priority` = 0, `outputuid` = ".intval($_SESSION['wspvars']['userid']).", `lang` = ".isset($plv)?"'".trim($plv)."'":'NULL'.", `output` = 'NULL'");
                                if (!($pubsql['res'])) {
                                    addWSPMsg('errormsg', returnIntLang('publisher error setup queue'));
                                }
                            }
                        }
                    }
                    else {
                        $publishlang = unserializeBroken($_SESSION['wspvars']['sitelanguages']);
                        if (is_array($publishlang) && count($publishlang)>0) {
                            foreach ($publishlang['shortcut'] AS $sk => $sv) {
                                $pucnum = getNumSQL("SELECT `id` FROM `wspqueue` WHERE `action` = '".$publishaction."', `param` = '".intval($pvalue)."' AND `lang` ".(isset($plv)?" = '".trim($plv)."'":' IS NULL ')." AND `done` = 0");
                                if (intval($pucnum)==0) {
                                    $pubsql = doSQL("INSERT INTO `wspqueue` SET `uid` = ".intval($_SESSION['wspvars']['userid']).", `set` = '".$setuptime."', `action` = '".$publishaction."', `param` = '".intval($pvalue)."', `timeout` = 0, `done` = 0, `priority` = 1, `outputuid` = ".intval($_SESSION['wspvars']['userid']).", `lang` = ".(isset($plv)?"'".trim($plv)."'":'NULL').", `output` = 'NULL'");
                                    if (!($pubsql['res'])) {
                                        addWSPMsg('errormsg', returnIntLang('publisher error setup queue'));
                                    }
                                }
                            }
                        }
                    }
                }
            }
            addWSPMsg('noticemsg', returnIntLang('publisher added files to queue'));
        }

        // publish css
        if (array_key_exists('publishcss', $_POST) && is_array($_POST['publishcss']) && count($_POST['publishcss'])>0) {
            foreach ($_POST['publishcss'] AS $pvalue) {
                $pucnum = getNumSQL("SELECT `id` FROM `wspqueue` WHERE `action` = 'publishcss', `param` = '".intval($pvalue)."' AND `done` = 0");
                if (intval($pucnum)==0) {
                    $pubsql = doSQL("INSERT INTO `wspqueue` SET `uid` = ".intval($_SESSION['wspvars']['userid']).", `set` = '".time()."', `action` = 'publishcss', `param` = '".intval($pvalue)."', `timeout` = 0, `done` = 0, `priority` = 1, `outputuid` = ".intval($_SESSION['wspvars']['userid']).", `output` = ''");
                    if ($pubsql['inf']>0) {
//                        $publisher[] = 'css'.intval($pvalue);
                    }
                    else {
                        addWSPMsg('errormsg', returnIntLang('publisher error adding css files'));
                    }
                }
            }
            addWSPMsg('noticemsg', returnIntLang('publisher added css files to queue'));
        }

        // publish javascript
        if (array_key_exists('publishjs', $_POST) && is_array($_POST['publishjs']) && count($_POST['publishjs'])>0) {
            foreach ($_POST['publishjs'] AS $pvalue) {
                $pub_sql = "INSERT INTO `wspqueue` SET `uid` = ".intval($_SESSION['wspvars']['userid']).", `set` = '".time()."', `action` = 'publishjs', `param` = '".intval($pvalue)."', `timeout` = 0, `done` = 0, `priority` = 1, `outputuid` = ".intval($_SESSION['wspvars']['userid']).", `output` = ''";
                $pub_res = doSQL($pub_sql);
                if ($pub_res['inf']>0) {
                    $publisher[] = 'js'.intval($pvalue);
                }
                else {
                    addWSPMsg('errormsg', returnIntLang('publisher error adding js files'));
                }
            }
            addWSPMsg('noticemsg', returnIntLang('publisher added js files to queue'));
        }

        // publish rss
        if (array_key_exists('publishrss', $_POST) && is_array($_POST['publishrss']) && count($_POST['publishrss'])>0) {
            foreach ($_POST['publishrss'] AS $pvalue) {
                $pubsql = "INSERT INTO `wspqueue` SET `uid` = ".intval($_SESSION['wspvars']['userid']).", `set` = '".time()."', `action` = 'publishrss', `param` = '".intval($pvalue)."', `timeout` = 0, `done` = 0, `priority` = 1, `outputuid` = ".intval($_SESSION['wspvars']['userid']).", `output` = ''";
                $pub_res = doSQL($pub_sql);
                if ($pub_res['inf']>0) {
                    $publisher[] = 'rss'.intval($pvalue);
                }
                else {
                    addWSPMsg('errormsg', returnIntLang('publisher error adding rss files'));
                }
            }
            addWSPMsg('noticemsg', returnIntLang('publisher added rss files to queue'));
        }

        var_export(showWSPMsg(2));
        die();
        
        echo "<script type='text/javascript'>\n";
        if (count($publisher)>0) { echo "parent.updatePublish(".json_encode($publisher).");\n"; }
        echo "parent.updateQueue(".intval(getWSPqueue()).");\n";
        echo "parent.callBackgroundPublish();";
        echo "</script>\n";
    }
} else {
    echo "<pre>no direct access allowed</pre>";
}

// EOF