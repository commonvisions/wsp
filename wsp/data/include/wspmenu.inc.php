<?php
/**
 * aufbau des menues
 * @author stefan@covi.de
 * @since 3.1
 * @version GIT
 * 
 * 2023-01-10
 * 6.11.1
 * Fixed error with "DELETE *"
 * 
 * 2024-01-08
 * Fixed error with GROUP BY statements
 * 
 */

if (!function_exists('buildWSPMenu')):
	function buildWSPMenu ($parent, $spaces, $rights) {
		$checkrights = array();
		if (is_array($rights)):
			foreach ($rights AS $key => $value):
				if ($value==1):
					$checkrights[] = $key;
				endif;
			endforeach;
		endif;
		$wspmenu_sql = "SELECT `id`, `title`, `link`, `parent_id`, `position`, `guid` FROM `wspmenu` WHERE `parent_id` = ".intval($parent)." ORDER BY `position`, `title`";
		$wspmenu_res = doSQL($wspmenu_sql);
		if ($wspmenu_res['num']>0) {
			foreach ($wspmenu_res['set'] AS $wmrsk => $wmrsv) {

				if (in_array($wmrsv['guid'], $checkrights) || (array_key_exists('wspvars', $_SESSION) && array_key_exists('usertype', $_SESSION['wspvars']) && $_SESSION['wspvars']['usertype']==1)) {
					
                    $_SESSION['wspvars']['wspmodmenu'][$wmrsv['guid']] = array($spaces, intval($wmrsv['id']), intval($wmrsv['parent_id']), trim($wmrsv['title']), trim($wmrsv['link']));
					if ($spaces==0):
						$_SESSION['wspvars']['wspmodmenucount']++;
					endif;
					
					$wspsubmenu_sql = "SELECT `id` FROM `wspmenu` WHERE `parent_id` = ".intval($wmrsv['id']);
					$wspsubmenu_res = doSQL($wspsubmenu_sql);
					if ($wspsubmenu_res['num']>0) {
						for ($smres=0; $smres<$wspsubmenu_res['num']; $smres++) {
							buildWSPMenu (intval($wmrsv['id']), ($spaces+1), $rights);
						}
                    }
                }
            }
        }
    }
endif;

/**
* Hauptfunktion
*/
$menu = checkParamVar('menu', '');
$mp = 0;

// request defined standard template for preview/publisher/content

