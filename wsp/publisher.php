<?php
/**
 * website publisher
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-03-08
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'publisher';
$_SESSION['wspvars']['mgroup'] = 7;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
$_SESSION['wspvars']['pagedesc'] = array('fa fa-globe',returnIntLang('menu changed publisher'),returnIntLang('menu changed'));
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* page specific includes */

/* define page specific vars ----------------- */
$_SESSION['wspvars']['showpublish'] = 0;
if (isset($_REQUEST['sp'])) {
    if (trim($_REQUEST['sp'])=='view-all') {
        $_SESSION['wspvars']['showpublish'] = 0;
    } else if (trim($_REQUEST['sp'])=='view-changes') {
        $_SESSION['wspvars']['showpublish'] = 1;
    } else if (trim($_REQUEST['sp'])=='view-content') {
        $_SESSION['wspvars']['showpublish'] = 2;
    } else if (trim($_REQUEST['sp'])=='view-structure') {
        $_SESSION['wspvars']['showpublish'] = 3;
    }
} else {
    $aNum = getNumSQL('SELECT `mid` FROM `menu` WHERE `trash` = 0');
    $pNum = getNumSQL('SELECT `mid` FROM `menu` WHERE (`contentchanged` != 0  OR `structurechanged`) != 0 AND `trash` = 0');
    if ($pNum>0) {
        $_SESSION['wspvars']['showpublish'] = 1;
    }
}

/* define page specific functions ------------ */

// setup publisher filter if it is given
if (isset($_POST['publisherfilter'])) { 
    $_SESSION['wspvars']['publisherfilter'] = false;
    if (strlen(trim($_POST['publisherfilter']))>2) {
        $_SESSION['wspvars']['publisherfilter'] = trim($_POST['publisherfilter']);
    }
}
// setup publisher filter by search
if (isset($_SESSION['wspvars']['publisherfilter']) && trim($_SESSION['wspvars']['publisherfilter'])!='') {
    // get all menupoints that fit search statement by path, description or langdescription
    $_SESSION['wspvars']['publisherfilterid'] = getResultSQL("SELECT `mid` FROM `menu` WHERE ((`description` LIKE '%".escapeSQL(trim($_SESSION['wspvars']['publisherfilter']))."%' OR `langdescription` LIKE '%".escapeSQL(trim($_SESSION['wspvars']['publisherfilter']))."%') OR `filename` LIKE '%".escapeSQL(trim($_SESSION['wspvars']['publisherfilter']))."%') AND `trash` = 0");
}
else {
    $_SESSION['wspvars']['publisherfilter'] = '';
    $_SESSION['wspvars']['publisherfilterid'] = false;
}

$_SESSION['wspvars']['publisherdata'] = getWSPProperties();

// head der datei
include ("./data/include/header.inc.php");
include ("./data/include/navbar.inc.php");
require ("./data/include/sidebar.inc.php");

