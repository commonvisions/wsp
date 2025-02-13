<?php
/**
 * website publisher
 * @author stefan@covi.de
 * @since 3.1
 * @version GIT
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
require ("./data/include/wsplang.inc.php");
require ("./data/include/dbaccess.inc.php");
if (file_exists("./data/include/ftpaccess.inc.php")) require ("./data/include/ftpaccess.inc.php");
require ("./data/include/funcs.inc.php");
/* checkParamVar ----------------------------- */

/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'publisher';
$_SESSION['wspvars']['mgroup'] = 7;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* page specific includes */

/* define page specific vars ----------------- */

/* define page specific functions ------------ */

if (isset($_POST) && array_key_exists('op', $_POST) && $_POST['op']=="clearqueue"):
	$cpsql = "DELETE FROM `wspqueue` WHERE `uid` = ".intval($_SESSION['wspvars']['userid'])." AND `done` = 0";
	doSQL($cpsql);
elseif (isset($_POST) && array_key_exists('op', $_POST) && $_POST['op']=="clearallqueues" && $_SESSION['wspvars']['usertype']==1):
	$cpsql = "DELETE FROM `wspqueue` WHERE `done` = 0";
	doSQL($cpsql);
endif;

if (array_key_exists('publishitem', $_POST) && is_array($_POST['publishitem']) && count($_POST['publishitem'])>0):
	$setuptime = time();
	$pubid = $_POST['publishitem'];
	if ($_POST['publishsubs']==1):
		$tmppubid = array();
		foreach ($pubid AS $pvalue):
			$tmppubid = array_merge($tmppubid, subpMenu($pvalue));
		endforeach;
		$pubid = array_merge($pubid, $tmppubid);
		unset($tmppubid);
	endif;
	array_unique($pubid);
	
	// find ... if structure is in publishing mode
	if(array_key_exists('op', $_POST) && is_array($_POST['op']) && in_array("structure", $_POST['op'])): 
		$tmppubid = $pubid;
		$struc_pubid = array();
		foreach ($tmppubid AS $pvalue):		
			if(getChangeStat($pvalue) == 4 || getChangeStat($pvalue) == 5 || getChangeStat($pvalue) == 7): // 7 = rename file
				$emp_tmp = getEffectedMPs($pvalue);
				if(count($emp_tmp)>0):
					foreach($emp_tmp AS $emp):
						if($emp!="" && $emp!=$pvalue):
							array_push($struc_pubid,$emp);
						endif;
					endforeach;
				endif;
			endif;
		endforeach;

		// Alle abhängigen MPs werden in die Warteschlange eingetragen
		if(count($struc_pubid)>0):
			foreach ($struc_pubid AS $strucvalue):
				if ($strucvalue>0):
					if (array_key_exists('publishlang', $_POST) && is_array($_POST['publishlang'])):
						foreach ($_POST['publishlang'] AS $plk => $plv):
							$pubsql = "DELETE FROM `wspqueue` WHERE `uid` = ".intval($_SESSION['wspvars']['userid'])." AND `action` = 'publishstructure' AND `param` = '".intval($strucvalue)."' AND `lang` = '".trim($plv)."' AND `done` = 0";
							doSQL($pubsql);
							$pubsql = "REPLACE INTO `wspqueue` SET `uid` = ".intval($_SESSION['wspvars']['userid']).", `set` = '".$setuptime."', `action` = 'publishstructure', `param` = '".intval($strucvalue)."', `timeout` = 0, `done` = 0, `priority` = 1, `outputuid` = ".intval($_SESSION['wspvars']['userid']).", `lang` = '".trim($plv)."', `output` = ''";
							$pubres = doSQL($pubsql);
                            if ($pubres['res']===false):
								addWSPMsg('errormsg', returnIntLang('publisher error setup queue'));
							endif;
						endforeach;
					else:
						$publishlang = unserializeBroken($_SESSION['wspvars']['sitelanguages']);
						foreach ($publishlang['languages']['shortcut'] AS $sk => $sv):
							$pubsql = "DELETE FROM `wspqueue` WHERE `uid` = ".intval($_SESSION['wspvars']['userid'])." AND `action` = 'publishstructure' AND `param` = '".intval($strucvalue)."' AND `lang` = '".trim($sv)."' AND `done` = 0";
							doSQL($pubsql);
							$pubsql = "REPLACE INTO `wspqueue` SET `uid` = ".intval($_SESSION['wspvars']['userid']).", `set` = '".$setuptime."', `action` = 'publishstructure', `param` = '".intval($strucvalue)."', `timeout` = 0, `done` = 0, `priority` = 1, `outputuid` = ".intval($_SESSION['wspvars']['userid']).", `lang` = '".trim($sv)."', `output` = ''";
							$pubres = doSQL($pubsql);
                            if ($pubres['res']===false):
								addWSPMsg('errormsg', returnIntLang('publisher error setup queue'));
							endif;
						endforeach;
					endif;
				endif;
			endforeach;
		endif;
	endif;
	
	// run publisher ids
	foreach ($pubid AS $pvalue):
		
		// different publish modes
		if (array_key_exists('op', $_POST) && is_array($_POST['op']) && in_array("structure", $_POST['op']) && !(in_array("content", $_POST['op'])) && !(in_array("force", $_POST['op']))):
			// only structure publishing
			$cccheck_sql = "SELECT `mid` FROM `menu` WHERE `mid` = ".intval($pvalue)." AND `contentchanged` = 7";
			$cccheck_res = doSQL($cccheck_sql);
			if ($cccheck_res['num']>0):
				$publishaction = 'renamestructure';
			else:
				$publishaction = 'publishstructure';
			endif;
		elseif (array_key_exists('op', $_POST) && is_array($_POST['op']) && in_array("content", $_POST['op']) && !(in_array("structure", $_POST['op']))):
			// remove pages without changed contents
			$cccheck_sql = "SELECT `mid` FROM `menu` WHERE `mid` = ".intval($pvalue)." AND (`contentchanged` = 2 || `contentchanged` = 3 || `contentchanged` = 5)";
			$cccheck_res = doSQL($cccheck_sql);
			if ($cccheck_res['num']>0):
				$publishaction = 'publishcontent';
			else:
				$pvalue = 0;
			endif;
		elseif (array_key_exists('op', $_POST) && is_array($_POST['op']) && in_array("force", $_POST['op']) && !(in_array("structure", $_POST['op']))):
			$publishaction = 'publishcontent';
		elseif (array_key_exists('op', $_POST) && is_array($_POST['op']) && in_array("structure", $_POST['op']) && in_array("content", $_POST['op'])):
			// remove pages without changed contents 
			$cccheck_sql = "SELECT `mid` FROM `menu` WHERE `mid` = ".intval($pvalue)." AND (`contentchanged` = 2 || `contentchanged` = 3 || `contentchanged` = 5)";
			$cccheck_res = doSQL($cccheck_sql);
			if ($cccheck_res['num']>0):
				$publishaction = 'publishitem';
			else:
				$publishaction = 'publishstructure';
			endif;
		elseif (array_key_exists('op', $_POST) && is_array($_POST['op']) && in_array("structure", $_POST['op']) && in_array("force", $_POST['op'])):
			$publishaction = 'publishitem';
		endif;
		
		if ($pvalue>0):
			// setup queue
			if (array_key_exists('publishlang', $_POST) && is_array($_POST['publishlang'])):
				foreach ($_POST['publishlang'] AS $plk => $plv) {
					$pub_sql = "INSERT INTO `wspqueue` SET `uid` = ".intval($_SESSION['wspvars']['userid']).", `set` = '".$setuptime."', `action` = '".$publishaction."', `param` = '".intval($pvalue)."', `timeout` = 0, `done` = 0, `priority` = 1, `outputuid` = ".intval($_SESSION['wspvars']['userid']).", `lang` = '".trim($plv)."', `output` = ''";
                    $pub_res = doSQL($pub_sql);
					if ($pub_res['aff']===false) {
						addWSPMsg('errormsg', returnIntLang('publisher error setup queue'));
                    }
                }
			else:
				$publishlang = unserializeBroken($_SESSION['wspvars']['sitelanguages']);
				foreach ($publishlang['languages']['shortcut'] AS $sk => $sv) {
					$pub_sql = "INSERT INTO `wspqueue` SET `uid` = ".intval($_SESSION['wspvars']['userid']).", `set` = '".$setuptime."', `action` = '".$publishaction."', `param` = '".intval($pvalue)."', `timeout` = 0, `done` = 0, `priority` = 1, `outputuid` = ".intval($_SESSION['wspvars']['userid']).", `lang` = '".trim($sv)."', `output` = ''";
                    $pub_res = doSQL($pub_sql);
					if ($pub_res['aff']===false) {
						addWSPMsg('errormsg', returnIntLang('publisher error setup queue'));
                    }
                }
			endif;
		endif;
	endforeach;
	addWSPMsg('noticemsg', returnIntLang('publisher added files to queue'));
    header('location: ./publishqueue.php');
