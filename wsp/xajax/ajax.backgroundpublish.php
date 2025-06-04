<?php
/**
 * @author stefan@covi.de
 * @since 6.0
 * @version GIT
 * 
 * 2025-02-14
 * fixed bug creating directory w/o ftp
 * 
 */

if (!empty($_SERVER['HTTP_REFERER'] ?? null)) {
	session_start();
	$wspdir = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".($_SESSION['wspvars']['wspbasediradd'] ?? "")."/".($_SESSION['wspvars']['wspbasedir'] ?? "")));

	include $wspdir."/data/include/globalvars.inc.php";
	include $wspdir."/data/include/wsplang.inc.php";
	include $wspdir.'/data/include/errorhandler.inc.php';
	include $wspdir.'/data/include/funcs.inc.php';
	include $wspdir.'/data/include/dbaccess.inc.php';
	if (file_exists($wspdir."/data/include/ftpaccess.inc.php")) include $wspdir."/data/include/ftpaccess.inc.php";
	include $wspdir."/data/include/filesystemfuncs.inc.php";
	include $wspdir."/data/include/menuparser.inc.php";
	include $wspdir."/data/include/fileparser.inc.php";
    
    // get time to put action to the end of the queue
    $queue_sql = "SELECT MAX(`timeout`) AS `time` FROM `wspqueue` WHERE `done` = 0";
	$queue_res = doSQL($queue_sql);
    $queue_res = ($queue_res['num']>0)?((intval($queue_res['set'][0]['time'])>0)?intval($queue_res['set'][0]['time']):time()):time();

    // find LONG TERM open entries (that has to be done ONLY after publishing OTHER points)
    $longtermpub_sql = "SELECT * FROM `wspqueue` WHERE `done` = -1";
	$longtermpub_res = doSQL($longtermpub_sql);
    
    // find open entries to do
	$publish_sql = "SELECT * FROM `wspqueue` WHERE `done` = 0 AND `timeout` <= ".time()." ORDER BY `timeout` ASC, `priority` DESC, `action` ASC, `set` ASC, `id` ASC LIMIT 0,10";
	$publish_res = doSQL($publish_sql);
    
	if ($publish_res['num']>0) {

        // do ftp connect to establish only ONE ftp-connection while publishing
        $ftp = false; $ftpt = 0; $usedirect = false;
        if (isset($_SESSION['wspvars']['directwriting']) && $_SESSION['wspvars']['directwriting']===true) {
            $usedirect = true;
        }
        else {
            while ($ftp===false && $ftpt<3) {
                $ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])));
                if ($ftp!==false) {
                    if (!ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass'])) { 
                        $ftp = false; 
                    }
                }
                if (isset($_SESSION['wspvars']['ftppasv']) && $ftp!==false) { 
                    ftp_pasv($ftp, $_SESSION['wspvars']['ftppasv']); 
                }
                $ftpt++;
            }
        }

        if (isset($_SESSION['wspvars']['logdevmsg']) && $_SESSION['wspvars']['logdevmsg']) {
            error_log(date('Y-m-d H:i:s') . ' ——————————————— ' . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
            error_log(date('Y-m-d H:i:s') . ' backgroundpublish : publish entries ' . $publish_res['num'] . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
            error_log(date('Y-m-d H:i:s') . ' backgroundpublish : ftp connect ' . var_export(($ftp!==false), true) . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
        }
        
        if ($ftp!==false || $usedirect===true) {
            foreach ($publish_res['set'] AS $prsk => $prsv) {

                $newendmenu = false; if($publish_res['num']==1): $newendmenu = true; endif;

                $timeline_sql = "UPDATE `wspqueue` SET `timeout` = ".(60+intval($queue_res))." WHERE `id` = ".intval($prsv['id']);
                $timeline_res = doSQL($timeline_sql);

                if (trim($prsv['action'])=='publishitem') {
                    
                    if (isset($_SESSION['wspvars']['logdevmsg']) && $_SESSION['wspvars']['logdevmsg']) {
                        error_log(date('Y-m-d H:i:s') . ' backgroundpublish : publishitem ' . intval($prsv['param']) . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
                    }

                    // update long term elements to be published
                    // only if structure is affected
                    $longtermpub_sql = "UPDATE `wspqueue` SET `timeout` = ".(60+intval($queue_res)).", `lang` = '".$prsv['lang']."' WHERE `done` = -1 AND `priority` = ".intval($prsv['param']);
                    $longtermpub_res = doSQL($longtermpub_sql);
                    
                    // include base cls class definition
                    if (is_file(str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/clsinterpreter.inc.php"))))) {
                        include(str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/clsinterpreter.inc.php"))));
                    }
                    // call publisher function
                    $returnpublish = publishSites(intval($prsv['param']), 'publish', $prsv['lang'], $newendmenu);
                }
                elseif (trim($prsv['action'])=='publishcontent') {

                    if (isset($_SESSION['wspvars']['logdevmsg']) && $_SESSION['wspvars']['logdevmsg']) {
                        error_log(date('Y-m-d H:i:s') . ' backgroundpublish : publishcontent ' . intval($prsv['param']) . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
                    }

                    // include base cls class definition
                    if (is_file(str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/clsinterpreter.inc.php"))))) {
                        include(str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/clsinterpreter.inc.php"))));
                    }
                    // call publisher function
                    $returnpublish = publishSites(intval($prsv['param']), 'publish', $prsv['lang'], $newendmenu);
                }
                elseif (trim($prsv['action'])=='publishstructure') {

                    if (isset($_SESSION['wspvars']['logdevmsg']) && $_SESSION['wspvars']['logdevmsg']) {
                        error_log(date('Y-m-d H:i:s') . ' backgroundpublish : publishstructure ' . intval($prsv['param']) . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
                    }

                    // update long term elements to be published
                    // only if structure is affected
                    $longtermpub_sql = "UPDATE `wspqueue` SET `timeout` = ".(60+intval($queue_res)).", `done` = 0, `lang` = '".$prsv['lang']."' WHERE `done` = -1 AND `priority` = ".intval($prsv['param']);
                    $longtermpub_res = doSQL($longtermpub_sql);

                    // include base cls class definition
                    if (is_file(str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/clsinterpreter.inc.php"))))) {
                        include(str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/clsinterpreter.inc.php"))));
                    }
                    // call publisher function
                    $returnpublish = publishMenu(intval($prsv['param']), 'publish', $prsv['lang'], $newendmenu, false);
                }
                elseif (trim($prsv['action'])=='renamestructure') {

                    if (isset($_SESSION['wspvars']['logdevmsg']) && $_SESSION['wspvars']['logdevmsg']) {
                        error_log(date('Y-m-d H:i:s') . ' backgroundpublish : renamestructure ' . intval($prsv['param']) . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
                    }

                    // update long term elements to be published
                    // only if structure is affected
                    $longtermpub_sql = "UPDATE `wspqueue` SET `timeout` = ".(60+intval($queue_res)).", `done` = 0, `lang` = '".$prsv['lang']."' WHERE `done` = -1 AND `priority` = ".intval($prsv['param']);
                    $longtermpub_res = doSQL($longtermpub_sql);

                    // include base cls class definition
                    if (is_file(str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/clsinterpreter.inc.php"))))) {
                        include(str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/clsinterpreter.inc.php"))));
                    }
                    // call publisher function
                    $returnpublish = publishMenu(intval($prsv['param']), 'publish', $prsv['lang'], $newendmenu, true);
                }
                elseif (trim($prsv['action'])=='publishcss') {
                    if (isset($_SESSION['wspvars']['logdevmsg']) && $_SESSION['wspvars']['logdevmsg']) {
                        error_log(date('Y-m-d H:i:s') . ' backgroundpublish : publishcss ' . intval($prsv['param']) . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
                    }
                    require_once ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/cssparser.inc.php");
                    $returnpublish = publishCSS(intval($prsv['param']), ($ftp ?? false), ($usedirect ?? false));
                }
                elseif (trim($prsv['action'])=='publishjs') {
                    if (isset($_SESSION['wspvars']['logdevmsg']) && $_SESSION['wspvars']['logdevmsg']) {
                        error_log(date('Y-m-d H:i:s') . ' backgroundpublish : publishjs ' . intval($prsv['param']) . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
                    }
                    require_once ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/jsparser.inc.php");
                    $returnpublish = publishJS(intval($prsv['param']), ($ftp ?? false), ($usedirect ?? false));
                }
                elseif (trim($prsv['action'])=='publishrss') {
                    if (isset($_SESSION['wspvars']['logdevmsg']) && $_SESSION['wspvars']['logdevmsg']) {
                        error_log(date('Y-m-d H:i:s') . ' backgroundpublish : publishrss ' . intval($prsv['param']) . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
                    }
                    // call publisher function
                    require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/rssparser.inc.php");
                    $returnpublish = publishRSS(intval($prsv['param']), ($ftp ?? false), ($usedirect ?? false));
                }

                if (isset($_SESSION['wspvars']['logdevmsg']) && $_SESSION['wspvars']['logdevmsg']) {
                    error_log(date('Y-m-d H:i:s') . ' backgroundpublish : returnpublish ' . var_export($returnpublish, true) . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
                }

                // updating queue to published
                if (isset($returnpublish) && $returnpublish===true) {
                    $timeline_sql = "UPDATE `wspqueue` SET `done` = ".time().", `priority` = 0 WHERE `id` = ".intval($prsv['id']);
                    $timeline_res = doSQL($timeline_sql);
                }

                // check for elements in queue
                $restqueue_sql = "SELECT `id` FROM `wspqueue` WHERE `uid` = ".intval($_SESSION['wspvars']['userid'])." AND done = 0";
                $restqueue_res = doSQL($restqueue_sql);
                if ($restqueue_res['num']==0) {
                    // update long term elements to be published
                    // only if structure is affected
                    $longtermpub_sql = "UPDATE `wspqueue` SET `timeout` = 0, `done` = 0 WHERE `uid` = ".intval($_SESSION['wspvars']['userid'])." AND `done` = -1";
                    $longtermpub_res = doSQL($longtermpub_sql);
                }

            }
        } else {
            addWSPMsg('errormsg', 'publisher could not connect');
        }
    }

	$queuedone_sql = "UPDATE `wspqueue` SET `outputuid` = 0, `priority` = -1 WHERE `done` > 0 AND `priority` != -1 AND `outputuid` = ".intval($_SESSION['wspvars']['userid'])." AND `timeout` < ".(time()-180);
	doSQL($queuedone_sql);
    
}

// EOF ?>