?>
<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('publisher headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('publisher info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
            <?php
            
            if (isset($aNum) && $aNum>0) {
            
            ?>
            <script language="JavaScript" type="text/javascript">

                function selectPublish(id, selectType) {
                    $("." + id).toggleClass('publish');
                    if ($("." + id).hasClass('publish')) { $("#check" + id).prop('checked', true); } else { $("#check" + id).prop('checked', false); }
                    if (!($("#check" + id).prop('checked'))) { $("#checkall" + selectType).prop('checked', false); }
                }	// selectPublish()

                function selectAllPublish(selectType) {
                    $("." + selectType + "publish").addClass('publish');
                    $("." + selectType + "publishbox").prop('checked', true);
                    $("#checkall" + selectType).prop('checked', true);
                    $('tr.publisheritem').removeClass('info').removeClass('success').addClass('success');
                    $('.itempublish').each(function() {
                        if ($(this).css('display')=='none') {
                            $(this).removeClass('publish');
                            $(this).find('input').prop('checked', false);
                            $(this).find('input').css('display', 'none');
                            $(this).find('input').prop('disabled', 'disabled');
                        }
                        else if ($(this).hasClass('inqueue')) {
                            $(this).removeClass('publish');
                            $(this).find('input').prop('checked', false);
                            $(this).find('input').prop('disabled', 'disabled');
                            $(this).find('input').prop('disabled', 'disabled');
                        }
                        else {
                            $(this).find('input').css('display', 'table-cell');
                            $(this).find('input').prop('disabled', false);
                        }
                    });
                }	// selectAllPublish()

                function deselectAllPublish(selectType) {
                    $("." + selectType + "publish").removeClass('publish');
                    $("." + selectType + "publishbox").prop('checked', false);
                    $("#checkall" + selectType).prop('checked', false);
                    $('tr.publisheritem').removeClass('success');
                }	// deselectAllPublish()
	
                function checkallpublish(selectType) {
                    if (document.getElementById('checkall' + selectType).checked) {
                        selectAllPublish(selectType);
                    }
                    else {
                        deselectAllPublish(selectType);
                    }
                }

                function setToPublish(selectType) {
                    var publishItems = true;
                    if (publishItems) {
                        document.getElementById(selectType + 'publish').submit();
                    }
                }

                function clearQueue() {
                    document.getElementById('clearqueue').submit();
                    $('#queueinfo').hide();
                    $('.queue-num-badge').hide();
                    $('tr.publisheritem.info').removeClass('info').removeClass('success');
                }

            </script>
            <?php 
                
                $_SESSION['publishrun'] = 0;
                $queue_num = getWSPqueue();

                if ($queue_num>0) {
            ?>
            <div class="row" id="queueinfo">
                <div class="col-md-12">
                    <div class="panel" id="queueinfo">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('publisher queue', true); ?></h3>
                        </div>
                        <div class="panel-body">
                            <p><?php echo (($queue_num==1)?(returnIntLang('publisher job in queue1')." <span class='queue-num-badge'>1</span> ".returnIntLang('publisher job in queue2')):(returnIntLang('publisher jobs in queue1')." <span class='queue-num-badge'>".(intval($queue_num))."</span> ".returnIntLang('publisher jobs in queue2'))); ?></p>
                            <form action="./xajax/iframe.publisherpost.php" id="clearqueue" method="post" target="publisherpost"><input type="hidden" name="op" id="queueop" value="clearqueue" /></form>
                            <p><a onclick="clearQueue();" class="btn btn-danger"><?php echo returnIntLang('publisher clear queue'); ?></a> <?php if ($_SESSION['wspvars']['usertype']=='admin'): ?><a onclick="document.getElementById('queueop').value = 'clearallqueues'; document.getElementById('clearqueue').submit();" class="redfield"><?php echo returnIntLang('publisher clear all queues'); ?></a><?php endif; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <?php } 
            
            // check for some publisher subrights
            $showpanel = array();
            if (isset($_SESSION['wspvars']['rights']['design']) && intval($_SESSION['wspvars']['rights']['design'])==1) {
                $showpanel['css'] = 0;
                // check css-files to be published
                $aCSS = array();
                $cssfiles_res = doSQL("SELECT `id` FROM `stylesheets` WHERE ");
                $csschanges_sql = "SELECT `id`, `describ`, `file`, `cfolder`, `lastchange`, (`lastchange` > `lastpublish`) AS `changed` FROM `stylesheets` ORDER BY `lastchange` < `lastpublish`, `describ`";
                $csschanges_res = doSQL($csschanges_sql);
                if ($csschanges_res['num']>0) {
                    foreach ($csschanges_res['set'] AS $cssk => $cssv) {
                        $aCSS[] = array(
                            'id' => intval($cssv['id']),
                            'description' => trim($cssv['describ']),
                            'lastchange' => $cssv['lastchange'], 
                            'changed' => intval($cssv['changed']),
                            'filename' => trim($cssv['file']),
                            'foldername' => trim($cssv['cfolder'])
                        );
                        if (intval($cssv['changed'])==1 && $cssv['cfolder']==$cssv['lastchange']): $showpanel['css']++; endif;
                    }
                } else {
                    unset($showpanel['css']);
                }
            
                $showpanel['js'] = 0;
                // check js-files to be published
                $aJS = array();

                $jsfiles_res = doSQL("SELECT `id` FROM `javascript`");
                if ($jsfiles_res['num']>0) {
                    $jschanges_sql = "SELECT `id`, `describ`, `file`, `cfolder`, `lastchange`, (`lastchange` > `lastpublish`) AS `changed` FROM `javascript` ORDER BY `lastchange` < `lastpublish`, `describ`";
                    $jschanges_res = doSQL($jschanges_sql);
                    if ($jschanges_res['num']>0) {
                        foreach ($jschanges_res['set'] AS $jsk => $jsv) {
                            $aJS[] = array(
                                'id' => $jsv['id'], 
                                'description' => $jsv['describ'], 
                                'lastchange' => $jsv['lastchange'], 
                                'changed' => $jsv['changed'], 
                                'filename' => $jsv['file'],
                                'foldername' => trim($jsv['cfolder'])
                            );
                            if (intval($jsv['changed'])==1 && $jsv['cfolder']==$jsv['lastchange']): $showpanel['js']++; endif;
                        }
                    } else {
                        unset($showpanel['js']);
                    }
                } else {
                    unset($showpanel['js']);
                }

            }
            
            /*
            if (isset($_SESSION['wspvars']['rights']['rss']) && intval($_SESSION['wspvars']['rights']['rss'])==1) {
                $showpanel['rss'] = 0;
                // check rss-files to be published
                $aRSS = array();
                $pRSS = 0;
                $rssdata_sql = "SELECT * FROM `rssdata`";
                $rssdata_res = doSQL($rssdata_sql);

                echo "<pre>";
                var_export($rssdata_res);
                echo "</pre>";
                
                if ($rssdata_res['num']>0) {
                    $rssentry_sql = "SELECT `eid` FROM `rssentries` WHERE `epublished` = 0";
                    $rssentry_res = doSQL($rssentry_sql);

                    var_export($rssentry_res);

                    if ($rssentry_res['num']>0) {



                        foreach ($csschanges_res['set'] AS $cssk => $cssv) {
                            $aRSS[] = array(
                                'id' => intval($cssv['id']),
                                'description' => trim($cssv['describ']),
                                'changed' => intval($cssv['changed']),
                                'filename' => trim($cssv['file']),
                                'foldername' => trim($cssv['cfolder'])
                            );
                        if (intval($cssv['changed'])==1): $showpanel['rss']++; endif;
                        }
                        $pRSS++;
                    }
                } else {
                    unset($showpanel['rss']);
                }
            }
            */
                // rss feature disabled
                unset($showpanel['rss']);
            
                // show mediapanels
	        if (count($showpanel)>0) {
                ?>
                <div class="row">
                    <?php if (isset($showpanel['css'])) { ?>
                        <div class="col-md-<?php echo (12/count($showpanel)); ?>" id="panel-publish-css">
                            <div class="panel" id="mediainfo">
                                <div class="panel-heading">
                                    <h3 class="panel-title"><?php echo returnIntLang('publisher css-files', true); echo ((intval($showpanel['css'])>0) ? " <span class='badge inline-badge'>".intval($showpanel['css'])."</span>" : '') ; ?></h3>
                                    <?php panelOpener(true, array(), false, 'panel-publish-css'); ?>
                                </div>
                                <div class="panel-body">
                                    <form action="./xajax/iframe.publisherpost.php" target="publisherpost" id="csspublish" enctype="multipart/form-data" method="post">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <table class="table publishinglist">
                                                    <thead>
                                                        <tr>
                                                            <th class="desktop"></th>
                                                            <th class="col-md-4"><?php echo returnIntLang('str description', true); ?></th>
                                                            <th class="col-md-6"><?php echo returnIntLang('publisher created filename', true); ?></th>
                                                            <th class="col-md-2"><input type="checkbox" id="checkallcss" onchange="checkallpublish('css');"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                    <?php
                                                             
                                                        $crun=1; 
                                                        foreach ($aCSS as $key => $value) {
                                                            if ($value['foldername']==$value['lastchange']) {
                                                        ?>
                                                        <tr>
                                                            <td class="csspublish css<?php echo $value['id']; ?> <?php if ($value['changed']==1): echo " publishrequired"; endif; ?>" onclick="selectPublish('css<?php echo $value['id']; ?>','css'); return true;" ><?php 
                                                        
                                                                if ($value['changed']==1) {
                                                                    echo "<i class='fas fa-file-code'></i>"; 
                                                                }
                                                                else {
                                                                    echo "<i class='far fa-file-code'></i>"; 
                                                                }
                                                         
                                                            ?></td>
                                                            <td class="tablecell three csspublish css<?php echo $value['id']; ?> <?php if ($value['changed']==1): echo " publishrequired"; endif; ?>" onclick="selectPublish('css<?php echo $value['id']; ?>','css'); return true;"><?php echo $value['description']; ?></td>
                                                            <td class="tablecell three csspublish css<?php echo $value['id']; ?> <?php if ($value['changed']==1): echo " publishrequired"; endif; ?>" onclick="selectPublish('css<?php echo $value['id']; ?>','css'); return true;">/media/layout/<?php echo $value['filename'].".css"; ?></td>
                                                            <td class="tablecell two csspublish css<?php echo $value['id']; ?> <?php if ($value['changed']==1): echo " publishrequired"; endif; ?>" onclick="selectPublish('css<?php echo $value['id']; ?>','css'); return true;"><input type="checkbox" class="csspublishbox" name="publishcss[]" value="<?php echo $value['id']; ?>" id="checkcss<?php echo $value['id']; ?>"></td>
                                                        </tr>
                                                        <?php    
                                                                $crun++; 
                                                            } 
                                                        } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <hr />
                                                <input type="hidden" name="op" value="publishcss" />
                                                <p><a  onclick="setToPublish('css'); return false;" class="btn btn-primary"><?php echo returnIntLang('publisher publish selection', true); ?></a></p>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    <?php if (isset($showpanel['js'])) { ?>
                        <div class="col-md-<?php echo (12/count($showpanel)); ?>" id="panel-publish-js">
                            <div class="panel" id="mediainfo">
                                <div class="panel-heading">
                                    <h3 class="panel-title"><?php echo returnIntLang('publisher js-files', true); echo ((intval($showpanel['js'])>0) ? " <span class='badge inline-badge'>".intval($showpanel['js'])."</span>" : ''); ?></h3>
                                    <?php panelOpener(true, array(), false, 'panel-publish-js'); ?>
                                </div>
                                <div class="panel-body">
                                    <form action="./xajax/iframe.publisherpost.php" target="publisherpost" id="jspublish" enctype="multipart/form-data" method="post">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <table class="table publishinglist">
                                                    <thead>
                                                        <tr>
                                                            <th class="desktop"></th>
                                                            <th><?php echo returnIntLang('str description', true); ?></th>
                                                            <th><?php echo returnIntLang('publisher created filename', true); ?></th>
                                                            <th><input type="checkbox" id="checkalljs" onchange="checkallpublish('js');"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        
                                                        $crun=0; 
                                                        foreach ($aJS as $key => $value) { 
                                                            if ($value['foldername']==$value['lastchange']) {
                                                        
                                                        ?>
                                                        
                                                        <tr>
                                                            <td class="desktop <?php if ($value['changed']==1) { echo " publishrequired"; } ?>"><?php
                                                        
                                                                if ($value['changed']==1) {
                                                                    echo "<i class='fas fa-file-code'></i>"; 
                                                                }
                                                                else {
                                                                    echo "<i class='far fa-file-code'></i>"; 
                                                                }
                                                            
                                                                ?></td>
                                                            <td class="tablecell three jspublish js<?php echo $value['id']; ?> <?php if ($value['changed']==1): echo " publishrequired"; endif; ?>" onclick="selectPublish('js<?php echo $value['id']; ?>','js'); return true;"><?php echo $value['description']; ?></td>
                                                            <td class="tablecell three jspublish js<?php echo $value['id']; ?> <?php if ($value['changed']==1): echo " publishrequired"; endif; ?>" onclick="selectPublish('js<?php echo $value['id']; ?>','js'); return true;">/data/script/<?php echo $value['filename'].".js"; ?></td>
                                                            <td class="tablecell two jspublish js<?php echo $value['id']; ?> <?php if ($value['changed']==1): echo " publishrequired"; endif; ?>" onclick="selectPublish('js<?php echo $value['id']; ?>','js'); return true;"><input type="checkbox" class="jspublishbox" name="publishjs[]" value="<?php echo $value['id']; ?>" id="checkjs<?php echo $value['id']; ?>"></td>
                                                        </tr>
                                                        <?php $crun++; 
                                                            }
                                                        }
                                                        
                                                        ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-3">
                                                <hr />
                                                <p><a onclick="setToPublish('js'); return false;" class="btn btn-primary"><?php echo returnIntLang('publisher publish selection', true); ?></a></p>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    <?php if (isset($showpanel['rss'])) { ?>
                        <div class="col-md-<?php echo (12/count($showpanel)); ?>" id="panel-publish-rss">
                            <div class="panel" id="mediainfo">
                                <div class="panel-heading">
                                    <h3 class="panel-title"><?php echo returnIntLang('publisher rss-files', true); echo " <span class='badge inline-badge'>".$pRSS."</span>"; ?></h3>
                                    <?php panelOpener(true, array(), false, 'panel-publish-rss'); ?>
                                </div>
                                <div class="panel-body">
                                    <form action="./xajax/iframe.publisherpost.php" target="publisherpost" id="rsspublish" enctype="multipart/form-data" method="post">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <table class="tablelist publishinglist">
                                                    <tr>
                                                        <td class="tablecell three head"><?php echo returnIntLang('str description'); ?></td>
                                                        <td class="tablecell three head"><?php echo returnIntLang('publisher created filename', true); ?></td>
                                                        <td class="tablecell two head"><input type="checkbox" id="checkallrss" onchange="checkallpublish('rss');"></td>
                                                    </tr>
                                                    <?php for ($r=0; $r<$rssdata_num; $r++): 
                                                        $rssentry_sql = "SELECT `eid` FROM `rssentries` WHERE `rid` = '".mysql_result($rssdata_res,$r,"rid")."' AND `epublished` = 0";
                                                        $rssentry_res = mysql_query($rssentry_sql);
                                                        $rssentry_num = mysql_num_rows($rssentry_res);
                                                        ?>
                                                        <tr>
                                                            <td class="tablecell three rsspublish rss<?php echo intval(mysql_result($rssdata_res, $r, 'rid')); ?> <?php if ($rssentry_num>0): echo " publishrequired"; endif; ?>" onclick="selectPublish('rss<?php echo intval(mysql_result($rssdata_res,$r,"rid")); ?>','rss'); return true;"><?php echo trim(mysql_result($rssdata_res, $r, 'rsstitle')); ?></td>
                                                            <td class="tablecell three rsspublish rss<?php echo intval(mysql_result($rssdata_res, $r, 'rid')); ?> <?php if ($rssentry_num>0): echo " publishrequired"; endif; ?>" onclick="selectPublish('rss<?php echo intval(mysql_result($rssdata_res,$r,"rid")); ?>','rss'); return true;">/media/rss/<?php echo mysql_result($rssdata_res,$r,"rssfilename"); ?>.rss</td>
                                                            <td class="tablecell two rsspublish rss<?php echo intval(mysql_result($rssdata_res, $r, 'rid')); ?> <?php if ($rssentry_num>0): echo " publishrequired"; endif; ?>" onclick="selectPublish('rss<?php echo intval(mysql_result($rssdata_res,$r,"rid")); ?>','rss'); return true;"><input type="checkbox" class="rsspublishbox" name="publishrss[]" value="<?php echo intval(mysql_result($rssdata_res, $r, 'rid')); ?>" id="checkrss<?php echo intval(mysql_result($rssdata_res, $r, 'rid')); ?>" /></td>
                                                        </tr>
                                                    <?php endfor; ?>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <input type="hidden" name="op" value="publishit" />
                                                <p><a onclick="setToPublish('rss'); return false;" class="greenfield"><?php echo returnIntLang('publisher publish selection', false); ?></a></p>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
                <?php
            }
            
            ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="panel" id="panel-publish-files" style="-moz-user-select: none; -webkit-user-select: none; -ms-user-select: none; -o-user-select: none; user-select: none" unselectable="on" onselectstart="return false;">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('publisher files', true); echo " <span class='badge inline-badge'>".$pNum."</span>"; ?></h3>
                            <div class="right">
                                <div class="dropdown">
                                    <a href="#" class="toggle-dropdown" data-toggle="dropdown" aria-expanded="false"><i class="<?php echo ((isset($_SESSION['wspvars']['showpublish']) && intval($_SESSION['wspvars']['showpublish'])==0)?'far fa-file':((isset($_SESSION['wspvars']['showpublish']) && intval($_SESSION['wspvars']['showpublish'])==1)?'far fa-file-code':((isset($_SESSION['wspvars']['showpublish']) && intval($_SESSION['wspvars']['showpublish'])==2)?'far fa-file-alt':((isset($_SESSION['wspvars']['showpublish']) && intval($_SESSION['wspvars']['showpublish'])==1)?'far fa-copy':'far fa-bar')))); ?>" id="toogleview"></i></a>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                        <li><a onclick="toggleView('view-all')"><i class="<?php echo ((isset($_SESSION['wspvars']['showpublish']) && intval($_SESSION['wspvars']['showpublish'])==0)?'far fa-check':'far fa-file'); ?>" id="view-all"></i> <?php echo returnIntLang('publisher show all'); ?></a></li>
                                        <li><a onclick="toggleView('view-changes')"><i class="<?php echo ((isset($_SESSION['wspvars']['showpublish']) && intval($_SESSION['wspvars']['showpublish'])==1)?'far fa-check':'far fa-file-code'); ?>" id="view-changes"></i> <?php echo returnIntLang('publisher show changes'); ?></a></li>
                                        <li><a onclick="toggleView('view-content')"><i class="<?php echo ((isset($_SESSION['wspvars']['showpublish']) && intval($_SESSION['wspvars']['showpublish'])==2)?'far fa-check':'far fa-file-alt'); ?>" id="view-content"></i> <?php echo returnIntLang('publisher show content changes'); ?></a></li>
                                        <li><a onclick="toggleView('view-structure')"><i class="<?php echo ((isset($_SESSION['wspvars']['showpublish']) && intval($_SESSION['wspvars']['showpublish'])==3)?'far fa-check':'far fa-copy'); ?>" id="view-structure"></i> <?php echo returnIntLang('publisher show structure changes'); ?></a></li>
                                    </ul>
                                </div>
                            </div>
                            <?php
                            
                            include ("./data/panels/workspacelang.inc.php");
                
                            ?>
                        </div>
                        <div class="panel-option">
                            <form name="searchpublisher-form" id="searchpublisher-form" method="post">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-search"></i><?php
                                    
                                    if (isset($_SESSION['wspvars']['publisherfilterid']) && is_array($_SESSION['wspvars']['publisherfilterid']) && count($_SESSION['wspvars']['publisherfilterid'])>0): echo " (".count($_SESSION['wspvars']['publisherfilterid']).")"; endif;
                                    
                                    ?>
                                </span>
                                <input type="text" class="form-control" placeholder="<?php echo returnIntLang('publisher filter input and return', false); ?>" id="publisherfilter" name="publisherfilter" value="<?php if(isset($_SESSION['wspvars']['publisherfilter']) && trim($_SESSION['wspvars']['publisherfilter'])!=''): echo trim($_SESSION['wspvars']['publisherfilter']); endif; ?>" />
                            </div>
                            </form>
                        </div>
                        <div class="panel-body">
                            <form action="./xajax/iframe.publisherpost.php" target="publisherpost" id="itempublish" enctype="multipart/form-data" method="post">
                                <input type="hidden" name="sp" id="sp" value="<?php echo ((isset($_SESSION['wspvars']['showpublish']) && intval($_SESSION['wspvars']['showpublish'])==0)?'view-all':((isset($_SESSION['wspvars']['showpublish']) && intval($_SESSION['wspvars']['showpublish'])==1)?'view-changes':((isset($_SESSION['wspvars']['showpublish']) && intval($_SESSION['wspvars']['showpublish'])==2)?'view-content':((isset($_SESSION['wspvars']['showpublish']) && intval($_SESSION['wspvars']['showpublish'])==1)?'view-structure':'')))); ?>" />
                                <?php

                                $structure = returnIDRoot(0);
                                
                                foreach ($structure AS $sk => $sv) {
                                    if (isset($_SESSION['wspvars']['publisherfilterid']) && is_array($_SESSION['wspvars']['publisherfilterid']) && count($_SESSION['wspvars']['publisherfilterid'])>0) {
                                        if (!(in_array($sv, $_SESSION['wspvars']['publisherfilterid']))) {
                                            unset($structure[$sk]);
                                        }
                                    }
                                }
                                
                                if (count($structure)>0) {
                                    echo "<div class='row'><div class='col-md-12'>";
                                    
                                    echo '<table class="table publishinglist '.((isset($_SESSION['wspvars']['showpublish']) && intval($_SESSION['wspvars']['showpublish'])==0)?'view-all':((isset($_SESSION['wspvars']['showpublish']) && intval($_SESSION['wspvars']['showpublish'])==1)?'view-changes':((isset($_SESSION['wspvars']['showpublish']) && intval($_SESSION['wspvars']['showpublish'])==2)?'view-content':((isset($_SESSION['wspvars']['showpublish']) && intval($_SESSION['wspvars']['showpublish'])==1)?'view-structure':'')))).'">';

                                    echo '<thead>';
                                    echo '<tr>';
                                    echo '<th class="desktop"></th>';
                                    echo '<th class="col-md-3">'.returnIntLang('str description').'</th>';
                                    echo '<th class="col-md-5 desktop singleline">'.returnIntLang('publisher created path').'</th>';
                                    echo '<th class="col-md-2 desktop">'.returnIntLang('publisher last publish').'</th>';
                                    echo '<th>'.(($_SESSION['wspvars']['rights']['publisher']<100)?'<i class="far fa-check-square checkall"></i>':'').'</th>';
                                    echo '</tr>';
                                    echo '</thead>';
                                    
                                    echo '<tbody>';    
                                    
                                        $changetypes = array(
                                            0 => '',
                                            1 => '<i class="far fa-copy"></i>', // menupoint was edited in structureedit
                                            2 => '<i class="far fa-file-alt"></i>',
                                            3 => '<i class="far fa-file-code"></i>', // in templatesedit 3 is changed template
                                            4 => '<i class="far fa-copy"></i>',
                                            5 => '<i class="far fa-file-alt"></i>', // 5 is changed content from contentedit.php
                                            6 => '6?',
                                            7 => '<i class="far fa-file-alt"></i>', // filename of specific file changed
                                            8 => '8?',
                                            9 => '<i class="fas fa-globe"></i>',
                                        );
                                        $changeclasses = array(
                                            0 => 'nochange',
                                            1 => 'structure',
                                            2 => 'content warning',
                                            3 => 'content structure', // in templatesedit 3 is changed template
                                            4 => 'structure',
                                            5 => 'content', // 5 is changed content from contentedit.php
                                            6 => '',
                                            7 => 'structure', // filename of specific file changed
                                            8 => '',
                                            9 => 'content structure queue info',
                                        );
                                        $foldertype = array( 0 => 'fas', 1 => 'far' );
                                        
                                        $r = 0; foreach ($structure AS $sk => $sv) {
                                            $r++;
                                            $mpointdata = doSQL("SELECT `level`, `editable`, `visibility`, `description`, `langdescription`, `offlink`, `forwarding_id`, `contentchanged`, `structurechanged`, `lastchange`, `isindex`, `breaktree`, `lastpublish` FROM `menu` WHERE `mid` = ".intval($sv));
                                            if (intval(getNumSQL("SELECT `id` FROM `wspqueue` WHERE `param` = ".intval($sv)." AND `done` = 0 "))>0) { $mpointdata['set'][0]['contentchanged'] = 9; }
                                            
                                            // show only breaktree = 0
                                            // breaktree != 0 » structure affected menupoints (like dynamic or forwarding) 
                                            if (intval($mpointdata['set'][0]['breaktree'])==0) {
                                                echo "<tr id='mid-".$sv."' onclick='togglePublish(".$sv.",".$r.")' class='publisheritem ".$changeclasses[intval($mpointdata['set'][0]['contentchanged'])]."'>";

                                                echo "<td class='desktop text-center'>".$changetypes[intval($mpointdata['set'][0]['contentchanged'])]."</td>";
                                                echo "<td class='col-md-4 singleline'>";
                                                for ($l=0; $l<$mpointdata['set'][0]['level']; $l++) {
                                                    if ($l>1) { echo "&nbsp;&nbsp;&nbsp;&nbsp;"; }
                                                }
                                                if ($l>1) { echo "<i class='".$foldertype[(($l%2)?0:1)]." fa-folder-open'></i>&nbsp;&nbsp;"; }

                                                $desc = setUTF8(trim($mpointdata['set'][0]['description']));
                                                if (isset($mpointdata['set'][0]['langdescription']) && trim($mpointdata['set'][0]['langdescription'])!='') {
                                                    $tdesc = unserializeBroken($mpointdata['set'][0]['langdescription']);
                                                    if (isset($tdesc[$_SESSION['wspvars']['workspacelang']]) && trim($tdesc[$_SESSION['wspvars']['workspacelang']])!='') {
                                                        $desc = setUTF8(trim($tdesc[$_SESSION['wspvars']['workspacelang']]));
                                                    }
                                                }
                                                echo $desc;

                                                if (intval($mpointdata['set'][0]['isindex'])==1) {
                                                    echo " <sup><i class='fas fa-asterisk'></i></sup>";
                                                }
                                                echo "</td>";
                                                echo "<td class='col-md-5 desktop'><span class='singleline'>".fileNamePath($sv, 0, 2)."</span></td>";
                                                echo "<td class='col-md-2 desktop singleline'>";
                                                if ($mpointdata['set'][0]['lastpublish']>100) {
                                                    if (date('Y-m-d', $mpointdata['set'][0]['lastpublish'])==date('Y-m-d')) {
                                                        echo date(returnIntLang('format time', false), $mpointdata['set'][0]['lastpublish']);
                                                    } else {
                                                        echo date(returnIntLang('format date', false), $mpointdata['set'][0]['lastpublish']);
                                                    }
                                                }
                                                else {
                                                    echo "-";
                                                }
                                                echo "</td>";
                                                echo "<td>";
                                                if (intval($mpointdata['set'][0]['contentchanged'])!=9) {
                                                    echo "<input type='checkbox' onchange='togglePublish(".$sv.", ".$sk.")' id='check-".$sv."' name='publishitem[]' value='".$sv."' />";
                                                }
                                                echo "</td>";
                                                echo "</tr>";
                                            }
                                        } 
                                    echo "</tbody>";
                                    echo "</table>";
                                    echo "</div></div>";
                                }
                                // if user is allowed to see the publisher
                                if (isset($_SESSION['wspvars']['rights']['publisher']) && intval($_SESSION['wspvars']['rights']['publisher'])<100) { 
                                ?>
                                <div class="row">
                                    <div class="col-md-12">
                                        <?php if (intval(count($_SESSION['wspvars']['sitelanguages']['shortcut']))>1) { ?>
                                            <p><?php echo returnIntLang('publisher publish only'); ?></p>
                                            <p><?php foreach ($_SESSION['wspvars']['sitelanguages']['shortcut'] AS $key => $value): ?><label><input type="checkbox" name="publishlang[]" value="<?php echo $_SESSION['wspvars']['sitelanguages']['shortcut'][$key]; ?>" <?php if($_SESSION['wspvars']['workspacelang']==$_SESSION['wspvars']['sitelanguages']['shortcut'][$key]): echo " checked='checked' "; endif; ?> />&nbsp;<?php echo $_SESSION['wspvars']['sitelanguages']['longname'][$key]; ?></label>&nbsp;&nbsp; <?php endforeach; ?></p>
                                        <?php } ?>
                                        <input type="hidden" name="publishsubs" id="publishsubs" value="0" />
                                        <input type="hidden" name="startpublish" id="startpublish" value="<?php echo time(); ?>" />
                                        <script type="text/javascript">
                                       
                                            function checkcontentpublish(changedID) {
                                                if ($('#contentpublish_' + changedID).prop('checked')) {
                                                    $('#contentpublish_changed').prop('checked', false);
                                                    $('#contentpublish_force').prop('checked', false);
                                                    $('#contentpublish_' + changedID).prop('checked', true);
                                                }
                                            }

                                        </script>
                                        <p><?php echo returnIntLang('str setuppublish'); ?></p>
                                        <p><label><input type="checkbox" name="op[]" value="structure" checked="checked" />&nbsp;<?php echo returnIntLang('str setuppublish structure'); ?></label>&nbsp;&nbsp; <label><input type="checkbox" name="op[]" id="contentpublish_changed" value="content" onchange="checkcontentpublish('changed')" />&nbsp;<?php echo returnIntLang('str setuppublish changed contents'); ?></label>&nbsp;&nbsp; <label><input type="checkbox" name="op[]" id="contentpublish_force" value="force" onchange="checkcontentpublish('force')" checked="checked" />&nbsp;<?php echo returnIntLang('str setuppublish all contents'); ?></label></p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <p><a onclick="$('#publishsubs').val(0); setToPublish('item'); return false;" class="btn btn-primary"><?php echo returnIntLang('publisher publish selection', false); ?></a> <a onclick="$('#publishsubs').val(1); setToPublish('item'); return false;" class="btn btn-info"><?php echo returnIntLang('publisher publish selection and subs', false); ?></a> <a class="btn btn-default uncheckall"><?php echo returnIntLang('publisher unselect selection', false); ?></a></p>
                                    </div>
                                </div>
                                <?php } ?>
                            </form>
                            <script>
                            
                                var doShift = false;
                                var lastCheck = false;
                                
                                $(document).mousedown(function(e){
                                    if (e.shiftKey) {
                                        doShift = true;
                                    } else {
                                        doShift = false;
                                    }
                                });
                                
                                function togglePublish(mid, row) {
                                    isChecked = $('tr#mid-'+mid).find('#check-'+mid).prop('checked');
                                    if (isChecked) {
                                        $('tr#mid-'+mid).removeClass('success').find('#check-'+mid).prop('checked', false);
                                    } 
                                    else {
                                        $('tr#mid-'+mid).addClass('success').find('#check-'+mid).prop('checked', true);
                                    }
                                }
                                
                                function toggleView(view) {
                                    var icons = {
                                        'view-all':"fa-file",
                                        'view-changes':'fa-file-code',
                                        'view-content':'fa-file-alt', 
                                        'view-structure':'fa-copy'
                                    }
                                    var showview = {
                                        'view-all':"fa-file",
                                        'view-changes':'fa-file-code',
                                        'view-content':'fa-file-alt', 
                                        'view-structure':'fa-copy'
                                    }
                                    var hideview = {
                                        'view-all':"fa-file",
                                        'view-changes':'fa-file-code',
                                        'view-content':'fa-file-alt', 
                                        'view-structure':'fa-copy'
                                    }
                                    // changes the icon in dropdown
                                    $('#toogleview').removeClass('fa-file').removeClass('fa-file-code').removeClass('fa-file-alt').removeClass('fa-copy').removeClass('fa-bars').addClass(icons[view]);
                                    $('#panel-publish-files').find('.panel-heading').find('.dropdown-menu').find('i').each(function(e){
                                        $(this).removeClass('fa-check').addClass(icons[$(this).attr('id')]);
                                    });
                                    $('#' + view).removeClass(icons[view]).addClass('fa-check');
                                    $('table.table.publishinglist').removeClass('view-all').removeClass('view-changes').removeClass('view-content').removeClass('view-structure').addClass(view);
                                    // unsets the checked box from hidden entries 
                                    $('table.table.publishinglist').find('tr:hidden').find('input:checkbox').prop('checked', false);
                                    $('#sp').val(view);
                                }
                                
                                $(document).ready(function() {
                                    // does check all avaiable checkboxes
                                    
                                    $('.checkall').on('click', function() {
                                        $('.checkall').removeClass('checkall').addClass('uncheckall');
                                        $('table.publishinglist').find('tr:visible').find('input:checkbox').prop('checked', true);
                                    });
                                    
                                    $('.uncheckall').on('click', function() {
                                        $('.uncheckall').removeClass('uncheckall').addClass('checkall');
                                        $('table.publishinglist').find('input:checkbox').prop('checked', false);
                                    });
                                    
                                });
                                
                            </script>
                        </div>
                    </div>
                </div>
            </div>
        
            <?php } else { ?>
            <div class="row">
                <div class="col-md-12">
                    <h3 style="text-align: center; font-weight: 300"><?php echo returnIntLang('publisher no pages in structure'); ?></h3>
                    <h1 style="text-align: center; font-size: 10vw;">
                        <i class="fas fa-sitemap"></i>
                    </h1>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</div>
<?php

include ("./data/include/footer.inc.php");