endif;
	
// publish css
if (array_key_exists('publishcss', $_POST) && is_array($_POST['publishcss']) && count($_POST['publishcss'])>0):
	foreach ($_POST['publishcss'] AS $pvalue):
		$pub_sql = "INSERT INTO `wspqueue` SET `uid` = ".intval($_SESSION['wspvars']['userid']).", `set` = '".time()."', `action` = 'publishcss', `param` = '".intval($pvalue)."', `timeout` = 0, `done` = 0, `priority` = 1, `outputuid` = ".intval($_SESSION['wspvars']['userid']).", `output` = ''";
		$pub_res = doSQL($pub_sql);
        if ($pub_res['aff']===false) {
			addWSPMsg('errormsg', returnIntLang('publisher error adding css files'));
        } else {
            addWSPMsg('noticemsg', returnIntLang('publisher added css file to queue'));
        }
	endforeach;
    header('location: ./publishqueue.php');
endif;

// publish javascript
if (array_key_exists('publishjs', $_POST) && is_array($_POST['publishjs']) && count($_POST['publishjs'])>0):
	foreach ($_POST['publishjs'] AS $pvalue):
		$pub_sql = "INSERT INTO `wspqueue` SET `uid` = ".intval($_SESSION['wspvars']['userid']).", `set` = '".time()."', `action` = 'publishjs', `param` = '".intval($pvalue)."', `timeout` = 0, `done` = 0, `priority` = 1, `outputuid` = ".intval($_SESSION['wspvars']['userid']).", `output` = ''";
		$pub_res = doSQL($pub_sql);
        if ($pub_res['aff']===false) {
			addWSPMsg('errormsg', returnIntLang('publisher error adding js files'));
        } else {
            addWSPMsg('noticemsg', returnIntLang('publisher added js file to queue'));
        }
	endforeach;
    header('location: ./publishqueue.php');
