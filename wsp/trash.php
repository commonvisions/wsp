<?php
/**
 * global site-setup
 * @author stefan@covi.de
 * @since 4.0
 * @version 7.0
 * @lastchange 2020-06-30
 */

// start session -----------------------------
session_start();
// base includes -----------------------------
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
// define actual system position -------------
$_SESSION['wspvars']['lockstat'] = 'trash';
$_SESSION['wspvars']['pagedesc'] = array('fa fa-sitemap',returnIntLang('menu content'),returnIntLang('menu content trash'));
$_SESSION['wspvars']['mgroup'] = 5;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
$_SESSION['wspvars']['addpagecss'] = array('sweetalert2.css');
$_SESSION['wspvars']['addpagejs'] = array('sweetalert2.js');
/* define page specific functions ------------ */

if (isset($_REQUEST['untrash']) && intval($_REQUEST['untrash'])>0) {
	// find template to find contentareas
	
	// if contentarea is not in template set to contentarea 1
	$carea = 1;
	
	// make unvisible and untrashed
	$sql = "UPDATE `content` SET `trash` = 0, `visibility` = 0 WHERE `cid` = ".intval($_REQUEST['untrash']);
	if (mysql_query($sql)):
		addWSPMsg('noticemsg', returnIntLang('selected content was untrashed'));
	endif;
	unset($sql);
}
else if (isset($_REQUEST['untrash']) && trim($_REQUEST['untrash'])=='all') {
	
}

if (isset($_POST['op']) && trim($_POST['op'])=='ct' && isset($_POST['cid']) && is_array($_POST['cid'])) {
    // clear all contents
    $ct = 0;
    foreach ($_POST['cid'] AS $ck => $cv) {
        $res = doSQL("DELETE FROM `content` WHERE `trash` = 1 AND `cid` = ".intval($cv));
        if ($res['aff']>0) {
            $ct++;
        }
    }
    if ($ct>0) {
        addWSPMsg('noticemsg', returnIntLang('trash selected contents were finally removed1').$ct.returnIntLang('trash selected contents were finally removed2'));
    }
}

if (isset($_POST['op']) && trim($_POST['op'])=='cu' && isset($_POST['cid']) && is_array($_POST['cid'])) {
    // clear all contents
    $cu = 0;
    foreach ($_POST['cid'] AS $ck => $cv) {
        $res = doSQL("UPDATE `content` SET `trash` = 0, `lastchange` = ".time()." WHERE `trash` = 1 AND `cid` = ".intval($cv));
        if ($res['aff']>0) {
            $m_sql = "SELECT `mid` FROM `content` WHERE `cid` = ".intval($cv);
            $m_res = doResultSQL($m_sql);
            if ($m_res>0) {
                $sql = "UPDATE `menu` SET `contentchanged` = ".contentChangeStat(intval($m_res),'content')." WHERE `mid` = ".intval($m_res);
                getAffSQL($sql);
            }
            $cu++;
        }
    }
    if ($cu>0) {
        addWSPMsg('noticemsg', returnIntLang('trash selected contents were reset1').$ct.returnIntLang('trash selected contents were reset2'));
    }
}

if (isset($_POST['op']) && trim($_POST['op'])=='cac') {
    // clear all contents
    $res = doSQL("DELETE FROM `content` WHERE trash = 1");
	if ($res['aff']>0) {
		addWSPMsg('noticemsg', returnIntLang('all trashed contents were finally removed'));
    }
}

