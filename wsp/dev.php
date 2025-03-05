<?php
/**
 * @description developer tools
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-03-08
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
include("./data/include/usestat.inc.php");
include("./data/include/globalvars.inc.php");
// define actual system position -------------
$_SESSION['wspvars']['lockstat'] = '';
$_SESSION['wspvars']['pagedesc'] = array('fa fa-gears',returnIntLang('menu manage'),returnIntLang('menu manage developer'));
$_SESSION['wspvars']['menuposition'] = 'dev'; // string mit der aktuellen position fuer backend-auswertung
$_SESSION['wspvars']['mgroup'] = 10;
$_SESSION['wspvars']['mpos'] = 10;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false; // tells user, to save data before leaving page
/* second includes --------------------------- */
include("./data/include/checkuser.inc.php");
include("./data/include/errorhandler.inc.php");
include("./data/include/siteinfo.inc.php");
// define page specific vars -----------------
if (isset($_POST['save_data'])):
	// save changes
	$setstat = array("false", "true");
	$usestatfile = "<?php
/**
 * @since 3.1
 * @version 7.".time()."
 * @lastchange ".date('Y-m-d')."
 */

\$_SESSION['wspvars']['devstat'] = ".$setstat[intval($_POST['devstat'])]."; // true | false
\$_SESSION['wspvars']['showdeverrors'] = ".$setstat[intval($_POST['showdeverrors'])]."; // true | false
\$_SESSION['wspvars']['showdevmsg'] = ".$setstat[intval($_POST['showdevmsg'])]."; // true | false
\$_SESSION['wspvars']['devcontent'] = ".$setstat[intval($_POST['devcontent'])]."; // true | false
\$_SESSION['wspvars']['debugcontent'] = ".$setstat[intval($_POST['debugcontent'])]."; // true | false
\$_SESSION['wspvars']['showsql'] = ".$setstat[intval($_POST['showsql'])]."; // true | false
\$_SESSION['wspvars']['showpost'] = ".$setstat[intval($_POST['showpost'])]."; // true | false
\$_SESSION['wspvars']['showrequest'] = ".$setstat[intval($_POST['showrequest'])]."; // true | false

?>";

    $ftp = ftp_connect(FTP_HOST, FTP_PORT);
    if ($ftp):
        $login = @ftp_login($ftp, FTP_USER, FTP_PASS);
        if ($login):
            // create temporary usestat-file
            $fh = fopen(cleanPath(DOCUMENT_ROOT."/".WSP_DIR."/tmp/".$_SESSION['wspvars']['usevar']."/usestat.inc.php"), "w+");
            fwrite($fh, $usestatfile);
            if (ftp_put($ftp, cleanPath(FTP_BASE."/".WSP_DIR."/data/include/usestat.inc.php"), cleanPath(DOCUMENT_ROOT."/".WSP_DIR."/tmp/".$_SESSION['wspvars']['usevar']."/usestat.inc.php"), FTP_BINARY)):
                // remove temporary usestat-file
                fclose($fh);
                unlink(cleanPath(DOCUMENT_ROOT."/".WSP_DIR."/tmp/".$_SESSION['wspvars']['usevar']."/usestat.inc.php"));
            else:
                die("<br />fail".var_export(error_get_last()));
            endif;
        endif;
        ftp_close($ftp);
    endif;
endif;

/*
// make sql statements
$sqlresult = '';
$sqlquery = '';
if ($_SESSION['wspvars']['usertype']==1 || $_SESSION['wspvars']['usertype']=='admin'):
	if (isset($_POST['sqlquery']) && trim($_POST['sqlquery'])!=''):
		$sqlquery = trim($_POST['sqlquery']);
		$sqltest_sql = $sqlquery;
		$sqltest_res = mysql_query($sqltest_sql);
		$sqltest_num = 0; 
		if ($sqltest_res):
			$sqltest_num = mysql_num_rows($sqltest_res);
			$sqlresult = "<pre>";
			$sqlresult.= "results: ".$sqltest_num."\n";
			$sqlresult.= "</pre>";
			$sqlresultdata = '';
			for ($sres=0; $sres<$sqltest_num; $sres++):
				$sqlresultdata.= str_replace("}", "}\n", str_replace(";", ";\n\t", str_replace("{", "{\n\t", serialize(mysql_fetch_assoc($sqltest_res)))))."\n";
			endfor;
		else:
			$sqlresult = mysql_error();
		endif;
	endif;
endif;
*/

// include head ------------------------------
include("./data/include/header.inc.php");
include("./data/include/navbar.inc.php");
include("./data/include/sidebar.inc.php");