endif;

// publish rss
if (array_key_exists('publishrss', $_POST) && is_array($_POST['publishrss']) && count($_POST['publishrss'])>0):
	foreach ($_POST['publishrss'] AS $pvalue):
		$pub_sql = "INSERT INTO `wspqueue` SET `uid` = ".intval($_SESSION['wspvars']['userid']).", `set` = '".time()."', `action` = 'publishrss', `param` = '".intval($pvalue)."', `timeout` = 0, `done` = 0, `priority` = 1, `outputuid` = ".intval($_SESSION['wspvars']['userid']).", `output` = ''";
		$pub_res = doSQL($pub_sql);
        if ($pub_res['aff']===false) {
			addWSPMsg('errormsg', returnIntLang('publisher error adding rss files'));
        } else {
            addWSPMsg('noticemsg', returnIntLang('publisher added rss file to queue'));
        }
	endforeach;
    header('location: ./publishqueue.php');
endif;

$siteinfo_sql = "SELECT * FROM `wspproperties`";
$siteinfo_res = doSQL($siteinfo_sql);
if ($siteinfo_res['num']>0) {
	foreach ($siteinfo_res['set'] AS $sirk => $sirv) {
		$_SESSION['wspvars']['publisherdata'][trim($sirv['varname'])] = trim($sirv['varvalue']);
    }
}

// head der datei
include ("./data/include/header.inc.php");
include ("./data/include/wspmenu.inc.php");

?>
<script language="JavaScript" type="text/javascript">
<!--
function selectPublish(id, selectType) {
	$("." + id).toggleClass('publish');
	if ($("." + id).hasClass('publish')) { $("#check" + id).prop('checked', true); } else { $("#check" + id).prop('checked', false); }
	if (!($("#check" + id).prop('checked'))) { $("#checkall" + selectType).prop('checked', false); }
	}	// selectPublish()

function selectAllPublish(selectType) {
	$("." + selectType + "publish").addClass('publish');
	$("." + selectType + "publishbox").prop('checked', true);
	$("#checkall" + selectType).prop('checked', true);
	
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
		})
	
	}	// selectAllPublish()

