<?php
/**
 * edit and create templates
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2021-09-15
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'design';
$_SESSION['wspvars']['pagedesc'] = array('fa fa-paint-brush',returnIntLang('menu design'),returnIntLang('menu design templates'));
$_SESSION['wspvars']['mgroup'] = 4;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = true;
$_SESSION['wspvars']['addpagejs'] = array(
    'jquery/jquery.autogrowtextarea.js',
    'jquery/jquery.nestable.js',
    );
// second includes ---------------------------
require ("./data/include/checkuser.inc.php");
require ("./data/include/siteinfo.inc.php");
// define page specific vars -----------------
// header disables the problem with sending jquery-script-call in templates sourcecode 
header("X-XSS-Protection: 0");

/* define page specific functions ------------ */

if (isset($_REQUEST['op']) && $_REQUEST['op']=='savetemplate'):
    if (intval($_POST['id'])==0):
		// add template
		$sql = "INSERT INTO `templates` SET `name` = '".escapeSQL(trim($_POST['templatename']))."', `template` = '".escapeSQL($_POST['template'])."', `bodytag` = '".escapeSQL($_POST['bodytag'])."', `head` = '".escapeSQL($_POST['selfhead'])."', `generic_viewport` = '".escapeSQL(trim($_POST['generic_viewport']))."'";
		if (array_key_exists('framework', $_POST)):
			$sql.= ", `framework` = '".serialize($_POST['framework'])."'";
		else:
			$sql.= ", `framework` = ''";
		endif;
		if (array_key_exists('fonts', $_POST)):
			$sql.= ", `fonts` = '".serialize($_POST['fonts'])."'";
		else:
			$sql.= ", `fonts` = ''";
		endif;
		$id = getInsSQL($sql);
		// create output message
		$_SESSION['wspvars']['resultmsg'] = 'Das Template "'.trim($_POST['templatename']).'" wurde angelegt.';
	else:
        $id = intval($_POST['id']);
		// update template
		$sql = "UPDATE `templates` SET `name` = '".escapeSQL(trim($_POST['templatename']))."', `template` = '".escapeSQL($_POST['template'])."', `bodytag` = '".escapeSQL($_POST['bodytag'])."', `head` = '".escapeSQL($_POST['selfhead'])."', `generic_viewport` = '".escapeSQL(trim($_POST['generic_viewport']))."'";
		if (array_key_exists('framework', $_POST)):
			$sql.= ", `framework` = '".serialize($_POST['framework'])."'";
		else:
			$sql.= ", `framework` = ''";
		endif;
		if (array_key_exists('fonts', $_POST)):
			$sql.= ", `fonts` = '".serialize($_POST['fonts'])."'";
		else:
			$sql.= ", `fonts` = ''";
		endif;
		$sql.= " WHERE `id` = ".intval($_POST['id']);
		doSQL($sql);

        // find all menupoints using this template (and connected menupoints, that use template from upper menupoint
        $mid = getTemplateTree($id);
		foreach($mid as $mk => $mv):
			$sql = "UPDATE `menu` SET `contentchanged` = 3 WHERE `mid` = ".intval($mv);
			doSQL($sql);
		endforeach;
		// create output message
		addWSPMsg('resultmsg', returnIntLang('templates changes saved 1')." \"".trim($_POST['templatename'])."\" ".returnIntLang('templates changes saved 2'));
	endif;

	// connect template and selected css-files in selected order
	doSQL("DELETE FROM `r_temp_styles` WHERE `templates_id` = ".intval($id));
	if (isset($_POST['cssfiles']) && is_array($_POST['cssfiles'])):
		foreach ($_POST['cssfiles'] AS $value):
			doSQL("INSERT INTO `r_temp_styles` SET `templates_id` = ".intval($id).", `stylesheets_id` = ".intval($value));
		endforeach;
	endif;
	// connect template and selected js-files and libraries in selected order
	doSQL("DELETE FROM `r_temp_jscript` WHERE `templates_id` = ".intval($id));
	if (isset($_POST['jsfiles']) && $_POST['jsfiles']!="" && is_array($_POST['jsfiles'])):
		foreach ($_POST['jsfiles'] AS $value):
			doSQL("INSERT INTO `r_temp_jscript` SET `templates_id` = ".intval($id).", `javascript_id` = ".intval($value));
		endforeach;
	endif;
	// connect template and selected rss-file
	/*
    $sql = "DELETE FROM `r_temp_rss` WHERE `templates_id` = ".intval($id);
	mysql_query($sql);
	if ($_POST['rssfile']>0):
		$sql = "INSERT INTO `r_temp_rss` SET `templates_id` = ".intval($id).", `rss_id` = ".intval($_POST['rssfile']);
		mysql_query($sql);
	endif;
    */
elseif (isset($_REQUEST['op']) && $_REQUEST['op']=='defaulttemplate' && isset($_REQUEST['id']) && intval(checkParamVar('id'))>0):

    // set selected template as default template
	$sql = "DELETE FROM `wspproperties` WHERE `varname` = 'templates_id'";
	doSQL($sql);
	$sql = "INSERT INTO `wspproperties` SET `varname` = 'templates_id', `varvalue` = '".intval(checkParamVar('id'))."'";
	doSQL($sql);

