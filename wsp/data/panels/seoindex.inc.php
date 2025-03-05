<div class="panel">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo returnIntLang('seo index'); ?></h3>
    </div>
    <div class="panel-body">
        <p><select name="siterobots" id="siterobots" size="1" class="singleselect fullwidth">
            <option value="none" <?php if ($sitedata['siterobots']=="none") echo "selected=\"selected\""; ?>><?php echo returnIntLang('seo robots none', false); ?></option>
            <option value="nofollow" <?php if ($sitedata['siterobots']=="nofollow") echo "selected=\"selected\""; ?>><?php echo returnIntLang('seo robots nofollow', false); ?></option>
            <option value="noindex" <?php if ($sitedata['siterobots']=="noindex") echo "selected=\"selected\""; ?>><?php echo returnIntLang('seo robots noindex', false); ?></option>
            <option value="all" <?php if ($sitedata['siterobots']=="all") echo "selected=\"selected\""; ?>><?php echo returnIntLang('seo robots all', false); ?></option>
        </select></p>
        <div class="input-group">
            <span class="input-group-addon"><?php echo returnIntLang('str interval'); ?></span>
            <input class="form-control" name="robotinterval" type="number" min="-1" value="<?php echo $sitedata['robotinterval']; ?>" />
            <span class="input-group-addon"><?php echo returnIntLang('str days'); ?></span>
        </div>
    </div>
</div>