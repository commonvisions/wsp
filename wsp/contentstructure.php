<?php
/**
 * @author stefan@covi.de
 * @since 3.1
 * @version GIT
 * 
 * 2013-01-09
 * 6.11
 * fixed overwriting messages
 * 
 * 6.11.1
 * fixed error with wrong type of sid  
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
require ("./data/include/funcs.inc.php");
/* checkParamVar ----------------------------- */

/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'contents';
$_SESSION['wspvars']['mgroup'] = 5;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
$worklang = unserializeBroken($_SESSION['wspvars']['sitelanguages']);
$openpath = 0;
// jump from modules ..
if (isset($_REQUEST['mjid']) && intval($_REQUEST['mjid'])>0):
	$_SESSION['wspvars']['editmenuid'] = intval($_REQUEST['mjid']);
endif;
// jump from globalcontents
if (isset($_REQUEST['sgc']) && intval($_REQUEST['sgc'])>0):
	$_SESSION['wspvars']['editmenuid'] = intval($_REQUEST['sgc']);
endif;
/* page specific includes */
require ("./data/include/clsinterpreter.inc.php");
/* page specific funcs and actions */
if (!($_SESSION['wspvars']['rights']['contents']==2 || $_SESSION['wspvars']['rights']['contents']==4 || $_SESSION['wspvars']['rights']['contents']==5 || $_SESSION['wspvars']['rights']['contents']==7 || $_SESSION['wspvars']['rights']['contents']==15)):
	$_SESSION['wspvars']['rights']['contents_array'] = array();
endif;

if ((isset($_POST['op']) && $_POST['op']=='add') && isset($_POST['sid']) && isset($_POST['gcid']) && isset($_POST['mid']) && intval($_POST['mid'])>0):
	// find contents in same content area
	$newpos = intval($_POST['posvor']);
	if ($newpos>0):
		$exc_sql = "SELECT `cid` FROM `content` WHERE `mid` = ".intval($_POST['mid'])." AND `content_area` = ".intval($_POST['carea'])." AND `position` >= ".$newpos." ORDER BY `position`";
		$exc_res = doSQL($exc_sql);
		if ($exc_res['num']>0):
			foreach ($exc_res['set'] AS $ecresk => $ecresv) {
				doSQL("UPDATE `content` SET `position` = ".($newpos+$ecresk+1)." WHERE `cid` = ".intval($ecresv['cid']));
            }
		endif;
	else:
		$pc_sql = "SELECT MAX(`position`) AS maxpos FROM `content` WHERE `mid` = ".intval($_POST['mid'])." AND `content_area` = ".intval($_POST['carea']);
		$pc_res = doResultSQL($pc_sql);
		if ($pc_res!==false): $newpos = (intval($pc_res)+1); else: $newpos = 1; endif;
	endif;
	// check for globalcontent
	$interpreterguid = trim($_POST['sid']);
	$globalcontentid = 0;
    if (intval($_POST['gcid'])>0): // nur noch Prüfung, ob GlobalContent gewählt wurde $_POST['sid']=='0' && 
		$gc_sql = "SELECT `id`, `interpreter_guid` FROM `globalcontent` WHERE `id` = ".intval($_POST['gcid'])." LIMIT 0,1";
		$gc_res = doSQL($gc_sql);
		if ($gc_res['num']>0): $interpreterguid = $gc_res['set'][0]['interpreter_guid']; $globalcontentid = intval($gc_res['set'][0]['id']); endif; // interpreter_guid
	endif;
	$nc_sql = "INSERT INTO `content` SET 
		`mid` = ".intval($_POST['mid']).",
		`uid` = " . intval($_SESSION['wspvars']['userid']) . ",
		`globalcontent_id` = ".intval($globalcontentid).",
		`connected` = 0,
		`content_area` = ".intval($_POST['carea']).",
		`content_lang` = '".escapeSQL(trim($_POST['lang']))."',
		`position` = ".$newpos.",
		`visibility` = 1,
		`showday` = 0,
		`showtime` = '',
		`sid` = 0,
		`valuefields` = '',
		`lastchange` = ".time().",
		`interpreter_guid` = '".escapeSQL($interpreterguid)."'";
	$nc_res = doSQL($nc_sql);
    if ($nc_res['inf']>0):
		$_SESSION['wspvars']['editcontentid'] = $nc_res['inf'];
		// updating menu for changed content
		$minfo_sql = "SELECT `contentchanged` FROM `menu` WHERE `mid` = ".intval($_POST['mid']);
		$minfo_res = doResultSQL($minfo_sql);
		$ccres = 0; if ($minfo_res!==false): $ccres = intval($minfo_res); endif;
		$nccres = 0; if ($ccres==0): $nccres = 2;
		elseif ($ccres==1): $nccres = 3;
		elseif ($ccres==2): $nccres = 2;
		elseif ($ccres==3): $nccres = 3;
		elseif ($ccres==4): $nccres = 5;
		elseif ($ccres==5): $nccres = 5;
		endif;
		doSQL("UPDATE `menu` SET `contentchanged` = ".intval($nccres)." WHERE `mid` = ".intval($_POST['mid']));
		addWSPMsg('resultmsg', "<p>".returnIntLang('contentedit new content created succesfully')."</p>");
		header('location: contentedit.php');
	else:
		addWSPMsg('errormsg', "<p>".returnIntLang('contentedit failure creating new content')."</p>");
	endif;
