<?php
/**
 * edit and create templates
 * @author stefan@covi.de
 * @since 3.1
 * @version 6.11
 * @lastchange 2023-01-13
 * 
 * 2023-01-13
 * 6.11
 * fixed error in rss-feed sql statement
 * 
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* first includes ---------------------------- */
require ("./data/include/wsplang.inc.php");
require ("./data/include/dbaccess.inc.php");
if (file_exists("./data/include/ftpaccess.inc.php")) require ("./data/include/ftpaccess.inc.php");
require ("./data/include/funcs.inc.php");
/* checkParamVar ----------------------------- */
$op = checkParamVar('op', '');
$id = checkParamVar('id', 0);
/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'design';
$_SESSION['wspvars']['mgroup'] = 4;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = true;
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
$GLOBALS['under'] = array();
/* define page specific functions ------------ */

function getSubTemplate($mid) { // Auslesen aller untergeordneter Menupunkte/Inhalte die kein eigenes Seitentemplate benutzen
	foreach($mid as $midvalue):
		$under_sql = "SELECT mid FROM `menu` WHERE `templates_id` = '0' AND `connected` = ".intval($midvalue);
		$under_res = doSQL($under_sql);
		if($under_res['num']>0):
			$return = array();
			foreach ($under_res['set'] AS $k => $row) {
				$return[] = $row['mid'];
            }
			$submid = getSubTemplate($return);
            if (is_array($submid)) {
                array_merge($return, $submid);
            }
		endif;
	endforeach;
    return ($return);
	} //getUnderTemplate();		

if ($op == 'savetemplate'):
	if (intval($_POST['id'])==0):
		// add template
		$sql = "INSERT INTO `templates` SET `name` = '".escapeSQL(trim($_POST['templatename']))."', `template` = '".escapeSQL($_POST['template'])."', `bodytag` = '".escapeSQL($_POST['bodytag'])."', `head` = '".escapeSQL($_POST['selfhead'])."', `generic_viewport` = '".escapeSQL(trim($_POST['generic_viewport']))."'";
		if (array_key_exists('framework', $_POST)):
			$sql.= ", `framework` = '".escapeSQL(serialize($_POST['framework']))."'";
		else:
			$sql.= ", `framework` = ''";
		endif;
		if (array_key_exists('fonts', $_POST)):
			$sql.= ", `fonts` = '".escapeSQL(serialize($_POST['fonts']))."'";
		else:
			$sql.= ", `fonts` = ''";
		endif;
		$res = doSQL($sql);
		$id = $res['inf'];
		// create output message
		$_SESSION['wspvars']['resultmsg'] = 'Das Template "'.trim($_POST['templatename']).'" wurde angelegt.';
	else:
		// update template
		$sql = "UPDATE `templates` SET `name` = '".escapeSQL(trim($_POST['templatename']))."', `template` = '".escapeSQL($_POST['template'])."', `bodytag` = '".escapeSQL($_POST['bodytag'])."', `head` = '".escapeSQL($_POST['selfhead'])."', `generic_viewport` = '".escapeSQL(trim($_POST['generic_viewport']))."'";
		if (array_key_exists('framework', $_POST)):
			$sql.= ", `framework` = '".escapeSQL(serialize($_POST['framework']))."'";
		else:
			$sql.= ", `framework` = ''";
		endif;
		if (array_key_exists('fonts', $_POST)):
			$sql.= ", `fonts` = '".escapeSQL(serialize($_POST['fonts']))."'";
		else:
			$sql.= ", `fonts` = ''";
		endif;
		$sql.= " WHERE `id` = ".intval($_POST['id']);
		doSQL($sql);

		// find all menupoints using this template (and connected menupoints, that use template from upper menupoint
		
		$mid = array();
		$menu_sql = "SELECT `mid` FROM `menu` WHERE `templates_id` = ".$id;
		$menu_res = doSQL($menu_sql);
		if($menu_res['num']>0):
			foreach ($menu_res['set'] AS $mresk => $mresv):
				$mid[] = intval($mresv['mid']);
			endforeach;
		endif;

		// Ende Rekursion
		$sql = "UPDATE `menu` SET `contentchanged` = 3 WHERE `templates_id` = ".$id;
		doSQL($sql);
		
		foreach(getSubTemplate($mid) as $sk => $sv):
			doSQL("UPDATE `menu` SET `contentchanged` = 3 WHERE `mid` = ".intval($sv));
		endforeach;

		// create output message
		addWSPMsg('resultmsg', 'Die Änderungen am Template "'.trim($_POST['templatename']).'" wurden gespeichert.');
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
	doSQL("DELETE FROM `r_temp_rss` WHERE `templates_id` = ".intval($id));
	if ($_POST['rssfile']>0):
		doSQL("INSERT INTO `r_temp_rss` SET `templates_id` = ".intval($id).", `rss_id` = ".intval($_POST['rssfile']));
	endif;

