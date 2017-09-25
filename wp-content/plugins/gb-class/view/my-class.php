<div class="wrap nosubsub">
    <h1>我的班级 </h1>
    <form>
        <input type="button" value="修改班级">
    </form>
    <div >
        <div class="col-wrap">
            <h2 class="screen-reader-text">学生列表</h2>
                <table class="wp-list-table widefat fixed striped tags">
                    <thead>
                    <tr>

                        <th scope="col" id="name" class="manage-column column-name column-primary">
                            <a><span>姓名</span></a></th>
                        <th scope="col" id="description" class="manage-column column-description">
                            <a><span>账户</span></a></th>
                        <th scope="col" id="description" class="manage-column column-description">
                            <a><span>邮箱</span></a></th>
                        <th scope="col" id="description" class="manage-column column-description">
                            <a><span>博客</span></a></th>
                    </thead>

                    <tbody id="the-list" data-wp-lists="list:tag">
                    <?php
                    for($i=0;$i<count($students);$i++) {
                        $v = $students[$i];
                    ?>
                    <tr id="tag-<?=$i?>">
                        <td class="username column-username has-row-actions column-primary"><?=get_avatar($v['ID'],32)?><strong><?=$v['display_name']?></strong></td>
                        <td><?=$v['user_login']?></td>
                        <td><?=$v['user_email']?></td>
                        <td>
                            <?php
                            if($v['blog_id']>1) {
                                echo '<a href="http://'.$v['domain'].$v['path'].'" target="_blank">查看</a> | ';
                                echo '<a href="http://'.$v['domain'].$v['path'].'wp-admin/">仪表盘</a>';
                            }else {
                                echo '<a href="/wp-admin/admin.php?page=gb_my_class&action=create_blog&user_id='.$v['ID'].'">开通博客</a>';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php
                    }
                    ?>
                </table>
        </div>
    </div>
</div>