endif;

if (isset($_POST['editcontentid']) && intval($_POST['editcontentid'])>0):
	$_SESSION['wspvars']['editcontentid'] = intval($_POST['editcontentid']);
	header('location: contentedit.php');
	die();
endif;

/* include head */
require ("./data/include/header.inc.php");
require ("./data/include/wspmenu.inc.php");
?>

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
		
	?><h1><?php echo returnIntLang('contentstructure headline', true); ?></h1></fieldset>
	<script type="text/javascript" charset="utf-8">
	<!--
	
	function showSub(mid, showlevel) {
		if (mid>0) {
			$.post("xajax/ajax.showstructure.php", { 'mid': mid, 'showlevel': showlevel, 'type': 'contents'}).done (function(data) {
				$("#ul_" + mid).html(data);
				$("#ul_" + mid).toggle('blind', 500, function() {
					$("#ul_" + mid).addClass('open');
					passLiTable('#display_sitestructure ul.tablelist', 'li.tablecell', 8, new Array('one','two','three','four','five','six','seven','eight'), 'tblcss');
					});
				})
			}
		}
	
	function showContent(mid, showlevel, copystat) {
		copystat = typeof copystat !== 'undefined' ? copystat : 500;
		if (mid>0) {
			if ($("#ulc_" + mid).css('display')=='none') {
				$.post("xajax/ajax.showcontent.php", { 'mid': mid, 'showlevel': showlevel, 'type': 'contents'}).done (function(data) {
					$("#ulc_" + mid).html(data);
					$("#ulc_" + mid).toggle('blind', copystat, function() {
						$("#ulc_" + mid).addClass('open');
						$("#ulc_" + mid).addClass('tablelist');
						passLiTable('ul.tablelist.contentstructure', 'li.tablecell', 8, new Array('one','two','three','four','five','six','seven','eight'), 'tblcss');
						$(".droplist").sortable({
							connectWith: ".droplist",
							helper: function(e,li) {
								if(e.shiftKey){
									console.log(e.shiftKey);
									copyHelper= li.clone().insertAfter(li);
    		    					return li.clone();
								} else {
									console.log(e.shiftKey);
									
									return li;
								}
    						},
							placeholder: "emptytablecell",
							dropOnEmpty: true,
							start: function (event, ui) {
				     			$(".droplist").css('min-height', '29px');
				     			},
							handle: '.movehandle', 
							// receive -> if dropped from one list to another
				     		receive: function (event, ui) { 
								$.post("xajax/ajax.dragdropcontent.php", {
									'action': 'dragdrop',
									'itempos': ui.item.index(),
									'dropid': ui.item.context.id,
									'sentarea': ui.sender.context.parentElement.className,
									'droparea': ui.item.context.parentElement.className,
									'dropmid': $(this).parentsUntil('.moveable').parent().attr('id'),
									'copy': event.shiftKey}).done (function(data) {
										var return_vals = JSON.parse(data);
	            						if (return_vals['copy']=='copy') {
	            							var oldID = ui.item.context.id;
	            							var newID = $(ui.item.html());
											ui.item.html(newID.html().replace(new RegExp(oldID.substring(4),"g"), return_vals['thenewid']));
											}
										else if ($.trim(return_vals['copy'])!='') { 
											console.log(return_vals);
											}
										});
								if(event.shiftKey){
									copyHelper= null;
									}
						     	},
							// update -> if sorted in list
							update: function (event, ui) { 
								if (!ui.sender && this === ui.item.parent()[0]) {
									$.post("xajax/ajax.dragdropcontent.php", {
										'action': 'sort',
										'itempos': ui.item.index(),
										'dropid': ui.item.context.id,
										'droparea': ui.item.context.parentElement.className,
										'dropmid': $(this).parentsUntil('.moveable').parent().attr('id'),
										'copy': event.shiftKey}).done (function(data) {
											var return_vals = JSON.parse(data);
											if (return_vals['copy']=='copy') {		
	        	    							var oldID = ui.item.context.id;
	            								var newID = $(ui.item.html());
												ui.item.html(newID.html().replace(new RegExp(oldID.substring(4),"g"), return_vals['thenewid']));
												}
											else if ($.trim(return_vals['copy'])!='') {
												console.log(return_vals);
												}
											});
									if(event.shiftKey){
							     		copyHelper= null;
										}
									}
					     		},
					     	stop: function(e, li) {
								if(e.shiftKey){
							    	copyHelper && copyHelper.remove();
									}
    							},
				     		deactivate: function (event, ui) {
				     			$(".droplist").css('min-height', '0px');	
				     			}
				     		});
				     	$( ".movehandle" ).disableSelection();
						});
					})
				}
			else {
				$("#ulc_" + mid).toggle('blind', copystat, function() {
					$("#ulc_" + mid).removeClass('open');
					$("#ulc_" + mid).removeClass('tablelist');
					$("#ulc_" + mid).html('');
					});
				}
			}
		}
	
	function addContent(mid, carea) {
		if (mid>0) {
			$.post("/wsp/xajax/ajax.addcontent.php", { 'mid': mid, 'carea': carea})
				.done (function(data) {
					$("#newcontentarea").html(data);
					$.fancybox("#newcontent");
					});
			}
		}

	function setMHCSS() {
	//	alert('sfs');
		$(".droplist").css('min-height', '29px');
		};
	
	function searchContent() {
		$.post("xajax/ajax.findcontent.php", { 'searchval': $('#searchcontent-form-searchcontent').val(), 'searchlang': $('#searchcontent-form-searchlang').val() }).done (function(data) {
			$("#searchcontentarea").html(data);
			createFloatingTable();
			});
		}
	
	// -->
	</script>	
	<fieldset>
		<legend><?php echo returnIntLang('contentstructure find content', true); ?></legend>
		<form name="searchcontent-form" id="searchcontent-form" />
		<table class="tablelist">
			<tr>
				<td class="tablecell two"><?php echo returnIntLang('contentstructure content contains', true); ?></td>
				<td class="tablecell six"><input type="text" name="searchcontent" id="searchcontent-form-searchcontent" value="<?php if(isset($_SESSION['wspvars']['searchcontent']) && trim($_SESSION['wspvars']['searchcontent'])!=''): echo trim($_SESSION['wspvars']['searchcontent']); endif; ?>" class="full" onblur="searchContent();" /></td>
			</tr>
		</table>
		<input type="hidden" name="searchlang" id="searchcontent-form-searchlang" value="<?php echo $_SESSION['wspvars']['workspacelang']; ?>" />
		</form>
		<div id="searchcontentarea"></div>
	</fieldset>
	<fieldset>
		<?php if ($_SESSION['wspvars']['rights']['contents']==1): ?>
			<legend><?php echo returnIntLang('structure actualstructure', true); ?></legend>
		<?php else: ?>
			<legend><?php echo returnIntLang('structure restrictedstructure', true); ?></legend>
		<?php endif; ?>
		<div id="display_sitestructure">
		<ul id="ul_0" class="tablelist contentstructure"><?php 
		
		if (isset($_REQUEST['sgc']) && intval($_REQUEST['sgc'])>0):
			$oc_sql = "SELECT `mid` FROM `content` WHERE `cid` = ".intval($_REQUEST['sgc']);
			$oc_res = doResultSQL($oc_sql);
			if ($oc_res!==false):
                returnReverseStructure(intval($oc_res));
                $openpath = $midpath[1];
                $_SESSION['opencontent'] = intval($_REQUEST['sgc']);
			endif;
		elseif (isset($_SESSION['opencontent']) && intval($_SESSION['opencontent'])>0):
			$oc_sql = "SELECT `mid` FROM `content` WHERE `cid` = ".$_SESSION['opencontent'];
			$oc_res = doResultSQL($oc_sql);
			if ($oc_res!==false):
				returnReverseStructure(intval($oc_res));
				$openpath = $midpath[1];
			endif;
		elseif (isset($_SESSION['pathmid']) && intval($_SESSION['pathmid'])>0):
			returnReverseStructure(intval($_SESSION['pathmid']));
			$openpath = $midpath[1];
		else:
			$openpath = 0;
		endif;
		
		$topmid = 0;
		$contentallowed = array();
		if ($_SESSION['wspvars']['rights']['contents']==2 || $_SESSION['wspvars']['rights']['contents']==4):
			if (count($_SESSION['wspvars']['rights']['contents_array'])>0):
				$menuallowed = $_SESSION['wspvars']['rights']['contents_array'];
				$contentallowed = $_SESSION['wspvars']['rights']['contents_array'];
			endif;
			if (!(in_array($openpath, $menuallowed))):
				$openpath = 0;
			endif;
		elseif ($_SESSION['wspvars']['rights']['contents']==7):
			if (count($_SESSION['wspvars']['rights']['contents_array'])>0):
				$GLOBALS['midlist'] = array($_SESSION['wspvars']['rights']['contents_array'][0]);
				subMID($_SESSION['wspvars']['rights']['contents_array'][0]);
				$menuallowed = $GLOBALS['midlist'];
				$contentallowed = $GLOBALS['midlist'];
			endif;
		elseif ($_SESSION['wspvars']['rights']['contents']==15):
			$topm_sql = "SELECT `connected` FROM `menu` WHERE `mid` = ".intval($_SESSION['structuremidlist'][0]);
			$topm_res = doResultSQL($topm_sql);
			if ($topm_res!==false): $topmid = intval($topm_res); endif;
			
			$GLOBALS['midlist'] = array($_SESSION['wspvars']['rights']['sitestructure_array'][0]);
			subMID($_SESSION['wspvars']['rights']['sitestructure_array'][0]);
			$menuallowed = $GLOBALS['midlist'];

			$contentallowed = $GLOBALS['midlist'];
		endif;

		if (isset($menuallowed) && is_array($menuallowed)):
			$allowedstructure = array();
			foreach ($menuallowed AS $makey => $mavalue):
				$allowedstructure = array_merge($allowedstructure, returnIDTree($mavalue));
			endforeach;
			$allowedstructure = array_unique($allowedstructure);
			$menuallowed = $allowedstructure;
		else:
			$menuallowed = array();
		endif;
		
		if ($_SESSION['wspvars']['rights']['contents']==7 && is_array($contentallowed)):
			if (!(in_array($openpath, $contentallowed))):
				$openpath = 0;
			endif;
		endif;
		
		if (!(isset($_SESSION['wspvars']['editmenuid']))) $_SESSION['wspvars']['editmenuid'] = 0;
		$openlist = array_reverse(returnMIDList($_SESSION['wspvars']['editmenuid']));
		array_pop($openlist);
		
		/* 
		
		getjMenuStructure(
			ausgehend von hier wird der baum dargestellt
			array mit mid's, deren eigenschaften bearbeitet werden dürfen
			array mit mid's, die überhaupt in der ansicht auftauchen dürfen, beinhaltet unter umständen mehr punkte als ^, wenn mehrere contents über die struktur verteilt bearbeitet werden dürfen
			array mit mid's, die den status OFFEN haben, um ggf. substrukturen abzubilden
			darstellungsmodus
			sprache
			)
		*/
		
		echo getjMenuStructure($topmid, $contentallowed, $menuallowed, $openlist, 'contents', $_SESSION['wspvars']['workspacelang']);
		
		?></ul>
		</div>
	</fieldset>
	<fieldset>
		<ul class="icondesc">
			<li class="icondescitem"><span class="bubblemessage orange "><?php echo returnIntLang('bubble showsub', false); ?></span> <?php echo returnIntLang('bubble showsub icondesc'); ?></li>
			<li class="icondescitem"><span class="bubblemessage blue"><?php echo returnIntLang('bubble showcontent', false); ?></span> <?php echo returnIntLang('bubble showcontent icondesc'); ?></li>
			<li class="icondescitem"><span class="bubblemessage orange"><?php echo returnIntLang('bubble edit', false); ?></span> <?php echo returnIntLang('bubble create content icondesc'); ?></li>
			<li class="icondescitem"><span class="bubblemessage orange"><?php echo returnIntLang('bubble move', false); ?></span> <?php echo returnIntLang('bubble move content icondesc'); ?></li>
			<li class="icondescitem"><span class="bubblemessage green"><?php echo returnIntLang('bubble hide', false); ?></span> <?php echo returnIntLang('bubble shown content icondesc'); ?></li>
			<li class="icondescitem"><span class="bubblemessage"><?php echo returnIntLang('bubble hide', false); ?></span> <?php echo returnIntLang('bubble timebased content icondesc'); ?></li>
			<li class="icondescitem red"><span class="bubblemessage red"><?php echo returnIntLang('bubble show', false); ?></span> <?php echo returnIntLang('bubble hidden content icondesc'); ?></li>
			<li class="icondescitem"><span class="bubblemessage orange">x 2</span> <?php echo returnIntLang('bubble clone content icondesc'); ?></li>
			<li class="icondescitem"><span class="bubblemessage red"><?php echo returnIntLang('bubble delete', false); ?></span> <?php echo returnIntLang('bubble delete content icondesc'); ?></li>
			<li class="icondescitem"><span class="bubblemessage green"><?php echo returnIntLang('bubble edit', false); ?></span> <?php echo returnIntLang('bubble edit content icondesc'); ?></li>
		</ul>
	</fieldset>	
	
	<!-- <fieldset class="options">
		<p><a onclick="toggleOpenView()" class="greenfield"><?php echo returnIntLang('contentstructure toogle open'); ?></a></p>
	</fieldset> -->
	
	<input type="hidden" id="toggleopenview" value="" />
	<script type="text/javascript" charset="utf-8">
	<!--
	
	function toggleOpenView() {
		if ($('#toggleopenview').val()=='') {
			$('.sub.sortable').each(function(e) {
				if ($(this).hasClass('open')) {
					$(this).parent().addClass('openview');
					}
				});
			$('.moveable').each(function(e) {
				if (!($(this).hasClass('openview'))) {
					$(this).hide('fade');
					}
				});
			$('#toggleopenview').val('open');
			}
		else {
			$('.moveable').each(function(e) {
				if ($(this).hasClass('openview')) {
					}
				else {
					$(this).show('fade');
					}
				});
			$('#toggleopenview').val('');
			$('.openview').removeClass('openview');
			}
		}
	
	function checkData() {
		if ((document.getElementById('sid').value == 0) && (document.getElementById('gcid').value == 0)) {
			alert(unescape('<?php echo setUTF8(returnIntLang('contentstructure jshint select interpreter', false)); ?>'));
			return false;
			}	// if
		else {
			document.getElementById('formnewcontent').submit();
			}	// if
		}	// checkData()
	
	function contentShowHide(cid) {
		$.post("xajax/ajax.togglecontentview.php", { 'cid': cid}).done (function(data) {
			if (data=="show") {
				$('.id'+cid).removeClass('hiddencontent',500);
				$('#acsh'+cid).html('<span class="bubblemessage green"><?php echo returnIntLang('bubble hide', false); ?></span>');
				}
			else if (data=="hide") {
				$('.id'+cid).addClass('hiddencontent',500);
				$('#acsh'+cid).html('<span class="bubblemessage red"><?php echo returnIntLang('bubble show', false); ?></span>');
				}
			});
		}

	function contentRemove(cid, cname) {
		if (cid>0) {
			if (confirm('<?php echo returnIntLang('contentstructure jshint confirmdelete content1', false); ?>»' + cname + '«<?php echo returnIntLang('contentstructure jshint confirmdelete content2', false); ?>')) {
				$.post("xajax/ajax.deletecontent.php", { 'cid': cid})
				.done (function(data) {
					$(data).toggle('fade', {}, 300);
					});
				
				
				}
			}
		}

	function contentClone(cid, cname) {
		if (cid>0) {
			$.post("xajax/ajax.clonecontent.php", { 'cid': cid })
			.done (function(data) {
				var return_vals = JSON.parse(data);
          		if (return_vals['clone']==true) {
					var copyHelper= $('#cli_'+cid).clone(); //.insertAfter(li); 							
					var oldID = cid;
	         		//	var newID = $(ui.item.html());
					copyHelper.html($('#cli_'+cid).html().replace(new RegExp(cid,"g"), return_vals['newcid']));
					copyHelper.insertAfter($('#cli_'+cid));
          			copyHelper.attr('id', 'cli_'+return_vals['newcid']);
          			}
				});
			}
		}
	
	-->
	</script>
	<?php if($_SESSION['wspvars']['rights']['contents']!=3 && $_SESSION['wspvars']['rights']['contents']!=4): ?>
		<div style="display: none;">
		<fieldset id="newcontent">
			<legend><?php echo returnIntLang('contentstructure create new content', true)?></legend>
			<form method="post" enctype="multipart/form-data" id="formnewcontent" style="margin: 0px;">
			<div id="newcontentarea">
			<!-- erste seite abrufen -->
			<?php
			// check for selected page if mid is given, otherwise first page of structure
			if (isset($_SESSION['wspvars']['editmenuid']) && intval($_SESSION['wspvars']['editmenuid'])>0):
				$fp_sql = "SELECT `mid`, `templates_id` FROM `menu` WHERE `mid` = ".intval($_SESSION['wspvars']['editmenuid']);
			elseif (isset($_SESSION['pathmid']) && intval($_SESSION['pathmid'])>0):
				$fp_sql = "SELECT `mid`, `templates_id` FROM `menu` WHERE `mid` = ".intval($_SESSION['pathmid']);
			else:
				$fp_sql = "SELECT `mid`, `templates_id` FROM `menu` WHERE `connected` = 0 ORDER BY `level`, `position`";
			endif;
			$fp_res = doSQL($fp_sql);
			if ($fp_res['num']>0):
				$realtemp = getTemplateID(intval($fp_res['set'][0]['mid']));
				$templatevars = getTemplateVars($realtemp);
				?>
				<table class="tablelist" id="addcontentlist">
					<tr>
						<td class="tablecell two"><?php echo returnIntLang('contentstructure page', true); ?></td>
						<td class="tablecell two"><select name="mid" id="insertpage" size="1" class="four" onchange="updateNewContent(this.value, 0);">
							<?php 
							
							// hier sollte noch eine rechteüberprüfung stattfinden, wenn jemand nur menüpunkt und untergeordnet machen kann
							getMenuLevel(0, 0, 1, array(intval($fp_res['set'][0]['mid'])), $menuallowed); 
							
							?>
						</select></td>
					</tr>
				</table>
				<?php if (is_array($templatevars) && count($templatevars['contentareas'])>0): ?>
					<input type="hidden" name="op" value="add" />
					<input type="hidden" name="lang" value="<?php echo $_SESSION['wspvars']['workspacelang']; ?>" />
					<fieldset class="innerfieldset options"><p><a href="#" onclick="checkData();" class="greenfield"><?php echo returnIntLang('str create', false); ?></a></p></fieldset>
					<?php else: ?>
						<p><?php echo returnIntLang('contentstructure this menupoint has no template with contentvars defined'); ?></p>
					<?php endif; ?>
				<?php endif; ?>
				</div>
				</form>
			</fieldset>
			</div>
		<?php endif; ?>
		<form id="editcontents" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<input type="hidden" id="editcontentid" name="editcontentid" value="" />
		</form>
		<form id="editglobal" method="post" action="globalcontentedit.php">
		<input type="hidden" name="op" value="edit" />
		<input type="hidden" id="editglobalid" name="gcid" value="" />
		</form>
		<div style="width: 100%; height: 1px; line-height: 1px; font-size: 0; background: none; clear: both;"></div>
	<?php
	
	if (array_key_exists('opencontent', $_SESSION) && intval($_SESSION['opencontent']) > 0):
		$oc_sql = "SELECT `mid` FROM `content` WHERE `cid` = ".intval($_SESSION['opencontent']);
		$oc_res = doResultSQL($oc_sql);
		if ($oc_res!==false):
            $mp_sql = "SELECT `internlink_id`, `offlink`, `forwardmenu` FROM `menu` WHERE `mid` = ".intval($oc_res);
            $mp_res = doSQL($mp_sql);
            if ($mp_res['num']>0):
                if (intval($mp_res['set'][0]['internlink_id'])>0 || trim($mp_res['set'][0]['offlink'])!=""):
                    $mpforward = $mp_res['set'][0]['forwardmenu'];
                endif;
            endif;
            if ((!(isset($mpforward)) || (isset($mpforward) && intval($mpforward)==0)) && (in_array(intval($oc_res), $_SESSION['wspvars']['rights']['contents_array']) || count($_SESSION['wspvars']['rights']['contents_array'])==0)):
                echo "<script type=\"text/javascript\" charset=\"utf-8\">\n";
                echo "<!--\n";
                echo "showContent(".intval($oc_res).", '');\n";
                echo "// -->\n";
                echo "</script>\n";
            endif;
		endif;
	elseif (array_key_exists('pathmid', $_SESSION) && intval($_SESSION['pathmid']) > 0):
		$mp_sql = "SELECT `internlink_id`, `offlink`, `forwardmenu` FROM `menu` WHERE `mid` = ".intval($_SESSION['pathmid']);
		$mp_res = doSQL($mp_sql);
		if ($mp_res['num']>0):
            if (intval($mp_res['set'][0]['internlink_id'])>0 || trim($mp_res['set'][0]['offlink'])!=""):
                $mpforward = $mp_res['set'][0]['forwardmenu'];
            endif;
        endif;
        if ((!(isset($mpforward)) || (isset($mpforward) && intval($mpforward)==0)) && (in_array(intval($_SESSION['pathmid']), $_SESSION['wspvars']['rights']['contents_array']) || count($_SESSION['wspvars']['rights']['contents_array'])==0)):
            echo "<script type=\"text/javascript\" charset=\"utf-8\">\n";
            echo "<!--\n";
            echo "showContent(".$_SESSION['pathmid'].", '');\n";
            echo "// -->\n";
            echo "</script>\n";
        endif;
	endif;
	?>
	<input type="hidden" id="keypress" value="" />
	<span id="debug"></span>
