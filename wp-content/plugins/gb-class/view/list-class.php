<script src="/wp-content/plugins/gb-class/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="/wp-content/plugins/gb-class/css/bootstrap.css" type="text/css">
<div class="wrap nosubsub">
    <h1><?=$classObj->getVar("class_name")?> (<?=$classObj->getVar("class_tag")?>) </h1>
    <?php
    for($i=0;$i<count($ret_msg);$i++) {
        echo '<div class="err_msg">' . $ret_msg[$i] . "</div>";
    }
        ?>
        <div id="class_header">
            <input type="button" value="<?=_l("Modify Class Name")?>" data-toggle="modal" data-target="#myModal">
        </div>
        <div>
            <div class="col-wrap">
                <h2 class="screen-reader-text"><?=_l("Student Lists")?></h2>
                <table class="wp-list-table widefat fixed striped tags">
                    <thead>
                    <tr>

                        <th scope="col" id="name" class="manage-column column-name column-primary">
                            <a><span><?=_l("Student Name")?></span></a></th>
                        <th scope="col" id="description" class="manage-column column-description">
                            <a><span><?=_l("Login Account")?></span></a></th>
                        <th scope="col" id="description" class="manage-column column-description">
                            <a><span><?=_l("Email")?></span></a></th>
                        <th scope="col" id="description" class="manage-column column-description">
                            <a><span><?=_l("Visit Password")?></span></a></th>
                        <th scope="col" id="description" class="manage-column column-description">
                            <a><span><?=_l("Blog")?></span></a></th>
                    </thead>

                    <tbody id="the-list" data-wp-lists="list:tag">
                    <?php
                    for ($i = 0; $i < count($students); $i++) {
                        $v = $students[$i];
                        ?>
                        <tr id="tag-<?= $i ?>">
                            <td class="username column-username has-row-actions column-primary"><?= get_avatar($v['ID'], 32) ?>
                                <strong><?= $v['display_name'] ?></strong></td>
                            <td><?= $v['user_login'] ?></td>
                            <td><?= $v['user_email'] ?></td>
                            <td><?=$v['visit_password']?></td>
                            <td>
                                <?php
                                if ($v['blog_id'] > 1) {
                                    echo '<a href="http://' . $v['domain'] . $v['path'] . '" target="_blank">'._l("View").'</a> | ';
                                    echo '<a href="http://' . $v['domain'] . $v['path'] . 'wp-admin/">'._l("Dashboard").'</a>';
                                }else {
                                    echo _l("No Blog");
                                }
                                ?>
                                | <a href="#" class="set_pwd" data-user-id="<?=$v['ID']?>"><?=_l("Set Visit Password")?></a>
                            </td>
                        </tr>
                    <?php
                    }
                    ?>
                </table>
            </div>
        </div>
</div>

<!-- Modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel"><?=_l("Edit Class")?></h4>
            </div>
            <div class="modal-body">
                <form action="/wp-admin/admin.php?page=gb_class_manage&action=update_class" method="post" id="class_form">
                    <input type="hidden" name="class_id" value="<?=$classObj->getKeyId()?>">
                    <div class="form-group">
                        <label for="exampleInputEmail1"><?=_l("Class Name")?></label>
                        <input type="email" class="form-control" id="class_name" name="class_name" value="<?=$classObj->getVar("class_name")?>">
                    </div>
                    <div class="form-group">
                        <label for="exampleInputEmail1"><?=_l("Class Tag")?></label>
                        <input type="email" class="form-control" id="class_tag" name="class_tag" value="<?=$classObj->getVar("class_tag")?>">
                        <?=_l("The class tag should be unique tag in the all classes")?>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=_l("Close")?></button>
                <button type="button" class="btn btn-primary" id="save_btn"><?=_l("Save")?></button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="visitPwdModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel"><?=_l("Set Visit Password")?></h4>
            </div>
            <div class="modal-body">
                <form action="/wp-admin/admin.php?page=gb_class_manage&class_id=<?=$classObj->getKeyId()?>" method="post" id="visit_pwd_form">
                    <div class="form-group">
                        <label for="exampleInputEmail1"><?=_l("Password")?></label>
                        <input type="text" class="form-control" id="visit_pwd" name="visit_pwd" >
                        <input type="hidden" name="user_id" value="">
                        <input type="hidden" name="action" value="set_visit_pwd">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=_l("Close")?></button>
                <button type="button" class="btn btn-primary" id="visit_pwd_btn"><?=_l("Save")?></button>
            </div>
        </div>
    </div>
</div>
<script language="javascript">
    jQuery("#save_btn").click(function(){
       if(jQuery("#class_name").val() == "") {
           alert("<?=_l("Please fill in the class name")?>");
           return;
       }
        if(jQuery("#class_tag").val() == "") {
            alert("<?=_l("Please fill in the class tag")?>");
            return;
        }

        jQuery("#class_form").submit();
    });

    jQuery(".set_pwd").click(function(){
        jQuery("#visitPwdModal input[name='user_id']").val(jQuery(this).attr("data-user-id"));
        jQuery("#visitPwdModal #visit_pwd").val("");
        jQuery("#visitPwdModal").modal("show");
    });

    jQuery("#visit_pwd_btn").click(function(){
        jQuery("#visit_pwd_form").submit();
    });
</script>