elseif ($op == 'clonetemplate'):
	
    // template klonen
	$sql = 'INSERT INTO `templates` (`name`, `template`, `bodytag`, `head`, `generic_viewport`, `framework`, `fonts`) (SELECT \''.$_POST['templatename'].'\', `template`, `bodytag`, `head`, `generic_viewport`, `framework`, `fonts` FROM `templates` WHERE `id` = '.intval($id).')';
	$res = doSQL($sql);
	$idnew = $res['inf'];
	doSQL("INSERT INTO `r_temp_styles` (`templates_id`, `stylesheets_id`) (SELECT ".intval($idnew).", `stylesheets_id` FROM `r_temp_styles` WHERE `templates_id` = ".intval($id)." ORDER BY `id`)");
    doSQL("INSERT INTO `r_temp_jscript` (`templates_id`, `javascript_id`) (SELECT ".intval($idnew).", `javascript_id` FROM `r_temp_jscript` WHERE `templates_id` = ".intval($id)." ORDER BY `id`)");
	doSQL("INSERT INTO `r_temp_rss` (`templates_id`, `rss_id`) (SELECT ".intval($idnew).", `rss_id` FROM `r_temp_rss` WHERE `templates_id` = ".intval($id)." ORDER BY `id`)");

elseif ($op == 'defaulttemplate'):

	// ausgewaehltes template wird als default-template gesetzt
	doSQL("DELETE FROM `wspproperties` WHERE `varname` = 'templates_id'");
	doSQL("INSERT INTO `wspproperties` SET `varname` = 'templates_id', `varvalue` = '".intval($id)."'");

elseif ($op == 'deltemplate'):
	
    // loeschen eines templates
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

// head der datei

include ("./data/include/header.inc.php");
include ("./data/include/wspmenu.inc.php");
?>