$standardtemp = intval(@doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'templates_id'"));
$isanalytics = trim(@doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'googleanalytics'"));
$isextended = intval(@doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'extendedmenu'"));

// disable filesystem functions without ftp etc
$fsaccess = false;
if (isset($_SESSION['wspvars']['ftpcon']) && $_SESSION['wspvars']['ftpcon']===true) {
	$fsaccess = true;
}
if (isset($_SESSION['wspvars']['directwriting']) && $_SESSION['wspvars']['directwriting']===true) {
	$fsaccess = true;
}

?>
<script type="text/javascript">

function jumpTo(jumpValue) {
	var jumpVal = jumpValue;
	var jumpRes = jumpVal.split("_");
	if (jumpVal=='logout') {
		window.location.href = '/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/logout.php';
		}
	else if (jumpRes[0]=='site') {
		// ajax function to setup site
		$.post("xajax/ajax.setwspsite.php", { 'site': jumpRes[1] }).done (function(data) {});		
		}
	else {
		$('li.level0').not('li.select').hide('fade');
		$('li.level0').not('li.select').css('display', 'none');
		$('li.' + jumpValue).show('fade');
		}
	}
	
function mobileJump(jumpValue) {
	window.location.href = jumpValue;
	}

</script>

<nav id="menuholder" class="row">
	<div id="imenu" class="col">toggle menu</div>
	<?php
	
	if ($plugin_res['num']>0):
		foreach ($plugin_res['set'] AS $presk => $presv):
			$pluginident = $presv["guid"];
			if ((isset($_SESSION['wspvars']) && array_key_exists('rights', $_SESSION['wspvars']) && array_key_exists($pluginident, $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights'][$pluginident]==1) || (array_key_exists('wspvars', $_SESSION) && array_key_exists('usertype', $_SESSION['wspvars']) && $_SESSION['wspvars']['usertype']==1)):
				$pluginfolder = $presv["pluginfolder"];
				if (is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/plugins/".$pluginfolder."/data/include/wsplang.inc.php")):
					@require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/plugins/".$pluginfolder."/data/include/wsplang.inc.php");
				endif;
				if (is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/plugins/".$pluginfolder."/data/include/wspmenu.inc.php")):
					@require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/plugins/".$pluginfolder."/data/include/wspmenu.inc.php");
				endif;
			endif;
		endforeach;
	endif;
		
	$mp = 1;
		
	?>
	<div class="col-auto px-1" id="m_<?php echo $mp; ?>">
		<button type="button" class="btn btn-sm <?php echo ((($_SESSION['wspvars']['mgroup'] ?? 0)==$mp) ? "btn-primary" : ""); ?> dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
    		<?php echo returnIntLang('menu home'); ?>
		</button>
		<ul class="dropdown-menu">
			<li><a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/index.php" class="dropdown-item"><?php echo returnIntLang('menu home cms'); ?></a></li>
			<?php if ($_SESSION['wspvars']['liveurl']==$_SESSION['wspvars']['workspaceurl']): ?>
				<li><a href="http://<?php echo $_SESSION['wspvars']['liveurl']; ?>" target="_blank" title="<?php echo returnIntLang('hint newwindow', false); ?>" class="dropdown-item"><?php echo returnIntLang('menu home'); ?> <?php echo returnIntLang('menu home website'); ?></a></li>
			<?php else: ?>
				<li><a href="http://<?php echo $_SESSION['wspvars']['liveurl']; ?>" target="_blank" title="<?php echo returnIntLang('hint newwindow', false); ?>" class="dropdown-item"><?php echo returnIntLang('menu home website'); ?> (LIVE)</a></li>
				<li><a href="http://<?php echo $_SESSION['wspvars']['workspaceurl']; ?>" target="_blank" title="<?php echo returnIntLang('hint newwindow', false); ?>" class="dropdown-item"><?php echo returnIntLang('menu home website'); ?> (DEV)</a></li>
			<?php endif; ?>
		</ul>
	</div>

	<?php $mp = 2; if (array_key_exists('wspvars', $_SESSION) && array_key_exists('usertype', $_SESSION['wspvars']) && $_SESSION['wspvars']['usertype']==1): ?>
		<div class="col-auto px-1" id="m_<?php echo $mp; ?>">
			<button type="button" class="btn btn-sm <?php echo ((($_SESSION['wspvars']['mgroup'] ?? 0)==$mp) ? "btn-primary" : ""); ?> dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
				<?php echo returnIntLang('menu user'); ?>
			</button>
			<ul class="dropdown-menu">
				<li class="level1" id="m_<?php echo $mp; ?>_0">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/usermanagement.php"><?php echo returnIntLang('menu user manage'); ?></a>
				</li>
				<li class="level1" id="m_<?php echo $mp; ?>_1">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/usershow.php"><?php echo returnIntLang('menu user login'); ?></a>
				</li>
				<?php if ($isextended==1): ?>
					<li class="level1" id="m_<?php echo $mp; ?>_3">
						<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/userhistory.php"><?php echo returnIntLang('menu user logs'); ?></a>
					</li>
				<?php endif; ?>
			</ul>
		</div>
	<?php else: ?>
		<div class="col-auto px-1" id="m_<?php echo $mp; ?>">
			<a><?php echo returnIntLang('menu user'); ?></a> <?php
			
			if (array_key_exists('wspvars', $_SESSION) && array_key_exists('messages', $_SESSION['wspvars'])):
				$allmessage = unserialize($_SESSION['wspvars']['messages']);
				$i = 0;
				if (count($allmessage)>0 && strlen(trim($_SESSION['wspvars']['messages']))>4):
					foreach ($allmessage AS $key => $value):
						if ($value[3]==0):
							$i++;
						endif;
					endforeach;
				endif;
				if ($i>0):
					echo "<span class=\"bubblemessageholder\"><span class=\"bubblemessage orange\" id=\"\">".$i."</span></span>";
				endif;
			endif;
			
			?>
			<ul class="dropdown-menu">
				<li id="m_<?php echo $mp; ?>_0">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/usermanagement.php"><?php echo returnIntLang('menu user managedata'); ?></a>
				</li>
			</ul>
		</div>
	<?php endif; ?>
		
	<?php $mp = 3; if ($_SESSION['wspvars']['rights']['siteprops']!=0): ?>
		<div class="col-auto px-1" id="m_<?php echo $mp; ?>">
			<button type="button" class="btn btn-sm <?php echo ((($_SESSION['wspvars']['mgroup'] ?? 0)==$mp) ? "btn-primary" : ""); ?> dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
				<?php echo returnIntLang('menu siteprefs'); ?>
			</button>
			<ul class="dropdown-menu">
				<?php if ($isextended==1): ?>
					<li class="level1" id="m_<?php echo $mp; ?>_2">
						<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/siteprefs.php"><?php echo returnIntLang('menu siteprefs generell'); ?></a>
					</li>
					<li class="level1" id="m_<?php echo $mp; ?>_2">
						<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/sites.php"><?php echo returnIntLang('menu siteprefs sites'); ?></a>
					</li>
					<li class="level1" id="m_<?php echo $mp; ?>_1">
						<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/headerprefs.php"><?php echo returnIntLang('menu siteprefs redirects'); ?></a>
					</li>
				<?php endif; ?>
				<li class="level1" id="m_<?php echo $mp; ?>_3">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/semanagement.php"><?php echo returnIntLang('menu siteprefs seo'); ?></a>
				</li>
				<li class="level1" id="m_<?php echo $mp; ?>_4">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/analytics.php"><?php echo returnIntLang('menu siteprefs google'); ?></a>
				</li>
				<li class="level1" id="m_<?php echo $mp; ?>_4">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/privacy.php"><?php echo returnIntLang('menu siteprefs privacy'); ?></a>
				</li>
			</ul>
		</div>
	<?php endif; ?>
		
		<?php $mp = 4; if (array_key_exists('wspvars', $_SESSION) && array_key_exists('rights', $_SESSION['wspvars']) && array_key_exists('design', $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights']['design']!=0): ?>
			<div class="col-auto px-1" id="m_<?php echo $mp; ?>">
				<button type="button" class="btn btn-sm <?php echo ((($_SESSION['wspvars']['mgroup'] ?? 0)==$mp) ? "btn-primary" : ""); ?> dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
					<?php echo returnIntLang('menu design'); ?>
				</button>	
				<ul class="dropdown-menu">
			<?php if ($fsaccess) { ?><li class="level1" id="m_<?php echo $mp; ?>_0">
				<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/screenmanagement.php"><?php echo returnIntLang('menu design media'); ?></a>
			</li>
			<?php if ($isextended==1) { ?>
				<li class="level1" id="m_<?php echo $mp; ?>_8">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/fontmanagement.php"><?php echo returnIntLang('menu design fonts'); ?></a>
				</li>
			<?php } ?>
			<?php } ?>
			<li class="level1" id="m_<?php echo $mp; ?>_1">
				<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/designedit.php"><?php echo returnIntLang('menu design css'); ?></a>
			</li>
			<?php if ($isextended==1): ?>
				<li class="level1" id="m_<?php echo $mp; ?>_7">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/scriptedit.php"><?php echo returnIntLang('menu design js'); ?></a>
				</li>
			<?php endif; ?>
			<li class="level1" id="m_<?php echo $mp; ?>_2">
				<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/menutemplate.php"><?php echo returnIntLang('menu design menutmp'); ?></a>
			</li>
			<li class="level1" id="m_<?php echo $mp; ?>_3">
				<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/selfvarsedit.php"><?php echo returnIntLang('menu design selfvars'); ?></a>
			</li>
			<li class="level1" id="m_<?php echo $mp; ?>_4">
				<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/templates.php"><?php echo returnIntLang('menu design templates'); ?></a>
			</li>
			</ul>
			</div>
		<?php endif; ?>
		
		<?php $mp = 6; if ((array_key_exists('wspvars', $_SESSION) && array_key_exists('rights', $_SESSION['wspvars']) && array_key_exists('imagesfolder', $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights']['imagesfolder']!="0") || (array_key_exists('wspvars', $_SESSION) && array_key_exists('rights', $_SESSION['wspvars']) && array_key_exists('downloadfolder', $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights']['downloadfolder']!="0") || (array_key_exists('wspvars', $_SESSION) && array_key_exists('rights', $_SESSION['wspvars']) && array_key_exists('flashfolder', $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights']['flashfolder']!="0")): 
		
		if ($fsaccess) {
			?>
			<div class="col-auto px-1 <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "active";?>" id="m_6">
				<button type="button" class="btn btn-sm <?php echo ((($_SESSION['wspvars']['mgroup'] ?? 0)==$mp) ? "btn-primary" : ""); ?> dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
				<?php echo returnIntLang('menu files'); ?>
				</button>
				<ul class="dropdown-menu">
					<?php if (($_SESSION['wspvars']['rights']['imagesfolder'] ?? 0)!="0") { ?>
					<li class="level1" id="m_6_1">
						<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/imagemanagement.php"><?php echo returnIntLang('menu files images'); ?></a>
					</li>
					<?php } ?>
					<?php if (($_SESSION['wspvars']['rights']['downloadfolder'] ?? 0)!="0") { ?>
					<li class="level1" id="m_6_2">
						<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/documentmanagement.php"><?php echo returnIntLang('menu files docs'); ?></a>
					</li>
					<?php } ?>
					<?php if (($_SESSION['wspvars']['rights']['videofolder'] ?? 0)!="0") { ?>
					<li class="level1" id="m_6_3">
						<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/videomanagement.php"><?php echo returnIntLang('menu files video'); ?></a>
					</li>
					<?php } ?>
				</ul>
			</div>
		<?php 
		}
		endif;
		
		if ($standardtemp>0): /* allow structure/contents only with defined standard template */ ?>
			<?php $mp = 5; if ((array_key_exists('wspvars', $_SESSION) && array_key_exists('rights', $_SESSION['wspvars']) && array_key_exists('sitestructure', $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights']['sitestructure']!="0") || (array_key_exists('wspvars', $_SESSION) && array_key_exists('rights', $_SESSION['wspvars']) && array_key_exists('contents', $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights']['contents']!="0")): ?>
				<div class="col-auto px-1" id="m_<?php echo $mp; ?>">
					<button type="button" class="btn btn-sm <?php echo "btn-" . ((($_SESSION['wspvars']['mgroup'] ?? 0)==$mp) ? "primary" : "secondary"); ?> dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
					<?php echo returnIntLang('menu content'); ?>
					</button>
			
				<ul class="dropdown-menu">
					<?php if ($_SESSION['wspvars']['rights']['sitestructure']!=0): ?>
						<li class="level1" id="m_<?php echo $mp; ?>_0">
							<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/menuedit.php"><?php echo returnIntLang('menu content structure'); ?></a>
						</li>
					<?php endif; ?>
					<?php if ($_SESSION['wspvars']['rights']['contents']!=0): ?>
						<li class="level1" id="m_<?php echo $mp; ?>_1">
							<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/contentstructure.php"><?php echo returnIntLang('menu content contents'); ?></a>
						</li>
					<?php endif; ?>
					<?php if ($isextended==1): ?>
						<?php $worklang = unserialize($_SESSION['wspvars']['sitelanguages']); if ($_SESSION['wspvars']['usertype']==1): ?>
							<li class="level1" id="m_<?php echo $mp; ?>_4">
								<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/languagetools.php"><?php echo returnIntLang('menu content localize'); ?></a>
							</li>
						<?php endif; ?>
					<?php endif; ?>
					<?php if ($_SESSION['wspvars']['rights']['contents']==1): ?>
						<li class="level1" id="m_<?php echo $mp; ?>_2">
							<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/globalcontent.php"><?php echo returnIntLang('menu content global'); ?></a>
						</li>
					<?php endif; ?>
				</ul>
						</div>
			<?php endif; ?>
		<?php endif;

		$mp = 7; 
		if ((array_key_exists('wspvars', $_SESSION) && array_key_exists('usertype', $_SESSION['wspvars']) && $_SESSION['wspvars']['usertype']==1) || (array_key_exists('wspvars', $_SESSION) && array_key_exists('rights', $_SESSION['wspvars']) && array_key_exists('publisher', $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights']['publisher']!=0 && $_SESSION['wspvars']['rights']['publisher']<100)):
			if ($standardtemp>0): // allow preview/publisher only with defined standard template
				
				$queue_sql = "SELECT `id` FROM `wspqueue` WHERE `done` = 0 GROUP BY `param`, `id`";
				$queue_res = doSQL($queue_sql);
				$queue_num = $queue_res['num'];
				
				if ($isextended==1) {
				// show publisher and queue link as submenupoints
				if ($fsaccess) { ?>
					<div class="col-auto px-1" id="m_<?php echo $mp; ?>">
						<button type="button" class="btn btn-sm <?php echo ((($_SESSION['wspvars']['mgroup'] ?? 0)==$mp) ? "btn-primary" : ""); ?> dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
							<?php 
							
							echo returnIntLang('menu changed publisher'); 
							if($queue_num>0) { 
								echo " <span class='badge bg-danger'>".$queue_num."</span>"; 
							} 
							
							?>
						</button>
						<ul class="dropdown-menu">
							<li id="m_<?php echo $mp; ?>_0">
								<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/publisher.php" class="dropdown-item"><?php echo returnIntLang('menu changed'); ?></a>
							</li>
							<li id="m_<?php echo $mp; ?>_1">
								<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/publishqueue.php" class="dropdown-item"><?php echo returnIntLang('menu changed queue'); if($queue_num>0) { 
									echo " <span class='badge bg-danger'>".$queue_num."</span>"; 
								} ?></a>
							</li>
						</ul>
					</div>
				<?php 
				}
			}
				else { 
					if ($fsaccess) {
					// show only publisher as main menupoint
					?>
					<div class="col-auto px-1" id="m_<?php echo $mp; ?>">
						<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/publisher.php" class="btn btn-sm <?php echo ((($_SESSION['wspvars']['mgroup'] ?? 0)==$mp) ? "btn-primary" : ""); ?>"><?php echo returnIntLang('menu changed'); ?></a>
					</div>
					<?php 
					}
				} ?>
		<?php endif; ?>
		<?php elseif ($_SESSION['wspvars']['rights']['contents']!=0): ?>
			<div class="col-auto px-1" id="m_<?php echo $mp; ?>">
				<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/publisher.php"><?php echo returnIntLang('menu changed preview'); ?></a>
			</div>
		<?php endif; ?>
		
		<?php $mp = 10; if (array_key_exists('wspvars', $_SESSION) && array_key_exists('usertype', $_SESSION['wspvars']) && $_SESSION['wspvars']['usertype']==1): ?>
			<div class="col-auto px-1" id="m_<?php echo $mp; ?>">
				<button type="button" class="btn btn-sm <?php echo ((($_SESSION['wspvars']['mgroup'] ?? 0)==$mp) ? "btn-primary" : ""); ?> dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
					<?php echo returnIntLang('menu manage'); ?>
				</button>
				<ul class="dropdown-menu">
					<?php if (is_array($_SESSION['wspvars']['locallanguages']) && count($_SESSION['wspvars']['locallanguages'])>1): 
					
					ksort($_SESSION['wspvars']['locallanguages'], SORT_STRING);
					
					?>
					<li><span class="dropdown-item disabled"><?php echo returnIntLang('menu manage language'); ?></span></li>
					<li><hr class="dropdown-divider"></li>
					<?php foreach($_SESSION['wspvars']['locallanguages'] AS $llkey => $llvalue): 
						echo "<li class='ml-2'><a class='dropdown-item' href=\"".$_SERVER['PHP_SELF']."?setlang=".$llkey."\">".$llvalue."</a></li>";
					endforeach; ?>
					<li><hr class="dropdown-divider"></li>
				<?php endif; ?>
			<li class="level1" id="m_<?php echo $mp; ?>_0">
				<a class='dropdown-item' href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/editorprefs.php"><?php echo returnIntLang('menu manage editor'); ?></a>
			</li>
			<?php if ($isextended==1): ?>
				<li id="m_<?php echo $mp; ?>_4">
					<a class='dropdown-item' href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/dev.php"><?php echo returnIntLang('menu manage developer'); ?></a>
				</li>
				<?php if ($fsaccess) { ?>
				<li id="m_<?php echo $mp; ?>_4">
					<a class='dropdown-item' href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/cleanup.php"><?php echo returnIntLang('menu manage cleanup'); ?></a>
				</li>
                <li id="m_<?php echo $mp; ?>_6">
                    <a class='dropdown-item' href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/trash.php"><?php echo returnIntLang('menu content trash'); ?></a>
                </li>
				<?php } ?>
			<?php endif; ?>
			<li id="m_<?php echo $mp; ?>_2">
				<a class='dropdown-item' href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/modules.php"><?php echo returnIntLang('menu manage modules'); ?></a>
			</li>
			<?php if (is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/system.php")): ?>
				<li id="m_<?php echo $mp; ?>_3">
					<a class='dropdown-item' href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/system.php"><?php echo returnIntLang('menu manage system'); if (isset($_SESSION['wspvars']['updatesystem']) && $_SESSION['wspvars']['updatesystem']===true): echo " &nbsp;<span class='bubblemessage orange'>!</span>"; endif; ?></a>
				</li>
			<?php endif; ?>
			</ul></div>
		<?php else: ?>
			<?php if (is_array($_SESSION['wspvars']['locallanguages']) && count($_SESSION['wspvars']['locallanguages'])>1): 
			
			ksort($_SESSION['wspvars']['locallanguages'], SORT_STRING);
			
			?>
			<div class="col-auto px-1">
				<button type="button" class="btn btn-sm <?php echo ((($_SESSION['wspvars']['mgroup'] ?? 0)==$mp) ? "btn-primary" : ""); ?> dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
					<?php echo returnIntLang('menu manage language'); ?>
				</button>
				<ul class="dropdown-menu" id="m_lang">
					<?php foreach($_SESSION['wspvars']['locallanguages'] AS $llkey => $llvalue): 
						echo "<li><a class='dropdown-item' href=\"".$_SERVER['PHP_SELF']."?setlang=".$llkey."\">".$llvalue."</a></li>";
					endforeach; ?>
				</ul>
			</div>
			<?php endif; ?>	
		<?php endif; ?>
		<?php
		
		$_SESSION['wspvars']['wspmodmenu'] = array();
		$_SESSION['wspvars']['wspmodmenucount'] = 0;
		if (array_key_exists('rights', $_SESSION['wspvars'])):
			buildWSPMenu (0, 0, $_SESSION['wspvars']['rights']);
		endif;
		
		$mrun = 0;
		foreach ($_SESSION['wspvars']['wspmodmenu'] AS $key => $value):
			$showmodmenu[$mrun]['guid'] = $key;
			$showmodmenu[$mrun]['level'] = $value[0];
			$showmodmenu[$mrun]['id'] = $value[1];
			$showmodmenu[$mrun]['parent_id'] = $value[2];
			$showmodmenu[$mrun]['title'] = $value[3];
			$showmodmenu[$mrun]['link'] = $value[4];
			$mrun++;
		endforeach;
		
		if ($_SESSION['wspvars']['wspmodmenucount'] > 0):
			echo "<div class='col-auto px-1'>";
			if ($_SESSION['wspvars']['wspmodmenucount']==1) {
				echo "<a href=\"/".$_SESSION['wspvars']['wspbasedir']."/modgoto.php?modid=".$showmodmenu[0]['id']."\" title=\"".$showmodmenu[0]['title']."\">".$showmodmenu[0]['title']."</a>";
			} else {
				echo '<button type="button" class="btn btn-sm ' . ((($_SESSION['wspvars']['mgroup'] ?? 0)==$mp) ? "btn-primary" : "") . ' dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">' . returnIntLang('menu modmenu') . '</button>';
			}
			echo "<ul class=\"dropdown-menu\">";
			$wmstart = (($_SESSION['wspvars']['wspmodmenucount']>1) ? 0 : 1);
		
			for ($wmrun=$wmstart; $wmrun<$mrun; $wmrun++):
				$buf = "";
				if ($showmodmenu[$wmrun]['level']==0):
					$buf.= "<li id=\"m_".$showmodmenu[$wmrun]['id']."\" >"; //onmouseover=\"document.getElementById('sub_".$showmodmenu[$wmrun]['id']."').style.display = 'block';\" onmouseout=\"document.getElementById('submod_".$showmodmenu[$wmrun]['id']."').style.display = 'none';\"
					if ($wmstart==0 && key_exists(($wmrun+1), $showmodmenu) && $showmodmenu[($wmrun+1)]['level']>0):
						$buf.= "\n<a class='dropdown-item'>".$showmodmenu[$wmrun]['title']." ...</a>";
						$buf.= "<ul id=\"submod_".$showmodmenu[$wmrun]['id']."\" class=\"dropdown-menu\">"; //class=\"level2\"
						while (isset($showmodmenu[intval($wmrun+1)]['level']) && $showmodmenu[intval($wmrun+1)]['level']>0):
							$wmrun++;
							$buf.= "<li style=\"\">\n<a href=\"/".$_SESSION['wspvars']['wspbasedir']."/modgoto.php?modid=".$showmodmenu[$wmrun]['id']."\" title=\"".$showmodmenu[$wmrun]['title']."\">".$showmodmenu[$wmrun]['title']."</a></li>";
						endwhile;
						$buf.= "</ul>";
					else:
						$buf.= "\n<a href=\"/".$_SESSION['wspvars']['wspbasedir']."/modgoto.php?modid=".$showmodmenu[$wmrun]['id']."\" title=\"".$showmodmenu[$wmrun]['title']."\">".$showmodmenu[$wmrun]['title']."</a>";
					endif;
					$buf.= "</li>";
				elseif ($wmstart==1 && $showmodmenu[$wmrun]['level']>0):
					$buf.= "<li id=\"m_".$showmodmenu[$wmrun]['id']."\">";
					$buf.= "\n<a href=\"/".$_SESSION['wspvars']['wspbasedir']."/modgoto.php?modid=".$showmodmenu[$wmrun]['id']."\" title=\"".$showmodmenu[$wmrun]['title']."\">".$showmodmenu[$wmrun]['title']."</a>";
					$buf.= "</li>";
				endif;
				echo $buf;
			endfor;
		endif;
		
		if ($_SESSION['wspvars']['wspmodmenucount']>0):
			echo "</ul></div>";
		endif;
			
		?>
		<div class="col-auto px-1">
			<a class="btn btn-sm btn-danger" href="/<?php echo $_SESSION['wspvars']['wspbasedir'] . '/logout.php' ?>">Logout</a>
		</div>
		<div class="col">
			<button class="btn btn-sm disabled" id="cntdwn"></button>
			<script>
			
			TargetDate = "<?php echo date("m/d/Y h:i:s A", time()+(60*intval($_SESSION['wspvars']['autologout']))-1); ?>";
			CountActive = true;
			CountStepper = -1;
			LeadingZero = true;
			DisplayFormat = "%%H%%:%%M%%:%%S%%";
			
			function calcage(secs, num1, num2) {
			s = ((Math.floor(secs/num1))%num2).toString();
			if (LeadingZero && s.length < 2)
				s = "0" + s;
			return "<b>" + s + "</b>";
			}
			
			function CountBack(secs) {
			if (secs < 0) {
				window.location.href = '/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/logout.php';
				return;
				}
			DisplayStr = DisplayFormat.replace(/%%D%%/g, calcage(secs,86400,100000));
			DisplayStr = DisplayStr.replace(/%%H%%/g, calcage(secs,3600,24));
			DisplayStr = DisplayStr.replace(/%%M%%/g, calcage(secs,60,60));
			DisplayStr = DisplayStr.replace(/%%S%%/g, calcage(secs,1,60));
			
			document.getElementById("cntdwn").innerHTML = DisplayStr;
			setTimeout("CountBack(" + (secs+CountStepper) + ")", SetTimeOutPeriod);
			}
			
			CountStepper = Math.ceil(CountStepper);
			if (CountStepper == 0)
			CountActive = false;
			var SetTimeOutPeriod = (Math.abs(CountStepper)-1)*1000 + 990;
			var dthen = new Date(TargetDate);
			var dnow = new Date();
			if(CountStepper>0)
			ddiff = new Date(dnow-dthen);
			else
			ddiff = new Date(dthen-dnow);
			gsecs = Math.floor(ddiff.valueOf()/1000);
			CountBack(gsecs);

			</script>
		</div>
	</div>
</nav>
<?php

$msgcleanup = "DELETE FROM `wspmsg` WHERE `read` = 1";
doSQL($msgcleanup);

?>
<div id="topspacer">&nbsp;</div>
<div id="msgbar"></div>
<div id="infoholder">
	<div id="locationholder"><?php if (key_exists('location', $_SESSION['wspvars']) && $_SESSION['wspvars']['location']!='') echo $_SESSION['wspvars']['location']; $_SESSION['wspvars']['location']=''; ?></div>
	<div id="noticemsg" style="display: none;"></div>
	<div id="errormsg" style="display: none;"></div>
	<div id="resultmsg" style="display: none;"></div>
</div>
<?php

if (isset($_SESSION['wspvars']['devstat']) && $_SESSION['wspvars']['devstat']===true) {
    $mgu = memory_get_usage(); $size = array('B','KB','MB','GB'); $m = 0;
    while ($mgu>1024) { $mgu = $mgu/1024; $m++; }
    echo "<p style='padding: 0px 1%; color: darkgreen;'>RAM ".round($mgu, 2)." ".$size[$m]."</p>";
}
