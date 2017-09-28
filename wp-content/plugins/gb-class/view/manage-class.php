<script src="/wp-content/plugins/gb-class/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="/wp-content/plugins/gb-class/css/bootstrap.css" type="text/css">
<div class="wrap nosubsub">
    <h1><?=_l("Class Manage")?> </h1>
    <input type="button" value="<?=_l("Add Class")?>" data-toggle="modal" data-target="#myModal">
    <?php
    for($i=0;$i<count($ret_msg);$i++) {
        echo '<div class="err_msg">' . $ret_msg[$i] . "</div>";
    }
        ?>
        <div>
            <div class="col-wrap">
                <h2 class="screen-reader-text"><?=_l("Class Lists")?></h2>
                <table class="wp-list-table widefat fixed striped tags">
                    <thead>
                    <tr>

                        <th scope="col" id="name" class="manage-column column-name column-primary">
                            <a><span><?=_l("Class Name")?></span></a></th>
                        <th scope="col" id="description" class="manage-column column-description">
                            <a><span><?=_l("Class Tag")?></span></a></th>
                        <th scope="col" id="description" class="manage-column column-description">
                            <a><span><?=_l("Teacher")?></span></a></th>
                        <th scope="col" id="description" class="manage-column column-description">
                            <a><span><?=_l("Student Counts")?></span></a></th>
                        <th scope="col" id="description" class="manage-column column-description">
                            <a><span><?=_l("Manage")?></span></a></th>
                    </thead>

                    <tbody id="the-list" data-wp-lists="list:tag">
                    <?php
                    for ($i = 0; $i < count($class_lists); $i++) {
                        $v = $class_lists[$i];
                        ?>
                        <tr id="tag-<?= $i ?>" data-class-name="<?= $v['class_name'] ?>" data-class-tag="<?= $v['class_tag'] ?>" data-id="<?= $v['id'] ?>">
                            <td class="username column-username has-row-actions column-primary">
                                <strong><?= $v['class_name'] ?></strong></td>
                            <td><?= $v['class_tag'] ?></td>
                            <td><?= $v['display_name'] ?></td>
                            <td><?= $v['student_count'] ?></td>
                            <td>
                                <a href="#" class="c_t"><?=_l("Change Teacher")?></a> | <a><?=_l("Add Student")?></a>
                            </td>
                        </tr>
                    <?php
                    }
                    ?>
                </table>
            </div>
        </div>
</div>
<?=$pageOp->PageShow()?>
<!-- Modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel"><?=_l("Add Class")?></h4>
            </div>
            <div class="modal-body">
                <form action="/wp-admin/admin.php?page=gb_class_manage&action=save_class" method="post" id="class_form">
                    <div class="form-group">
                        <label for="exampleInputEmail1"><?=_l("Class Name")?></label>
                        <input type="email" class="form-control" id="class_name" name="class_name" >
                    </div>
                    <div class="form-group">
                        <label for="exampleInputEmail1"><?=_l("Class Tag")?></label>
                        <input type="email" class="form-control" id="class_tag" name="class_tag" >
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

<div class="modal fade" id="changeTeacherModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel"><?=_l("Change Teacher")?></h4>
            </div>
            <div class="modal-body">
                <form action="/wp-admin/admin.php?page=gb_class_manage&action=change_teacher" method="post" id="change_teacher_form">
                    <input type="hidden" name="class_id" id="c_class_id">
                    <div class="form-group" id="class_title">

                    </div>
                    <div class="form-group">
                        <label for="exampleInputEmail1"><?=_l("Please fill in the teacher's login account")?></label>
                        <input type="email" class="form-control" id="class_tag" name="class_tag" >
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=_l("Close")?></button>
                <button type="button" class="btn btn-primary" id="change_teacher_btn"><?=_l("Save")?></button>
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

    jQuery("#change_teacher_btn").click(function(){
        jQuery("#change_teacher_form").submit();
    });

    jQuery(".c_t").click(function(){
        jQuery("#class_title").html(jQuery(this).parent().parent().attr("data-class-name") + " (" + jQuery(this).parent().parent().attr("data-class-tag") + ")");
        jQuery("#c_class_id").val(jQuery(this).parent().parent().attr("data-id")");
        jQuery("#changeTeacherModal").modal("show");
    })
</script>