<div id="contentholder">
	<fieldset><h1><?php echo returnIntLang('templates headline'); ?></h1></fieldset>
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
	
	//-->
	</script>
	<fieldset><p><?php echo returnIntLang('templates info'); ?><a name="tpleditor">&nbsp;</a></p></fieldset>
	<?php
	
	$templates_sql = "SELECT `id`, `name` FROM `templates` ORDER BY `name`";
	$templates_res = doSQL($templates_sql);
	
	if (isset($_POST['op']) && $_POST['op']=='edit') $_SESSION['opentabs']['templates_fieldset'] = 'display: none;';
	if (isset($_POST['op']) && $_POST['op']=='clone') $_SESSION['opentabs']['templates_fieldset'] = 'display: none;';
		
	?>
	<fieldset>
		<legend><?php echo returnIntLang('templates existingtemplates'); ?> <span class="opencloseButton bubblemessage" rel="templates_fieldset">↕</span></legend>
		<div id="templates_fieldset" style="<?php echo $_SESSION['opentabs']['templates_fieldset']; ?>">
		<?php
		if ($templates_res['num']==0) {
			echo "<p>".returnIntLang('templates notemplates')."</p>\n";
		}
        else {
            ?>
            <ul class="tablelist">
                <li class="tablecell head two"><?php echo returnIntLang('str templatename'); ?></li>
                <li class="tablecell head two"><?php echo returnIntLang('str usage'); ?></li>
                <li class="tablecell head two"><?php echo returnIntLang('str connection'); ?></li>
                <li class="tablecell head two"><?php echo returnIntLang('str action'); ?></li>
            </ul>

            <ul class="tablelist">
            <?php
		
            // lookup for default temp
            $defaulttemp_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'templates_id'";
            $defaulttemp_res = doResultSQL($defaulttemp_sql);
            $defaulttemp = intval($defaulttemp_res);

            foreach ($templates_res['set'] AS $tsrk => $tsrv) {
                
                echo "<li class='tablecell two'><a href='#' onclick=\"document.getElementById('tempaction_".intval($tsrv['id'])."').submit();\">".trim($tsrv['name'])."</a></li>";
                echo "<li class='tablecell two'>";
			
                $tempused_num = 0;
                $tempused_sql = "SELECT `mid`, `description` FROM `menu` WHERE `templates_id` = ".intval($tsrv['id'])." AND `trash` = 0";
                $tempused_res = doSQL($tempused_sql);

                if ($tempused_res['num']>5):
                    $tempused_show = 5;
                else:
                    $tempused_show = $tempused_res['num'];
                endif;
                for ($t=0;$t<$tempused_show;$t++):
                    echo setUTF8(trim($tempused_res['set'][$t]["description"]))."<br />";
                endfor;
                if ($tempused_res['num']>5):
                    echo "...<br />";
                endif;

                echo "</li>";
                echo "<li class='tablecell two'>";
            
                $usedstyles_sql = "SELECT `stylesheets_id` FROM `r_temp_styles` WHERE `templates_id` = " . intval($tsrv['id']) . " ORDER BY `id`";
                $usedstyles_res = doSQL($usedstyles_sql);

                if ($usedstyles_res['num']>0):
                    $cssdisplay = FALSE;
                    foreach ($usedstyles_res['set'] AS $usedstyle):
                        $cssdata_sql = "SELECT `describ`, `cfolder` FROM `stylesheets` WHERE id = '" . intval($usedstyle['stylesheets_id']) . "'";
                        $cssdata_res = doSQL($cssdata_sql);
                        if ($cssdisplay==FALSE):
                            echo "<span style=\"float: left; width: 3em;\">CSS: </span>";
                            $cssdisplay = TRUE;
                        else:
                            echo "<span style=\"float: left; width: 3em;\">&nbsp;</span>";
                        endif;
                        if ($cssdata_res['num']>0):
                            echo $cssdata_res['set'][0]['describ'];
                            if (trim($cssdata_res['set'][0]['cfolder'])!=""):
                                echo " <em>Library</em>"; 
                            endif;
                            echo "<br />";
                        endif;
                    endforeach;
                endif;

                // show used javascript-files
                $used_sql = "SELECT `javascript_id` FROM `r_temp_jscript` WHERE `templates_id` = ".intval($tsrv['id'])." ORDER BY `id`";
                $used_res = doSQL($used_sql);
                if ($used_res['res'] > 0) {
                    $display = false;
                    foreach ($used_res['set'] AS $ursk => $ursv) {
                        $data_sql = "SELECT `describ` FROM `javascript` WHERE id = ".intval($ursv['javascript_id'])." LIMIT 1";
                        $data_res = doResultSQL($data_sql);
                        if ($display===false):
                            echo "<span style=\"float: left; width: 3em;\">JS: </span>";
                            $display = true;
                        else:
                            echo "<span style=\"float: left; width: 3em;\">&nbsp;</span>";
                        endif;
                        if ($data_res!==false) {
                            echo "<span style=\"display:table\">".trim($data_res)."</span>";
                        }
                    }
                }

                $usedrss_sql = "SELECT `rss_id` FROM `r_temp_rss` WHERE `templates_id` = ".intval($tsrv['id'])." ORDER BY `id`";
                $usedrss_res = doResultSQL($usedrss_sql);

                if ($usedrss_res!==false):
                    $rssdata_sql = "SELECT `rsstitle` FROM `rssdata` WHERE rid = ".intval($usedrss_res);
                    $rssdata_res = doResultSQL($rssdata_sql);
                    if ($rssdata_res!==false):
                        echo "<span style=\"float: left; width: 3em;\">RSS: </span>".trim($rssdata_res);
                    endif;
                endif;
                
                echo "</li>";

                if ($defaulttemp == $tsrv['id']):
                    $del = "<span class=\"bubblemessage red disabled\">".returnIntLang('bubble delete', false)."</span>";
                    $standard = "<span class=\"bubblemessage green disabled\">".returnIntLang('bubble standard', false)."</span>";
                else:
                    if ($tempused_num>0):
                        $del = "<span class=\"bubblemessage red disabled\">".returnIntLang('bubble delete', false)."</span>";
                    else:
                        $del = "<a onclick=\"return confirmDeleteTemplate('".$tsrv['name']."', ".$tsrv['id'].");\"><span class=\"bubblemessage red\">".returnIntLang('bubble delete', false)."</span></a>";
                    endif;
                    $standard = "<a href=\"".$_SERVER['PHP_SELF']."?op=defaulttemplate&id=".$tsrv['id']."\"><span class=\"bubblemessage green\">".strtoupper(returnIntLang('bubble standard', false))."</span></a>";
                endif;
                // clone button
                $cloneit = "<a onclick=\"document.getElementById('action_".$tsrv['id']."').value = 'clone'; document.getElementById('tempaction_".$tsrv['id']."').submit();\"><span class=\"bubblemessage green\">".returnIntLang('bubble clone', false)."</span></a>";
                // edit button
                $edittemplate = "<a onclick=\"document.getElementById('action_".$tsrv['id']."').value = 'edit'; document.getElementById('tempaction_".$tsrv['id']."').submit();\"><span class=\"bubblemessage green\">".returnIntLang('bubble edit', false)."</span></a>";

                $links = trim($edittemplate." ".$cloneit." ".$del." ".$standard);
                
                echo "<li class='tablecell two'>".$links;
                ?>
                <form name="tempaction" id="tempaction_<?php echo $tsrv['id']; ?>" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>#tpleditor"><input type="hidden" name="op" id="action_<?php echo $tsrv['id']; ?>" value="edit" /><input type="hidden" name="id" id="id_<?php echo $tsrv['id']; ?>" value="<?php echo $tsrv['id']; ?>" /></form>
                <?php
                echo "</li>";
            }
        }
                ?>
            </ul>
		</div>
	</fieldset>
	<form name="deletetemplateform" id="deletetemplateform" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>"><input type="hidden" name="op" value="deltemplate" /><input type="hidden" id="deleteid" name="deleteid" value="" /></form>
    <?php
    
    $edittemp_sql = "SELECT * FROM `templates` WHERE `id` = ".intval($id);
	$edittemp_res = doSQL($edittemp_sql);
	if ($edittemp_res['num']==0) {
		$id = 0;
	} else {
		$edittemp_name = trim($edittemp_res['set'][0]["name"]);
		$edittemp_temp = trim($edittemp_res['set'][0]["template"]);
		if ($_SESSION['wspvars']['stripslashes']>0):
			for ($strip=0; $strip<$_SESSION['wspvars']['stripslashes']; $strip++):
				$edittemp_temp = stripslashes($edittemp_temp);
			endfor;
		endif;
		$edittemp_body = trim($edittemp_res['set'][0]["bodytag"]);
		$edittemp_head = trim($edittemp_res['set'][0]["head"]);
		if ($_SESSION['wspvars']['stripslashes']>0):
			for ($strip=0; $strip<$_SESSION['wspvars']['stripslashes']; $strip++):
				$edittemp_head = stripslashes($edittemp_head);
			endfor;
		endif;
		$edittemp_generic_viewport = trim($edittemp_res['set'][0]["generic_viewport"]);
		$edittemp_framework = unserializeBroken(trim($edittemp_res['set'][0]["framework"]));
		$edittemp_fonts = unserializeBroken(trim($edittemp_res['set'][0]["fonts"]));
    }

	?>
	<fieldset id="templateeditor" <?php if ($op!="edit"): ?>style="display: none;"<?php endif; ?>>
		<legend><?php if ($id<1 && $op="edit"): ?><?php echo returnIntLang('templates create template'); ?><?php else: ?><?php echo returnIntLang('templates edittemplate'); ?> <?php echo setUTF8($edittemp_name); endif; ?></legend>
		<form id="formedittemplate" name="formedittemplate" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<table class="tablelist">
			<tr>
				<td class="tablecell two"><?php echo returnIntLang('templates templatename'); ?></td>
				<td class="tablecell two"><input type="text" maxlength="200" id="templatename" name="templatename" value="<?php echo $edittemp_name; ?>" class="one full" /></td>
				<td class="tablecell two"><?php echo returnIntLang('templates rss'); ?></td>
				<td class="tablecell two"><?php
				
				$rssfiles_sql = "SELECT * FROM rssdata";
				$rssfiles_res = doSQL($rssfiles_sql);
				if ($rssfiles_res['num']==0):
					echo returnIntLang('templates norssdefined');
				else:
					?><select name="rssfile" id="rssfile" size="1" class="one full">
						<option value="0"><?php echo returnIntLang('hint choose', false); ?></option>
						<?php
                        foreach ($rssfiles_res['set'] AS $rssrsk => $rssrsv) {
							$rsscon_sql = "SELECT * FROM `r_temp_rss` WHERE `templates_id` = ".intval($id)." AND `rss_id` = ".intval($rssrsv['rid']);
							$rsscon_res = doSQL($rsscon_sql);
							echo "<option value=\"".intval($rssrsv['rid'])."\"";
							if ($rsscon_res['num']>0):
								echo " selected";
							endif;
							echo ">".setUTF8(trim($rssrsv['rsstitle']))."</option>";
						}
						?>
					</select><?php
				endif; ?></td>
			</tr>
			<tr>
				<td class="tablecell two"><?php echo returnIntLang('templates frameworks'); ?></td>
				<td class="tablecell two"><ul class="checklist">
                    <?php if(is_file('../data/script/jquery/jquery-3.3.1.js')): ?>
					   <li><input type="checkbox" name="framework[jquery]" id="framework_jquery" value="1" <?php if (is_array($edittemp_framework) && array_key_exists('jquery', $edittemp_framework) && intval($edittemp_framework['jquery'])==1): echo "checked=\"checked\""; endif; ?> /> jQuery 3.3.1 local (JavaScript)</li>
                    <?php endif; ?>
                    <?php if(is_file('../data/script/bootstrap/bootstrap.js')): ?>
					   <li><input type="checkbox" name="framework[bootstrap]" id="framework_bootstrap" value="1" <?php if (is_array($edittemp_framework) && array_key_exists('bootstrap', $edittemp_framework) && intval($edittemp_framework['bootstrap'])==1): echo "checked=\"checked\""; endif; ?> /> Bootstrap 3 local (JavaScript)</li>
                    <?php endif; ?>
                    <?php if(is_file('../data/script/bootstrap_5/bootstrap.js')): ?>
					   <li><input type="checkbox" name="framework[bootstrap_5]" id="framework_bootstrap_5" value="1" <?php if (is_array($edittemp_framework) && array_key_exists('bootstrap_5', $edittemp_framework) && intval($edittemp_framework['bootstrap_5'])==1): echo "checked=\"checked\""; endif; ?> /> Bootstrap 5 local (JavaScript)</li>
                    <?php endif; ?>
					<li><input type="checkbox" name="framework[covifuncs]" id="framework_covifuncs" value="1" <?php if (is_array($edittemp_framework) && array_key_exists('covifuncs', $edittemp_framework) && intval($edittemp_framework['covifuncs'])==1): echo "checked=\"checked\""; endif; ?> /> COVI Scripts (JavaScript)</li>
				</ul></td>
				<td class="tablecell two"><?php echo returnIntLang('templates jslib'); ?></td>
				<td class="tablecell two"><?php
				
				$javascript_sql = "SELECT `id`, `describ` FROM `javascript` WHERE `describ`!='' AND `cfolder` != '' ORDER BY `describ`";
				$javascript_res = doSQL($javascript_sql);
				if ($javascript_res['num']==0):
					echo returnIntLang('templates nojsdefined');
				else:
					$jsarray = array();
					$jscon_sql = "SELECT * FROM `r_temp_jscript` WHERE `templates_id` = ".intval($id)." ORDER BY `id`";
					$jscon_res = doSQL($jscon_sql);
					if ($jscon_res['num']>0) {
						foreach ($jscon_res['set'] AS $jcrsk => $jcrsv) {
                			$jsarray[intval($jcrsv['javascript_id'])]['checked'] = true;
						}
                    }
                    foreach ($javascript_res['set'] AS $jsrk => $jsrv) {
						$jsarray[intval($jsrv["id"])]['describ'] = trim($jsrv["describ"]);
                    }
					echo "<ul id=\"jsliblist\" class=\"checklist\">";
					foreach ($jsarray AS $key => $value):
						if (key_exists('describ', $value) && trim($value['describ'])!=""):
							echo "<li id=\"item_".$key."\"><input type=\"checkbox\" name=\"jsfiles[]\" value=\"".$key."\" ";
							if (key_exists('checked', $value) && $value['checked']):
								echo "checked=\"checked\"";
							endif;
							echo " />&nbsp;<span class=\"handle\" style=\"cursor: move;\">".$value['describ']."</span></li>";
						endif;
					endforeach;
					echo "</ul>";
				endif;
				?>
				<script type="text/javascript" language="javascript" charset="utf-8">
				$(function(){$("#jsliblist").sortable({placeholder:"ui-state-highlight"});$("#jsliblist").disableSelection();});
				</script></td>
			</tr>
			<tr>
				<td class="tablecell two"><?php echo returnIntLang('templates javascript'); ?></td>
				<td class="tablecell two"><?php
				
				$javascript_sql = "SELECT `id`, `describ` FROM `javascript` WHERE `describ` != '' AND `cfolder` = '' ORDER BY `describ`";
				$javascript_res = doSQL($javascript_sql);
				if ($javascript_res['num']==0):
					echo returnIntLang('templates nojsdefined');
				else:
					$jsarray = array();
					$jscon_sql = "SELECT * FROM `r_temp_jscript` WHERE `templates_id` = ".intval($id)." ORDER BY `id`";
					$jscon_res = doSQL($jscon_sql);
					if ($jscon_res['num']>0) {
						foreach ($jscon_res['set'] AS $jcrsk => $jcrsv) {
                			$jsarray[intval($jcrsv['javascript_id'])]['checked'] = true;
						}
                    }
                    foreach ($javascript_res['set'] AS $jsrk => $jsrv) {
						$jsarray[intval($jsrv["id"])]['describ'] = trim($jsrv["describ"]);
                    }
					echo "<ul id=\"jsliblist\" class=\"checklist\">";
					foreach ($jsarray AS $key => $value):
						if (key_exists('describ', $value) && trim($value['describ'])!=""):
							echo "<li id=\"item_".$key."\"><input type=\"checkbox\" name=\"jsfiles[]\" value=\"".$key."\" ";
							if (key_exists('checked', $value) && $value['checked']):
								echo "checked=\"checked\"";
							endif;
							echo " />&nbsp;<span class=\"handle\" style=\"cursor: move;\">".$value['describ']."</span></li>";
						endif;
					endforeach;
					echo "</ul>";
				endif;
				?>
				<script type="text/javascript" language="javascript" charset="utf-8">
				$(function(){$("#jslist").sortable({placeholder:"ui-state-highlight"});$("#jslist").disableSelection();});
				</script></td>
				<td class="tablecell two"><?php echo returnIntLang('templates stylesheets'); ?></td>
				<td class="tablecell two"><?php
				
				$designs_sql = "SELECT `id`, `describ`, `cfolder` FROM `stylesheets` ORDER BY `describ`";
				$designs_res = doSQL($designs_sql);
				if ($designs_res['num']==0):
					echo returnIntLang('templates nocssdefined');
				else:
					$cssarray = array();
					$csscon_sql = "SELECT * FROM `r_temp_styles` WHERE `templates_id` = ".intval($id)." ORDER BY `id`";
					$csscon_res = doSQL($csscon_sql);
					if ($csscon_res['num']>0):
						foreach ($csscon_res['set'] AS $cresk => $cresv):
							$cssarray[intval($cresv['stylesheets_id'])]['checked'] = true;
						endforeach;
					endif;
					foreach ($designs_res['set'] AS $drsk => $drsv):
						$cssarray[intval($drsv['id'])]['describ'] = trim($drsv['describ']);
						$cssarray[intval($drsv['id'])]['cfolder'] = trim($drsv['cfolder']);
					endforeach;
					echo "<ul id=\"csslist\" class=\"checklist\">";
					foreach ($cssarray AS $key => $value):
						if (key_exists('describ', $value) && trim($value['describ'])!=""):
							echo "<li id=\"item_".$key."\"><input type=\"checkbox\" name=\"cssfiles[]\" value=\"".$key."\" ";
							if (key_exists('checked', $value) && $value['checked']):
								echo "checked=\"checked\"";
							endif;
							echo " />&nbsp;<span class=\"handle\" style=\"cursor: move;\">".$value['describ'];
							if ($value['cfolder']!=""): echo " <em>Library</em>"; endif;
							echo "</span></li>";
						endif;
					endforeach;
					echo "</ul>";
				endif; 
				
				?>
				<script type="text/javascript" language="javascript" charset="utf-8">
				$(function(){$("#csslist").sortable({placeholder:"ui-state-highlight"});$("#csslist").disableSelection();});
				</script></td>
			</tr>
			<tr>
				<td class="tablecell two"><?php echo returnIntLang('templates fontsource'); ?></td>
				<td class="tablecell two"><select name="fonts[source]" id="font_source" class="one full">
					<option value=""><?php echo returnIntLang('hint choose', false); ?></option>
					<option value="google" <?php if (isset($edittemp_fonts['source']) && $edittemp_fonts['source']=='google') echo " selected='selected' "; ?>>Google Fonts</option>
				</select></td>
				<td class="tablecell two"><?php echo returnIntLang('templates fonts'); ?></td>
				<td class="tablecell two"><input type="text" name="fonts[list]" id="fonts_list" class="three full" value="<?php if (isset($edittemp_fonts['list']) && $edittemp_fonts['list']!='') echo prepareTextField($edittemp_fonts['list']); ?>" placeholder="<?php echo returnIntLang('templates fonts hint', false); ?>" /></td>
			</tr>
			<tr>
				<td class="tablecell two"><?php echo returnIntLang('templates generic viewport'); ?></td>
				<td class="tablecell two"><input type="text" name="generic_viewport" id="generic_viewport" class="three full" value="<?php if (isset($edittemp_generic_viewport)) echo htmlspecialchars($edittemp_generic_viewport); ?>" /></td>
				<td class="tablecell two"><?php echo returnIntLang('templates bodytag'); ?></td>
				<td class="tablecell two"><input type="text" name="bodytag" id="bodytag"  class="three full" value="<?php if (isset($edittemp_body)) echo htmlspecialchars($edittemp_body); ?>" /></td>
			</tr>
			<tr>
				<td class="tablecell two"><?php echo returnIntLang('templates head'); ?></td>
				<td class="tablecell six"><textarea name="selfhead" id="selfhead" cols="80" rows="5" class="full medium noresize"><?php echo $edittemp_head; ?></textarea></td>
			</tr>
			<tr>
				<td class="tablecell two"><?php echo returnIntLang('templates template'); ?></td>
				<td class="tablecell six"><textarea name="template" id="template" cols="80" rows="15" class="full large"><?php echo $edittemp_temp; ?></textarea></td>
			</tr>
		</table>
		<ul class="tablelist">
			<li class="tablecell two"><?php echo returnIntLang('templates contentvars'); ?></li>
			<li class="tablecell two"><?php 
		
			$templatevars = getTemplateVars(intval($id));
			
			$siteinfo_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'contentvardesc'";
			$siteinfo_res = doResultSQL($siteinfo_sql);
			if ($siteinfo_res!==false):
				$contentvardesc = unserializeBroken($siteinfo_res);
			endif;
					
			?><select name="globalvar" id="globalvar" size="1" style="width: 95%;" onchange="insertVar('globalvar'); return false;">
				<option value=""><?php echo returnIntLang('hint choose', false); ?></option>
				<?php if (isset($contentvardesc) && is_array($contentvardesc)): ?>
					<option value="[%CONTENTVAR%]"><?php if(array_key_exists(0, $contentvardesc)): echo trim($contentvardesc[0])." (".returnIntLang('templates contentelement', false)." 1)"; else: echo returnIntLang('templates contentelement', false)." 1"; endif; ?></option>
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
			</select></li>
			<li class="tablecell two"><?php echo returnIntLang('templates menuvars'); ?></li>
			<li class="tablecell two"><script type="text/javascript" language="javascript">
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
			<select name="menuvar" id="menuvar" style="width: 95%;" onchange="insertMenu(); return false;">
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
					<option value="LINKNEXT"><?php echo returnIntLang('templates menuvar linknext', false); ?></option>
					<option value="LINKUP"><?php echo returnIntLang('templates menuvar linkup', false); ?></option>
				</optgroup>
				<?php
				
				$menuvar_sql = "SELECT `guid`, `title`, `describ` FROM `templates_menu` ORDER BY `title`";
				$menuvar_res = doSQL($menuvar_sql);
				if ($menuvar_res['num']>0):
					echo "<optgroup name=\"".returnIntLang('templates menuvars selfdefined', false)."\" label=\"".returnIntLang('templates menuvars selfdefined', false)."\">";
                    foreach ($menuvar_res['set'] AS $mvrsk => $mvrsv) {
						echo "<option value=\"".strtoupper(trim($mvrsv['guid']))."\">".setUTF8(trim($mvrsv['title']))."</option>";
					}
					echo "</optgroup>";
				else:
					echo "<option value=\"\">".returnIntLang('templates menuvars none selfdefined', false)."</option>";	
				endif;
				
				?>
			</select></li>
			<li class="tablecell two"><?php echo returnIntLang('templates wspbasedvars', false); ?></li>
			<li class="tablecell two"><select name="wspbasedvar" id="wspbasedvar" size="1" style="width: 95%;" onchange="insertVar('wspbasedvar'); return false;">
				<option value=""><?php echo returnIntLang('hint choose', false); ?></option>
				<option value="[%LANGUAGE%]"><?php echo returnIntLang('templates language', false); ?></option>
				<option value="[%PAGETITLE%]"><?php echo returnIntLang('templates pagetitle', false); ?></option>
				<option value="[%LASTPUBLISHED%]"><?php echo returnIntLang('templates date of last publishing', false); ?></option>
				<option value="[%PUBLISHTIME%]"><?php echo returnIntLang('templates time to publish this page', false); ?></option>
				<option value="[%FILEPATH%]"><?php echo returnIntLang('templates path to file in order to root directory', false); ?></option>
				<option value="[%FILEAUTHOR%]"><?php echo returnIntLang('templates author of file', false); ?></option>
			</select></li>
			<?php
		
			$selfvars_sql = "SELECT `id`, `name` FROM `selfvars` ORDER BY `name`";
			$selfvars_res = doSQL($selfvars_sql);
			
			if ($selfvars_res['num']>0): ?>
			<li class="tablecell two"><?php echo returnIntLang('templates selfvars'); ?></li>
			<li class="tablecell two"><select name="selfvar" id="selfvar" size="1" style="width: 95%;" onchange="insertVar('selfvar'); return false;">
				<option value=""><?php echo returnIntLang('hint choose', false); ?></option>
				<?php
				
				foreach($selfvars_res['set'] AS $svk => $svv):
					echo "<option value=\"[%".trim(strtoupper($svv['name']))."%]\">".$svv['name']."</option>\n";
				endforeach;
					
				?>
			</select></li>
			<?php endif; ?>
			<?php
		
			$globalcontents_sql = "SELECT `gc`.`id`, `gc`.`valuefield`, `ip`.`name` FROM `globalcontent` as `gc`, `interpreter` AS `ip` WHERE `gc`.`interpreter_guid` = `ip`.`guid` AND `gc`.`trash` = 0 ORDER BY `interpreter_guid`";
			$globalcontents_res = doSQL($globalcontents_sql);
			
			if ($globalcontents_res['num']>0):
			?><li class="tablecell two"><?php echo returnIntLang('templates globalcontents'); ?></li>
			<li class="tablecell two"><select name="globalcontent" id="globalcontent" size="1" style="width: 95%;" onchange="insertVar('globalcontent'); return false;">
				<option value=""><?php echo returnIntLang('hint choose', false); ?></option>
				<?php
				
				foreach ($globalcontents_res['set'] AS $gcsrk => $gcsrv):
					echo "<option value=\"[%GLOBALCONTENT:".intval($gcsrv['id'])."%]\">".$gcsrv['name']."</option>\n";
				endforeach;
					
				?>
			</select></li><?php endif; ?>
		</ul>
		<fieldset class="options innerfieldset">
			<p><input type="hidden" name="op" value="savetemplate" /><input type="hidden" name="id" id="id" value="<?php echo $id; ?>" /><a onclick="checkEditFields(); return false;" class="greenfield"><?php echo returnIntLang('str save', false); ?></a> <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="orangefield"><?php echo returnIntLang('str cancel', false); ?></a></p>
		</fieldset>
		</form>
	</fieldset>
	<?php if(isset($_POST['op']) && $_POST['op']=='clone' && isset($_POST['id']) && intval($_POST['id'])>0): 
		
		$clonetemp_sql = "SELECT `name` FROM `templates` WHERE `id` = ".intval($id);
		$clonetemp_res = doResultSQL($clonetemp_sql);
		if ($clonetemp_res!==false):
			$clonetemp_name = setUTF8(trim($clonetemp_res));
		endif;
		
		?>
		<fieldset id="fscloneit">
			<legend><?php echo returnIntLang('templates clone legend1'); echo $clonetemp_name; echo returnIntLang('templates clone legend2'); ?></legend>
			<!-- <p><?php echo returnIntLang('templates clone info'); ?></p> -->
			<form id="formcloneit" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
			<table class="contenttable">
				<tr>
					<td width="25%"><?php echo returnIntLang('templates clone new name'); ?></td>
					<td width="75%"><input type="text" name="templatename" id="templatename" value="<?php echo $clonetemp_name." Copy"; ?>" placeholder="<?php echo $clonetemp_name." Copy"; ?>" class="three full" /></td>
				</tr>
			</table>
			<input type="hidden" name="op" value="clonetemplate" />
			<input type="hidden" name="id" id="cloneid" value="<?php echo intval($_POST['id']); ?>" />
			</form>
		</fieldset>
		<fieldset class="options">
			<p><a href="" onclick="document.getElementById('formcloneit').submit(); return false" class="greenfield"><?php echo returnIntLang('str clone', false); ?></a> <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="orangefield"><?php echo returnIntLang('str cancel', false); ?></a></p>
		</fieldset>
	<?php endif; ?>
	<?php if(!(isset($_POST['op'])) || (isset($_POST['op']) && $_POST['op']!='edit' && $_POST['op']!='clone')): ?>
		<fieldset class="options">
			<p><a onclick="document.getElementById('createnewtemplate').submit();" class="greenfield"><?php echo returnIntLang('templates create template'); ?></a></p>
			<form name="createnewtemplate" id="createnewtemplate" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>#tpleditor"><input type="hidden" name="op" value="edit"></form>
		</fieldset>
	<?php endif; ?>
</div>
<?php include ("./data/include/footer.inc.php"); ?>
<!-- EOF -->