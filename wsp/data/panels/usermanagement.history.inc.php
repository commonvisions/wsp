<?php

$usercheck_sql = "SELECT * FROM `restrictions` ORDER BY `user` ASC";
$usercheck_res = doSQL($usercheck_sql);

if ($usercheck_res['num']>0) {

?>
<div class="panel">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo returnIntLang('userlog show userlogs'); ?></h3>
    </div>
    <div class="panel-body">
        <table class="table">
            <thead>
                <tr>
                    <th><?php echo returnIntLang('str user'); ?></th>
                    <th><?php echo returnIntLang('userlog lastlogin'); ?></th>
                    <th><?php echo returnIntLang('userlog showcount'); ?></th>
                    <th><?php echo returnIntLang('str action'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php

                $usernames = array(); 
                foreach ($usercheck_res['set'] AS $uresk => $uresv) {
                    $usernames[intval($uresv['rid'])] = trim($uresv['realname']);
                    echo "<tr><td><a onclick=\"document.getElementById('showlogs_".intval($uresv['rid'])."').submit();\" style=\"cursor: pointer;\">";
                    echo trim($uresv['realname']);
                    echo "</a></td>";

                    $log_sql = "SELECT `lastchange` FROM `securitylog` WHERE `uid` = ".intval($uresv['rid'])." ORDER BY `lastchange` DESC";
                    $log_res = doSQL($log_sql);
                    if ($log_res['num']>0):
                        $lastlogin = date("Y-m-d", $log_res['set'][0]['lastchange']);
                    else:
                        $lastlogin = returnIntLang('userlog nologin');
                    endif;

                    echo "<td>".$lastlogin."</td>";
                    echo "<td>".intval($log_res['num'])."</td>";
                    echo "<td>";
                    if ($log_res['num']>0):
                        echo "<a onClick=\"document.getElementById('showlogs_".intval($uresv['rid'])."').submit();\"><span class=\"bubblemessage green\">".strtoupper(returnIntLang('bubble showlog', false))."</span></a> ";
                        echo "<a onClick=\"checkClearLog('".intval($uresv['rid'])."','".trim($uresv['realname'])."',".intval($log_res['num']).");\"><span class=\"bubblemessage red\">".strtoupper(returnIntLang('bubble clearlog', false))."</span></a> ";

                        echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" id=\"clearlogs_".intval($uresv['rid'])."\" style=\"margin: 0px; padding: 0px;\">\n";
                        echo "<input type=\"hidden\" name=\"userrid\" value=\"".intval($uresv['rid'])."\">\n";
                        echo "<input type=\"hidden\" name=\"op\" value=\"cl\">\n";
                        echo "<input type=\"hidden\" id=\"countrows_".intval($uresv['rid'])."\" name=\"countrows\" value=\"1\">\n";
                        echo "</form>\n";
                        echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" id=\"showlogs_".intval($uresv['rid'])."\" style=\"margin: 0px; padding: 0px;\">\n";
                        echo "<input type=\"hidden\" name=\"userrid\" value=\"".intval($uresv['rid'])."\">\n";
                        echo "<input type=\"hidden\" name=\"op\" value=\"sl\">\n";
                        echo "</form>\n";
                    endif;
                    echo "</td>";
                    echo "</tr>";
                }

                ?>
            </tbody>
        </table>
	</div>
</div>
<script type="text/javascript">

function checkClearLog(logid, logname, logrows) {
    var countrows = prompt('<?php echo setUTF8(returnIntLang('userlog confirmdeletecountrows', false)); ?>', logrows*1);
    if (countrows!=null && countrows>0) {
        if (countrows>logrows) {
            document.getElementById('countrows_' + logid).value = logrows*1;
            }
        else {
            document.getElementById('countrows_' + logid).value = countrows*1;
            }
        document.getElementById('clearlogs_' + logid).submit();
        }
    }

</script>
<?php

}

?>