<?php
/**
 * Verwaltung von Globalen Inhalten
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-06-17
 */

// start session -----------------------------
session_start();
// base includes -----------------------------
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'contentedit';
$_SESSION['wspvars']['mgroup'] = 5;
$_SESSION['wspvars']['pagedesc'] = array('fa fa-sitemap',returnIntLang('menu content'),returnIntLang('menu content global'));
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
$_SESSION['wspvars']['addpagecss'] = array(
    'bootstrap-multiselect.css'
    );
$_SESSION['wspvars']['addpagejs'] = array(
    'bootstrap/bootstrap-multiselect.js'
    );
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
require ("./data/include/clsinterpreter.inc.php");
/* define page specific vars ----------------- */
/* define page specific functions ------------ */

if (isset($_POST['op']) && $_POST['op']=='create'):
    // insert new global content
	$sql = "INSERT INTO `content_global` SET `interpreter_guid` = '".escapeSQL($_POST['sid'])."', `content_lang` = '".escapeSQL($_POST['lang'])."'";
	$res = doSQL($sql);
    if ($res['aff']==1):
        $_SESSION['wspvars']['editglobalcontentid'] = intval($res['inf']);
        header('location: globalcontentedit.php');
    else:
        addWSPMsg('errormsg', '<p>'.returnIntLang('globalcontent error creating globalcontent', true).'</p>');
    endif;
endif;

if (isset($_POST['op']) && $_POST['op']=='delete' && isset($_POST['gcid']) && intval($_POST['gcid'])>0):
	if (intval($_POST['gcid'])>0):
		// update contentchange to menupoints
		$sql = "UPDATE `menu` AS `m`, `content` AS `c` SET `m`.`contentchanged` = 1 WHERE `c`.`mid` = `m`.`mid` AND `c`.`globalcontent_id` = ".intval(intval($_POST['gcid']));
		doSQL($sql);
        // delete global contents from content table by given id
		$sql = "DELETE FROM `content` WHERE `globalcontent_id` = ".intval($_POST['gcid']);
		$aff = doSQL($sql);
        if ($aff['res']):
			if ($aff['aff']>0):
				addWSPMsg('resultmsg', '<p>'.returnIntLang('globalcontent removed from contents 1', true).' '.$aff['aff'].' '.returnIntLang('globalcontent removed from contents 2', true).'</p>');
			else:
				addWSPMsg('resultmsg', '<p>'.returnIntLang('globalcontent deleted from contents no affected', true).'</p>');
			endif;
		else:
			addWSPMsg('errormsg', '<p>'.returnIntLang('globalcontent not deleted from contents', true).'</p>');
		endif;
		// delete global contents from global content table by given id
		$sql = "UPDATE `content_global` SET `trash` = 1 WHERE `id` = ".intval($_POST['gcid']);
		$aff = doSQL($sql);
        if ($aff['aff']>0):
			addWSPMsg('resultmsg', '<p>'.returnIntLang('globalcontent deleted from globalcontents', true).'</p>');
		else:
			addWSPMsg('errormsg', '<p>'.returnIntLang('globalcontent not deleted from globalcontents', true).'</p>');
		endif;
	endif;
endif;