if (isset($_POST['op']) && trim($_POST['op'])=='cas') {
    // clear all structure
    // lookup (again) all menupoints located in trash submenupoints of the selected list 
    $tm_sql = "SELECT `mid` FROM `menu` WHERE `trash` != 0 AND `description` NOT LIKE '%autofile-%' ORDER BY `lastchange` DESC";
    $tm_data = doSQL($tm_sql);
    // for list of this values find ALL submenupoints

    $ltd = array(); // list to delete
    foreach ($tm_data['set'] AS $tmk => $tmv) {
        $ltd[] = intval($tmv['mid']);
        $ltd = array_merge($ltd, returnIDRoot($tmv['mid'], array(), true));
    }
    $ltd = array_unique($ltd);
    // run delete-list
    foreach ($ltd AS $lmk => $lmv) {
        // get ALL content_backup ids associated to contents associated to menupoint list
        $cblist_res = getResultSQL("SELECT `cbid` FROM `content` AS c, `content_backup` AS cb WHERE `c`.`mid` = ".intval($lmv)." AND `c`.`cid` = `cb`.`cid`");
        if ($cblist_res!==false && is_array($cblist_res) && count($cblist_res)>0) {
            // remove the backups
            doSQL("DELETE FROM `content_backup` WHERE `cbid` IN (".implode(",", $cblist_res).")");
        }
        // remove the contens
        doSQL("DELETE FROM `content` WHERE `mid` = ".intval($lmv));
    }    
    // lookup $_SESSION['wspvars']['handledelete']
    // 0 = hold files in structure
    // 1 = clear files from structure
    // 2 = set header to home page
    // 3 = replace with "file deleted" file
    if (isset($_SESSION['wspvars']['handledelete']) && intval($_SESSION['wspvars']['handledelete'])==0) {
        $res = doSQL("DELETE FROM `menu` WHERE trash = 1");
        if ($res['aff']>0) {
            addWSPMsg('noticemsg', returnIntLang('all trashed structure entries were finally removed'));
        }
    }
    else {
        addWSPMsg('errormsg', returnIntLang('We are sorry. We were not sure what to do with finaly removed menupoints. So we decided to let everything as it was and did not remove the menupoints as stored in database. But we removed associated contents and their backups to save a little bit of your database space.'));
    }
    
}

// head der datei
include ("./data/include/header.inc.php");
include ("./data/include/navbar.inc.php");
require ("./data/include/sidebar.inc.php");

