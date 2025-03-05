<?php

$menuinfo_sql = "SELECT `description`, `changetime` FROM `menu` WHERE `contentchanged` != 1 ORDER BY `changetime` DESC LIMIT 0, 10";
$menuinfo_res = doSQL($menuinfo_sql);

if ($menuinfo_res['num']>0) {

    echo (isset($panel['outerclass'])?'<div class="'.$panel['outerclass'].'">':'');
    ?>
    <div class="panel" id="panel-published-contents">
        <div class="panel-heading">
            <h3 class="panel-title"><?php echo returnIntLang('home published'); ?></h3>
            <?php panelOpener(true, false, false, 'panel-published-contents'); ?>
        </div>
        <div class="panel-body">
            <?php

            echo "<table class='table'>";
            echo "<thead>
                <tr>
                    <th>#</th>
                    <th>".returnIntLang('str page')."</th>
                    <th>".returnIntLang('str lastchange')."</th>
                </tr>
            </thead>";
            echo "<tbody>";
            $p = 0;
            foreach ($menuinfo_res['set'] AS $mirsk => $mirsv) {
                echo "<tr>";
                echo "<td>".($p+1)."</td>";
                echo "<td>".trim($mirsv['description'])."</td>";
                echo "<td>";
                if (intval($mirsv['changetime'])>0) {
                    echo date(returnIntLang("format date time", false), intval($mirsv['changetime']));
                }
                echo "</td>";
                echo "</tr>";
                $p++;
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
