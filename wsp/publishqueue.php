<?php
/**
 * @description website publisher queue
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2020-03-11
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'publisherqueue';
$_SESSION['wspvars']['mgroup'] = 7;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
$_SESSION['wspvars']['pagedesc'] = array('fa fa-globe',returnIntLang('menu changed publisher'),returnIntLang('menu changed queue'));
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */

/* define page specific functions ------------ */

// temporaere loesung, bis ueberall das neue
// rechtesystem umgesetzt werden kann

if (isset($_POST['killjobid']) && intval($_POST['killjobid'])>0):
	doSQL("DELETE FROM `wspqueue` WHERE `id` = ".intval($_POST['killjobid']));
endif;

$_SESSION['publishrun'] = 0;

// head der datei
include ("./data/include/header.inc.php");
include ("./data/include/navbar.inc.php");
include ("./data/include/sidebar.inc.php");
?>
<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('queue headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('queue info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
            <?php

            $queue_sql = "SELECT * FROM `wspqueue` WHERE `done` = 0 ORDER BY `priority` DESC, `timeout` ASC, `action` ASC, `set` ASC, `param` ASC, `id` ASC";
            $queue_res = doSQL($queue_sql);
            if ($queue_res['num']>0) {
            ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('publisher queue'); ?></h3>
                            <p class="panel-subtitle"><?php echo (($queue_res['num']==1)?(returnIntLang('publisher job in queue1')." 1 ".returnIntLang('publisher job in queue2')):(returnIntLang('publisher jobs in queue1')." ".(intval($queue_res['num']))." ".returnIntLang('publisher jobs in queue2'))); ?> <a href="./publishlog.php">QueueLog</a></p>
                        </div>
                        <div class="panel-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><?php echo returnIntLang('queue setuptime'); ?></th>
                                        <th><?php echo returnIntLang('queue user'); ?></th>
                                        <th><?php echo returnIntLang('queue action'); ?></th>
                                        <th><?php echo returnIntLang('queue param'); ?></th>
                                        <th><?php echo returnIntLang('queue timeout'); ?></th>
                                        <th><?php echo returnIntLang('str action'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($queue_res['set'] AS $qrk => $qrv) { ?>
                                    <tr id="queuerow-<?php echo $qrv['id']; ?>">
                                        <td><?php echo date(returnIntLang('format date time'), intval($qrv['set'])); ?></td>
                                        <td><?php 

                                        $usrinfo = doResultSQL("SELECT `realname` FROM `restrictions` WHERE `rid` = ".intval($qrv['uid']));
                                        if ($usrinfo!==false && trim($usrinfo)!='') { echo trim($usrinfo); } else { echo returnIntLang('queue user system', false); }

                                        ?></td>
                                        <td nowrap="nowrap"><?php echo returnIntLang('queue action '.$qrv['action']); if($qrv['lang']!='') {
                                            echo " » ".$qrv['lang'];
                                        } ?></td>
                                        <td><?php 

                                        if (intval($qrv['param'])==$qrv['param']) {
                                            // check for css publishing
                                            if ($qrv['action']=='publishcss') {
                                                $queue_sql = "SELECT * FROM `stylesheets` WHERE `id` = ".intval($qrv['param']);
                                                $queue_res = doSQL($queue_sql);
                                                if ($queue_res['num']>0) {
                                                    if ($queue_res['set'][0]['cfolder']==$queue_res['set'][0]['lastchange']) {
                                                        echo $queue_res['set'][0]['file'].".css";
                                                    } else {
                                                        echo returnIntLang('queue str folder1', false).$queue_res['set'][0]['cfolder'].returnIntLang('queue str folder2', false);
                                                    }
                                                } else {
                                                    echo returnIntLang('str undefined', false);
                                                }
                                            }
                                            // check for js publishing
                                            else if ($qrv['action']=='publishjs') {
                                            
                                                $queue_sql = "SELECT * FROM `javascript` WHERE `id` = ".intval($qrv['param']);
                                                $queue_res = doSQL($queue_sql);
                                                if ($queue_res['num']>0) {
                                                    if ($queue_res['set'][0]['cfolder']==$queue_res['set'][0]['lastchange']) {
                                                        echo $queue_res['set'][0]['file'].".js";
                                                    } else {
                                                        echo returnIntLang('queue str folder1', false).$queue_res['set'][0]['cfolder'].returnIntLang('queue str folder2', false);
                                                    }
                                                } else {
                                                    echo returnIntLang('str undefined', false);
                                                }
                                            
                                            }
                                            // check for rss publishing
                                            // disabled in wsp 7.0 » will be back maybe
                                            else if ($qrv['action']=='publishrss') {
                                                echo "<em>function deprecated</em>";
                                            } 
                                            // check for site publishing
                                            else {
                                                $mnuinfo_sql = "SELECT `description`, `langdescription` FROM `menu` WHERE `mid` = ".intval($qrv['param']);
                                                $mnuinfo_res = doSQL($mnuinfo_sql);
                                                if ($mnuinfo_res['num']>0) {
                                                    if (trim($mnuinfo_res['set'][0]['langdescription'])=='') {
                                                        echo trim($mnuinfo_res['set'][0]['description']);
                                                    }
                                                    else {
                                                        $langdesc = unserializeBroken(trim($mnuinfo_res['set'][0]['langdescription']));
                                                        if (is_array($langdesc) && count($langdesc)>0) {
                                                            if (isset($langdesc[$qrv['lang']])) {
                                                                echo trim($langdesc[$qrv['lang']]);
                                                            }
                                                            else {
                                                                echo trim($mnuinfo_res['set'][0]['description'])." [".$qrv['lang']."]";
                                                            }
                                                        }
                                                        else {
                                                            echo trim($mnuinfo_res['set'][0]['description']);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        else {
                                            echo $qrv['param']; 
                                        }
                                                                                  
                                        ?></td>
                                        <td><?php if (intval($qrv['timeout'])>86400) { echo date(returnIntLang('format date time'), intval($qrv['timeout'])); } else { echo returnIntLang('queue timeout running'); } ?></td>
                                        <td nowrap="nowrap">
                                            <a onclick="$('#priojobid').val(<?php echo intval($qrv['id']); ?>); $('#priojob').submit();"><i class="fab fa-btn fa-hotjar"></i></a> 
                                            <a onclick="$('#killjobid').val(<?php echo intval($qrv['id']); ?>); $('#killjob').submit();"><i class="far fa-btn fa-trash btn-danger"></i></a>
                                        </td>
                                    </tr>

                                    <?php } ?>
                                </tbody>
                            </table>
                            <form name="killjob" id="killjob" method="post"><input type="hidden" name="killjobid" id="killjobid" value="" /></form>
                            <form action="./xajax/iframe.publisherreact.php" id="priojob" method="post" target="publisherpost"><input type="hidden" name="priojobid" id="priojobid" value="" /></form>
                        </div>
                    </div>
                </div>
            </div>
            <?php } else { ?>
            <div class="row">
                <div class="col-md-12">
                    <h3 style="text-align: center; font-weight: 300"><?php echo returnIntLang('queue no jobs in queue'); ?></h3>
                    <h1 style="text-align: center; font-size: 10vw;">
                        <i class="fas fa-check-square"></i>
                    </h1>
                </div>
            </div>
            <?php } ?>
        </div>
	</div>
</div>
<script> cT = 5000; </script>
<?php require ("./data/include/footer.inc.php"); ?>