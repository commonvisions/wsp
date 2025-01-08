<?php
/**
 * aufbau des dynamischen menues fuer modulare menueeintraege
 * @author s.haendler@covi.de
 * @copyright (c) 2018, Common Visions Media.Agentur (COVI)
 * @since 3.1
 * @version 6.7
 * @lastchange 2018-09-18
 */

if (!function_exists("buildWSPMenu")):
	function buildWSPMenu ($parent, $spaces, $rights) {

		$checkrights = array();
		foreach ($rights AS $key => $value):
			if ($value==1):
				$checkrights[] = $key;
			endif;
		endforeach;
		
		$wspmenu_sql = "SELECT `id`, `title`, `link`, `parent_id`, `position`, `guid` FROM `wspmenu` WHERE `parent_id` = '".$parent."' ORDER BY `title` ASC";
		$wspmenu_res = mysql_query($wspmenu_sql);
		if ($wspmenu_res):
			$wspmenu_num = mysql_num_rows($wspmenu_res);
		endif;
		
		if ($wspmenu_num>0):
			for ($mres=0; $mres<$wspmenu_num; $mres++):
				if (in_array(mysql_result($wspmenu_res, $mres, "guid"), $checkrights) || $GLOBALS['wspvars']['usertype']=='admin'):
					$GLOBALS['wspvars']['wspmodmenu'][mysql_result($wspmenu_res, $mres, "guid")] = array($spaces, mysql_result($wspmenu_res, $mres, "id"), mysql_result($wspmenu_res, $mres, "parent_id"), mysql_result($wspmenu_res, $mres, "title"), mysql_result($wspmenu_res, $mres, "link"));
					if ($spaces==0):
						$GLOBALS['wspvars']['wspmodmenucount'] = $GLOBALS['wspvars']['wspmodmenucount']+1;
					endif;
					
					$wspsubmenu_sql = "SELECT `id` FROM `wspmenu` WHERE `parent_id` = '".mysql_result($wspmenu_res, $mres, "id")."'";
					$wspsubmenu_res = mysql_query($wspsubmenu_sql);
					if ($wspsubmenu_res):
						$wspsubmenu_num = mysql_num_rows($wspsubmenu_res);
					endif;
					
					if ($wspsubmenu_num>0):
						for ($smres=0; $smres<$wspsubmenu_num; $smres++):
							buildWSPMenu (mysql_result($wspmenu_res, $mres, "id"), ($spaces+1), $rights);
						endfor;
					endif;
				endif;
			endfor;
		endif;
		}
endif;

/**
* Hauptfunktion
*/
$menu = checkParamVar('menu', '');
$mp = 0;

// request defined standard template for preview/publisher/content

$standardtemp = intval(@mysql_result(mysql_query("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'templates_id'"),0));
$isanalytics = trim(@mysql_result(mysql_query("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'googleanalytics'"),0));

?>
<script type="text/javascript"><!--//--><![CDATA[//><!--
startList = function() {
	if (document.all&&document.getElementById) {
		cssdropdownRoot = document.getElementById("cssdropdown");
		for (x=0; x<cssdropdownRoot.childNodes.length; x++) {
			node = cssdropdownRoot.childNodes[x];
			if (node.nodeName=="LI") {
				node.onmouseover=function() {
					this.className+=" over";
					}
				node.onmouseout=function() {
					this.className=this.className.replace(" over", "");
					}
				}
			}
		}
	}

if (window.attachEvent) { window.attachEvent("onload", startList) }
else { window.onload=startList; }

function jumpTo(jumpValue) {
	if (jumpValue=='logout') {
		window.location.href = '/<?php echo $wspvars['wspbasedir']; ?>/logout.php';
		}
	else {
		var menuLength = document.getElementById('cssdropdown').childNodes.length;
		for (var cm = 0; cm < menuLength; cm++) {
			if (document.getElementById('cssdropdown').childNodes[cm].nodeName=='DIV') {
				document.getElementById(document.getElementById('cssdropdown').childNodes[cm].getAttribute('id')).style.display = 'none';
				}
			}
		document.getElementById(jumpValue).style.display = 'block';
		}
	}
