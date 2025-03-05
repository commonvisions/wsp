<?php

$lastedit_sql = "SELECT `c`.`cid`, `c`.`mid`, `c`.`lastchange` FROM `content` AS `c`, `menu` AS `m` WHERE `c`.`lastchange` != 0 AND `c`.`trash` = 0 AND `m`.`trash` = 0 AND `c`.`mid` = `m`.`mid` ORDER BY `m`.`lastchange`, `c`.`lastchange` DESC LIMIT 0, 10"; 
$lastedit_res = doSQL($lastedit_sql);

if ($lastedit_res['num']>0) {

    echo (isset($panel['outerclass'])?'<div class="'.$panel['outerclass'].'">':'');
    ?>
    <div class="panel" id="panel-changed-contents">
        <div class="panel-heading">
            <h3 class="panel-title"><?php echo returnIntLang('home edited'); ?></h3>
            <?php panelOpener(true, false, false, 'panel-changed-contents'); ?>
        </div>
        <div class="panel-body">
            <?php

            echo "<table class='table'>";
            echo "<thead>
                <tr>
                    <th>#</th>
                    <th>".returnIntLang('str page')."</th>
                    <th class='text-right'>".returnIntLang('str lastchange')."</th>
                </tr>
            </thead>";
            echo "<tbody>";
            $p = 0;
            foreach ($lastedit_res['set'] AS $lersk => $lersv) {
                $menuinfo_sql = "SELECT `description` FROM `menu` WHERE `mid` = ".intval($lersv['mid']);
                $menuinfo_res = doResultSQL($menuinfo_sql);
                $contentinfo_sql = "SELECT `description`, `valuefields`, `globalcontent_id` FROM `content` WHERE `cid` = ".intval($lersv['cid']);
                $contentinfo_res = doSQL($contentinfo_sql);
                if ($menuinfo_res) {
                    echo "<tr>";
                    echo "<td>".($p+1)."</td>";
                    echo "<td class='nowrap'><a href='./contents.php?mjid=".intval($lersv['cid'])."'>".$menuinfo_res."</a>";
                    if ($contentinfo_res['num']==1) {
                        if (trim($contentinfo_res['set'][0]['description'])!='') {
                            echo " : ".trim($contentinfo_res['set'][0]['description']);
                        } else {
                            $tmp = unserializeBroken(trim($contentinfo_res['set'][0]['valuefields']));
                            if ($tmp!==null && array_key_exists('description', $tmp)) {
                                echo " : ".trim($tmp['description']);
                            }
                        }
                    }
                    echo "</td>";
                    echo "<td class='text-right'>";
                    if (intval($lersv['lastchange'])>0) {
                        echo date(returnIntLang("format date time", false), intval($lersv['lastchange']));
                    }
                    echo "</td>";
                    echo "</tr>";
                    $p++;
                }
            }
            echo "</tbody>";
            echo "</table>";

            ?>
        </div>
    </div>
    <?php 

    echo (isset($panel['outerclass'])?'</div>':'');
}
unset($panel);
