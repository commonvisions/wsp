<?php
/**
 * edit and create templates
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

function getUnderTemplate($mid){ // Auslesen aller untergeordneter Menupunkte/Inhalte die kein eigenes Seitentemplate benutzen
	die('function deprecated');
	} //getUnderTemplate();

if (isset($_REQUEST['op']) && $_REQUEST['op']=='savetemplate'):
	if (intval($_POST['id'])==0):
		// add template
		$sql = "INSERT INTO `templates` SET `name` = '".mysql_real_escape_string(trim($_POST['templatename']))."', `template` = '".mysql_real_escape_string($_POST['template'])."', `bodytag` = '".mysql_real_escape_string($_POST['bodytag'])."', `head` = '".mysql_real_escape_string($_POST['selfhead'])."', `generic_viewport` = '".mysql_real_escape_string(trim($_POST['generic_viewport']))."'";
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
		mysql_query($sql);
		$id = mysql_insert_id();
		// create output message
		$_SESSION['wspvars']['resultmsg'] = 'Das Template "'.trim($_POST['templatename']).'" wurde angelegt.';
	else:
		// update template
		$sql = "UPDATE `templates` SET `name` = '".mysql_real_escape_string(trim($_POST['templatename']))."', `template` = '".mysql_real_escape_string($_POST['template'])."', `bodytag` = '".mysql_real_escape_string($_POST['bodytag'])."', `head` = '".mysql_real_escape_string($_POST['selfhead'])."', `generic_viewport` = '".mysql_real_escape_string(trim($_POST['generic_viewport']))."'";
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
		mysql_query($sql);

		// find all menupoints using this template (and connected menupoints, that use template from upper menupoint
		
		$mid = array();
		$menu_sql = "SELECT `mid` FROM `menu` WHERE `templates_id` = ".$id;
		$menu_res = mysql_query($menu_sql);
		if ($menu_res):
			$menu_num = mysql_num_rows($menu_res);
		endif;
		if($menu_num>0):
			for ($mres=0; $mres<$menu_num; $mres++):
				$mid[] = mysql_result($menu_res, $mres, 'mid');
			endfor;
		endif;

		// should be replaced with a returning function to replace global vars
		getUnderTemplate($mid);

		// Ende Rekursion
		$sql = "UPDATE `menu` SET `contentchanged` = 3 WHERE `templates_id` = ".$id;
		mysql_query($sql);
		
		foreach($GLOBALS['under'] as $undervalue):
			$sql = "UPDATE `menu` SET `contentchanged` = 3 WHERE `mid` = ".$undervalue;
			mysql_query($sql);
		endforeach;
		// create output message
		$_SESSION['wspvars']['resultmsg'] = 'Die Änderungen am Template "'.trim($_POST['templatename']).'" wurden gespeichert.';
	endif;

	// connect template and selected css-files in selected order
	$sql = "DELETE FROM `r_temp_styles` WHERE `templates_id` = ".intval($id);
	mysql_query($sql);
	if (isset($_POST['cssfiles']) && is_array($_POST['cssfiles'])):
		foreach ($_POST['cssfiles'] AS $value):
			$sql = "INSERT INTO `r_temp_styles` SET `templates_id` = ".intval($id).", `stylesheets_id` = ".intval($value);
			mysql_query($sql);
		endforeach;
	endif;
	// connect template and selected js-files and libraries in selected order
	$sql = "DELETE FROM `r_temp_jscript` WHERE `templates_id` = ".intval($id);
	mysql_query($sql);
	if (isset($_POST['jsfiles']) && $_POST['jsfiles']!="" && is_array($_POST['jsfiles'])):
		foreach ($_POST['jsfiles'] AS $value):
			$sql = "INSERT INTO `r_temp_jscript` SET `templates_id` = ".intval($id).", `javascript_id` = ".intval($value);
			mysql_query($sql);
		endforeach;
	endif;
	// connect template and selected rss-file
	$sql = "DELETE FROM `r_temp_rss` WHERE `templates_id` = ".intval($id);
	mysql_query($sql);
	if ($_POST['rssfile']>0):
		$sql = "INSERT INTO `r_temp_rss` SET `templates_id` = ".intval($id).", `rss_id` = ".intval($_POST['rssfile']);
		mysql_query($sql);
	endif;
elseif (isset($_REQUEST['op']) && $_REQUEST['op']=='clonetemplate'):
	// template klonen
	$sql = 'INSERT INTO `templates` (`name`, `template`, `bodytag`, `head`, `generic_viewport`, `framework`, `fonts`) (SELECT \''.$_POST['templatename'].'\', `template`, `bodytag`, `head`, `generic_viewport`, `framework`, `fonts` FROM `templates` WHERE `id`='.$id.')';
	mysql_query($sql);
	$idnew = mysql_insert_id();
	$sql = "INSERT INTO `r_temp_styles` (`templates_id`, `stylesheets_id`) (SELECT ".intval($idnew).", `stylesheets_id` FROM `r_temp_styles` WHERE `templates_id` = ".intval($id)." ORDER BY `id`)";
	mysql_query($sql);
	$sql = "INSERT INTO `r_temp_jscript` (`templates_id`, `javascript_id`) (SELECT ".intval($idnew).", `javascript_id` FROM `r_temp_jscript` WHERE `templates_id` = ".intval($id)." ORDER BY `id`)";
	mysql_query($sql);
	$sql = "INSERT INTO `r_temp_rss` (`templates_id`, `rss_id`) (SELECT ".intval($idnew).", `rss_id` FROM `r_temp_rss` WHERE `templates_id` = ".intval($id)." ORDER BY `id`)";
	mysql_query($sql);
elseif (isset($_REQUEST['op']) && $_REQUEST['op']=='defaulttemplate'):
	// ausgewaehltes template wird als default-template gesetzt
	$sql = "DELETE FROM `wspproperties` WHERE `varname` = 'templates_id'";
	mysql_query($sql);
	$sql = "INSERT INTO `wspproperties` SET `varname` = 'templates_id', `varvalue` = '".intval($id)."'";
	mysql_query($sql);
elseif (isset($_REQUEST['op']) && $_REQUEST['op']=='deltemplate'):
	// loeschen eines templates
	$sql = "DELETE FROM `templates` WHERE `id` = ".intval($_POST['deleteid']);
	mysql_query($sql);
	// remove js connect
	$sql = "DELETE FROM `r_temp_jscript` WHERE `templates_id` = ".intval($_POST['deleteid']);
	mysql_query($sql);
	// remove rss connect
	$sql = "DELETE FROM `r_temp_rss` WHERE `id` = ".intval($_POST['deleteid']);
	mysql_query($sql);
	// remove stylesheet connect
	$sql = "DELETE FROM `r_temp_styles` WHERE `id` = ".intval($_POST['deleteid']);
	mysql_query($sql);
	// update menu table to set entries to use upper template
	$sql = "UPDATE `menu` SET `templates_id` = 0, contentchanged = 3, changetime = ".time()." WHERE `templates_id` = ".intval($_POST['deleteid']);
	mysql_query($sql);
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
            <?php
            
            $templates_sql = "SELECT `id`, `name` FROM `templates` ORDER BY `name`";
            $templates_res = doSQL($templates_sql);
            
            if ($templates_res['num']>0):
                ?>
                <div class="panel">
                    <div class="panel-heading">
                        <h3 class="panel-title"><?php echo returnIntLang('templates existingtemplates'); ?></h3>
                    </div>
                    <div class="panel-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="col-md-3"><?php echo returnIntLang('str templatename'); ?></th>
                                    <th class="col-md-3"><?php echo returnIntLang('str usage'); ?></th>
                                    <th class="col-md-3"><?php echo returnIntLang('str connection'); ?></th>
                                    <th class="col-md-3"></th>
                                </tr>
                            </thead>
                           
                       </table>
                        
                    </div>
                </div>
            <?php endif; ?>
            
            
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
	<?php
	
	$templates_sql = "SELECT `id`, `name` FROM `templates` ORDER BY `name`";
	$templates_res = mysql_query($templates_sql);
	if ($templates_res):
		$templates_num = mysql_num_rows($templates_res);
	endif;
	
	if (isset($_POST['op']) && $_POST['op']=='edit') $_SESSION['opentabs']['templates_fieldset'] = 'display: none;';
	if (isset($_POST['op']) && $_POST['op']=='clone') $_SESSION['opentabs']['templates_fieldset'] = 'display: none;';
		
	?>
	<fieldset>
		
		<?php if ($templates_num>0): ?>
		<ul class="tablelist">
		<?php
		
		// lookup for default temp
		$defaulttemp_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'templates_id'";
		$defaulttemp_res = mysql_query($defaulttemp_sql);
		if ($defaulttemp_res):
			$defaulttemp_num = mysql_num_rows($defaulttemp_res);
		endif;
		if ($defaulttemp_num!=0):
			$defaulttemp = intval(mysql_result($defaulttemp_res, 0));
		else:
			$defaulttemp = 0;
		endif;
		
		$i = 0;
		
		while ($row = mysql_fetch_array($templates_res, MYSQL_ASSOC)):
			$i++; ?>
			<li class="tablecell two"><a href="#" onclick="document.getElementById('tempaction_<?php echo $row['id']; ?>').submit();"><?php echo $row['name']; ?></a></li>
			<li class="tablecell two"><?php
			
			$tempused_num = 0;
			$tempused_sql = "SELECT `mid`, `description` FROM `menu` WHERE `templates_id` = ".intval($row['id'])." AND `trash` = 0";
			$tempused_res = mysql_query($tempused_sql);
			if ($tempused_res): $tempused_num = mysql_num_rows($tempused_res); endif;

			// in diese aufstellung muss auch noch irgendwie rein, wenn ein menuepunkt
			// das template des hoeher gestellten menuepunktes nutzt

			if ($tempused_num>5):
				$tempused_show = 5;
			else:
				$tempused_show = $tempused_num;
			endif;
			for ($t=0;$t<$tempused_show;$t++):
				echo mysql_result($tempused_res,$t,"description")."<br />";
			endfor;
			if ($tempused_num>5):
				echo "...<br />";
			endif;
			?></li>
			<li class="tablecell two"><?php
			$usedstyles_sql = "SELECT `stylesheets_id` FROM `r_temp_styles` WHERE `templates_id` = ".intval($row['id'])." ORDER BY `id`";
			$usedstyles_res = mysql_query($usedstyles_sql);
			$usedstyles_num = mysql_num_rows($usedstyles_res);

			if ($usedstyles_num!=0):
				$cssdisplay = FALSE;
				for ($u=0;$u<$usedstyles_num;$u++):
					$cssdata_sql = "SELECT `describ`, `cfolder` FROM `stylesheets` WHERE id = '".mysql_result($usedstyles_res,$u)."'";
					$cssdata_res = mysql_query($cssdata_sql);
					$cssdata_num = mysql_num_rows($cssdata_res);
					if ($cssdisplay==FALSE):
						echo "<span style=\"float: left; width: 3em;\">CSS: </span>";
						$cssdisplay = TRUE;
					else:
						echo "<span style=\"float: left; width: 3em;\">&nbsp;</span>";
					endif;
					if ($cssdata_num!=0):
						echo mysql_result($cssdata_res,0,'describ');
						if (trim(mysql_result($cssdata_res,0,'cfolder'))!=""):
							echo " <em>Library</em>"; 
						endif;
						echo "<br />";
					endif;
				endfor;
			endif;

			// show used javascript-files
			$used_sql = "SELECT `javascript_id` FROM `r_temp_jscript` WHERE `templates_id` = ".$row['id']." ORDER BY `id`";
			$used_res = mysql_query($used_sql);
			$used_num = 0;
			if ($used_res):
				$used_num = mysql_num_rows($used_res);
			endif;
			if ($used_num > 0):
				$display = FALSE;
				for ($u = 0; $u < $used_num; $u++):
					$data_sql = "SELECT `describ` FROM `javascript` WHERE id = ".mysql_result($used_res,$u)." LIMIT 1";
					$data_res = mysql_query($data_sql);
					if ($data_res):
						$data_num = mysql_num_rows($data_res);
					else:
						writeMySQLError($data_sql);
					endif;
					if ($display==FALSE):
						echo "<span style=\"float: left; width: 3em;\">JS: </span>";
						$display = TRUE;
					else:
						echo "<span style=\"float: left; width: 3em;\">&nbsp;</span>";
					endif;
					if ($data_num > 0):
						echo "<span style=\"display:table\">".mysql_result($data_res,0)."</span>";
					endif;
				endfor;
			endif;

			$usedrss_sql = "SELECT `rss_id` FROM `r_temp_rss` WHERE `templates_id` = '".$row['id']."' ORDER BY `id`";
			$usedrss_res = @mysql_query($usedrss_sql);
			$usedrss_num = @mysql_num_rows($usedrss_res);

			if ($usedrss_num!=0):
				$rssdata_sql = "SELECT `rsstitle` FROM `rssdata` WHERE rid = '".mysql_result($usedrss_res,0)."'";
				$rssdata_res = mysql_query($rssdata_sql);
				$rssdata_num = mysql_num_rows($rssdata_res);
				if ($rssdata_num!=0):
					echo "<span style=\"float: left; width: 3em;\">RSS: </span>".mysql_result($rssdata_res,0);
				endif;
			endif;
			?></li>
			<?php
				
			if ($defaulttemp == $row['id']):
				$del = "<span class=\"bubblemessage red disabled\">".returnIntLang('bubble delete', false)."</span>";
				$standard = "<span class=\"bubblemessage green disabled\">".returnIntLang('bubble standard', false)."</span>";
			else:
				if ($tempused_num>0):
					$del = "<span class=\"bubblemessage red disabled\">".returnIntLang('bubble delete', false)."</span>";
				else:
					$del = "<a onclick=\"return confirmDeleteTemplate('".$row['name']."', ".$row['id'].");\"><span class=\"bubblemessage red\">".returnIntLang('bubble delete', false)."</span></a>";
				endif;
				$standard = "<a href=\"".$_SERVER['PHP_SELF']."?op=defaulttemplate&id=".$row['id']."\"><span class=\"bubblemessage green\">".strtoupper(returnIntLang('bubble standard', false))."</span></a>";
			endif;
			// clone button
			$cloneit = "<a onclick=\"document.getElementById('action_".$row['id']."').value = 'clone'; document.getElementById('tempaction_".$row['id']."').submit();\"><span class=\"bubblemessage green\">".returnIntLang('bubble clone', false)."</span></a>";
			// edit button
			$edittemplate = "<a onclick=\"document.getElementById('action_".$row['id']."').value = 'edit'; document.getElementById('tempaction_".$row['id']."').submit();\"><span class=\"bubblemessage green\">".returnIntLang('bubble edit', false)."</span></a>";
		
			$links = trim($edittemplate." ".$cloneit." ".$del." ".$standard);
			?>
			<li class="tablecell two"><?php echo $links; ?><form name="tempaction" id="tempaction_<?php echo $row['id']; ?>" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>#tpleditor"><input type="hidden" name="op" id="action_<?php echo $row['id']; ?>" value="edit" /><input type="hidden" name="id" id="id_<?php echo $row['id']; ?>" value="<?php echo $row['id']; ?>" /></form></li>
			<?php
		endwhile;
		echo "</ul>\n";
		endif;
		?>
		</div>
	</fieldset>
	<form name="deletetemplateform" id="deletetemplateform" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>"><input type="hidden" name="op" value="deltemplate" /><input type="hidden" id="deleteid" name="deleteid" value="" /></form>
	
	
<?php
	$edittemp_sql = "SELECT * FROM `templates` WHERE `id` = ".intval($id);
	$edittemp_res = mysql_query($edittemp_sql);
	if ($edittemp_res):
		$edittemp_num = mysql_num_rows($edittemp_res);
	endif;
	if ($edittemp_num==0):
		$id = 0;
	else:
		$edittemp_name = mysql_result($edittemp_res,0,"name");
		$edittemp_temp = mysql_result($edittemp_res, 0, "template");
		if ($_SESSION['wspvars']['stripslashes']>0):
			for ($strip=0; $strip<$_SESSION['wspvars']['stripslashes']; $strip++):
				$edittemp_temp = stripslashes($edittemp_temp);
			endfor;
		endif;
		$edittemp_body = mysql_result($edittemp_res, 0, "bodytag");
		$edittemp_head = mysql_result($edittemp_res, 0, "head");
		if ($_SESSION['wspvars']['stripslashes']>0):
			for ($strip=0; $strip<$_SESSION['wspvars']['stripslashes']; $strip++):
				$edittemp_head = stripslashes($edittemp_head);
			endfor;
		endif;
		$edittemp_generic_viewport = mysql_result($edittemp_res, 0, "generic_viewport");
		$edittemp_framework = unserializeBroken(mysql_result($edittemp_res, 0, "framework"));
		$edittemp_fonts = unserializeBroken(mysql_result($edittemp_res, 0, "fonts"));
	endif;

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
				endif; ?></td>
			</tr>
			<tr>
				<td class="tablecell two"><?php echo returnIntLang('templates frameworks'); ?></td>
				<td class="tablecell two"><ul class="checklist">
					<li><input type="checkbox" name="framework[jquery]" id="framework_jquery" value="1" <?php if (is_array($edittemp_framework) && array_key_exists('jquery', $edittemp_framework) && intval($edittemp_framework['jquery'])==1): echo "checked=\"checked\""; endif; ?> /> jQuery local (JavaScript)</li>
					<li><input type="checkbox" name="framework[jqueryui]" id="framework_jqueryui" value="1" <?php if (is_array($edittemp_framework) && array_key_exists('jqueryui', $edittemp_framework) && intval($edittemp_framework['jqueryui'])==1): echo "checked=\"checked\""; endif; ?> /> jQuery UI local (JavaScript)</li>
					
					<li><input type="checkbox" name="framework[jquerygoogle]" id="framework_jquerygoogle" value="1" <?php if (is_array($edittemp_framework) && array_key_exists('jquerygoogle', $edittemp_framework) && intval($edittemp_framework['jquerygoogle'])==1): echo "checked=\"checked\""; endif; ?> /> jQuery 1.12.0 @ google (JavaScript)</li>
					<li><input type="checkbox" name="framework[jqueryuigoogle]" id="framework_jqueryuigoogle" value="1" <?php if (is_array($edittemp_framework) && array_key_exists('jqueryuigoogle', $edittemp_framework) && intval($edittemp_framework['jqueryuigoogle'])==1): echo "checked=\"checked\""; endif; ?> /> jQuery UI 1.11.4 @ google (JavaScript)</li>
					
					<li><input type="checkbox" name="framework[covifuncs]" id="framework_covifuncs" value="1" <?php if (is_array($edittemp_framework) && array_key_exists('covifuncs', $edittemp_framework) && intval($edittemp_framework['covifuncs'])==1): echo "checked=\"checked\""; endif; ?> /> COVI Scripts (JavaScript)</li>
					<!-- <li><input type="checkbox" name="framework[yui]" id="framework_yui" value="1" />YUI (CSS)</li> -->
					<!-- <li><input type="checkbox" name="framework[yaml]" id="framework_yaml" value="1" />YAML (CSS)</li> -->
				</ul></td>
				<td class="tablecell two"><?php echo returnIntLang('templates jslib'); ?></td>
				<td class="tablecell two"><?php
				
				$javascript_sql = "SELECT `id`, `describ` FROM `javascript` WHERE `describ`!='' AND `cfolder` != '' ORDER BY `describ`";
				$javascript_res = mysql_query($javascript_sql);
				if ($javascript_res):
					$javascript_num = mysql_num_rows($javascript_res);
				endif;
				if ($javascript_num==0):
					echo returnIntLang('templates nojsdefined');
				else:
					$jsarray = array();
					$jscon_sql = "SELECT * FROM `r_temp_jscript` WHERE `templates_id` = ".intval($id)." ORDER BY `id`";
					$jscon_res = mysql_query($jscon_sql);
					if ($jscon_res):
						$jscon_num = mysql_num_rows($jscon_res);
					endif;
					if ($jscon_num>0):
						for ($jres=0; $jres<$jscon_num; $jres++):
							$jsarray[mysql_result($jscon_res, $jres, 'javascript_id')]['checked'] = true;
						endfor;
					endif;
					for ($j=0;$j<$javascript_num;$j++):
						$jsarray[intval(mysql_result($javascript_res, $j, "id"))]['describ'] = trim(mysql_result($javascript_res, $j, "describ"));
					endfor;
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
				$javascript_res = mysql_query($javascript_sql);
				if ($javascript_res):
					$javascript_num = mysql_num_rows($javascript_res);
				endif;
				if ($javascript_num==0):
					echo returnIntLang('templates nojsdefined');
				else:
					$jsarray = array();
					$jscon_sql = "SELECT * FROM `r_temp_jscript` WHERE `templates_id` = ".intval($id)." ORDER BY `id`";
					$jscon_res = mysql_query($jscon_sql);
					if ($jscon_res):
						$jscon_num = mysql_num_rows($jscon_res);
					endif;
					if ($jscon_num>0):
						for ($jres=0; $jres<$jscon_num; $jres++):
							$jsarray[mysql_result($jscon_res, $jres, 'javascript_id')]['checked'] = true;
						endfor;
					endif;
					for ($j=0;$j<$javascript_num;$j++):
						$jsarray[intval(mysql_result($javascript_res, $j, "id"))]['describ'] = trim(mysql_result($javascript_res, $j, "describ"));
					endfor;
					echo "<ul id=\"jslist\" class=\"checklist\">";
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
				$designs_res = mysql_query($designs_sql);
				if ($designs_res):
					$designs_num = mysql_num_rows($designs_res);
				endif;
				if ($designs_num==0):
					echo returnIntLang('templates nocssdefined');
				else:
					$cssarray = array();
					$csscon_sql = "SELECT * FROM `r_temp_styles` WHERE `templates_id` = ".intval($id)." ORDER BY `id`";
					$csscon_res = mysql_query($csscon_sql);
					if ($csscon_res):
						$csscon_num = mysql_num_rows($csscon_res);
					endif;
					if ($csscon_num>0):
						for ($cres=0; $cres<$csscon_num; $cres++):
							$cssarray[mysql_result($csscon_res, $cres, 'stylesheets_id')]['checked'] = true;
						endfor;
					endif;
					for ($d=0;$d<$designs_num;$d++):
						$cssarray[intval(mysql_result($designs_res, $d, "id"))]['describ'] = trim(mysql_result($designs_res, $d, "describ"));
						$cssarray[intval(mysql_result($designs_res, $d, "id"))]['cfolder'] = trim(mysql_result($designs_res, $d, "cfolder"));
					endfor;
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
			
			$siteinfo_num = 0;
			$siteinfo_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'contentvardesc'";
			$siteinfo_res = mysql_query($siteinfo_sql);
			if ($siteinfo_res):
				$siteinfo_num = mysql_num_rows($siteinfo_res);
			endif;
			if ($siteinfo_num>0):
				$contentvardesc = unserializeBroken(mysql_result($siteinfo_res, 0));
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
				$menuvar_res = mysql_query($menuvar_sql);
				if ($menuvar_res):
					$menuvar_num = mysql_num_rows($menuvar_res);
				endif;
				if ($menuvar_num>0):
					echo "<optgroup name=\"".returnIntLang('templates menuvars selfdefined', false)."\" label=\"".returnIntLang('templates menuvars selfdefined', false)."\">";
					for ($mres=0; $mres<$menuvar_num; $mres++):
						echo "<option value=\"".strtoupper(mysql_result($menuvar_res, $mres, 'guid'))."\">".mysql_result($menuvar_res, $mres, 'title')."</option>";
					endfor;
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
			$selfvars_res = mysql_query($selfvars_sql);
			
			if (mysql_num_rows($selfvars_res)!=0): ?>
			<li class="tablecell two"><?php echo returnIntLang('templates selfvars'); ?></li>
			<li class="tablecell two"><select name="selfvar" id="selfvar" size="1" style="width: 95%;" onchange="insertVar('selfvar'); return false;">
				<option value=""><?php echo returnIntLang('hint choose', false); ?></option>
				<?php
				
				while ($row = mysql_fetch_assoc($selfvars_res)):
					echo "<option value=\"[%".trim(strtoupper($row['name']))."%]\">".$row['name']."</option>\n";
				endwhile;
					
				?>
			</select></li>
			<?php endif; ?>
			<?php
		
			$globalcontents_sql = "SELECT `gc`.`id`, `gc`.`valuefields`, `ip`.`name` FROM `content_global` as `gc`, `interpreter` AS `ip` WHERE `gc`.`interpreter_guid` = `ip`.`guid` AND `gc`.`trash` = 0 ORDER BY `interpreter_guid`";
			$globalcontents_res = mysql_query($globalcontents_sql);
			$globalcontents_num = 0;
			if ($globalcontents_res):
				$globalcontents_num = mysql_num_rows($globalcontents_res);
			endif;
			
			if ($globalcontents_num>0):
			?><li class="tablecell two"><?php echo returnIntLang('templates globalcontents'); ?></li>
			<li class="tablecell two"><select name="globalcontent" id="globalcontent" size="1" style="width: 95%;" onchange="insertVar('globalcontent'); return false;">
				<option value=""><?php echo returnIntLang('hint choose', false); ?></option>
				<?php
				
				while ($row = mysql_fetch_assoc($globalcontents_res)):
					echo "<option value=\"[%GLOBALCONTENT:".intval($row['id'])."%]\">".$row['name']."</option>\n";
				endwhile;
					
				?>
			</select></li><?php endif; ?>
		</ul>
		<fieldset class="options innerfieldset">
			<p><input type="hidden" name="op" value="savetemplate" /><input type="hidden" name="id" id="id" value="<?php echo $id; ?>" /><a onclick="checkEditFields(); return false;" class="greenfield"><?php echo returnIntLang('str save', false); ?></a> <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="orangefield"><?php echo returnIntLang('str cancel', false); ?></a></p>
		</fieldset>
		</form>
	</fieldset>
	<?php if(isset($_POST['op']) && $_POST['op']=='clone' && isset($_POST['id']) && intval($_POST['id'])>0): 
		
		$clonetemp_sql = "SELECT * FROM `templates` WHERE `id` = ".intval($id);
		$clonetemp_res = mysql_query($clonetemp_sql);
		if ($clonetemp_res):
			$clonetemp_num = mysql_num_rows($clonetemp_res);
		endif;
		if ($clonetemp_num!=0):
			$clonetemp_name = mysql_result($clonetemp_res,0,"name");
		endif;
		
		?>
		<fieldset id="fscloneit">
			<legend><?php echo returnIntLang('templates clone legend1'); echo $clonetemp_name; echo returnIntLang('templates clone legend2'); ?></legend>
			<!-- <p><?php echo returnIntLang('templates clone info'); ?></p> -->
			<form id="formcloneit" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
			<table class="contenttable">
				<tr>
					<td width="25%"><?php echo returnIntLang('templates clone new name'); ?></td>
					<td width="75%"><input type="text" name="templatename" id="templatename" value="" class="three full" /></td>
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
        </div>
    </div>
</div>
        
<?php require ("./data/include/footer.inc.php"); ?>