//--><!]]></script>
<div id="menuholder">
	<ul id="cssdropdown">
		<li class="mainlist"><a href="logout.php">Logout</a></li>
		<?php $mp = 2; ?>
		<li class="mainlist <?php if($wspvars['mgroup']==$mp) echo "active";?>" id="m_<?php echo $mp; ?>_1">
			<a href="usernotice.php"><?php echo returnIntLang('menu user messages'); ?> <?
			
			$allmessage = unserialize($wspvars['messages']);
			$i = 0;
			if (count($allmessage)>0 && strlen(trim($wspvars['messages']))>4):
				foreach ($allmessage AS $key => $value):
					if ($value[3]==0):
						$i++;
					endif;
				endforeach;
			endif;
			
			if ($i>0):
				echo "<span class=\"bubblemessageholder\"><span class=\"bubblemessage orange\" id=\"\">".$i."</span></span>";
			endif;
			
			?></a>
		</li>
				
		<?php
		
		$wspvars['wspmodmenu'] = array();
		$wspvars['wspmodmenucount'] = 0;
		
		$modmenu_sql = "SELECT * FROM `wspmenu` WHERE `guid` = '".ALLOWEDMOD."' ORDER BY `title` ASC";
		$modmenu_res = mysql_query($modmenu_sql);
		if ($modmenu_res):
			$modmenu_num = mysql_num_rows($modmenu_res);
			if ($modmenu_num>0):
				buildWSPMenu (intval(mysql_result($modmenu_res, 0, 'id')), 0, $wspvars['rights']);
			endif;
		endif;
			
		$mrun = 0;
		foreach ($wspvars['wspmodmenu'] AS $key => $value):
			$showmodmenu[$mrun]['guid'] = $key;
			$showmodmenu[$mrun]['level'] = $value[0];
			$showmodmenu[$mrun]['id'] = $value[1];
			$showmodmenu[$mrun]['parent_id'] = $value[2];
			$showmodmenu[$mrun]['title'] = $value[3];
			$showmodmenu[$mrun]['link'] = $value[4];
			$mrun++;
		endforeach;
		
		if ($wspvars['wspmodmenucount']==0):
			echo "<li class=\"mainlist\"><strong><a href=\"modindex.php\">Modul ".mysql_result($modmenu_res, 0, 'describ')."</a></strong>";
			echo "<ul class=\"sublist\">";
			$wmstart = 1;
		elseif ($wspvars['wspmodmenucount']>0):
			echo "<li class=\"mainlist\"><strong><a href=\"modindex.php\">Modul ".mysql_result($modmenu_res, 0, 'describ')."</a></strong> ";
			echo "<ul class=\"sublist\">";
			$wmstart = 0;
		endif;
		
		if ($wspvars['wspmodmenucount']>0):
			for ($wmrun=$wmstart; $wmrun<$mrun; $wmrun++):
				$buf = "";
				if ($showmodmenu[$wmrun]['level']==0):
					$buf.= "<li class=\"sublistitem\" id=\"m_".$showmodmenu[$wmrun]['id']."\" onmouseover=\"document.getElementById('sub_".$showmodmenu[$wmrun]['id']."').style.display = 'block';\" onmouseout=\"document.getElementById('sub_".$showmodmenu[$wmrun]['id']."').style.display = 'none';\">";
					if ($wmstart==0 && $showmodmenu[($wmrun+1)]['level']>0):
						$buf.= "\n<a>".$showmodmenu[$wmrun]['title']." ...</a>";
						$buf.= "<ul id=\"sub_".$showmodmenu[$wmrun]['id']."\" class=\"thirdlist\" style=\"display: none;\">";
						while ($showmodmenu[intval($wmrun+1)]['level']>0):
							$wmrun++;
							$buf.= "<li>\n<a href=\"modgoto.php?modid=".$showmodmenu[$wmrun]['id']."\" title=\"".$showmodmenu[$wmrun]['title']."\">".$showmodmenu[$wmrun]['title']."</a></li>";
						endwhile;
						$buf.= "</ul>";
					else:
						$buf.= "\n<a href=\"modgoto.php?modid=".$showmodmenu[$wmrun]['id']."\" title=\"".$showmodmenu[$wmrun]['title']."\">".$showmodmenu[$wmrun]['title']."</a>";
					endif;
					$buf.= "</li>";
				elseif ($wmstart==1 && $showmodmenu[$wmrun]['level']>0):
					$buf.= "<li class=\"sublistitem\" id=\"m_".$showmodmenu[$wmrun]['id']."\">";
					$buf.= "\n<a href=\"modgoto.php?modid=".$showmodmenu[$wmrun]['id']."\" title=\"".$showmodmenu[$wmrun]['title']."\">".$showmodmenu[$wmrun]['title']."</a>";
					$buf.= "</li>";
				endif;
				echo $buf;
			endfor;
		endif;
		
		echo "</ul></li>";
			
		?>
		<?php if (is_array($wspvars['locallanguages'])): 
			
			ksort($wspvars['locallanguages'], SORT_STRING);
		
			?>
			<li class="mainlist"><?php echo returnIntLang('menu manage language'); ?><ul class="sublist" id="m_lang">
				<?php foreach($wspvars['locallanguages'] AS $llkey => $llvalue): 
					echo "<li><a href=\"".$_SERVER['PHP_SELF']."?setlang=".$llkey."\">".$llvalue."</a></li>";
				endforeach; ?>
			</ul></li>
		<?php endif; ?>	
		</div>	
	</ul>
