<!-- NAVBAR -->
<nav class="navbar navbar-default navbar-fixed-top">
    <?php if (isset($_SESSION['wspvars']['userid']) && intval($_SESSION['wspvars']['userid'])>0) { ?>
    <div class="brand">
        <a href="<?php echo ((isset($_SESSION['wspvars']['related']) && trim ($_SESSION['wspvars']['related']!=''))?cleanPath('./'.WSP_DIR.'/'.$_SESSION['wspvars']['related']):'./'); ?>">WebSitePreview</a>
    </div>
    <div class="container-fluid">
        <?php if(!(isset($preview)) || (isset($preview) && !($preview))): ?>
        <div id="tour-fullwidth" class="navbar-btn">
            <button type="button" class="btn-toggle-fullwidth"><i class="fa fa-bars"></i></button>
        </div>
        <?php endif; ?>
        <div id="navbar-menu">
            <ul class="nav navbar-nav navbar-right">
                <!-- projects/sites -->
                <?php
			 
                if(!(isset($preview)) || (isset($preview) && !($preview))):

                    $siteinfo_sql = "SELECT * FROM `wspproperties` WHERE `varname` LIKE 'sites_%'";
                    $siteinfo_res = doSQL($siteinfo_sql);
                    $sitesdata = array();
                    if ($siteinfo_res['num']>0) {
                        for ($sres=0; $sres<$siteinfo_res['num']; $sres++) {
                            $siteinfo = explode("_", $siteinfo_res['set'][$sres]['varname']);
                            if (count($siteinfo)==2):
                                $sitesdata[($siteinfo[0])][($siteinfo[1])]['name'] = $siteinfo_res['set'][$sres]['varvalue'];
                            elseif (count($siteinfo)==3):
                                $sitesdata[($siteinfo[0])][($siteinfo[1])][($siteinfo[2])] = $siteinfo_res['set'][$sres]['varvalue'];
                            endif;
                        }
                    }
                    /*
                    if (isset($sitesdata['sites']) && is_array($sitesdata['sites'])):
                        echo "<li class='dropdown'>";
                        ?>
                        <a href="#" id="sites-drop" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-cubes"></i> <span class="hide">Sites</span>
                        </a>
                        <ul class="dropdown-menu">
                        <?php

                        echo "<li class=''><a href='#'><i class='fa fa-circle'></i> ".returnIntLang('menu no site')."</a></li>";
                        foreach ($sitesdata['sites'] AS $sk => $sv):
                            echo "<li class=''><a href='#'><i class='fa fa-cube'></i> ";
                            echo returnIntLang('menu site str')." \"".$sv['name']."\"</a></li>";
                        endforeach;
                        echo "</ul>";
                        echo "</li>";
                    endif;
                    */
                    
                    if (is_array($_SESSION['wspvars']['locallanguages']) && count($_SESSION['wspvars']['locallanguages'])>1): ?>
                    <!-- language -->
                    <li class="dropdown">
                        <a href="#" id="lang-drop" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="fas fa-language"></i> <span class="hide"><?php echo returnIntLang('choose language'); ?></span>
                        </a>
                        <ul class="dropdown-menu">
                            <?php ksort($_SESSION['wspvars']['locallanguages'], SORT_STRING);
                            foreach($_SESSION['wspvars']['locallanguages'] AS $llkey => $llvalue): ?>
                            <li><a href="?setlang=<?php echo $llkey; ?>"><?php echo $llvalue; ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <?php endif;
                        
                    if ((isset($_SESSION['wspvars']['menuposition']) && $_SESSION['wspvars']['menuposition']=='index') && (!(isset($_SESSION['wspvars']['disablenews'])) || $_SESSION['wspvars']['disablenews']==0)) {
                        print('<!-- legend -->
                        <li class="dropdown">
                            <a id="legend-start" onclick="legend.restart();">
                                <i class="fa fa-question"></i> <span class="hide">Help</span>
                            </a>
                        </li>');
                    }

                    $showmodmenu = array();
                    if (array_key_exists('rights', $_SESSION['wspvars'])):
                        $showmodmenu = buildModMenu(0, 0, $_SESSION['wspvars']['rights']);
                    endif;

                    if ($showmodmenu!==false && is_array($showmodmenu) && count($showmodmenu)==1) {
                        // mod based menu
                        echo '<li class="dropdown">';
                        foreach ($showmodmenu AS $smmk => $smmv) {
                            $_SESSION['wspvars']['wspmodmenu'][intval($smmv[0]['id'])] = $smmv[0]['title'];
                            echo '<a href="./modgoto.php?modid='.$smmv[0]['id'].'" id="modules-drop" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-folder" title="'.$smmv[0]['title'].'"></i> <span class="hide">'.$smmv[0]['title'].'</span>
                            </a>';
                        }
                        echo '</li>';
                    } 
                    else if ($showmodmenu!==false && is_array($showmodmenu) && count($showmodmenu)>1) {
                        echo '<li class="dropdown">';
                        echo '<a href="#" id="modules-drop" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-folder"></i> <span class="hide">Modules</span></a>';
                        echo '<ul class="dropdown-menu">';
                        foreach ($showmodmenu AS $smmk => $smmv) {
                            $_SESSION['wspvars']['wspmodmenu'][intval($smmv[0]['id'])] = $smmv[0]['title'];
                            echo '<li><a href="./modgoto.php?modid='.$smmv[0]['id'].'">'.$smmv[0]['title'].'</a></li>';
                            if ($smmv[0]['sub']) {
//                                echo '<li><ul class="">';
                                foreach ($smmv[0]['sub'] AS $submmk => $submmv) {
                                    echo '<li style="padding-left: 30px;"><a href="./modgoto.php?modid='.$submmv[0]['id'].'">'.$submmv[0]['title'].'</a></li>';
                                }
//                                echo '</ul></li>';
                            }
                        }
                        echo '</ul>';
                        echo '</li>';
                    }
                
                    ?>
                    <!-- user -->
                    <li class="dropdown">
                        <a href="/<?php echo WSP_DIR ?>/usermanagement.php"><i class="fa fa-user"></i> <span class="hide"><?php echo returnIntLang('menu user managedata'); ?></span></a>
                        <ul class="dropdown-menu logged-user-menu">
                            <li><a href="/<?php echo WSP_DIR ?>/usermanagement.php"><i class="fa fa-user"></i> <span><?php echo returnIntLang('menu user managedata'); ?></span></a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- preview -->
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-share-alt"></i> <?php echo returnIntLang('share link'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><?php echo $previewlink; ?></li>
                        </ul>
                    </li>    
                <?php endif; ?>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <i class="fa fa-unlock"></i> <span class="hide">Logout/Lock</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a onclick="showReLogin()"><i class="fa fa-lock"></i> <span><?php echo returnIntLang('lock screen'); ?></span></a></li>
                        <li><a href="/<?php echo WSP_DIR; ?>/login.php?logout"><i class="fas fa-sign-out-alt"></i> <span><?php echo returnIntLang('str logout'); ?></span></a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
    <?php } else if (isset($preview) && $preview===true) { ?>
    <div class="brand">
        <a href="https://www.wsp3.de" target="_blank">Powered by <strong>WebSitePreview</strong></a>
    </div>
    <?php } ?>
</nav>
<!-- END NAVBAR -->
<?php 

if(!(isset($preview)) || (isset($preview) && !($preview))) { 
    print ('<script type="text/javascript">
    var legend = new Tour();
    </script>');
}
