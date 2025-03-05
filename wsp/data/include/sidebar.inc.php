<?php
/**
 * aufbau des dynamischen menues fuer modulare menueeintraege
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-11-01
 */

$standardtemp = getWSPProperties('templates_id');
$isextended = getWSPProperties('extendedmenu');

?>
<!-- LEFT SIDEBAR -->
<div id="sidebar-nav" class="sidebar">
    <div class="sidebar-scroll">
        <nav>
            <ul class="nav" id="sidebar-nav-menu">
                <li class="menu-group">WSP</li>
                <?php $mp = 1; ?><li class="panel">
                    <a href="#dashboards" data-toggle="collapse" data-parent="#sidebar-nav-menu" class="<?php echo ($_SESSION['wspvars']['mgroup']==$mp) ? "active" : "collapsed"; ?>"><i class="far fa-tachometer-alt"></i> <span class="title"><?php echo returnIntLang('menu home'); ?></span> <i class="icon-submenu far fa-caret-left"></i></a>
                    <div id="dashboards" class="<?php echo ($_SESSION['wspvars']['mgroup']==$mp) ? "collapse in" : "collapse"; ?>">
                        <ul class="submenu">
                            <li><a href="/<?php echo WSP_DIR; ?>/index.php" <?php echo (isset($_SESSION['wspvars']['mgroup']) && isset($_SESSION['wspvars']['mpos']) && $_SESSION['wspvars']['mgroup']==$mp && $_SESSION['wspvars']['mpos']==1) ? "class='active'" : ''; ?>><?php echo returnIntLang('menu home cms'); ?></a></li>
                            <?php if (isset($_SESSION['wspvars']['liveurl']) && isset($_SESSION['wspvars']['workspaceurl']) && trim($_SESSION['wspvars']['liveurl'])!='' && trim($_SESSION['wspvars']['workspaceurl'])!='' && $_SESSION['wspvars']['liveurl']==$_SESSION['wspvars']['workspaceurl']) { ?>
                                <li><a href="//<?php echo $_SESSION['wspvars']['liveurl']; ?>" target="_blank"><?php echo returnIntLang('menu home'); ?> <?php echo returnIntLang('menu home website'); ?></a></li>
                            <?php } else { 
                                if (isset($_SESSION['wspvars']['liveurl']) && trim($_SESSION['wspvars']['liveurl'])!='') {
                                    ?><li><a href="//<?php echo $_SESSION['wspvars']['liveurl']; ?>" target="_blank"><?php echo returnIntLang('menu home website'); ?> (LIVE)</a></li><?php
                                }
                                if (isset($_SESSION['wspvars']['workspaceurl']) && trim($_SESSION['wspvars']['workspaceurl'])!='') {
                                    ?><li><a href="//<?php echo $_SESSION['wspvars']['workspaceurl']; ?>" target="_blank"><?php echo returnIntLang('menu home website'); ?> (DEV)</a></li><?php
                                }
                            } ?>
                        </ul>
                    </div>
                </li>
                <?php $mp = 3; if ($_SESSION['wspvars']['usertype']==1 || (isset($_SESSION['wspvars']['rights']) && isset($_SESSION['wspvars']['rights']['siteprops']) && $_SESSION['wspvars']['rights']['siteprops']!=0)): ?><li class="panel">
                    <a href="#siteprefs" data-toggle="collapse" data-parent="#sidebar-nav-menu" class="<?php echo ($_SESSION['wspvars']['mgroup']==$mp) ? "active" : "collapsed"; ?>"><i class="fa fa-wrench"></i> <span class="title"><?php echo returnIntLang('menu siteprefs'); ?></span> <i class="icon-submenu far fa-caret-left"></i></a>
                    <div id="siteprefs" class="<?php echo ($_SESSION['wspvars']['mgroup']==$mp) ? "collapse in" : "collapse"; ?>">
                        <ul class="submenu">
                            <?php if ($isextended==1): ?>
                                <li><a href="/<?php echo WSP_DIR; ?>/siteprefs.php" <?php echo ($_SESSION['wspvars']['mgroup']==$mp && $_SESSION['wspvars']['mpos']==1) ? "class='active'" : ''; ?>><?php echo returnIntLang('menu siteprefs generell'); ?></a></li>
                                <?php /* <li><a href="/<?php echo WSP_DIR; ?>/sites.php" <?php echo ($_SESSION['wspvars']['mgroup']==$mp && $_SESSION['wspvars']['mpos']==2) ? "class='active'" : ''; ?>><?php echo returnIntLang('menu siteprefs sites'); ?></a></li> */ ?>
                                <li><a href="/<?php echo WSP_DIR; ?>/headerprefs.php" <?php echo ($_SESSION['wspvars']['mgroup']==$mp && $_SESSION['wspvars']['mpos']==3) ? "class='active'" : ''; ?>><?php echo returnIntLang('menu siteprefs redirects'); ?></a></li>
                            <?php endif; ?>
                            <li><a href="/<?php echo WSP_DIR; ?>/semanagement.php" <?php echo ($_SESSION['wspvars']['mgroup']==$mp && $_SESSION['wspvars']['mpos']==4) ? "class='active'" : ''; ?>><?php echo returnIntLang('menu siteprefs seo'); ?></a></li>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>
                
                <?php $mp = 4; if ($_SESSION['wspvars']['usertype']==1 || (array_key_exists('wspvars', $_SESSION) && array_key_exists('rights', $_SESSION['wspvars']) && array_key_exists('design', $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights']['design']!=0)): ?>
                <li class="panel">
                    <a href="#design" data-toggle="collapse" data-parent="#sidebar-nav-menu" class="<?php echo ($_SESSION['wspvars']['mgroup']==$mp) ? "active" : "collapsed"; ?>"><i class="fa fa-paint-brush"></i> <span class="title"><?php echo returnIntLang('menu design'); ?></span> <i class="icon-submenu far fa-caret-left"></i></a>
                    <div id="design" class="<?php echo ($_SESSION['wspvars']['mgroup']==$mp) ? "collapse in" : "collapse"; ?>">
                        <ul class="submenu">
                            <?php if (isset($_SESSION['wspvars']['useftp']) && $_SESSION['wspvars']['useftp']===false): echo "<!-- no ftp -->"; else: ?>
                                <li><a href="/<?php echo WSP_DIR; ?>/screenmanagement.php"><?php echo returnIntLang('menu design media'); ?></a></li>
                            <?php endif; ?>
                            <?php if (isset($_SESSION['wspvars']['useftp']) && $_SESSION['wspvars']['useftp']===false): echo "<!-- no ftp -->"; elseif ($isextended==1): ?>
                                <li><a href="/<?php echo WSP_DIR; ?>/fontmanagement.php"><?php echo returnIntLang('menu design fonts'); ?></a></li>
                            <?php endif; ?>
                            <li><a href="/<?php echo WSP_DIR; ?>/cssedit.php"><?php echo returnIntLang('menu design css'); ?></a></li>
                            <?php if ($isextended==1): ?>
                                <li><a href="/<?php echo WSP_DIR; ?>/scriptedit.php"><?php echo returnIntLang('menu design js'); ?></a></li>
                            <?php endif; ?>
                            <li><a href="/<?php echo WSP_DIR; ?>/menutemplates.php"><?php echo returnIntLang('menu design menutmp'); ?></a></li>
                            <li><a href="/<?php echo WSP_DIR; ?>/selfvars.php"><?php echo returnIntLang('menu design selfvars'); ?></a></li>
                            <li><a href="/<?php echo WSP_DIR; ?>/templates.php"><?php echo returnIntLang('menu design templates'); ?></a></li>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>
                
                <?php $mp = 6; if ($_SESSION['wspvars']['usertype']==1 || ((array_key_exists('wspvars', $_SESSION) && array_key_exists('rights', $_SESSION['wspvars']) && array_key_exists('imagesfolder', $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights']['imagesfolder']!="") || (array_key_exists('wspvars', $_SESSION) && array_key_exists('rights', $_SESSION['wspvars']) && array_key_exists('downloadfolder', $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights']['downloadfolder']!="") || (array_key_exists('wspvars', $_SESSION) && array_key_exists('rights', $_SESSION['wspvars']) && array_key_exists('mediafolder', $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights']['mediafolder']!=""))): 
                    if (isset($_SESSION['wspvars']['useftp']) && $_SESSION['wspvars']['useftp']===false): echo "<!-- no ftp -->"; else: ?>
                        <li class="panel">
                            <a href="#filesystem" data-toggle="collapse" data-parent="#sidebar-nav-menu" class="<?php echo ($_SESSION['wspvars']['mgroup']==$mp) ? "active" : "collapsed"; ?>"><i class="far fa-copy"></i> <span class="title"><?php echo returnIntLang('menu files'); ?></span> <i class="icon-submenu far fa-caret-left"></i></a>
                            <div id="filesystem" class="<?php echo ($_SESSION['wspvars']['mgroup']==$mp) ? "collapse in" : "collapse"; ?>">
                                <ul class="submenu">
                                    <?php if ($_SESSION['wspvars']['usertype']==1 || $_SESSION['wspvars']['rights']['imagesfolder']!=""): ?>
                                        <li><a href="/<?php echo WSP_DIR; ?>/imagemanagement.php"><?php echo returnIntLang('menu files images'); ?></a></li>
                                    <?php endif; ?>
                                    <?php if ($_SESSION['wspvars']['usertype']==1 || $_SESSION['wspvars']['rights']['downloadfolder']!=""): ?>
                                        <li><a href="/<?php echo WSP_DIR; ?>/documentmanagement.php"><?php echo returnIntLang('menu files docs'); ?></a></li>
                                    <?php endif; ?>
                                    <?php if ($_SESSION['wspvars']['usertype']==1 || $_SESSION['wspvars']['rights']['mediafolder']!=""): ?>
                                        <li><a href="/<?php echo WSP_DIR; ?>/mediamanagement.php"><?php echo returnIntLang('menu files multimedia'); ?></a></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($standardtemp>0): /* allow structure/contents only with defined standard template */ ?>
                    <?php $mp = 5; if ($_SESSION['wspvars']['usertype']==1 || ((array_key_exists('wspvars', $_SESSION) && array_key_exists('rights', $_SESSION['wspvars']) && array_key_exists('sitestructure', $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights']['sitestructure']!="0") || (array_key_exists('wspvars', $_SESSION) && array_key_exists('rights', $_SESSION['wspvars']) && array_key_exists('contents', $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights']['contents']!="0") || (array_key_exists('wspvars', $_SESSION) && array_key_exists('rights', $_SESSION['wspvars']) && array_key_exists('rss', $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights']['rss']!="0"))): ?>
                        <li class="panel">
                            <a href="#contents" data-toggle="collapse" data-parent="#sidebar-nav-menu" class="<?php echo ($_SESSION['wspvars']['mgroup']==$mp) ? "active" : "collapsed"; ?>"><i class="fa fa-sitemap"></i> <span class="title"><?php echo returnIntLang('menu content'); ?></span> <i class="icon-submenu far fa-caret-left"></i></a>
                            <div id="contents" class="<?php echo ($_SESSION['wspvars']['mgroup']==$mp) ? "collapse in" : "collapse"; ?>">
                                <ul class="submenu">
                                    <?php if ($_SESSION['wspvars']['usertype']==1 || $_SESSION['wspvars']['rights']['sitestructure']!=0): ?>
                                        <li><a href="/<?php echo WSP_DIR; ?>/structure.php"><?php echo returnIntLang('menu content structure'); ?></a></li>
                                    <?php endif; ?>
                                    <?php if ($_SESSION['wspvars']['usertype']==1 || $_SESSION['wspvars']['rights']['contents']!=0): ?>
                                        <li><a href="/<?php echo WSP_DIR; ?>/contents.php"><?php echo returnIntLang('menu content contents'); ?></a></li>
                                    <?php endif; ?>
                                    <?php if (isset($_SESSION['wspvars']['useftp']) && $_SESSION['wspvars']['useftp']===false): echo "<!-- no ftp -->"; else: ?>
                                        <?php if ($isextended==1): ?>
                                            <?php if ($_SESSION['wspvars']['usertype']==1): ?>
                                                <li><a href="/<?php echo WSP_DIR; ?>/languagetools.php"><?php echo returnIntLang('menu content localize'); ?></a></li>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ($_SESSION['wspvars']['usertype']==1 || $_SESSION['wspvars']['rights']['contents']==1): ?>
                                        <li><a href="/<?php echo WSP_DIR; ?>/globalcontents.php"><?php echo returnIntLang('menu content global'); ?></a></li>
                                    <?php endif; ?>
                                    <?php if (isset($_SESSION['wspvars']['useftp']) && $_SESSION['wspvars']['useftp']===false): echo "<!-- no ftp -->"; elseif ($isextended==1): ?>
                                        <?php /* if ($_SESSION['wspvars']['usertype']==1 || $_SESSION['wspvars']['rights']['rss']!=0): ?>
                                            <li><a href="/<?php echo WSP_DIR; ?>/rss.php"><?php echo returnIntLang('menu content rss'); ?></a></li>
                                        <?php endif; */ ?>
                                        <?php
                                        // show trash menupoint only if trash is not empty
                                        $tm_sql = "SELECT `mid` FROM `menu` WHERE `trash` = 1 AND `description` NOT LIKE '%autofile-%'";
                                        $tc_sql = "SELECT `cid` FROM `content` WHERE `trash` = 1";
                                        ?>
                                        <li id='sidebar-trash-menu' <?php if ((getNumSQL($tm_sql)+getNumSQL($tc_sql))<=0) { echo ' style="display: none;" '; } ?>><a href="/<?php echo WSP_DIR; ?>/trash.php"><?php echo returnIntLang('menu content trash'); ?></a></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php $mp = 7; if ((array_key_exists('wspvars', $_SESSION) && array_key_exists('usertype', $_SESSION['wspvars']) && $_SESSION['wspvars']['usertype']==1) || (array_key_exists('wspvars', $_SESSION) && array_key_exists('rights', $_SESSION['wspvars']) && array_key_exists('publisher', $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights']['publisher']!=0 && $_SESSION['wspvars']['rights']['publisher']<100)): if ($standardtemp>0): // allow preview/publisher only with defined standard template ?>
                    <?php 
                    
                    $queue_num = getWSPqueue();

                    ?>
                    <?php if ($isextended==1): ?>
                        <li class="panel">
                            <a href="#publisher" data-toggle="collapse" data-parent="#sidebar-nav-menu" class="<?php echo ($_SESSION['wspvars']['mgroup']==$mp) ? "active" : "collapsed"; ?>"><i class="fa fa-globe"></i> <span class="title"><?php echo returnIntLang('menu changed publisher'); ?></span> <?php echo " <span class='badge inline-badge queue-num-badge' ".(($queue_num>0)?'':' style="display: none;" ').">".$queue_num."</span>"; ?> <i class="icon-submenu far fa-caret-left"></i></a>
                            <div id="publisher" class="<?php echo ($_SESSION['wspvars']['mgroup']==$mp) ? "collapse in" : "collapse"; ?>">
                                <ul class="submenu">
                                    <li><a href="/<?php echo WSP_DIR; ?>/publisher.php"><?php echo returnIntLang('menu changed'); ?></a></li>
                                    <li><a href="/<?php echo WSP_DIR; ?>/publishqueue.php"><?php echo returnIntLang('menu changed queue'); ?> <?php echo " <span class='badge inline-badge queue-num-badge' ".(($queue_num>0)?'':' style="display: none;" ').">".$queue_num."</span>"; ?></a></li>
                                </ul>
                            </div>
                        </li>
                    <?php else: ?>
                        <?php if (isset($_SESSION['wspvars']['useftp']) && $_SESSION['wspvars']['useftp']===false): echo "<!-- no ftp -->"; else: ?>
                            <li><a href="/<?php echo WSP_DIR; ?>/publisher.php"><i class="fa fa-globe"></i> <span class="title"><?php echo returnIntLang('menu changed'); ?></span></a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php elseif (isset($_SESSION['wspvars']['rights']['contents']) && $_SESSION['wspvars']['rights']['contents']!=0): ?>
                    <?php if (isset($_SESSION['wspvars']['useftp']) && $_SESSION['wspvars']['useftp']===false): echo "<!-- no ftp -->"; else: ?>
                        <li><a href="/<?php echo WSP_DIR; ?>/publisher.php"><i class="fas fa-globe"></i> <span class="title"><?php echo returnIntLang('menu changed preview'); ?></span></a></li>
                    <?php endif; ?>
                <?php endif; endif; ?>
                
                <?php $mp = 10; if (array_key_exists('wspvars', $_SESSION) && array_key_exists('usertype', $_SESSION['wspvars']) && $_SESSION['wspvars']['usertype']==1): ?>
                    <li class="panel">
                        <a href="#preferences" data-toggle="collapse" data-parent="#sidebar-nav-menu" class="<?php echo ($_SESSION['wspvars']['mgroup']==$mp) ? "active" : "collapsed"; ?>"><i class="fas fa-cogs"></i> <span class="title"><?php echo returnIntLang('menu manage'); ?></span> <?php if (isset($_SESSION['wspvars']['updatesystem']) && $_SESSION['wspvars']['updatesystem']===true): echo " <i class='fa fa-exclamation-circle' style='font-size: 16px;'></i>"; endif; ?> <i class="icon-submenu far fa-caret-left"></i></a>
                        <div id="preferences" class="<?php echo ($_SESSION['wspvars']['mgroup']==$mp) ? "collapse in" : "collapse"; ?>">
                            <ul class="submenu">
                                <li><a href="/<?php echo WSP_DIR; ?>/usermanagement.php"><?php echo returnIntLang('menu user manage'); ?></a></li>
                                <li><a href="/<?php echo WSP_DIR; ?>/editorprefs.php"><?php echo returnIntLang('menu manage editor'); ?></a></li>
                                <?php if ($isextended==1): ?>
                                    <li><a href="/<?php echo WSP_DIR; ?>/dev.php"><?php echo returnIntLang('menu manage developer'); ?></a></li>
                                    <?php if (isset($_SESSION['wspvars']['useftp']) && $_SESSION['wspvars']['useftp']===false): echo "<!-- no ftp -->"; else: ?>
                                        <li><a href="/<?php echo WSP_DIR; ?>/cleanup.php"><?php echo returnIntLang('menu manage cleanup'); ?></a></li>
                                    <?php endif; ?>
                                    <li><a href="/<?php echo WSP_DIR; ?>/editcon.php"><?php echo returnIntLang('menu manage connections'); ?></a></li>
                                <?php endif; ?>
                                <?php if (isset($_SESSION['wspvars']['useftp']) && $_SESSION['wspvars']['useftp']===false): echo "<!-- no ftp -->"; else: ?>
                                    <li><a href="/<?php echo WSP_DIR; ?>/modules.php"><?php echo returnIntLang('menu manage modules'); ?></a></li>
                                <?php endif; ?>
                                <?php if (is_file(DOCUMENT_ROOT."/".WSP_DIR."/system.php")): ?>
                                    <li><a href="/<?php echo WSP_DIR; ?>/system.php"><?php echo returnIntLang('menu manage system'); if (isset($_SESSION['wspvars']['updatesystem']) && $_SESSION['wspvars']['updatesystem']===true): echo " <i class='fa fa-exclamation-circle' style='font-size: 16px;'></i>"; endif; ?></a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>
                <?php endif; ?>
                
                <!-- plugin based menu -->
                
                <?php
                
                if (isset($plugin_num) && $plugin_num>0):
                    for ($pres=0; $pres<$plugin_num; $pres++):
                        $pluginident = $plugin_res['set'][$pres]['guid'];
                        if ((isset($_SESSION['wspvars']) && array_key_exists('rights', $_SESSION['wspvars']) && array_key_exists($pluginident, $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights'][$pluginident]==1) || (array_key_exists('wspvars', $_SESSION) && array_key_exists('usertype', $_SESSION['wspvars']) && $_SESSION['wspvars']['usertype']==1)):
                            $pluginfolder = $plugin_res['set'][$pres]['pluginfolder'];
                            echo "<li class='menu-group'>".$pluginfolder."</li>";
        //					if (is_file(DOCUMENT_ROOT."/".WSP_DIR."/plugins/".$pluginfolder."/data/include/wsplang.inc.php")):
        //						@require (DOCUMENT_ROOT."/".WSP_DIR."/plugins/".$pluginfolder."/data/include/wsplang.inc.php");
        //					endif;
        //					if (is_file(DOCUMENT_ROOT."/".WSP_DIR."/plugins/".$pluginfolder."/data/include/wspmenu.inc.php")):
        //						@require (DOCUMENT_ROOT."/".WSP_DIR."/plugins/".$pluginfolder."/data/include/wspmenu.inc.php");
        //					endif;
                        endif;
                    endfor;
                endif;

                ?>
                
            </ul>
        </nav>
    </div>
</div>
<!-- END LEFT SIDEBAR -->