</div>

<div id="infoholder"><fieldset id="locationholder"><?php echo $wspvars['location']; ?></fieldset>
<fieldset id="errormsg"><?php if($_SESSION['wspvars']['errormsg']!=""): echo $_SESSION['wspvars']['errormsg']; endif; unset($_SESSION['wspvars']['errormsg']); ?></fieldset>
<fieldset id="noticemsg"><?php if($_SESSION['wspvars']['noticemsg']!=""): echo $_SESSION['wspvars']['noticemsg']; endif; unset($_SESSION['wspvars']['noticemsg']); ?></fieldset>
<fieldset id="resultmsg"><?php if($_SESSION['wspvars']['resultmsg']!=""): echo $_SESSION['wspvars']['resultmsg']; endif; unset($_SESSION['wspvars']['resultmsg']); ?></fieldset></div>

<script language="JavaScript" type="text/javascript">
<!--

function blendItem(objID, start, blenddir) {
	document.getElementById(objID).style.opacity = start/100;
	document.getElementById(objID).style.filter = 'alpha(opacity: ' + start + ')';
	if (blenddir=='hide') {
		if (start>=5) {
			setTimeout("blendItem('" + objID + "', " + (start-5) + ", 'hide')", 100);
			}
		else {
			document.getElementById(objID).style.display = 'none';
			}
		}
	else if (blenddir=='show') {
		document.getElementById(objID).style.display = 'block';
		if (start<=95) {
			setTimeout("blendItem('" + objID + "', " + (start+5) + ", 'show')", 100);
			}
		else {
			document.getElementById(objID).style.display = 'block';
			}
		}
	}

if (document.getElementById('locationholder').innerHTML == '') {
	document.getElementById('locationholder').style.display = 'none';
	}
if (document.getElementById('errormsg').innerHTML == '') {
	document.getElementById('errormsg').style.display = 'none';
	}
else {
	setTimeout("blendItem('errormsg', " + 100 + ", 'hide')", 3000);
	}
if (document.getElementById('noticemsg').innerHTML == '') {
	document.getElementById('noticemsg').style.display = 'none';
	}
else {
	setTimeout("blendItem('noticemsg', " + 100 + ", 'hide')", 3000);
	}
if (document.getElementById('resultmsg').innerHTML == '') {
	document.getElementById('resultmsg').style.display = 'none';
	}
else {
	setTimeout("blendItem('resultmsg', " + 100 + ", 'hide')", 3000);
	}
// -->
</script>
<?php // EOF ?>