<?php
/**
 * Cleanup
 * @author stefan@covi.de
 * @since 3.3
 * @version GIT
 */

// start session -----------------------------
session_start();
// base includes -----------------------------
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
// define actual system position -------------
$_SESSION['wspvars']['lockstat'] = '';
$_SESSION['wspvars']['mgroup'] = 10;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
$_SESSION['wspvars']['pagedesc'] = array('fa fa-gears',returnIntLang('menu manage'),returnIntLang('menu manage cleanup'));
// second includes ---------------------------
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
// define page specific vars -----------------
$filesizes = array('Byte','KB','MB','GB');

// define page specific funcs ----------------
if (isset($_REQUEST['cfd']) && trim($_REQUEST['cfd'])!=''): 
    // define jumper to scroll down in footer.inc
    $_SESSION['document_jumper'] = '#filesystem-jumper';
endif;

// do ftp connect to establish ONE ftp-connection
$cFTP = $tFTP = 3;
$ftp = false;
$ftpcon = false;
while (!$ftp && ($tFTP > 0)):
    if ($cFTP != $tFTP):
        $cFTP = $tFTP;
        sleep(1);
    endif;
    $ftp = ftp_connect(FTP_HOST, FTP_PORT);
    $tFTP--;
endwhile;
if ($ftp===false):
    addWSPMsg('errormsg', returnIntLang('cleanup cant connect to ftp'));
else:
    $ftplogin = @ftp_login($ftp, FTP_USER, FTP_PASS);
    if (!($ftplogin)):
        addWSPMsg('errormsg', returnIntLang('cleanup cant login to ftp'));
    else:
        // basedir to read from
        $fsc['ftpbase'] = cleanPath("/".FTP_BASE."/");
        $fsc['ftpdir'] = cleanPath("/".FTP_BASE."/");
        // if isset request to change directory, use it
        if (isset($_REQUEST['cfd']) && trim($_REQUEST['cfd'])!=''): 
            $fsc['ftpdir'] = cleanPath($fsc['ftpdir']."/".str_replace(".", "", base64_decode(trim($_REQUEST['cfd'])))."/"); 
        endif;
        // error and attack management

        /// known directory names of some systems that should not be accessible by "normal" users and will not be shown
        $fsc['sysdirs'] = array(cleanPath("/".FTP_BASE."/media/"),cleanPath("/".FTP_BASE."/data/"),cleanPath("/".FTP_BASE."/".WSP_DIR."/"),cleanPath("/".FTP_BASE."/plesk-stat/"),cleanPath("/".FTP_BASE."/cgi_bin/"),cleanPath("/".FTP_BASE."/cgi/"),cleanPath("/".FTP_BASE."/wspmod/"));

        $fsc['data'] = array();
        // setup emtpy files-array
        $fsc['files'] = array();
        // setup emtpy directory array
        $fsc['dirs'] = array();
        // read list of folder and files from given directory list
        $cd = ''; $structure = explode("/", $fsc['ftpdir']);
        foreach ($structure AS $sk => $sv):
            if ($sv!=''):
                $cd = cleanPath("/".$cd."/".$sv."/");
                $fsc['data'][urltext($sv)] = ftp_nlist($ftp, $cd);
                if (is_array($fsc['data'][urltext($sv)]) && count($fsc['data'][urltext($sv)])>0):
                    // run array to differate between files and folders (for view)
                    $fsc['files'][urltext($sv)] = array();
                    $fsc['dirs'][urltext($sv)] = array();
                    foreach ($fsc['data'][urltext($sv)] AS $fk => $fv):
                        $checkftpname = cleanPath("/".$fv);
                        $checksysname = cleanPath(DOCUMENT_ROOT."/".substr($fv, strlen(cleanPath("/".$fsc['ftpbase']."/"))));
                        if (is_file($checksysname)):
                            $filename = substr($checkftpname, strlen($cd));
                            if ($filename=='index.php' && $fsc['ftpbase']==$fsc['ftpdir']):
                                // base directory index file
                                $fusql = "SELECT * FROM `menu` WHERE `isindex` = 1 AND `trash` = 0 AND `connected` = 0";
                            elseif ($filename=='index.php' && count($structure)>1):
                                // check for upper directory as filename
                                $fusql = "SELECT * FROM `menu` WHERE `filename` LIKE '".escapeSQL(urltext($sv))."' AND `trash` = 0";
                            else:
                                // check for file as filename
                                $fusql = "SELECT * FROM `menu` WHERE `filename` LIKE '".escapeSQL(str_replace("/", "", str_replace(".php", "", $filename)))."' AND `trash` = 0";
                            endif;
                            $fileusage = (getNumSQL($fusql)>0)?true:false;
                            $filesize = filesize($checksysname);
                            if ($filesize==0): $fileusage = false; endif;
                            $fi = 0; while ($filesize>1014):
                                $filesize = ceil($filesize/1024);
                                $fi++;
                            endwhile;
                            $filemtime = filemtime($checksysname);
                            $fsc['files'][urltext($sv)][cleanPath("/".substr((FTP_BASE."/".$checkftpname), strlen(cleanPath("/".FTP_BASE."/"))))] = array(
                                'name' => $filename,
                                'action' => base64_encode(cleanPath("/".substr((FTP_BASE."/".$checkftpname), strlen(cleanPath("/".FTP_BASE."/"))))),
                                'link' => cleanPath("/".substr(cleanPath("/".substr((FTP_BASE."/".$checkftpname), strlen(cleanPath("/".FTP_BASE."/")))), strlen(cleanPath("/".FTP_BASE."/")))),
                                'usage' => $fileusage,
                                'size' => $filesize,
                                'weight' => $filesizes[$fi],
                                'time' => $filemtime,
                                );
                        elseif (is_dir(cleanPath($checksysname."/"))):
                            if (!(in_array($checkftpname."/", $fsc['sysdirs']))):
                                $subdata = count(ftp_nlist($ftp, cleanPath("/".$checkftpname."/")));
                                $fsc['dirs'][urltext($sv)][cleanPath("/".$checkftpname."/")] = array(
                                    'name' => cleanPath(substr(cleanPath("/".$checkftpname), strlen(cleanPath("/".$cd."/")))),
                                    'action' => base64_encode(cleanPath(substr(cleanPath("/".$checkftpname), strlen(cleanPath("/".FTP_BASE."/"))))),
                                    'sub' => intval($subdata),
                                    );
                            endif;
                        endif;
                    endforeach;
                    // sorting directory- and files-array
                    ksort($fsc['dirs'][urltext($sv)]);
                    ksort($fsc['files'][urltext($sv)]);
                endif;
                unset($fsc['data'][urltext($sv)]);
            endif;
        endforeach;
        
        $ftpcon = true;
        ftp_close($ftp);
    endif;