elseif (isset($_REQUEST['op']) && $_REQUEST['op']=='deltemplate'):
    // remove template
	doSQL("DELETE FROM `templates` WHERE `id` = ".intval($_POST['deleteid']));
	// remove js connect
	doSQL("DELETE FROM `r_temp_jscript` WHERE `templates_id` = ".intval($_POST['deleteid']));
	// remove rss connect
	doSQL("DELETE FROM `r_temp_rss` WHERE `id` = ".intval($_POST['deleteid']));
	// remove stylesheet connect
	doSQL("DELETE FROM `r_temp_styles` WHERE `id` = ".intval($_POST['deleteid']));
	// update menu table to set entries to use upper template
	doSQL("UPDATE `menu` SET `templates_id` = 0, contentchanged = 3, changetime = ".time()." WHERE `templates_id` = ".intval($_POST['deleteid']));
endif;

/* include head ------------------------------ */
require("./data/include/header.inc.php");
require("./data/include/navbar.inc.php");
require("./data/include/sidebar.inc.php");
?>
<!-- MAIN -->
<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('templates headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('templates info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
            <?php
            
            $templates_sql = "SELECT `id`, `name` FROM `templates` ORDER BY `name`";
            $templates_res = doSQL($templates_sql);
            
            if ($templates_res['num']>0 && checkParamVar('op')!='edit') { ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel">
                            <div class="panel-heading">
                                <h3 class="panel-title"><?php echo returnIntLang('templates existingtemplates'); ?></h3>
                                <?php panelOpener(false, array('op',array('clone','new','edit'),array(false,false,false)), true, 'existingtemplates'); ?>
                            </div>
                            <div class="panel-body">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th class="col-md-3"><?php echo returnIntLang('str templatename'); ?></th>
                                            <th class="col-md-4"><?php echo returnIntLang('str usage'); ?></th>
                                            <th class="col-md-2"><?php echo returnIntLang('str connection css'); ?></th>
                                            <th class="col-md-2"><?php echo returnIntLang('str connection js'); ?></th>
                                            <th class="col-md-1"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php

                                        foreach ($templates_res['set'] AS $trk => $trv):
                                            echo "<tr>";
                                            echo "<td>".$trv['name']."</td>";
                                            // get usage of template 
                                            $tempusage_sql = "SELECT `mid`, `templates_id`, `description` FROM `menu` WHERE (`templates_id` = ".intval($trv['id'])." OR `templates_id` = 0) AND `trash` = 0";
                                            $tempusage_res = doSQL($tempusage_sql);
                                            $usage = array();
                                            foreach ($tempusage_res['set'] AS $tuk => $tuv):
                                                if ($tuv['templates_id']==intval($trv['id'])):
                                                    // page uses THIS template
                                                    $usage[intval($tuv['mid'])] = trim($tuv['description']);
                                                else:
                                                    // check for pages that use parent pages template    
                                                    if (getTemplateID(intval($tuv['mid']))==intval($trv['id'])):
                                                        $usage[intval($tuv['mid'])] = trim($tuv['description']);
                                                    endif;
                                                endif;
                                            endforeach;
                                            sort($usage);
                                            echo "<td>";
                                            if (count($usage)>4):
                                                echo "<span data-toggle='tooltip' data-placement='top' data-original-title='";
                                                echo implode(", ", $usage);
                                                echo "'>".(count($usage))." ".returnIntLang('str menupoints')."</span>";
                                            elseif (count($usage)>0):
                                                echo "<span style='white-space: nowrap;'>".implode("</span>, <span style='white-space: nowrap;'>", $usage)."</span>";
                                            else:
                                                echo "-";
                                            endif;    
                                            echo "</td>";
                                            // get connections to stylesheets, javascript, etc.
                                            echo "<td>";
                                            $usedcss_sql = "SELECT `stylesheets_id` FROM `r_temp_styles` WHERE `templates_id` = ".intval($trv['id']);
                                            $usedcss_res = doSQL($usedcss_sql);
                                            if ($usedcss_res['num']>0):
                                                foreach($usedcss_res['set'] AS $uck => $ucv):
                                                    $cssdata_sql = "SELECT `describ`, `cfolder`, `lastchange` FROM `stylesheets` WHERE id = ".intval($ucv['stylesheets_id']);
                                                    $cssdata_res = doSQL($cssdata_sql);
                                                    if ($cssdata_res['num']!=0):
                                                        echo trim($cssdata_res['set'][0]['describ']);
                                                        if (trim($cssdata_res['set'][0]['cfolder'])!="" && trim($cssdata_res['set'][0]['cfolder'])!=$cssdata_res['set'][0]['lastchange']):
                                                            echo " <em>".returnIntLang('str library')."</em>"; 
                                                        endif;
                                                        echo "<br />";
                                                    endif;
                                                endforeach;
                                            endif;
                                            echo "</td>";
                                            echo "<td>";
                                            // show used javascript-files
                                            $usedscript_sql = "SELECT `javascript_id` FROM `r_temp_jscript` WHERE `templates_id` = ".intval($trv['id']);
                                            $usedscript_res = doSQL($usedscript_sql);
                                            if ($usedscript_res['num']>0):
                                                foreach($usedscript_res['set'] AS $usk => $usv):
                                                    $jsdata_sql = "SELECT `describ`, `cfolder`, `lastchange` FROM `javascript` WHERE id = ".intval($usv['javascript_id']);
                                                    $jsdata_res = doSQL($jsdata_sql);
                                                    if ($jsdata_res['num']!=0):
                                                        echo trim($jsdata_res['set'][0]['describ']);
                                                        if (trim($jsdata_res['set'][0]['cfolder'])!="" && trim($jsdata_res['set'][0]['cfolder'])!=$jsdata_res['set'][0]['lastchange']):
                                                            echo " <em>Library</em>"; 
                                                        endif;
                                                        echo "<br />";
                                                    endif;
                                                endforeach;
                                            endif;
                                            echo "</td>";
                                            // rss is (temp?) deactivated - 2017-09-07
                                            /*
                                            $usedrss_sql = "SELECT `rss_id` FROM `r_temp_rss` WHERE `templates_id` = ".intval($trv['id']);
                                            $usedrss_res = doSQL($usedrss_sql);
                                            if ($usedrss_res['num']>0):
                                                $rssdata_sql = "SELECT `rsstitle` FROM `rssdata` WHERE rid = '".mysql_result($usedrss_res,0)."'";
                                                $rssdata_res = mysql_query($rssdata_sql);
                                                $rssdata_num = mysql_num_rows($rssdata_res);
                                                if ($rssdata_num!=0):
                                                    echo "<span style=\"float: left; width: 3em;\">RSS: </span>".mysql_result($rssdata_res,0);
                                                endif;
                                            endif;
                                            */
                                            $deftemp = intval(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'templates_id'"));

                                            if ($deftemp==intval($trv['id'])):
                                                $del = "<i class='fa fa-ban fa-disabled fa-btn'></i> ";
                                                $standard = "<i class='fa fa-check-circle fa-btn btn-success'></i> ";
                                            else:
                                                if (count($usage)>0):
                                                    $del = "<i class='fa fa-ban fa-disabled fa-btn btn-disabled'></i> ";
                                                else:
                                                    $del = "<a onclick=\"return confirmDeleteTemplate('".$trv['name']."', ".$trv['id'].");\"><i class='fa fa-btn fa-trash btn-danger'></i></a> ";
                                                endif;
                                                $standard = "<a href=\"".$_SERVER['PHP_SELF']."?op=defaulttemplate&id=".$trv['id']."\"><i class='fa fa-btn fa-check-circle'></i></a> ";
                                            endif;
                                            // clone button
                                            $cloneit = "<a onclick=\"document.getElementById('action_".$trv['id']."').value = 'clone'; document.getElementById('tempaction_".$trv['id']."').submit();\"><i class='fa fa-clone fa-btn'></i></a> ";
                                            // edit button
                                            $edittemplate = "<a onclick=\"document.getElementById('action_".$trv['id']."').value = 'edit'; document.getElementById('tempaction_".$trv['id']."').submit();\" style='cursor: pointer;'><i class='fas fa-pencil-alt fa-btn'></i></a> ";

                                            $links = trim($edittemplate.$cloneit.$del.$standard);

                                            echo "<td class='text-right' nowrap='nowrap'>".$links." <form name='tempaction' id='tempaction_".$trv['id']."' method='post' action='".$_SERVER['PHP_SELF']."#tpleditor'><input type='hidden' name='op' id='action_".$trv['id']."' value='edit' /><input type='hidden' name='id' id='id_".$trv['id']."' value='".$trv['id']."' /></form></td>";
                                            echo "</tr>";
                                        endforeach;

                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php }
            
            if ((checkParamVar('op')=='edit' || checkParamVar('op')=='clone' && intval(checkParamVar('id'))>0) || checkParamVar('op')=='new'): 
                
                $edittemp_res = array('num' => 0);
                if (intval(checkParamVar('id'))>0):
                    $edittemp_sql = "SELECT * FROM `templates` WHERE `id` = ".intval(checkParamVar('id'));
                    $edittemp_res = doSQL($edittemp_sql);
                endif;
                if ($edittemp_res['num']>0):
                    $edittemp_name = $edittemp_res['set'][0]['name'];
                    $edittemp_temp = $edittemp_res['set'][0]['template'];
                    if ($_SESSION['wspvars']['stripslashes']>0):
                        for ($strip=0; $strip<$_SESSION['wspvars']['stripslashes']; $strip++):
                            $edittemp_temp = stripslashes($edittemp_temp);
                        endfor;
                    endif;
                    $edittemp_body = $edittemp_res['set'][0]['bodytag'];
                    $edittemp_head = $edittemp_res['set'][0]['head'];
                    if ($_SESSION['wspvars']['stripslashes']>0):
                        for ($strip=0; $strip<$_SESSION['wspvars']['stripslashes']; $strip++):
                            $edittemp_head = stripslashes($edittemp_head);
                        endfor;
                    endif;
                    $edittemp_generic_viewport = $edittemp_res['set'][0]['generic_viewport'];
                    $edittemp_framework = unserializeBroken($edittemp_res['set'][0]['framework']);
                    $edittemp_fonts = unserializeBroken($edittemp_res['set'][0]['fonts']);
                    if (checkParamVar('op')=='clone'):
                        $edittemp_name = $edittemp_name." (".returnIntLang('str copy', false).")";
                    endif;
                else:
                    $edittemp_name = '';
                    $edittemp_temp = '';
                    $edittemp_body = '';
                    $edittemp_head = '';
                    $edittemp_generic_viewport = '';
                    $edittemp_framework = array();
                    $edittemp_fonts = array();
                endif;
                
                ?>
                <section id="#tpleditor">
                    <form id="formedittemplate" name="formedittemplate" method="post">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="panel">
                                    <div class="panel-heading">
                                        <h3 class="panel-title"><?php echo returnIntLang('templates templatename'); ?></h3>
                                        <?php panelOpener(false, array('op',array('clone','new','edit'),array(true,true,false))); ?>
                                    </div>
                                    <div class="panel-body">
                                        <input type="text" id="templatename" name="templatename" value="<?php echo $edittemp_name; ?>" class="form-control" />
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel">
                                    <div class="panel-heading">
                                        <h3 class="panel-title"><?php echo returnIntLang('templates fontsource'); ?></h3>
                                        <?php panelOpener(false, array('op',array('clone','new','edit'),array(false,true,false))); ?>
                                    </div>
                                    <div class="panel-body">
                                        <div class="form-group">
                                            <select name="fonts[source]" id="font_source" class="form-control">
                                                <option value=""><?php echo returnIntLang('hint choose', false); ?></option>
                                                <option value="google" <?php if (isset($edittemp_fonts['source']) && $edittemp_fonts['source']=='google') echo " selected='selected' "; ?>>Google Fonts</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label><?php echo returnIntLang('templates fonts'); ?></label>
                                            <input type="text" name="fonts[list]" id="fonts_list" class="form-control" value="<?php if (isset($edittemp_fonts['list']) && $edittemp_fonts['list']!='') echo prepareTextField($edittemp_fonts['list']); ?>" placeholder="<?php echo returnIntLang('templates fonts hint', false); ?>" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel">
                                    <div class="panel-heading">
                                        <h3 class="panel-title"><?php echo returnIntLang('templates generic viewport'); ?></h3>
                                        <?php panelOpener(false, array('op',array('clone','new','edit'),array(false,true,false))); ?>
                                    </div>
                                    <div class="panel-body">
                                        <input type="text" name="generic_viewport" id="generic_viewport" class="form-control" value="<?php if (isset($edittemp_generic_viewport)) echo htmlspecialchars($edittemp_generic_viewport); ?>" />
                                    </div>
                                </div>
                            </div>
                            <?php
                            
                            /*
                            
                            <div class="col-md-4">
				                <div class="panel">
                                    <div class="panel-heading">
                                        <h3 class="panel-title"><?php echo returnIntLang('templates rss'); ?></h3>
                                        <?php panelOpener(false, array('op',array('clone','new','edit'),array(false,true,false))); ?>
                                    </div>
                                    <div class="panel-body">
                                        <?php
                            
                                        $rssfiles_sql = "SELECT * FROM rssdata";
                                        $rssfiles_res = mysql_query($rssfiles_sql);
                                        if ($rssfiles_res):
                                            $rssfiles_num = mysql_num_rows($rssfiles_res);
                                        endif;
                                        if ($rssfiles_num==0):
                                            echo returnIntLang('templates norssdefined');
                                        else:
                                            ?><select name="rssfile" id="rssfile" size="1" class="one full">
                                                <option value="0"><?php echo returnIntLang('hint choose', false); ?></option>
                                                <?php
                                                for ($r=0;$r<$rssfiles_num;$r++):
                                                    $rsscon_sql = "SELECT * FROM `r_temp_rss` WHERE `templates_id` = '".$id."' AND `rss_id` = '".mysql_result($rssfiles_res,$r,"rid")."'";
                                                    $rsscon_res = @mysql_query($rsscon_sql);
                                                    $rsscon_num = @mysql_num_rows($rsscon_res);
                                                    echo "<option value=\"".mysql_result($rssfiles_res,$r,"rid")."\"";
                                                    if ($rsscon_num!=0):
                                                        echo " selected";
                                                    endif;
                                                    echo ">".mysql_result($rssfiles_res,$r,"rsstitle")."</option>";
                                                endfor;
                                                ?>
                                            </select><?php
                                        endif;
                                        
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            
                            */
                            
                            ?>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="panel">
                                    <div class="panel-heading">
                                        <h3 class="panel-title"><?php echo returnIntLang('templates frameworks'); ?></h3>
                                        <?php panelOpener(false, array('op',array('clone','new','edit'),array(false,true,false))); ?>
                                    </div>
                                    <div class="panel-body">
                                        <ul class="checklist" style='padding: 0px; list-style-type: none;'>
                                            <?php if(is_file(cleanPath(DOCUMENT_ROOT.'/data/script/jquery/jquery-3.3.1.js'))): ?>
                                                <li><input type="checkbox" name="framework[jquery]" id="framework_jquery" value="1" <?php if (is_array($edittemp_framework) && array_key_exists('jquery', $edittemp_framework) && intval($edittemp_framework['jquery'])==1): echo "checked=\"checked\""; endif; ?> /> jQuery local 3.3.1 (JavaScript)</li>
                                            <?php else: ?>
                                                <li style="color: #ddd;"><input type="checkbox" disabled="disabled" value="0" /> jQuery local 3.3.1 not installed</li>
                                            <?php endif; ?>
                                            <li><input type="checkbox" name="framework[jquerygoogle]" id="framework_jquerygoogle" value="1" <?php if (is_array($edittemp_framework) && array_key_exists('jquerygoogle', $edittemp_framework) && intval($edittemp_framework['jquerygoogle'])==1): echo "checked=\"checked\""; endif; ?> /> jQuery 3.3.1 @ google (JavaScript)</li>
                                            <?php if(is_file('../data/script/bootstrap/bootstrap.js') && is_file('../media/layout/bootstrap/bootstrap.css')): ?>
                                                <li><input type="checkbox" name="framework[bootstrap]" id="framework_bootstrap" value="1" <?php if (is_array($edittemp_framework) && array_key_exists('bootstrap', $edittemp_framework) && intval($edittemp_framework['bootstrap'])==1): echo "checked=\"checked\""; endif; ?> /> bootstrap local 4.1.3 (JavaScript)</li>
                                            <?php else: ?>
                                                <li style="color: #ddd;"><input type="checkbox" disabled="disabled" value="0" /> bootstrap local 4.1.3 not installed</li>
                                            <?php endif; ?>
                                            <li><input type="checkbox" name="framework[covifuncs]" id="framework_covifuncs" value="1" <?php if (is_array($edittemp_framework) && array_key_exists('covifuncs', $edittemp_framework) && intval($edittemp_framework['covifuncs'])==1): echo "checked=\"checked\""; endif; ?> /> COVI Scripts (JavaScript)</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel">
                                    <div class="panel-heading">
                                        <h3 class="panel-title"><?php echo returnIntLang('templates javascript'); ?></h3>
                                        <?php panelOpener(false, array('op',array('clone','new','edit'),array(false,true,false))); ?>
                                    </div>
                                    <div class="panel-body">
                                        <?php
				                        
                                        $javascript_sql = "SELECT `id`, `cfolder`, `describ`, `lastchange` FROM `javascript` WHERE `describ`!='' AND `cfolder` != '' ORDER BY `describ`";
                                        $javascript_res = doSQL($javascript_sql);
                                        
                                        echo "<div id='jslist'>";
                                        echo "<ul class='checklist' style='padding: 0px; list-style-type: none;'>";
                                        
                                        if ($javascript_res['num']>0) {
                                            $jsarray = array();
                                            $jscon_sql = "SELECT * FROM `r_temp_jscript` WHERE `templates_id` = ".intval(checkParamVar('id'))." ORDER BY `id`";
                                            $jscon_res = doSQL($jscon_sql);
                                            
                                            if ($jscon_res['num']>0) { 
                                                foreach ($jscon_res['set'] AS $jscrsk => $jscrsv) { 
                                                    $jsarray[intval($jscrsv['javascript_id'])]['checked'] = true; 
                                                } 
                                            }
                                            foreach ($javascript_res['set'] AS $jsrsk => $jsrsv) { 
                                                $jsarray[intval($jsrsv['id'])]['describ'] = trim($jsrsv['describ']);
                                                $jsarray[intval($jsrsv['id'])]['library'] = ($jsrsv['cfolder']!=$jsrsv['lastchange'])?true:false;
                                            }
                                            foreach ($jsarray AS $key => $value) {
                                                if (key_exists('describ', $value) && trim($value['describ'])!=""):
                                                    echo "<li id=\"item_".$key."\"><input type=\"checkbox\" name=\"jsfiles[]\" value=\"".$key."\" ";
                                                    if (key_exists('checked', $value) && $value['checked']):
                                                        echo "checked=\"checked\"";
                                                    endif;
                                                    echo " />&nbsp;<span class=\"handle\" style=\"cursor: move;\">".$value['describ']."</span> ";
                                                    if ($value['library']) {
                                                        echo "<i class='fa fa-folder-o'></i>";
                                                    }
                                                    echo "</li>";
                                                endif;
                                            }
                                        }
                                        
                                        echo "</ul>";
                                        echo "</div>";
                                        ?>
                                        <script type="text/javascript" language="javascript" charset="utf-8">
                                        $(function(){
                                            $("#jslist").nestable();
                                        });
                                        </script>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel">
                                    <div class="panel-heading">
                                        <h3 class="panel-title"><?php echo returnIntLang('templates stylesheets'); ?></h3>
                                        <?php panelOpener(false, array('op',array('clone','new','edit'),array(false,true,false))); ?>
                                    </div>
                                    <div class="panel-body">
                                        <?php

                                        $designs_sql = "SELECT `id`, `describ`, `cfolder`, `lastchange` FROM `stylesheets` ORDER BY `describ`";
                                        $designs_res = doSQL($designs_sql);

                                        if ($designs_res['num']==0):
                                           echo "<p>".returnIntLang('templates nocssdefined')."</p>";
                                        else:
                                            $cssarray = array();
                                            $csscon_sql = "SELECT `stylesheets_id` FROM `r_temp_styles` WHERE `templates_id` = ".intval(checkParamVar('id'))." ORDER BY `id`";
                                            $csscon_res = getResultSQL($csscon_sql);
                                            if (is_array($csscon_res)):
                                                foreach ($csscon_res AS $cck => $ccv):
                                                    $cssarray[$ccv]['checked'] = true;
                                                endforeach;
                                            endif;
                                            foreach ($designs_res['set'] AS $dk => $dv):    
                                                $cssarray[intval($dv['id'])]['describ'] = trim($dv['describ']);
                                                $cssarray[intval($dv['id'])]['cfolder'] = trim($dv['cfolder']);
                                                $cssarray[intval($dv['id'])]['lastchange'] = trim($dv['lastchange']);
                                            endforeach;
                                            echo "<ul id=\"csslist\" class=\"checklist\" style='padding: 0px; list-style-type: none;'>";
                                            foreach ($cssarray AS $key => $value):
                                                if (key_exists('describ', $value) && trim($value['describ'])!=""):
                                                    echo "<li id=\"item_".$key."\"><input type=\"checkbox\" name=\"cssfiles[]\" value=\"".$key."\" ";
                                                    if (key_exists('checked', $value) && $value['checked']):
                                                        echo "checked=\"checked\"";
                                                    endif;
                                                    echo " />&nbsp;<span class=\"handle\" style=\"cursor: move;\">".$value['describ'];
                                                    if ($value['cfolder']!="" && $value['cfolder']!=$value['lastchange']): echo " <em>Library</em>"; endif;
                                                    echo "</span></li>";
                                                endif;
                                            endforeach;
                                            echo "</ul>";
                                        endif; 

                                        ?>
                                        <script type="text/javascript" language="javascript" charset="utf-8">
                                        $(function(){$("#csslist").sortable();$("#csslist").disableSelection();});
                                        </script>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="panel">
                                    <div class="panel-heading">
                                        <h3 class="panel-title"><?php echo returnIntLang('templates head'); ?></h3>
                                        <?php panelOpener(true, '', false); ?>
                                    </div>
                                    <div class="panel-body">
                                        <textarea name="selfhead" id="selfhead" class="form-control autogrow" placeholder="<?php echo returnIntLang('templates head', false); ?>"><?php echo $edittemp_head; ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel">
                                    <div class="panel-heading">
                                        <h3 class="panel-title"><?php echo returnIntLang('templates bodytag'); ?></h3>
                                        <?php panelOpener(true, '', false); ?>
                                    </div>
                                    <div class="panel-body">
                                        <input type="text" name="bodytag" id="bodytag"  class="form-control" value="<?php if (isset($edittemp_body)) echo htmlspecialchars($edittemp_body); ?>" placeholder="<?php echo returnIntLang('templates bodytag', false); ?>" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-9">
                                <div class="panel">
                                    <div class="panel-heading">
                                        <h3 class="panel-title"><?php echo returnIntLang('templates pagesource'); ?></h3>
                                    </div>
                                    <div class="panel-body">
                                        <div class="form-group">
                                            <textarea name="template" id="template" cols="80" rows="15" class="form-control autogrow allowTabChar" placeholder="<?php echo returnIntLang('templates pagesource', false); ?>"><?php echo $edittemp_temp; ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="panel">
                                    <div class="panel-heading">
                                        <h3 class="panel-title"><?php echo returnIntLang('templates templatevars'); ?></h3>
                                    </div>
                                    <div class="panel-body">
                                        <p><?php echo returnIntLang('templates contentvars'); ?></p>
                                        <?php 
		
                                        $templatevars = getTemplateVars(intval(checkParamVar('id')));

                                        $siteinfo_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'contentvardesc'";
                                        $siteinfo_res = doResultSQL($siteinfo_sql);
                                        if ($siteinfo_res!==false):
                                            $contentvardesc = unserializeBroken($siteinfo_res);
                                        endif;
                                        
                                        ?><div class="form-group">
                                            <select name="globalvar" id="globalvar" size="1" class="form-control singleselect" onchange="insertVar('globalvar'); return false;">
                                                <option value=""><?php echo returnIntLang('hint choose', false); ?></option>
                                                <?php if (isset($contentvardesc) && is_array($contentvardesc)): ?>
                                                    <option value="[%CONTENTVAR%]"><?php if(array_key_exists(0, $contentvardesc) && $contentvardesc[0]!=''): echo trim($contentvardesc[0])." (".returnIntLang('templates contentelement', false)." 1)"; else: echo returnIntLang('templates contentelement', false)." 1"; endif; ?></option>
                                                    <?php for($c=1; $c<count($contentvardesc); $c++): ?>
                                                        <option value="[%CONTENTVAR:<?php echo intval($c); ?>%]"><?php if(array_key_exists($c, $contentvardesc) && trim($contentvardesc[intval($c)])!=''): echo trim($contentvardesc[$c])." (".returnIntLang('templates contentelement', false)." ".(intval($c)+1).")"; else: echo returnIntLang('templates contentelement', false)." ".(intval($c)+1); endif; ?></option>
                                                    <?php endfor; ?>
                                                <?php else: ?>
                                                <option value="[%CONTENTVAR%]"><?php echo returnIntLang('templates contentelement', false); ?> 1</option>
                                                <option value="[%CONTENTVAR:1%]"><?php echo returnIntLang('templates contentelement', false); ?> 2</option>
                                                <option value="[%CONTENTVAR:2%]"><?php echo returnIntLang('templates contentelement', false); ?> 3</option>
                                                <option value="[%CONTENTVAR:3%]"><?php echo returnIntLang('templates contentelement', false); ?> 4</option>
                                                <option value="[%CONTENTVAR:4%]"><?php echo returnIntLang('templates contentelement', false); ?> 5</option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <p><?php echo returnIntLang('templates menuvars'); ?></p>
                                        <script type="text/javascript" language="javascript">
                                        <!--
                                        function insertMenu() {
                                            // IE
                                            if (document.all) {
                                                document.getElementById('template').focus();
                                                strSelection = document.selection.createRange().text;
                                                document.selection.createRange().text = '[%MENUVAR:'+document.getElementById('menuvar').value+'%]';
                                            }
                                            // Mozilla
                                            else if (document.getElementById) {
                                                var selLength = document.getElementById('template').textLength;
                                                var selStart = document.getElementById('template').selectionStart;
                                                var selEnd = document.getElementById('template').selectionEnd;
                                                if ((selEnd == 1) || (selEnd == 2)) {
                                                    selEnd = selLength;
                                                }	// if
                                                var s1 = (document.getElementById('template').value).substring(0,selStart);
                                                var s2 = (document.getElementById('template').value).substring(selStart, selEnd)
                                                var s3 = (document.getElementById('template').value).substring(selEnd, selLength);
                                                document.getElementById('template').value = s1 + '[%MENUVAR:'+document.getElementById('menuvar').value+'%]' + s3;
                                            }	// if
                                            document.getElementById('menuvar').value='';
                                        }	// insertMenu()
                                        //-->
                                        </script>
                                        <div class="form-group">
                                            <select name="menuvar" id="menuvar" class="form-control singleselect" onchange="insertMenu(); return false;">
                                                <option value=""><?php echo returnIntLang('hint choose', false); ?></option>
                                                <optgroup name="<?php echo returnIntLang('templates menuvars predefined', false); ?>" label="<?php echo returnIntLang('templates menuvars predefined', false); ?>">
                                                    <option value="FULLLIST"><?php echo returnIntLang('templates menuvar fulllist', false); ?></option>
                                                    <option value="FULLSELECT"><?php echo returnIntLang('templates menuvar fullselect', false); ?></option>
                                                    <option value="HORIZONTALLIST"><?php echo returnIntLang('templates menuvar horizontallist', false); ?></option>
                                                    <option value="HORIZONTALDIV"><?php echo returnIntLang('templates menuvar horizontaldiv', false); ?></option>
                                                    <option value="HORIZONTALSELECT"><?php echo returnIntLang('templates menuvar horizontalselect', false); ?></option>
                                                    <option value="SUBLIST"><?php echo returnIntLang('templates menuvar sublist', false); ?></option>
                                                    <option value="SUBDIV"><?php echo returnIntLang('templates menuvar subdiv', false); ?></option>
                                                    <option value="SUBSELECT"><?php echo returnIntLang('templates menuvar subselect', false); ?></option>
                                                    <option value="LINKLAST"><?php echo returnIntLang('templates menuvar linklast', false); ?></option>
                                                    <option value="LINKLASTALL"><?php echo returnIntLang('templates menuvar linklast all', false); ?></option>
                                                    <option value="LINKNEXT"><?php echo returnIntLang('templates menuvar linknext', false); ?></option>
                                                    <option value="LINKNEXTALL"><?php echo returnIntLang('templates menuvar linknext all', false); ?></option>
                                                    <option value="LINKUP"><?php echo returnIntLang('templates menuvar linkup', false); ?></option>
                                                </optgroup>
                                                <?php

                                                $menuvar_sql = "SELECT `guid`, `title`, `describ` FROM `templates_menu` ORDER BY `title`";
                                                $menuvar_res = doSQL($menuvar_sql);
                                                if ($menuvar_res['num']>0):
                                                    echo "<optgroup name=\"".returnIntLang('templates menuvars selfdefined', false)."\" label=\"".returnIntLang('templates menuvars selfdefined', false)."\">";
                                                    foreach ($menuvar_res['set'] AS $mvk => $mvv) {
                                                        echo "<option value=\"".strtoupper($mvv['guid'])."\">".((trim($mvv['title'])!='')?trim($mvv['title']):trim($mvv['guid']))."</option>";
                                                    }
                                                    echo "</optgroup>";
                                                else:
                                                    echo "<option value=\"\">".returnIntLang('templates menuvars none selfdefined', false)."</option>";	
                                                endif;

                                                ?>
                                            </select>
                                        </div>
                                        <p><?php echo returnIntLang('templates menuvars info', false); ?></p>
                                        <p><?php echo returnIntLang('templates wspbasedvars', false); ?></p>
                                        <div class="form-group">
                                            <select name="wspbasedvar" id="wspbasedvar" size="1" class="form-control" onchange="insertVar('wspbasedvar'); return false;">
                                                <option value=""><?php echo returnIntLang('hint choose', false); ?></option>
                                                <option value="[%LANGUAGE%]"><?php echo returnIntLang('templates language', false); ?></option>
                                                <option value="[%PAGETITLE%]"><?php echo returnIntLang('templates pagetitle', false); ?></option>
                                                <option value="[%LASTPUBLISHED%]"><?php echo returnIntLang('templates date of last publishing', false); ?></option>
                                                <option value="[%PUBLISHTIME%]"><?php echo returnIntLang('templates time to publish this page', false); ?></option>
                                                <option value="[%FILEPATH%]"><?php echo returnIntLang('templates path to file in order to root directory', false); ?></option>
                                                <option value="[%FILEAUTHOR%]"><?php echo returnIntLang('templates author of file', false); ?></option>
                                            </select>
                                        </div>
                                        <?php

                                        $selfvars_sql = "SELECT `name`, `shortcut` FROM `selfvars` ORDER BY `name`";
                                        $selfvars_res = doSQL($selfvars_sql);
                                        if ($selfvars_res['num']>0): ?>
                                        <p><?php echo returnIntLang('templates selfvars'); ?></p>
                                        <div class="form-group">
                                            <select name="selfvar" id="selfvar" size="1" class="form-control" onchange="insertVar('selfvar'); return false;">
                                                <option value=""><?php echo returnIntLang('hint choose', false); ?></option>
                                                <?php foreach ($selfvars_res['set'] AS $svk => $svv): if(trim($svv['shortcut'])!=''):
                                                    echo "<option value=\"[%".trim(strtoupper($svv['shortcut']))."%]\">".$svv['name']."</option>\n";
                                                endif; endforeach; ?>
                                            </select>
                                        </div>
                                        <?php endif;

                                        $globalcontents_sql = "SELECT `gc`.`id`, `gc`.`valuefields`, `ip`.`name` FROM `content_global` as `gc`, `interpreter` AS `ip` WHERE `gc`.`interpreter_guid` = `ip`.`guid` AND `gc`.`trash` = 0 ORDER BY `interpreter_guid`";
                                        $globalcontents_res = doSQL($globalcontents_sql);
                                        if ($globalcontents_res['num']>0): ?>
                                        <p><?php echo returnIntLang('templates globalcontents'); ?></p>
                                        <div class="form-group">
                                            <select name="globalcontent" id="globalcontent" size="1" class="form-control" onchange="insertVar('globalcontent'); return false;">
                                                <option value=""><?php echo returnIntLang('hint choose', false); ?></option>
                                                <?php

                                                foreach ($globalcontents_res['set'] AS $gck => $gcv):
                                                    echo "<option value=\"[%GLOBALCONTENT:".intval($gcv['id'])."%]\">".$gcv['name']."</option>\n";
                                                endforeach;

                                                ?>
                                            </select>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <p><input type="hidden" name="op" value="savetemplate" /><input type="hidden" name="id" id="id" value="<?php if (checkParamVar('op')!='edit'): echo 0; else: echo intval(checkParamVar('id')); endif; ?>" /><a onclick="checkEditFields(); return false;" class="btn btn-primary"><?php echo returnIntLang('str save', false); ?></a> <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-warning"><?php echo returnIntLang('str cancel', false); ?></a></p>
                            </div>
                        </div>
                    </form>
                </section>
            <?php endif; ?>
            <?php if(checkParamVar('op')!='edit' && checkParamVar('op')!='new' && checkParamVar('op')!='clone'): ?>
                <p><a onclick="document.getElementById('createnewtemplate').submit();" class="btn btn-primary"><?php echo returnIntLang('templates create template'); ?></a></p>
                <form name="createnewtemplate" id="createnewtemplate" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>#tpleditor"><input type="hidden" name="op" value="new"></form>
            <?php endif; ?>
            <form name="deletetemplateform" id="deletetemplateform" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>"><input type="hidden" name="op" value="deltemplate" /><input type="hidden" id="deleteid" name="deleteid" value="" /></form>
        </div>
    </div>
</div>

<script type="text/javascript" language="javascript">
<!--

function checkEditFields() {
    if (document.getElementById('templatename').value == '') {
        alert('<?php echo returnIntLang('templates errormsg missing tplname', false); ?>');
        document.getElementById('templatename').focus();
        return false;
        }	// if
    document.getElementById('formedittemplate').submit();
    return true;
    }	// checkEditFields()

function insertVar(selectvar) {
    if (document.getElementById(selectvar).value != '') {
        // IE
        if (document.all) {
            document.getElementById('template').focus();
            strSelection = document.selection.createRange().text
            document.selection.createRange().text = document.getElementById(selectvar).value;
        }
        // Mozilla
        else if (document.getElementById) {
            var selLength = document.getElementById('template').textLength;
            var selStart = document.getElementById('template').selectionStart;
            var selEnd = document.getElementById('template').selectionEnd;
            if ((selEnd == 1) || (selEnd == 2)) {
                selEnd = selLength;
            }	// if
            var s1 = (document.getElementById('template').value).substring(0,selStart);
            var s2 = (document.getElementById('template').value).substring(selStart, selEnd)
            var s3 = (document.getElementById('template').value).substring(selEnd, selLength);
            document.getElementById('template').value = s1 + document.getElementById(selectvar).value + s3;
        }	// if
    }	// if
    document.getElementById('template').focus();
    return;
    document.getElementById(selectvar).value = '';
    }	// insertVar()

function confirmDeleteTemplate(templatename, tid) {
    if (confirm ('<?php echo returnIntLang('templates confirm delete template1', false); ?> »' + templatename + '« <?php echo returnIntLang('templates confirm delete template2', false); ?>')) {
        document.getElementById('deleteid').value = tid;
        document.getElementById('deletetemplateform').submit();			
        }
    }
    
$(function() {
    
    $(".allowTabChar").allowTabChar();
    $('.autogrow').autoGrow();
    
});

//-->
</script>
    
<?php require ("./data/include/footer.inc.php"); ?>