// head der datei
require ("data/include/header.inc.php");
require ("data/include/navbar.inc.php");
require ("data/include/sidebar.inc.php");
?>
<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('globalcontent headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('globalcontent legend'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
            <?php
            
            if ((array_key_exists('workspacelang', $_SESSION['wspvars']) && $_SESSION['wspvars']['workspacelang']=="") || (!(array_key_exists('workspacelang', $_SESSION['wspvars'])))):
                $_SESSION['wspvars']['workspacelang'] = $_SESSION['wspvars']['sitelanguages']['shortcut'][0];
            endif;
            if (isset($_REQUEST['wsl']) && trim($_REQUEST['wsl'])!=""):
                $_SESSION['wspvars']['workspacelang'] = trim($_REQUEST['wsl']);
            endif;
            
            $globalcontents_sql = "SELECT * FROM `content_global` WHERE `trash` = 0 AND (`content_lang` = '".$_SESSION['wspvars']['workspacelang']."' || `content_lang` = '') ORDER BY `interpreter_guid`";
            $globalcontents_res = doSQL($globalcontents_sql);
            
            if ($globalcontents_res['num']>0):
            ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('globalcontent existing contents for lang', true); ?> "<?php echo (isset($_SESSION['wspvars']['sitelanguages']['longname'][(@array_keys($_SESSION['wspvars']['sitelanguages']['shortcut'], $_SESSION['wspvars']['workspacelang'])[0])]))?($_SESSION['wspvars']['sitelanguages']['longname'][(@array_keys($_SESSION['wspvars']['sitelanguages']['shortcut'], $_SESSION['wspvars']['workspacelang'])[0])]):$_SESSION['wspvars']['workspacelang']; ?>"</h3>
                            <?php panelOpener(true, array(), true); ?>
                            <?php 
                            // block to define workspace language
                            if (intval(count($_SESSION['wspvars']['sitelanguages']['shortcut']))>1):
                                ?>
                                <div class="right">
                                    <div class="dropdown">
                                        <a href="#" class="toggle-dropdown" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-globe"></i> <?php echo strtoupper($_SESSION['wspvars']['workspacelang']); ?></a>
                                        <ul class="dropdown-menu dropdown-menu-right">
                                            <?php
                                            
                                            foreach ($_SESSION['wspvars']['sitelanguages']['shortcut'] AS $key => $value):
                                                if (trim($_SESSION['wspvars']['sitelanguages']['longname'][$key])!=''):
                                                    echo "<li><a href='?wsl=".$_SESSION['wspvars']['sitelanguages']['shortcut'][$key]."'>";
                                                    echo "<i class=\"fa ";
                                                    echo ($_SESSION['wspvars']['workspacelang']==$_SESSION['wspvars']['sitelanguages']['shortcut'][$key]) ? 'fa-check-circle' : 'fa-globe';
                                                    echo "\"></i>".$_SESSION['wspvars']['sitelanguages']['longname'][$key]."</a></li>";
                                                endif;
                                            endforeach;
                                            
                                            ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="panel-body">
                            <?php
                            
                            $globalcontents_sql = "SELECT * FROM `content_global` WHERE `trash` = 0 AND (`content_lang` = '".$_SESSION['wspvars']['workspacelang']."' || `content_lang` = '') ORDER BY `interpreter_guid`";
                            $globalcontents_res = doSQL($globalcontents_sql);
                            
                            if ($globalcontents_res['num']>0): ?>
                            <script type="text/javascript">

                            function showUsage(usageid) {
                                $('#gcusage_' + usageid).toggle('blind');
                                }

                            function delGlobalContent(gcid) {
                                if (confirm('<?php echo returnIntLang('globalcontent confirm delete', false); ?>')) {
                                    document.getElementById('opdelete').value = 'delete';
                                    document.getElementById('iddelete').value = gcid;
                                    document.getElementById('deleteglobal').submit();
                                    }
                                }

                            function editGlobalContent(gcid) {
                                document.getElementById('opedit').value = 'edit';
                                document.getElementById('idedit').value = gcid;
                                document.getElementById('editglobal').submit();
                                }

                            </script>
                            <?php
                            foreach ($globalcontents_res['set'] AS $gck => $gcv):
                                
                                $guid = $gcv['interpreter_guid'];
                                
                                $interpreter_sql = "SELECT * FROM `interpreter` WHERE `guid` = '".escapeSQL($guid)."'";
                                $interpreter_res = doSQL($interpreter_sql);

                                if ($interpreter_res['num']>0):
                                    $file = $interpreter_res['set'][0]["parsefile"];
                                    $name = $interpreter_res['set'][0]["name"];
                                else:
                                    $file = 'genericwysiwyg';
                                    $name = returnIntLang('hint generic wysiwyg', false);
                                endif;
                                
                                $fieldvalue = unserializeBroken($gcv['valuefields']);
                                $interpreterdesc = returnIntLang('globalcontent interpreter desc not found');
                                // Interpreter einlesen
                                if (is_file(DOCUMENT_ROOT."/".WSP_DIR."/data/interpreter/".$file)):
                                    require DOCUMENT_ROOT."/".WSP_DIR."/data/interpreter/".$file;
                                    $clsInterpreter = new $interpreterClass;
                                    $interpreterdesc = $name." » ".$clsInterpreter->getView($fieldvalue, 'array');
                                elseif ($file=='genericwysiwyg'):
                                    // genericwysiwyg
                                    $genericvalue = unserializeBroken($gcv['valuefields']);
                                    $interpreterdesc = returnIntLang('hint generic wysiwyg', false);
                                    if (trim($genericvalue['desc'])!=''):
                                        $interpreterdesc.= " » ".$genericvalue['desc'];
                                    endif;
                                else:
                                    $interpreterdesc = $name.' <i class="far fa-exclamation-triangle text-danger"></i>';
                                    addWSPMsg('errormsg', returnIntLang('globalcontent could not read parser file for interpreter1', false).' <strong>'.$name.'</strong> '.((WSP_DEV)?'('.$file.') ':'').returnIntLang('globalcontent could not read parser file for interpreter2', false));
                                endif;
                                if (trim($gcv['content_lang'])=='') {
                                    $interpreterdesc.= ' <i class="far fa-globe"></i>';
                                }    
                            
                                // getting contents
                                // jetzt mit zusätzlicher Überprüfung ob die Contents nicht gelöscht sind
                                $gcusage_sql = "SELECT m.`description` AS `menudesc`, m.`mid` AS `mid`, c.`cid` AS `cid` FROM `content` AS c, `menu` AS m WHERE c.`globalcontent_id` = ".intval($gcv['id'])." AND c.`mid` = m.`mid` AND c.`trash` = 0 AND m.`trash` = 0 GROUP BY c.`mid`";
                                $gcusage_res = doSQL($gcusage_sql);
                                $gcusage_num = $gcusage_res['num'];

                                $gctemplate_sql = "SELECT `name` FROM `templates` WHERE `template` LIKE '%[\%GLOBALCONTENT:".intval($gcv['id'])."\%]%' GROUP BY `name`";
                                $gctemplate_res = doSQL($gctemplate_sql);
                                $gctemplate_num = $gctemplate_res['num'];
                                
                                echo "<div class='row'>";
                                    echo "<div class='col-md-5'><a onclick=\"editGlobalContent(".intval($gcv['id']).");\" style=\"cursor: pointer;\">".$interpreterdesc."</a></div>";
                                    echo "<div class='col-md-3'>";
                                    if ($gcusage_num>0): echo "<a onclick=\"showUsage(".intval($gcv['id']).");\" style='cursor: pointer;'>".intval($gcusage_num)." ".returnIntLang('globalcontent content usages', true)."</a>"; else: echo returnIntLang('globalcontent no content usages', true); endif;
                                    echo "</div>";
                                    echo "<div class='col-md-3'>";
                                    if ($gctemplate_num>0): echo "".intval($gctemplate_num)." ".returnIntLang('globalcontent template usages', true).""; else: echo returnIntLang('globalcontent no template usages', true); endif;
                                    echo "</div>";
                                    echo "<div class='col-md-1 text-right'>";
                                    // edit globalcontent option
                                    echo "<a onclick=\"editGlobalContent(".intval($gcv['id']).");\"><i class='fa fa-btn fa-edit fa-btn'></i></a> ";        
                                    // delete globalcontent option
                                    if ($gctemplate_num==0):
                                        echo "<a onclick=\"delGlobalContent(".intval($gcv['id']).");\"><i class='fa fa-btn fa-trash fa-btn'></i></a>";
                                    endif;
                                    echo "</div>";
                                echo "</div>";
                                if ($gcusage_num>0):
                                    echo "<div class='row' style='padding-top: 10px; display: none;' id='gcusage_".intval($gcv['id'])."'>";
                                    echo "<div class='col-md-12'>";
                                    foreach ($gcusage_res['set'] AS $gcuk => $gcuv):
                                        // sgc = show global content
                                        echo "<a href=\"./contentedit.php?sgc=".intval($gcuv['mid'])."\" class='btn btn-xs fa-btn' style='margin-top: 2px; margin-right: 3px;'>".trim($gcuv['menudesc'])."</a>";
                                    endforeach;
                                    echo "</div>";
                                    echo "</div>";
                                endif;
                            echo "<hr />";
                        endforeach;
                            ?>
                            <form name="deleteglobal" id="deleteglobal" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                                <input type="hidden" name="op" id="opdelete" value="" /><input type="hidden" name="gcid" id="iddelete" value="" />
                            </form>
                            <form name="editglobal" id="editglobal" method="post" action="globalcontentedit.php">
                                <input type="hidden" name="op" id="opedit" value="" /><input type="hidden" name="gcid" id="idedit" value="" />
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: // no global contents found ?>
            <div class="row">
                <div class="col-md-12">
                    <?php include ("./data/panels/globalcontents.notfound.inc.php"); ?>
                </div>
            </div>
            <?php endif; ?>
            <div class="row">
               <div class="col-md-12">
                    <?php include ("./data/panels/globalcontents.create.inc.php"); ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include ("./data/include/footer.inc.php"); ?>