// update session because reloading is much faster then ftp-writing so the usestat-file is not yet updated when reloading page ;)
if(isset($_POST) && is_array($_POST) && count($_POST)>0): foreach ($_POST AS $dk => $dv): $_SESSION['wspvars'][$dk] = intval($dv); endforeach; endif;

?>
<!-- MAIN -->
<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('development settings headline'); ?></h1>
                <p class="page-subtitle">Activate or deactivate development settings. The results of these settings are visible to all users.</p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title">Development Options </h3>
                        </div>
                        <div class="panel-body">
                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="frmprefs">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="fancy-checkbox custom-bgcolor-blue">
                                            <label>
                                                <input type="hidden" name="devstat" value="0" /><input type="checkbox" name="devstat" value="1" <?php if(isset($_SESSION['wspvars']['devstat']) && $_SESSION['wspvars']['devstat']) echo " checked='checked' "; ?> />
                                                <span>show box containing all defined variables</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="fancy-checkbox custom-bgcolor-blue">
                                            <label>
                                                <input type="hidden" name="showdeverrors" value="0" /><input type="checkbox" name="showdeverrors" value="1" <?php if(array_key_exists('showdeverrors', $_SESSION['wspvars']) && $_SESSION['wspvars']['showdeverrors']) echo "checked=\"checked\""; ?> />
                                                <span>set errormsg level to "E_ALL"</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="fancy-checkbox custom-bgcolor-blue">
                                            <label>
                                                <input type="hidden" name="showdevmsg" value="0" /><input type="checkbox" name="showdevmsg" value="1" <?php if(array_key_exists('showdevmsg', $_SESSION['wspvars']) && $_SESSION['wspvars']['showdevmsg']) echo "checked=\"checked\""; ?> />
                                                <span>show dev output, if returned</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="fancy-checkbox custom-bgcolor-blue">
                                            <label>
                                                <input type="hidden" name="devcontent" value="0" /><input type="checkbox" name="devcontent" value="1" <?php if(array_key_exists('devcontent', $_SESSION['wspvars']) && $_SESSION['wspvars']['devcontent']) echo "checked=\"checked\""; ?> />
                                                <span>show development params, if supported</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="fancy-checkbox custom-bgcolor-blue">
                                            <label>
                                                <input type="hidden" name="debugcontent" value="0" /><input type="checkbox" name="debugcontent" value="1" <?php if(array_key_exists('debugcontent', $_SESSION['wspvars']) && $_SESSION['wspvars']['debugcontent']) echo "checked=\"checked\""; ?> />
                                                <span>show xajax contentdebug output, if returned</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="fancy-checkbox custom-bgcolor-blue">
                                            <label>
                                                <input type="hidden" name="showsql" value="0" /><input type="checkbox" name="showsql" value="1" <?php if($_SESSION['wspvars']['showsql']) echo "checked=\"checked\""; ?> />
                                                <span>show sql output, if new styled sql-requests returned</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="fancy-checkbox custom-bgcolor-blue">
                                            <label>
                                                <input type="hidden" name="showpost" value="0" /><input type="checkbox" name="showpost" value="1" <?php if(isset($_SESSION['wspvars']['showpost']) && $_SESSION['wspvars']['showpost']) echo "checked=\"checked\""; ?> />
                                                <span>show $_POST output</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="fancy-checkbox custom-bgcolor-blue">
                                            <label>
                                                <input type="hidden" name="showrequest" value="0" /><input type="checkbox" name="showrequest" value="1" <?php if(isset($_SESSION['wspvars']['showrequest']) && $_SESSION['wspvars']['showrequest']) echo "checked=\"checked\""; ?> />
                                                <span>show $_REQUEST output</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <p>&nbsp;</p>
                                <p><a href="#" onclick="document.getElementById('frmprefs').submit(); return false;" class="btn btn-primary"><?php echo returnIntLang('str save', false); ?></a><input name="save_data" type="hidden" value="Speichern" /></p>
                                <p>In some cases it's possible, that you reload this page and usestat-file seems not to be changed. This can happen if FTP-writing is very slow. It is not a bug, but we don't have a clever solution to check for that case.</p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title">SQL Tester</h3>
                        </div>
                        <div class="panel-body">
                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="sqltest" style="margin: 0px;">
                                <p>SQL statement</p>
                                <p><textarea name="sqlquery" class="form-control large" ><?php echo isset($sqlquery)?$sqlquery:''; ?></textarea></p>
                                <?php if (isset($sqlresult) && trim($sqlresult)!='') { ?>
                                    <p>SQL result</p>
                                    <pre><?php echo isset($sqlresult)?$sqlresult:''; ?></pre>
                                    <pre><?php echo isset($sqlresultdata)?$sqlresultdata:''; ?></pre>
                                <?php } ?>
                                <p><a href="#" onclick="document.getElementById('sqltest').submit(); return false;" class="greenfield">submit</a></p>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title">Development Messages</h3>
                        </div>
                        <div class="panel-body">
                            <p>Window Information</p>
                            <pre><script language="JavaScript" type="text/javascript">
                            <!--

                            document.write("height: " + $(window).height() + " x width: " + $(window).width()); 

                            // -->
                            </script></pre>
                            <?php

                            echo serialize($_SESSION['wspvars']['showdevmsg']);

                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title">SESSION Info</h3>
                        </div>
                        <div class="panel-body">
                            <pre><?php var_export($_SESSION); ?></pre>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title">WSP Rights</h3>
                        </div>
                        <div class="panel-body">
                            <?php

                            $wsprights = array();
                            $wspinfos = array();
                            $sessinfos = array();
                            foreach ($_SESSION['wspvars']['rights'] as $key => $value):
                                $wsprights[] = $key." : ".$value;
                            endforeach;

                            ?>
                            <pre><?php var_export($wsprights); ?></pre>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title">WSP Information</h3>
                        </div>
                        <div class="panel-body">
                            <?php asort($sessinfos); ?>
                            <p>usevar</p>
                            <pre><?php var_export($_SESSION['wspvars']['usevar']); ?></pre>
                            <p>userid</p>
                            <pre><?php var_export($_SESSION['wspvars']['userid']); ?></pre>
                            <p>mgroup</p>
                            <pre><?php var_export($_SESSION['wspvars']['mgroup']); ?></pre>
                            <p>fpos</p>
                            <pre><?php var_export($_SESSION['wspvars']['fpos']); ?></pre>
                            <p>fposcheck</p>
                            <pre><?php var_export($_SESSION['wspvars']['fposcheck']); ?></pre>
                            <p>preventleave</p>
                            <pre><?php var_export($_SESSION['wspvars']['preventleave']); ?></pre>
                            <p>Server side basedir (DOCUMENT_ROOT)</p>
                            <pre><?php echo cleanPath($_SERVER['DOCUMENT_ROOT']."/"); ?></pre>
                            <p>Server side <em>"calculated"</em> basedir</p>
                            <pre><?php echo cleanPath(DOCUMENT_ROOT); ?></pre>
                            <p>File path</p>
                            <pre><?php echo str_replace("//", "/", $_SERVER['SCRIPT_FILENAME']); ?></pre>
                            <p>File name</p>
                            <pre><?php echo str_replace("//", "/", $_SERVER['SCRIPT_NAME']); ?></pre>
                            <p>FTP-logindir</p>
                            <pre><?php

                            $ftp = ftp_connect(FTP_HOST, FTP_PORT);
                            $ftperror = array();
                            $ftprawlist = false;
                            $ftpmkdir = false;
                            $ftprename = false;
                            $testdir = '';
                            if ($ftp):
                                $login = @ftp_login($ftp, FTP_USER, FTP_PASS);
                                if ($login):
                                    echo ftp_pwd($ftp);
                                    $testdir = md5("ftpcheck".time());
                                    $ftplist = @ftp_rawlist($ftp, ftp_pwd($ftp));
                                    $ftpmkdir = @ftp_mkdir($ftp, cleanPath("/".FTP_BASE."/".$testdir));
                                    $ftprename = @ftp_rename($ftp, cleanPath("/".FTP_BASE."/".$testdir), cleanPath("/".FTP_BASE."/".$testdir."-rename"));
                                    ftp_rmdir($ftp, str_replace("//", "/", str_replace("//", "/", "/".FTP_BASE."/".$testdir."-rename")));
                                    endif;
                                ftp_close($ftp);
                            else:
                                echo "no ftp connect";
                            endif;
                            
                            ?></pre>
                            <p>FTP login structure</p>
                            <pre><?php if (isset($ftplist) && is_array($ftplist)): var_export($ftplist); endif; ?></pre>
                            <p>ftp-mkdir</p>
                            <pre><?php var_export((str_replace("//", "/", str_replace("//", "/", "/".FTP_BASE."/".$testdir))==$ftpmkdir)?array($ftpmkdir => true):false); ?></pre>
                            <p>ftp-rename</p>
                            <pre><?php echo intval($ftprename); ?></pre>
                            <p>get_included_files()</p>
                            <pre><?php var_export(get_included_files()); ?></pre>
                            <p>get_defined_constants()</p>
                            <pre><?php var_export(get_defined_constants(true)['user']); ?></pre>
                            <p>error_get_last()</p>
                            <pre><?php var_export(error_get_last()); ?></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END MAIN CONTENT -->
</div>
<!-- END MAIN -->
<?php require ("./data/include/footer.inc.php"); ?>