</div>
<script type="text/javascript" charset="utf-8">
<!--	

$(document).ready(function() {
	<?php if (!(isset($_SESSION['wspvars']['editmenuid']))) $_SESSION['wspvars']['editmenuid'] = 0; if (intval($_SESSION['wspvars']['editmenuid'])>0): ?>showContent(<?php echo intval($_SESSION['wspvars']['editmenuid']); ?>,1);<?php endif; ?>

	$(document).bind('keydown', function(e) {
		if(e.keyCode==16){
			document.getElementById('keypress').value = 'copy';
			}
		});	

	$(document).bind('keyup', function(e) {
		if(e.keyCode==16){
			document.getElementById('keypress').value = '';
			}
		});	
	
	<?php if(isset($_SESSION['wspvars']['searchcontent']) && trim($_SESSION['wspvars']['searchcontent'])!=''): ?>searchContent();<?php endif; ?>
	
	});

function contentOverlay() {
	$("#newcontent").fancybox({
		maxWidth: '90%',
		maxHeight: '90%',
		fitToView: true,
		width: '50%',
		autoHeight: true,
		closeClick: false,
		openEffect: 'fade',
		closeEffect: 'fade',
		scrollOutside: false,
		type: 'html',
		});
	}
	
// -->
</script>
<?php
include ("./data/include/footer.inc.php");
?>
<!-- EOF -->