endif;

// cleanup tmp directory
if (isset($_POST['action']) && $_POST['action']=='cleanuptmp'):
	if (is_array($_POST['tmp'])):
		foreach ($_POST['tmp'] AS $dk => $dv):
			CleanupFolder($dv);
		endforeach;
	endif;
endif;

function showDirs($sDir = '', $bRecursive = true) {
	$subdirs = array();
	$d = dir(DOCUMENT_ROOT.$sDir);
	while (false !== ($entry = $d->read())):
		if ((substr($entry, 0, 1)!='.') && (is_dir(DOCUMENT_ROOT.$sDir.'/'.$entry))):
			$subdirs[] = $sDir.'/'.$entry;
			if ($bRecursive):
				$subdirs = array_merge($subdirs, showDirs($sDir.'/'.$entry, true));
			endif;
		endif;
	endwhile;
	$d->close();
	return $subdirs;
	}	// showDirs()

// head of file
include ("./data/include/header.inc.php");
include ("./data/include/navbar.inc.php");
require ("./data/include/sidebar.inc.php");
?>
<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('cleanup headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('cleanup info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
            <?php
	       
            $tempdirs = showDirs("/".WSP_DIR."/tmp", false);
            if (count($tempdirs)>1):
                foreach ($tempdirs AS $key => $value):
                    $stat = stat(DOCUMENT_ROOT."/".$value);
                    $tempdirs[$key] = str_replace("//", "/", str_replace("//", "/", $value));
                    if (intval($stat[9])>=intval(time()-1209600)):
                        $tempdirs[$key] = "";
                        unset($tempdirs[$key]);
                    elseif (strchr($value, 'previewtmp')):
                        unset($tempdirs[$key]);
                    endif;
                endforeach;
            endif;
            
            if (count($tempdirs)>1):
                ?>
                <div class="row">
                    <div class="col-lg-6">    
                        <div class="panel">
                            <div class="panel-heading">
                                <h3 class="panel-title"><?php echo returnIntLang('cleanup tempdirs'); ?></h3>
                            </div>
                            <div class="panel-body" id="cleanup_tmp_panel">
                                <p><?php echo returnIntLang('cleanup temp directories found1'); ?> <?php echo count($tempdirs); ?> <?php echo returnIntLang('cleanup temp directories found2'); ?></p>
                                <p><a onClick="cleanup_tmp()" class="btn btn-danger"><?php echo returnIntLang('cleanup tmp submit', false); ?></a></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">    
                        <div class="panel">
                            <div class="panel-heading">
                                <h3 class="panel-title"><?php echo returnIntLang('cleanup sysbackup'); ?></h3>
                            </div>
                            <div class="panel-body">
                                <p><?php echo returnIntLang('cleanup sysbackup description'); ?></p>
                                <form name="cleanup_sys" id="cleanup_sys" method="post">
                                <input type="hidden" name="action" value="cleanupsys" />
                                <p><a onClick="cleanup_sys()" class="btn btn-danger"><?php echo returnIntLang('cleanup sys submit', false); ?></a></p>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-6">    
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('refresh mediadb'); ?></h3>
                        </div>
                        <div class="panel-body" id="refresh_media_panel">
                            <p><?php echo returnIntLang('refresh mediadb description'); ?></p>
                            <p><a onClick="refresh_media()" class="btn btn-danger"><?php echo returnIntLang('refresh mediadb submit', false); ?></a></p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">    
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('cleanup database'); ?></h3>
                        </div>
                        <div class="panel-body">
                            <p><?php echo returnIntLang('cleanup database description'); ?></p>
                            <p><a onClick="cleanup_db()" class="btn btn-danger"><?php echo returnIntLang('cleanup database submit', false); ?></a></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row" id="filesystem-jumper">
                <div class="col-lg-9">    
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('cleanup filesystem'); ?> <em>beta</em></h3>
                            <p class="panel-subtitle"><?php echo returnIntLang('cleanup filesystem description'); ?></p>
                        </div>
                        <div class="panel-body">
                            <?php if ($ftpcon): ?>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="panel panel-tab">
                                        <div class="panel-heading">
                                            <ul class="nav nav-tabs pull-left">
                                                <?php
                                                
                                                $f = 0; foreach ($fsc['dirs'] AS $sk => $sv):
                                                    $f++;
                                                    if ($f==1):
                                                        echo "<li ".(($f==count($fsc['dirs']))?' class="active" ':'')."><a href='#".$sk."' data-toggle='tab'><i class='fa fa-globe'></i> ".returnIntLang('str homedir')." (".$sk.")</a></li>";
                                                    else:
                                                        echo "<li ".(($f==count($fsc['dirs']))?' class="active" ':'')."><a href='#".$sk."' data-toggle='tab'><i class='fa fa-folder'></i> ".$sk."</a></li>";
                                                    endif;
                                                endforeach;
                                                
                                                ?>
                                            </ul>
                                            <h3 class="panel-title">&nbsp;</h3>
                                        </div>
                                        <div class="panel-body" >
                                            <div class="tab-content no-padding">
                                                <?php $f = 0; foreach ($fsc['dirs'] AS $sk => $sv): $f++; ?>
                                                    <div class="tab-pane fade in <?php if ($f==count($fsc['dirs'])): echo ' active '; endif; ?>" id="<?php echo $sk; ?>">
                                                        <?php if (count($fsc['dirs'][$sk])>0): ?>
                                                        <div class="row">
                                                            <?php

                                                            foreach ($sv AS $dk => $dv):
                                                                
                                                                if ($dv['sub']==0):
                                                                    echo "<div class='col-md-4 col-sm-6 col-xs-6'><a onclick=\"alert('".$dv['action']."');\" style='margin-bottom: 3px;' class='btn btn-xs btn-danger'>".$dv['name']."</a></div>";
                                                                else:
                                                                    echo "<div class='col-md-4 col-sm-6 col-xs-6'><a href='?cfd=".$dv['action']."' style='margin-bottom: 3px;' class='btn btn-xs btn-primary'>".$dv['name']." (".$dv['sub'].")</a></div>";
                                                                endif;
                                                            endforeach;

                                                            ?>
                                                        </div>
                                                        <?php endif; ?>
                                                        <?php if (count($fsc['dirs'][$sk])>0 && count($fsc['files'][$sk])>0): echo "<hr />"; endif; ?>
                                                        <?php if (count($fsc['files'][$sk])>0): ?>
                                                        <?php

                                                        foreach ($fsc['files'][$sk] AS $dk => $dv):
                                                            echo "<div class='row";
                                                            if ($dv['usage']): echo " btn-success "; endif;
                                                            echo "' >";
                                                            echo "<div class='col-md-3'>".$dv['name']." ";
                                                            if ($dv['usage']): echo "<i class='fa fa-check'></i>"; endif;
                                                            echo "</div>";
                                                            echo "<div class='col-md-3'>".$dv['size']." ".$dv['weight']."</div>";
                                                            echo "<div class='col-md-3'>".date(returnIntLang('format date time', false), $dv['time'])."</div>";
                                                            if ($dv['usage']):
                                                                echo "<div class='col-md-3'> <a href='".$dv['link']."' target='_blank' style='color: white;'><i class='fa fa-eye'></i></a> </div>";
                                                            else:  
                                                                echo "<div class='col-md-3'>";
                                                        
                                                                echo " <a onclick=\"alert('crf: ".$dv['action']."')\" target='_blank'><i class='fa fa-trash'></i></a> ";
                                                                echo " <a onclick=\"alert('cif: ".$dv['action']."')\" target='_blank'><i class='fa fa-arrow-circle-o-up'></i></a> ";
                                                                echo " <a onclick=\"alert('cff: ".$dv['action']."')\" target='_blank'><i class='fa fa-arrow-circle-o-right'></i></a> ";

                                                                echo " <a href='".$dv['link']."' target='_blank'><i class='fa fa-eye'></i></a> ";
                                                                echo "</div>";
                                                            endif;
                                                            echo "</div>";
                                                        endforeach;
                                                    endif;

                                                    ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <em>no data</em>
                            <?php endif; ?>
                            
                            <?php
			
			// if ftp-connect exists
			if ($ftpcon):
				
				if (isset($_POST['crf']) && trim($_POST['crf'])!=''): 
					$filekey = array_keys($fsc['fileaction'], trim($_POST['crf']));
					$filekey = $filekey[0];
					if (array_key_exists($filekey, $fsc['files'])):
						// remove from filesystem
						if (ftp_delete($ftp, str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/".$filekey)))):
							// remove from files-array
							unset($fsc['files'][$filekey]);
						endif;
					endif;
				endif;

				if (isset($_POST['cif']) && trim($_POST['cif'])!=''): 
					$filekey = array_keys($fsc['fileaction'], trim($_POST['cif']));
					$filekey = $filekey[0];
					if (array_key_exists($filekey, $fsc['files'])):
						// set file content
						$tmpbuf = '<'.'?'.'php header("HTTP/1.1 301 Moved Permanently"); 
header("location: /"); 
?'.'>';
						$tmppath = $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/";
						// define temp filename
						$tmpfile = tempnam($tmppath, 'wsp');
						// open file in tmp to write
						$fh = fopen($tmpfile, "r+");
						// write contents to file
						fwrite($fh, $tmpbuf);
						fclose($fh);
						
						if (ftp_put($ftp, str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/".$filekey)), $tmpfile, FTP_BINARY)):
							addWSPMsg('resultmsg', "<p>Weiterleitung definiert</p>");
						else:
							addWSPMsg('errormsg', "<p>Die Datei \"".$ftppath."\" konnte nicht hochgeladen werden.</p>");
						endif;
						unlink($tmpfile);
					endif;
				endif;
				
				if (isset($_POST['cff']) && trim($_POST['cff'])!=''): 
					$filekey = array_keys($fsc['fileaction'], trim($_POST['cff']));
					$filekey = $filekey[0];
					if (array_key_exists($filekey, $fsc['files'])):
						$target = returnInterpreterPath(intval($_POST['cffid']));
						// set file content
						$tmpbuf = '<'.'?'.'php header("HTTP/1.1 301 Moved Permanently"); 
header("location: '.$target.'"); 
?'.'>';
						$tmppath = $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/";
						// define temp filename
						$tmpfile = tempnam($tmppath, 'wsp');
						// open file in tmp to write
						$fh = fopen($tmpfile, "r+");
						// write contents to file
						fwrite($fh, $tmpbuf);
						fclose($fh);
						
						if (ftp_put($ftp, str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/".$filekey)), $tmpfile, FTP_BINARY)):
							addWSPMsg('resultmsg', "<p>Weiterleitung definiert</p>");
						else:
							addWSPMsg('errormsg', "<p>Die Datei \"".$ftppath."\" konnte nicht hochgeladen werden.</p>");
						endif;
						unlink($tmpfile);
					
					endif;
				endif;
			endif;
			
			?>
			<form id='switchcfd' name='switchcfd' method='post'><input type="hidden" id='configcfd' name='cfd' value='' /></form>
			<form id='removefile' name='removefile' method='post'><input type="hidden" name='cfd' value='<?php echo str_replace("//", "/", str_replace("//", "/", "/".substr($fsc['ftpdir'], strlen(str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/")))))))); ?>' /><input type="hidden" id='configremovefile' name='crf' value='' /></form>
			<form id='indexfile' name='indexfile' method='post'><input type="hidden" name='cfd' value='<?php echo str_replace("//", "/", str_replace("//", "/", "/".substr($fsc['ftpdir'], strlen(str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/")))))))); ?>' /><input type="hidden" id='configindexfile' name='cif' value='' /></form>
                            
                            
                            
                            
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">    
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('cleanup icondesc'); ?></h3>
                        </div>
                        <div class="panel-body">
                            <ul class="icondesc">
                                <li class="icondescitem"><i class='fa fa-trash'></i> <?php echo returnIntLang('bubble cleanup 404 remove icondesc'); ?></li>
                                <li class="icondescitem"><i class='fa fa-arrow-circle-o-up'></i> <?php echo returnIntLang('bubble cleanup 301 index icondesc'); ?></li>
                                <li class="icondescitem"><i class='fa fa-arrow-circle-o-right'></i> <?php echo returnIntLang('bubble cleanup 301 file icondesc'); ?></li>
                                <li class="icondescitem"><i class='fa fa-eye'></i> <?php echo returnIntLang('bubble cleanup open icondesc'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        <hr />
        
        <form id='filefile' name='filefile' method='post'><input type="hidden" name='cfd' value='<?php echo str_replace("//", "/", str_replace("//", "/", "/".substr($fsc['ftpdir'], strlen(str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/")))))))); ?>' /><input type="hidden" id='configfilefile' name='cff' value='' />
        <table class="tablelist">
            <tr>
                <td class="tablecell eight">&nbsp;<?php echo returnIntLang('inline forwarding targetpage'); ?></td>
            </tr>
            <tr>
                <td class="tablecell eight"><select name="cffid" class="one full">
                    <?php // getMenuLevel(0, 0, 1); ?>
                </select></td>
            </tr>
            <tr>
                <td class="tablecell eight">&nbsp;<a onclick="document.getElementById('filefile').submit();"><?php echo returnIntLang('inline set targetpage'); ?></a></td>
            </tr>
        </table>
        </form>
	
        <script language="JavaScript" type="text/javascript">
        <!--
        
        function panel_height(panelname) {
            var panelheight = parseInt($('#' + panelname ).outerHeight());
            var paneltopp = parseInt($('#' + panelname).css('padding-top'));
            var panelbottomp = parseInt($('#' + panelname).css('padding-bottom'));
            return (panelheight-paneltopp-panelbottomp-4);
        }
        
        function cleanup_tmp() {
            $('#cleanup_tmp_panel').html('<iframe src="./xajax/iframe.cleanuptmppanel.php" style="border: none; width: 100%; background: red; height: ' + panel_height('cleanup_tmp_panel') + 'px;"></iframe>');
        }
        
        function refresh_media() {
            $('#refresh_media_panel').html('<iframe src="./xajax/iframe.refreshmediapanel.php" style="border: none; width: 100%; background: red; height: ' + panel_height('refresh_media_panel') + 'px;"></iframe>');
        }
         
        /*
        $(document).ready(function() {
            $(".fancyhelper").fancybox({
                maxWidth	: 800,
                maxHeight	: 600,
                fitToView	: false,
                minWidth	: '20%',
                autoSize	: true,
                closeClick	: false,
                openEffect	: 'none',
                closeEffect	: 'none'
            });
        });
        */
        
        //-->
        </script>
    </div>
</div>
<?php
include ("./data/include/footer.inc.php");
// EOF?>