?>
<!-- MAIN -->
<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('trash headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('trash info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
            <?php
            
            $tm_sql = "SELECT * FROM `menu` WHERE `trash` != 0 AND `description` NOT LIKE '%autofile-%' ORDER BY `lastchange` DESC";
            $tm_data = doSQL($tm_sql);
            
            $tc_sql = "SELECT * FROM `content` WHERE `trash` != 0 ORDER BY `lastchange` DESC, `mid`";
            $tc_data = doSQL($tc_sql);
            
            if ($tm_data['num']>0 || $tc_data['num']>0) {
                if ($tm_data['num']>0) { 
                    ?>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel">
                                <div class="panel-heading">
                                    <h3 class="panel-title"><?php echo returnIntLang('trash structure legend'); ?></h3>
                                    <p class="panel-subtitle"><?php echo returnIntLang('trash structure info handledelete '.((isset($_SESSION['wspvars']['handledelete'])?intval($_SESSION['wspvars']['handledelete']):'undefined'))); ?></p>
                                </div>
                                <div class="panel-body">
                                    <form method="post" name="structure-action-form" id="structure-action-form">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th><?php echo returnIntLang('trash contentstructure'); ?></th>
                                                    <th><?php echo returnIntLang('trash template'); ?></th>
                                                    <th><?php echo returnIntLang('trash contentareas'); ?></th>
                                                    <th><?php echo returnIntLang('trash content removed'); ?></th>
                                                    <th class='text-right'><?php echo returnIntLang('str action'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($tm_data['set'] AS $tmk => $tmv) {

                                                    // return name of menupoint (set error output before to overwrite if data is set)
                                                    $mdescs = (trim($tmv['description'])!='')?trim($tmv['description']):returnIntLang('trash no structure avaiable');
                                                    $mdescl = unserializeBroken($tmv['langdescription']);
                                                    $template = $tmv['templates_id'];

                                                    echo "<tr>";
                                                    echo "<td><input type='checkbox' class='mid-action' id='mid-".$tmv['mid']."-action' name='mid[]' value='".$tmv['mid']."' /></td>";
                                                    echo "<td>".$mdescs."</td>";
                                                    echo "<td>Template ".$template."</td>";
                                                    echo "<td>???</td>";
                                                    echo "<td>".date(returnIntLang('format date time', false), $tmv['changetime'])."</td>";
                                                    echo "<td class='text-right'>";
                                                    // revert trashing
                                                    echo " <i class='far fa-undo fa-btn btn-success' onclick=\"$('#mid-".$tmv['mid']."-action').prop('checked', true); $('#structure-action').val('mu'); $('#structure-action-form').submit();\"></i>";
                                                    // finalize trashing
                                                    echo " <i class='far fa-trash fa-btn btn-danger' onclick=\"$('.mid-action').prop('checked', false); $('#mid-".$tmv['mid']."-action').prop('checked', true); $('#structure-action').val('mt'); $('#structure-action-form').submit();\"></i>";
                                                    echo "</td>";
                                                    echo "</tr>";
                        
                                                } ?>
                                            </tbody>
                                        </table>
                                        <input type="hidden" id="content-action" name="op" value="" />
                                    </form>
                                    <p><a id="#" class="btn btn-success disabled"><?php echo returnIntLang('trash untrash selected structure entries', false); ?></a> <a id="#" class="btn btn-danger disabled"><?php echo returnIntLang('trash clear selected structure entries', false); ?></a> <a id="emptystructure" class="btn btn-danger"><?php echo returnIntLang('trash clear all structure entries', false); ?></a></p>
                                </div>
                            </div>
                        </div>
                        <form name="" id="emptystructure_form" method="post">
                            <input type="hidden" name="op" value="cas" />
                        </form>
                        <script>

                            $('#emptystructure').on('click', function(e) {
                                e.preventDefault();
                                swal(
                                {
                                    title: '<?php echo returnIntLang('trash really clear all structure entries?', false); ?>',
                                    text: "<?php echo returnIntLang('trash this action cannot be undone', false); ?>",
                                    type: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#F9354C',
                                    cancelButtonColor: '#41B314',
                                    confirmButtonText: '<?php echo returnIntLang('str delete', false); ?>',
                                    cancelButtonText: '<?php echo returnIntLang('str cancel', false); ?>'
                                }).then(function()
                                {
                                    $('#emptystructure_form').submit();
                                }).catch(swal.noop);
                            });

                        </script>
                    </div>
                <?php } 
                if ($tc_data['num']>0) { ?>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel">
                                <div class="panel-heading">
                                    <h3 class="panel-title"><?php echo returnIntLang('trash content legend'); ?></h3>
                                    <p class="panel-subtitle"><?php echo returnIntLang('trash content info'); ?></p>
                                </div>
                                <div class="panel-body">
                                    <form method="post" name="content-action-form" id="content-action-form">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th><?php echo returnIntLang('trash contentstructure'); ?></th>
                                                    <th><?php echo returnIntLang('trash contentarea'); ?></th>
                                                    <th><?php echo returnIntLang('trash content element'); ?></th>
                                                    <th><?php echo returnIntLang('trash content interpreter'); ?></th>
                                                    <th><?php echo returnIntLang('trash content removed'); ?></th>
                                                    <th class='text-right'><?php echo returnIntLang('str action'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($tc_data['set'] AS $tck => $tsv):

                                                $mi_sql = "SELECT `description`, `langdescription` FROM `menu` WHERE `mid` = ".intval($tsv['mid']);
                                                $mi_data = doSQL($mi_sql);

                                                // return name of menupoint (set error output before to overwrite if data is set)
                                                $mdescs = returnIntLang('trash no structure avaiable');
                                                if ($mi_data['num']>0) {
                                                    $mdescs = trim($mi_data['set'][0]['description']);
                                                    $mdescl = unserializeBroken($mi_data['set'][0]['langdescription']);
                                                    if (isset($mdescl[($tsv['content_lang'])]) && trim($mdescl[($tsv['content_lang'])])!='') {
                                                        $mdescs = trim($mdescl[($tsv['content_lang'])]);
                                                    }
                                                }

                                                $cdescs = returnIntLang('trash content no desc defined');
                                                $cinfo = unserializeBroken($tsv['valuefields']);
                                                if (isset($cinfo['desc']) && trim($cinfo['desc'])!=''): $cdescs = trim($cinfo['desc']); endif;
                                                if (trim($tsv['description'])!=''): $cdescs = trim($tsv['description']); endif;

                                                if ((is_array($cinfo) && count($cinfo)==0) || (!(is_array($cinfo)) && trim($cinfo)=='')):
                                                    // there is NO content to untrash .. so we delete the item without question
                                                    doSQL("DELETE FROM `content` WHERE `cid` = ".intval($tsv['cid']));
                                                elseif (isset($cinfol) && isset($midl) && $cinfol==trim($tsv['valuefields']) && intval($tsv['mid'])==$midl):
                                                    // if mid AND content are the same .. we remove this entry because it's a duplicate of the last trashed content
                                                    doSQL("DELETE FROM `content` WHERE `cid` = ".intval($tsv['cid']));
                                                else:
                                                    $i_res = doSQL("SELECT `name` FROM `interpreter` WHERE `guid` = '".escapeSQL($tsv['interpreter_guid'])."'");
                                                    echo "<tr>";
                                                    echo "<td><input type='checkbox' class='cid-action' id='cid-".$tsv['cid']."-action' name='cid[]' value='".$tsv['cid']."' /></td>";
                                                    echo "<td>".$mdescs."</td>";
                                                    echo "<td>".$tsv['content_area']."</td>";
                                                    echo "<td>".((trim($tsv['description'])!='')?$tsv['description']:'-')."</td>";
                                                    echo "<td>".((isset($i_res['set'][0]['name']))?trim($i_res['set'][0]['name']):'<em>false</em>')."</td>";
                                                    echo "<td>".date(returnIntLang('format date time', false), $tsv['lastchange'])."</td>";
                                                    echo "<td class='text-right'>";
                                                    // revert trashing
                                                    echo " <i class='far fa-undo fa-btn btn-success' onclick=\"$('#cid-".$tsv['cid']."-action').prop('checked', true); $('#content-action').val('cu'); $('#content-action-form').submit();\"></i>";
                                                    // finalize trashing
                                                    echo " <i class='far fa-trash fa-btn btn-danger' onclick=\"$('.cid-action').prop('checked', false); $('#cid-".$tsv['cid']."-action').prop('checked', true); $('#content-action').val('ct'); $('#content-action-form').submit();\"></i>";
                                                    echo "</td>";
                                                    echo "</tr>";
                                                endif;
                                                // setup 'cinfo' "last" and 'mid' "last" to compare with next cinfo and mid
                                                $cinfol = trim($tsv['valuefields']);
                                                $midl = intval($tsv['mid']);
                                            endforeach; ?>
                                            </tbody>
                                        </table>
                                        <input type="_hidden" id="content-action" name="op" value="" />
                                    </form>
                                    <p><a id="untrashselected" class="btn btn-success"><?php echo returnIntLang('trash untrash selected contents', false); ?></a> <a id="emptyselected" class="btn btn-danger"><?php echo returnIntLang('trash clear selected contents', false); ?></a> <a id="emptycontents" class="btn btn-danger"><?php echo returnIntLang('trash clear all contents', false); ?></a></p>
                                </div>
                            </div>
                        </div>
                        <form name="" id="emptycontents_form" method="post">
                            <input type="_hidden" name="op" value="cac" />
                        </form>
                        <script>

                            $('#emptycontents').on('click', function(e) {
                                e.preventDefault();
                                swal(
                                {
                                    title: '<?php echo returnIntLang('trash really clear all contents?', false); ?>',
                                    text: "<?php echo returnIntLang('trash this action cannot be undone', false); ?>",
                                    type: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#F9354C',
                                    cancelButtonColor: '#41B314',
                                    confirmButtonText: '<?php echo returnIntLang('str delete', false); ?>',
                                    cancelButtonText: '<?php echo returnIntLang('str cancel', false); ?>'
                                }).then(function()
                                {
                                    $('#emptycontents_form').submit();
                                }).catch(swal.noop);
                            });
                            
                            $('#emptyselected').on('click', function(e) {
                                e.preventDefault();
                                swal(
                                {
                                    title: '<?php echo returnIntLang('trash really clear selected contents?', false); ?>',
                                    text: "<?php echo returnIntLang('trash this action cannot be undone', false); ?>",
                                    type: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#F9354C',
                                    cancelButtonColor: '#41B314',
                                    confirmButtonText: '<?php echo returnIntLang('str delete', false); ?>',
                                    cancelButtonText: '<?php echo returnIntLang('str cancel', false); ?>'
                                }).then(function()
                                {
                                    $('#content-action').val('ct');
                                    $('#content-action-form').submit();
                                }).catch(swal.noop);
                            });
                            
                            $('#untrashselected').on('click', function(e) {
                                alert('revoke trashing');
                            });

                        </script>
                    </div>
                <?php } ?>
            <?php } 
            else { ?>
                <div class="row">
                    <div class="col-md-12">
                        <h3 style="text-align: center; font-weight: 300"><?php echo returnIntLang('trash structure no trashed items found'); ?></h3>
                        <h1 style="text-align: center; font-size: 10vw;">
                            <i class="fa fa-coffee"></i>
                        </h1>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<?php require ("./data/include/footer.inc.php"); ?>