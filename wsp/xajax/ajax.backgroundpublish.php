<?php
/**
 * publishing files in background
 * @author stefan@covi.de
 * @copyright (c) 2021, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 7.0
 * @lastchange 2021-03-02
 */

session_start();

die('bgp is not supported in wsp7');

if (isset($_SESSION['wspvars'])) {

	include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/globalvars.inc.php";
	include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/wsplang.inc.php";
	include $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/errorhandler.inc.php';
	include $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/funcs.inc.php';
	include $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/dbaccess.inc.php';
	include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/ftpaccess.inc.php";
	include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/filesystemfuncs.inc.php";
	include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/menuparser.inc.php";
	include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/fileparser.inc.php";
    
    // get time to put action to the end of the queue
    $queue_sql = "SELECT MAX(`timeout`) AS `time` FROM `wspqueue` WHERE `done` = 0";
	$queue_res = doSQL($queue_sql);
    $queue_res = ($queue_res['num']>0)?((intval($queue_res['set'][0]['time'])>0)?intval($queue_res['set'][0]['time']):time()):time();

    // find LONG TERM open entries (that has to be done ONLY after publishing OTHER points)
    $longtermpub_sql = "SELECT * FROM `wspqueue` WHERE `done` = -1";
	$longtermpub_res = doSQL($lontermpub_sql);
    
    // find open entries to do
	$publish_sql = "SELECT * FROM `wspqueue` WHERE `done` = 0 AND `timeout` <= ".time()." ORDER BY `timeout` ASC, `priority` DESC, `action` ASC, `set` ASC, `id` ASC LIMIT 0,10";
	$publish_res = doSQL($publish_sql);
    
	if ($publish_res['num']>0) {

        $ftp = false; $srv = false;
        // do ftp connect to establish only ONE ftp-connection while publishing
        if (isset($_SESSION['wspvars']['ftp']) && $_SESSION['wspvars']['ftp']!==false) {
            $ftp = doFTP();
        } else if (isset($_SESSION['wspvars']['srv']) && $_SESSION['wspvars']['srv']!==false) {
            $srv = true;
        }

        if ($ftp!==false || $srv===true) {
            foreach ($publish_res['set'] AS $prsk => $prsv) {

                $newendmenu = false; if($publish_res['num']==1): $newendmenu = true; endif;

                $timeline_sql = "UPDATE `wspqueue` SET `timeout` = ".(60+intval($queue_res))." WHERE `id` = ".intval($prsv['id']);
                $timeline_res = doSQL($timeline_sql);

                if (trim($prsv['action'])=='publishitem') {
                    
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
                } else if (trim($prsv['action'])=='publishcontent') {
                    // include base cls class definition
                    if (is_file(str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/clsinterpreter.inc.php"))))) {
                        include(str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/clsinterpreter.inc.php"))));
                    }
                    // call publisher function
                    $returnpublish = publishSites(intval($prsv['param']), 'publish', $prsv['lang'], $newendmenu);
                } else if (trim($prsv['action'])=='publishstructure') {

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
                } else if (trim($prsv['action'])=='renamestructure') {

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
                } else if (trim($prsv['action'])=='publishcss') {
                    require_once ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/cssparser.inc.php");
                    $returnpublish = publishCSS(intval($prsv['param']), $ftp);
                } else if (trim($prsv['action'])=='publishjs') {
                    require_once ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/jsparser.inc.php");
                    $returnpublish = publishJS(intval($prsv['param']), $ftp);
                } else if (trim($prsv['action'])=='publishrss') {
                    // call publisher function
                    require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/rssparser.inc.php");
                    $returnpublish = publishRSS(intval($prsv['param']), $ftp);
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
            addWSPMsg('errormsg', 'publisher could not connect to system');
        }
    }

	$queuedone_sql = "UPDATE `wspqueue` SET `outputuid` = 0, `priority` = -1 WHERE `done` > 0 AND `priority` != -1 AND `outputuid` = ".intval($_SESSION['wspvars']['userid'])." AND `timeout` < ".(time()-180);
	doSQL($queuedone_sql);
    
}

// EOF