function deselectAllPublish(selectType) {
	$("." + selectType + "publish").removeClass('publish');
	$("." + selectType + "publishbox").prop('checked', false);
	$("#checkall" + selectType).prop('checked', false);
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

// -->
</script>
<div id="contentholder">
	<pre id="debugcontent"></pre>
	<fieldset><?php 
	// block to define workspace language
	if ((array_key_exists('workspacelang', $_SESSION['wspvars']) && $_SESSION['wspvars']['workspacelang']=="") || (!(array_key_exists('workspacelang', $_SESSION['wspvars'])))):
		$_SESSION['wspvars']['workspacelang'] = $worklang['languages']['shortcut'][0];
	endif;
	if (isset($_POST['workspacelang']) && $_POST['workspacelang']!=""):
		$_SESSION['wspvars']['workspacelang'] = $_POST['workspacelang'];
	endif;
	
	if (intval(count($worklang['languages']['shortcut']))>1):
			?>
			<form name="changeworkspacelang" id="changeworkspacelang" method="post" style="float: right;">
			<select name="workspacelang" onchange="document.getElementById('changeworkspacelang').submit();">
				<?php
				
				foreach ($worklang['languages']['shortcut'] AS $key => $value):
					echo "<option value=\"".$worklang['languages']['shortcut'][$key]."\" ";
					if ($_SESSION['wspvars']['workspacelang']==$worklang['languages']['shortcut'][$key]):
						echo " selected=\"selected\"";
					endif;
					echo ">".$worklang['languages']['longname'][$key]."</option>";
				endforeach;
				
				?>
			</select>
			</form>
			<?php
		endif;
		
	?><h1><?php echo returnIntLang('publisher headline'); ?></h1></fieldset>
	<?php
	
	$_SESSION['publishrun'] = 0;
	
	$queue_sql = "SELECT `id` FROM `wspqueue` WHERE `done` = 0 GROUP BY `param`, `id`";
	$queue_res = doSQL($queue_sql);

	$subqueue_sql = "SELECT `id` FROM `wspqueue` WHERE `done` = -1 GROUP BY `param`, `id`";
	$subqueue_res = doSQL($subqueue_sql);

	if ($queue_res['num']>0 || $subqueue_res['num']>0):
		?>
		<fieldset id="showqueue">
			<legend><?php echo returnIntLang('publisher queue', true); ?></legend>
			<?php if($queue_res['num']>0): ?>
				<p id="queue-text"><?php echo returnIntLang('publisher pages in queue1'); ?> <span id="queue-num"><?php echo $queue_res['num']; ?></span> <?php echo returnIntLang('publisher page'.(($queue_res['num']>1)?'s':'').' in queue2'); ?></p>
			<?php endif; ?>
			<?php if($subqueue_res['num']>0): ?>
				<p id="subqueue-text"><?php echo returnIntLang('publisher pages in subqueue1'); ?> <span id="subqueue-num"><?php echo $subqueue_res['num']; ?></span> <?php echo returnIntLang('publisher page'.(($subqueue_res['num']>1)?'s':'').' in subqueue2'); ?></p>
			<?php endif; ?>
			<?php if($queue_res['num']>0): ?>
				<form id="clearqueue" method="post"><input type="hidden" name="op" id="queueop" value="clearqueue" /></form>
				<fieldset class="innerfieldset options">
					<p><a onclick="document.getElementById('clearqueue').submit();" class="redfield"><?php echo returnIntLang('publisher clear queue'); ?></a> <?php if ($_SESSION['wspvars']['usertype']=='admin'): ?><a onclick="document.getElementById('queueop').value = 'clearallqueues'; document.getElementById('clearqueue').submit();" class="redfield"><?php echo returnIntLang('publisher clear all queues'); ?></a><?php endif; ?></p>
				</fieldset>
			<?php endif; ?>
		</fieldset>
	<?php endif; ?>
	<?php if ($_SESSION['wspvars']['rights']['publisher']<100): ?>
	<fieldset>
		<legend><?php echo returnIntLang('str legend'); ?> <?php echo legendOpenerCloser('wsplegend', 'closed'); ?></legend>
		<div id="wsplegend" style="<?php echo $_SESSION['opentabs']['wsplegend']; ?>">
			<p><?php echo returnIntLang('publisher intro desc', true); ?></p>
		</div>
	</fieldset>
	<?php endif;
	
	// check for rights
	
	if (intval($_SESSION['wspvars']['rights']['design'])==1):
	
		// pruefung auf aktualisierungen in der css-datei seit letztem publishing
			
		$csschanges_sql = "SELECT `id`, `describ`, `file`, `cfolder`, `lastchange`, (`lastchange` > `lastpublish`) AS `changed` FROM `stylesheets` ORDER BY `lastchange` < `lastpublish`, `describ`";
		$csschanges_res = doSQL($csschanges_sql);
		
		if ($csschanges_res['num']>0):
			$aCSS = array();
            foreach($csschanges_res['set'] AS $ccrk => $rowCSS):
				$aCSS[] = array(
					'id' => $rowCSS['id'],
					'description' => trim($rowCSS['describ']),
					'changed' => intval($rowCSS['changed']),
                    'lastchange' => intval($rowCSS['lastchange']),
					'filename' => trim($rowCSS['file']),
					'foldername' => trim($rowCSS['cfolder'])
					);
				if (intval($rowCSS['changed'])==1): $csspubchanged = true; endif;
            endforeach;
    
			?>
			<fieldset id="css-changes">
				<legend><?php echo returnIntLang('publisher css-files', true); ?> <?php if(isset($csspubchanged) && $csspubchanged===true): echo returnIntLang('unpublished changes'); endif; ?> <?php echo legendOpenerCloser('csspub', 'closed'); ?></legend>
				<div id="csspub">
					<form action="<?php echo $_SERVER['PHP_SELF']; ?>" id="csspublish" enctype="multipart/form-data" method="post">
					<table class="tablelist publishinglist">
						<tr>
							<td class="tablecell head three"><?php echo returnIntLang('str description', true); ?></td>
							<td class="tablecell head three"><?php echo returnIntLang('publisher created filename', true); ?></td>
							<td class="tablecell head two"><input type="checkbox" id="checkallcss" onchange="checkallpublish('css');"></td>
						</tr>
						<?php $crun=1; foreach ($aCSS as $key => $value): ?>
							<tr>
								<td class="tablecell three csspublish css<?php echo $value['id']; ?> <?php if ($value['changed']==1): echo " publishrequired"; endif; ?>" onclick="selectPublish('css<?php echo $value['id']; ?>','css'); return true;"><?php echo $value['description']; ?></td>
								<td class="tablecell three csspublish css<?php echo $value['id']; ?> <?php if ($value['changed']==1): echo " publishrequired"; endif; ?>" onclick="selectPublish('css<?php echo $value['id']; ?>','css'); return true;">/media/layout/<?php 
								
								if ($value['foldername']==$value['lastchange']):
									echo $value['filename'].".css"; 
								else:
									echo $value['foldername']."/";
								endif;
								
								?></td>
								<td class="tablecell two csspublish css<?php echo $value['id']; ?> <?php if ($value['changed']==1): echo " publishrequired"; endif; ?>" onclick="selectPublish('css<?php echo $value['id']; ?>','css'); return true;"><input type="checkbox" class="csspublishbox" name="publishcss[]" value="<?php echo $value['id']; ?>" id="checkcss<?php echo $value['id']; ?>"></td>
							</tr>
						<?php $crun++; endforeach; ?>
					</table>
					<fieldset class="options innerfieldset">
						<input type="hidden" name="usevar" value="<?php echo $_SESSION['wspvars']['usevar']; ?>" />
						<input type="hidden" name="op" value="publishit" />
						<p><a  onclick="setToPublish('css'); return false;" class="greenfield"><?php echo returnIntLang('publisher publish selection', true); ?></a> <a onclick="deselectAllPublish('css'); return true;" class="orangefield"><?php echo returnIntLang('publisher unselect selection', true); ?></a></p>
					</fieldset>
					</form>
				</div>
			</fieldset>
		<?php endif; ?>
	<?php endif; ?>
	
	<?php
	
	// check for rights to publish scripts
	// if ($wspvars['rights']['script'] == 1):
	// pruefung auf aktualisierungen in der js-datei seit letztem publishing
			
	$jschanges_sql = "SELECT `id`, `describ`, `file`, (`lastchange` > `lastpublish`) AS `changed` FROM `javascript` WHERE `cfolder` = '' ORDER BY `lastchange` < `lastpublish`, `describ`";
	$jschanges_res = doSQL($jschanges_sql);
	
	if ($jschanges_res['num']>0):
		$aJS = array();
		foreach($jschanges_res['set'] AS $jsrk => $rowJS) {
			$aJS[] = array(
				'id' => $rowJS['id'], 
				'description' => $rowJS['describ'], 
				'changed' => $rowJS['changed'], 
				'filename' => $rowJS['file']
				);
        };
		if ($rowJS['changed']==1): $jspubchanged = true; endif;

		?>
		<fieldset id="js-changes">
			<legend><?php echo returnIntLang('publisher js-files', true); ?> <?php if(isset($jspubchanged) && $jspubchanged===true): echo returnIntLang('unpublished changes'); endif; ?> <?php echo legendOpenerCloser('jspub'); ?></legend>
			<div id="jspub">
				<form action="<?php echo $_SERVER['PHP_SELF']; ?>" id="jspublish" enctype="multipart/form-data" method="post">
				<table class="tablelist publishinglist">
					<tr>
					<td class="tablecell three head"><?php echo returnIntLang('str description', true); ?></td>
					<td class="tablecell three head"><?php echo returnIntLang('publisher created filename', true); ?></td>
					<td class="tablecell two head"><input type="checkbox" id="checkalljs" onchange="checkallpublish('js');"></td>
					<?php $crun=0; foreach ($aJS as $key => $value): ?>
						<tr>
							<td class="tablecell three jspublish js<?php echo $value['id']; ?> <?php if ($value['changed']==1): echo " publishrequired"; endif; ?>" onclick="selectPublish('js<?php echo $value['id']; ?>','js'); return true;"><?php echo $value['description']; ?></td>
						<td class="tablecell three jspublish js<?php echo $value['id']; ?> <?php if ($value['changed']==1): echo " publishrequired"; endif; ?>" onclick="selectPublish('js<?php echo $value['id']; ?>','js'); return true;">/data/script/<?php echo $value['filename']; ?>.js</td>
							<td class="tablecell two jspublish js<?php echo $value['id']; ?> <?php if ($value['changed']==1): echo " publishrequired"; endif; ?>" onclick="selectPublish('js<?php echo $value['id']; ?>','js'); return true;"><input type="checkbox" class="jspublishbox" name="publishjs[]" value="<?php echo $value['id']; ?>" id="checkjs<?php echo $value['id']; ?>"></td>
						</tr>
					<?php $crun++; endforeach; ?>
				</table>
				<fieldset class="options innerfieldset">
					<input type="hidden" name="usevar" value="<?php echo $_SESSION['wspvars']['usevar']; ?>" />
					<input type="hidden" name="op" value="publishit" />
					<p><a onclick="setToPublish('js'); return false;" class="greenfield"><?php echo returnIntLang('publisher publish selection', true); ?></a> <a onclick="deselectAllPublish('js'); return true;" class="orangefield"><?php echo returnIntLang('publisher unselect selection', true); ?></a></p>
				</fieldset>
				</form>
			</div>
		</fieldset>
	<?php endif; ?>	
	<?php
	
	/*
	if (intval($_SESSION['wspvars']['rights']['rss'])==1):
		
		$rssdata_sql = "SELECT * FROM `rssdata`";
		$rssdata_res = doSQL($rssdata_sql);
		
		if ($rssdata_res['res']>0):
			
			$rssentry_sql = "SELECT `eid` FROM `rssentries` WHERE `epublished` = 0";
			$rssentry_res = doSQL($rssentry_sql);
			if ($rssentry_res['num']>0): $rsspubchanged = true; endif;
			
			?>
			<fieldset id="rss-changes">
				<legend><?php echo returnIntLang('publisher rss-files', true); ?> <?php if(isset($rsspubchanged) && $rsspubchanged===true): echo returnIntLang('unpublished changes'); endif; ?> <?php echo legendOpenerCloser('rsspub'); ?></legend>
				<div id="rsspub">
					<form action="<?php echo $_SERVER['PHP_SELF']; ?>" id="rsspublish" enctype="multipart/form-data" method="post">
					<table class="tablelist publishinglist">
						<tr>
							<td class="tablecell three head"><?php echo returnIntLang('str description'); ?></td>
							<td class="tablecell three head"><?php echo returnIntLang('publisher created filename', true); ?></td>
							<td class="tablecell two head"><input type="checkbox" id="checkallrss" onchange="checkallpublish('rss');"></td>
						</tr>
						<?php foreach ($rssdata_res['set'] AS $rssdk => $rssdv): 
							$rssentry_sql = "SELECT `eid` FROM `rssentries` WHERE `rid` = ".intval($rssdv["rid"])." AND `epublished` = 0";
							$rssentry_res = doSQL($rssentry_sql);
							?>
							<tr>
								<td class="tablecell three rsspublish rss<?php echo intval($rssdv["rid"]); ?> <?php if ($rssentry_res['num']>0): echo " publishrequired"; endif; ?>" onclick="selectPublish('rss<?php echo intval($rssdv["rid"]); ?>','rss'); return true;"><?php echo trim($rssdv["rsstitle"]); ?></td>
								<td class="tablecell three rsspublish rss<?php echo intval($rssdv["rid"]); ?> <?php if ($rssentry_num>0): echo " publishrequired"; endif; ?>" onclick="selectPublish('rss<?php echo intval($rssdv["rid"]); ?>','rss'); return true;">/media/rss/<?php echo trim($rssdv["rssfilename"]); ?>.rss</td>
								<td class="tablecell two rsspublish rss<?php echo intval($rssdv["rid"]); ?> <?php if ($rssentry_num>0): echo " publishrequired"; endif; ?>" onclick="selectPublish('rss<?php echo intval($rssdv["rid"]); ?>','rss'); return true;"><input type="checkbox" class="rsspublishbox" name="publishrss[]" value="<?php echo intval($rssdv["rid"]); ?>" id="checkrss<?php echo intval($rssdv["rid"]); ?>" /></td>
							</tr>
						<?php endforeach; ?>
					</table>
					<fieldset class="options innerfieldset">
						<input type="hidden" name="usevar" value="<?php echo $_SESSION['wspvars']['usevar']; ?>" />
						<input type="hidden" name="op" value="publishit" />
						<p><a onclick="setToPublish('rss'); return false;" class="greenfield"><?php echo returnIntLang('publisher publish selection', false); ?></a> <a onclick="deselectAllPublish('rss'); return true;" class="orangefield"><?php echo returnIntLang('publisher unselect selection', false); ?></a></p>
					</fieldset>
					</form>
				</div>
			</fieldset>
		<?php endif; ?>
	<?php endif; */ ?>

	<script language="JavaScript" type="text/javascript">
		<!--
		
		function searchPublish() {
			$('#contentpublisher').replaceWith('<table class="publishinglist tablelist" id="contentpublisher"><tr><td class="tablecell eight" style="text-align: center;"><?php echo returnIntLang('publisher loading data', false); ?></td></tr></table>');
			$.post("xajax/ajax.showpublisher.php", {'showpublish': document.getElementById('publishlist').value, 'searchpublish': document.getElementById('searchPublish').value}).done (function(data) {
				if (data!="") {
					$('#contentpublisher').replaceWith(data);
					createFloatingTable();
					}
				});
			}
		
		function setPublishList(listvalue) {
			document.getElementById('publishlist_all').className = 'bubblemessage orange';
			document.getElementById('publishlist_publishrequired').className = 'bubblemessage orange';
			document.getElementById('publishlist_publishcontent').className = 'bubblemessage orange';
			document.getElementById('publishlist_publishstructure').className = 'bubblemessage orange';
			document.getElementById('publishlist_' + listvalue).className = 'bubblemessage';
			document.getElementById('publishlist').value = listvalue;
			searchPublish();
			}
		
		//-->
		</script>
		<?php
		
		$mpoint_sql = "SELECT `mid`, `contentchanged` FROM `menu` WHERE `trash` = 0 AND `visibility` = 1 AND `editable` = 1 AND `contentchanged` != 0 AND `offlink` = ''";
		$mpoint_res = doSQL($mpoint_sql);
		
		?>
		<fieldset>
			<legend><?php echo returnIntLang('publisher search showhide', true); ?></legend>
			<table class="tablelist">
				<tr>
					<td class="tablecell five"><input type="text" id="searchPublish" onchange="searchPublish(this.value)" class="full" /></td>
					<td class="tablecell three"><span class="bubblemessage <?php if ($mpoint_res['num']>0): echo "orange"; endif; ?>" id="publishlist_all" onclick="setPublishList('all');"><?php echo returnIntLang('publisher all', false); ?></span> <span class="bubblemessage <?php if ($mpoint_res['num']==0): echo "orange"; endif; ?>" id="publishlist_publishrequired" onclick="setPublishList('publishrequired');"><?php echo returnIntLang('publisher changed', false); ?></span> <span class="bubblemessage orange" id="publishlist_publishcontent" onclick="setPublishList('publishcontent');"><?php echo returnIntLang('publisher content changed', false); ?></span> <span class="bubblemessage orange" id="publishlist_publishstructure" onclick="setPublishList('publishstructure');"><?php echo returnIntLang('publisher structure changed', false); ?></span></td>
				</tr>
			</table>
			<input type="hidden" id="publishlist" value="<?php if ($mpoint_res['num']>0): echo "publishrequired"; else: echo "all"; endif; ?>" />
		</fieldset>
		<fieldset>
			<legend><?php echo returnIntLang('publisher files'); ?></legend>
			<div id="publishinglistholder">
				<form action="<?php echo $_SERVER['PHP_SELF']; ?>" id="itempublish" enctype="multipart/form-data" method="post"">
					<table class="tablelist publishinglist">
						<tr>
							<td class="tablecell two head"><?php echo returnIntLang('str description'); ?></td>
							<td class="tablecell four head"><?php echo returnIntLang('publisher created filename', true); ?></td>
							<td class="tablecell one head"><?php echo returnIntLang('publisher last publish'); ?></td>
							<td class="tablecell one head"><?php if ($_SESSION['wspvars']['rights']['publisher']<100): ?><input type="checkbox" id="checkallitem" onchange="checkallpublish('item');"> <?php echo returnIntLang('publisher select to publish all'); ?><?php endif; ?></td>
						</tr>
					</table>
					<table class="publishinglist tablelist" id="contentpublisher">
					<tr>
						<td class="tablecell eight" style="text-align: center;"><?php echo returnIntLang('publisher loading data', false); ?></td>
					</tr>
					</table>
					<?php if (intval(count($worklang['languages']['shortcut']))>1):  ?>
					<table class="tablelist">
						<tr>
							<td class="tablecell head three"><?php echo returnIntLang('publisher publish only'); ?></td>
							<td class="tablecell head five"><?php foreach ($worklang['languages']['shortcut'] AS $key => $value): ?><input type="checkbox" name="publishlang[]" value="<?php echo $worklang['languages']['shortcut'][$key]; ?>" <?php if($_SESSION['wspvars']['workspacelang']==$worklang['languages']['shortcut'][$key]): echo " checked='checked' "; endif; ?> />&nbsp;<?php echo $worklang['languages']['longname'][$key]; ?>&nbsp;&nbsp; <?php endforeach; ?></td>
						</tr>
					</table>
					<?php endif; ?>
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
					
					$(document).ready(function() {
						$.post("xajax/ajax.showpublisher.php", {'showpublish': '<?php if ($mpoint_res['num']==0): echo "all"; else: echo "publishrequired"; endif; ?>'}).done (function(data) {
							if (data!="") {
								$('#contentpublisher').replaceWith(data);
								createFloatingTable();
								}
							});
						})
					
					</script>
					<table class="tablelist">
						<tr>
							<td class="tablecell three head"><?php echo returnIntLang('str setuppublish'); ?></td>
							<td class="tablecell five head"><input type="checkbox" name="op[]" value="structure" checked="checked" />&nbsp;<?php echo returnIntLang('str setuppublish structure'); ?> &nbsp; <input type="checkbox" name="op[]" id="contentpublish_changed" value="content" onchange="checkcontentpublish('changed')" /> &nbsp; <?php echo returnIntLang('str setuppublish changed contents'); ?>&nbsp;&nbsp; <input type="checkbox" name="op[]" id="contentpublish_force" value="force" onchange="checkcontentpublish('force')" checked="checked" /> &nbsp; <?php echo returnIntLang('str setuppublish all contents'); ?>&nbsp;&nbsp; </td>
						</tr>
					</table>
				</form>
			</div>
			<?php if ($_SESSION['wspvars']['rights']['publisher']<100): ?>
				<fieldset class="options innerfieldset">
					<p><a onclick="setToPublish('item'); return false;" class="greenfield"><?php echo returnIntLang('publisher publish selection', false); ?></a> <a onclick="document.getElementById('publishsubs').value = 1; setToPublish('item'); return false;" class="greenfield"><?php echo returnIntLang('publisher publish selection and subs', false); ?></a> <a onclick="deselectAllPublish('item'); return true;" class="orangefield"><?php echo returnIntLang('publisher unselect selection', false); ?></a></p>
				</fieldset>
			<?php endif; ?>
		</fieldset>
	</div>
<?php include ("./data/include/footer.inc.php"); ?>
<!-- EOF -->