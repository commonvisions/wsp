<?php if(isset($_POST) && array_key_exists('op', $_POST) && array_key_exists('userrid', $_POST) && $_POST['op']=='sl' && intval($_POST['userrid'])>0) { 
			
    $log_num = 0;
    $log_sql = "SELECT * FROM `securitylog` WHERE `uid` = ".intval($_POST['userrid'])." ORDER BY `lastchange` DESC";
    $log_res = doSQL($log_sql);
	   		
    if ($log_res['num']>0) { 
        
        $showlog = (($log_res['num']<101)?intval($log_res['num']):101);

?>
<div class="panel">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo returnIntLang('userlog last'); echo "&nbsp;".$showlog."&nbsp;"; echo returnIntLang('userlog log for'); ?> '<?php echo $usernames[intval($_POST['userrid'])]; ?>'</h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <?php
	   		
            for ($log=1; $log<$showlog; $log++) {
                echo "<div class=\"col-md-3 singleline\">".date("Y-m-d H:i:s", intval($log_res['set'][$log]['lastchange']))."<br />";
                if (trim($log_res['set'][$log]['lastaction'])=='login'):
                    echo "Login";
                else:
                    if (trim($log_res['set'][$log]['lastposition'])!=''):
                        $posparam = explode(";",trim($log_res['set'][$log]['lastposition']));
                        if (isset($posparam[1]) && trim($posparam[1])!=''):
                            $posdesc = returnIntLang('userlog posdesc '.str_replace("//", "/", str_replace("//", "/", str_replace("/".WSP_DIR."/", "/", $posparam[0]))), false);
                            $posdesc.= " : ".$posparam[1];
                        else:
                            $posdesc = returnIntLang('userlog posdesc '.str_replace("//", "/", str_replace("//", "/", str_replace("/".WSP_DIR."/", "/", trim($log_res['set'][$log]['lastposition'])))), false);
                        endif;

                        echo $posdesc." ".returnIntLang(trim('userlog action '.trim($log_res['set'][$log]['lastaction'])));
                    else:
                        echo returnIntLang('userlog pageload success');
                    endif;
                endif;
                echo "</div>";
            }
					
            ?>
        </div>
    </div>
</div>
<?php }} ?>