<?php
/**
 * 班级操作类
 */


class MyClass {

    var $teacher_id;

    public function __construct($id = "") {
        if($id) {
            $this->teacher_id = $id;
        }else {
            $this->teacher_id = get_current_user_id();
        }
    }

    public function setTeacherId($id) {
        $this->teacher_id = ceil($id);
    }

    public function setTeacherIdByStudentId($id) {
        global $wpdb;

        $id = ceil($id);
        if($id == 0)
            return;

        $prefix = $wpdb->get_blog_prefix(1);

        $sql = "select um1.user_id from  " . $prefix . "usermeta um1 inner join ".$prefix."usermeta um2 on um1.meta_value=um2.meta_value 
            where um2.user_id=$id and um1.meta_key='teach_class' and um2.meta_key='study_class'";

        $row = $wpdb->get_row($sql, ARRAY_A);
        if($row['user_id']) {
            $this->teacher_id = $row['user_id'];
            return true;
        }

        return false;
    }
    /**
     * 判断是否有权限可以创建blog
     * @param $user_id
     * @return bool
     */
    public function canCreateBlog($user_id) {
        if(!current_user_can(_GB_CAP)) {
            return;
        }

        $student_meta = get_user_meta($user_id);

        //已经存在blog
        if($student_meta['primary_blog'][0]>1) {
            return;
        }

        $teacher_meta = get_user_meta($this->teacher_id);

        if(!current_user_can("administrator")) {
            if($teacher_meta['teach_class'][0] != $student_meta['study_class'][0] || !$teacher_meta['teach_class'] || !$student_meta['study_class']) {
                return;
            }

        }

        return true;
    }

    /**
     * 判断是否可以修改学生访问密码
     * @param $user_id
     * @return bool
     */
    public function canChangeVisitPwd($user_id) {
        $student_meta = get_user_meta($user_id);

        $teacher_meta = get_user_meta($this->teacher_id);

        if(!current_user_can("administrator")) {
            if($teacher_meta['teach_class'][0] != $student_meta['study_class'][0] || !$teacher_meta['teach_class'] || !$student_meta['study_class']) {
                return;
            }

        }

        return true;
    }

    /**
     * 学生创建blog
     * @param $user_id
     * @param $domain
     */
    public function createBlog($user_id, $domain, $title) {
        global $wpdb;

        if ( is_subdomain_install() ) {
            $newdomain = $domain . '.' . preg_replace( '|^www\.|', '', get_network()->domain );
            $path      = get_network()->path;
        } else {
            $newdomain = get_network()->domain;
            $path      = get_network()->path . $domain . '/';
        }

        $meta['public'] = 1;
        $meta['WPLANG'] = "en_US";



        $wpdb->hide_errors();
        $id = wpmu_create_blog( $newdomain, $path, $title, $user_id, $meta, get_current_network_id() );
        $wpdb->show_errors();
        if ( ! is_wp_error( $id ) ) {
            add_user_to_blog($id, $user_id, 'contributor');
            update_user_option( $user_id, 'primary_blog', $id, true );
            $this->setDefaultBlogTheme($id);
            if ( ! is_super_admin( $user_id ) && !(get_user_option( 'primary_blog', $this->teacher_id )>1)) {
                //添加老师id到blog
                add_user_to_blog($id, $this->teacher_id, 'editor');
            }
        } else {
            wp_die( $id->get_error_message() );
        }
    }

    public function getClassStudents($class_id) {
        global $wpdb;

        $prefix = $wpdb->get_blog_prefix(1);

        $class_id = ceil($class_id);
        if($class_id == 0)
            return;

        $sql = "select u.*,b.*,um3.visit_password from " . $prefix."users u inner join
            "  . $prefix ."usermeta um1 on um1.user_id=u.ID
            inner join " . $prefix . "usermeta um2 on um2.user_id=u.ID
            left join ". $prefix . "blogs b on um2.meta_value=b.blog_id
            left join (select user_id, meta_value visit_password from ".$prefix."usermeta where meta_key='gb_visit_pwd') um3 on um3.user_id=u.ID
            where um1.meta_key='study_class' and um2.meta_key='primary_blog' and um1.meta_value=" . $class_id;

        return $wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * 通过搜索条件获取学生列表
     * @param $cond
     * @param $page
     * @param int $limit_num
     * @return mixed
     */
    public function getStudentsByCond($cond, $page, $limit_num = 0) {
        global $wpdb;

        $prefix = $wpdb->get_blog_prefix(1);

        $limit_sql = "";
        if($limit_num > 0) {
            $start_num = (ceil($page) - 1) * $limit_num;
            $limit_sql = " limit $start_num, $limit_num";
        }

        $where_sql = "";
        $cond_query = "";
        if($cond['st_name']) {
            $where_sql .= " and u.display_name like '%".addslashes($cond['st_name'])."%'";
            $cond_query .= "&st_name=".$cond['st_name'];
        }

        if($cond['cl_name']) {
            $where_sql .= " and gc.class_name like '%".addslashes($cond['cl_name'])."%'";
            $cond_query .= "&cl_name=".$cond['cl_name'];
        }

        $sql = "select u.*,b.*,gc.class_name, um3.visit_password from " . $prefix."users u inner join
            "  . $prefix ."usermeta um1 on um1.user_id=u.ID
            inner join gb_class gc on gc.id=um1.meta_value
            inner join " . $prefix . "usermeta um2 on um2.user_id=u.ID
            left join ". $prefix . "blogs b on um2.meta_value=b.blog_id
            left join (select user_id, meta_value visit_password from ".$prefix."usermeta where meta_key='gb_visit_pwd') um3 on um3.user_id=u.ID
            where um1.meta_key='study_class' and um2.meta_key='primary_blog' " . $where_sql . $limit_sql;

        $sql_total = "select count(u.ID) counts from " . $prefix."users u inner join
            "  . $prefix ."usermeta um1 on um1.user_id=u.ID
            inner join gb_class gc on gc.id=um1.meta_value
            inner join " . $prefix . "usermeta um2 on um2.user_id=u.ID
            left join ". $prefix . "blogs b on um2.meta_value=b.blog_id
            left join (select user_id, meta_value visit_password from ".$prefix."usermeta where meta_key='gb_visit_pwd') um3 on um3.user_id=u.ID
            where um1.meta_key='study_class' and um2.meta_key='primary_blog' " . $where_sql;

        $total_row = $wpdb->get_row($sql_total,ARRAY_A);

        $ret['students'] = $wpdb->get_results($sql, ARRAY_A);
        $ret['total'] = $total_row['counts'];
        $ret['cond_query'] = $cond_query;

        return $ret;
    }

    public function setDefaultBlogTheme($blog_id) {
        global $wpdb;
        $prefix = $wpdb->get_blog_prefix($blog_id);
        $siteurl = get_blog_option($blog_id, "siteurl");

        $sql[] = "UPDATE `{$prefix}options` SET `option_id`='40', `option_name`='template', `option_value`='slimwriter', `autoload`='yes' WHERE (`option_id`='40')";
        $sql[] = "UPDATE `{$prefix}options` SET `option_id`='41', `option_name`='stylesheet', `option_value`='slimwriter', `autoload`='yes' WHERE (`option_id`='41')";
        $sql[] = "UPDATE `{$prefix}options` SET `option_id`='95', `option_name`='widget_archives', `option_value`='a:1:{s:12:\"_multiwidget\";i:1;}', `autoload`='yes' WHERE (`option_id`='95')";
        $sql[] = "UPDATE `{$prefix}options` SET `option_id`='78', `option_name`='widget_categories', `option_value`='a:1:{s:12:\"_multiwidget\";i:1;}', `autoload`='yes' WHERE (`option_id`='78');";
        $sql[] = "UPDATE `{$prefix}options` SET `option_id`='96', `option_name`='widget_meta', `option_value`='a:1:{s:12:\"_multiwidget\";i:1;}', `autoload`='yes' WHERE (`option_id`='96');";
        $sql[] = "UPDATE `{$prefix}options` SET `option_id`='94', `option_name`='widget_recent-comments', `option_value`='a:1:{s:12:\"_multiwidget\";i:1;}', `autoload`='yes' WHERE (`option_id`='94');";
        $sql[] = "UPDATE `{$prefix}options` SET `option_id`='93', `option_name`='widget_recent-posts', `option_value`='a:1:{s:12:\"_multiwidget\";i:1;}', `autoload`='yes' WHERE (`option_id`='93');";
        $sql[] = "UPDATE `{$prefix}options` SET `option_id`='92', `option_name`='widget_search', `option_value`='a:1:{s:12:\"_multiwidget\";i:1;}', `autoload`='yes' WHERE (`option_id`='92');";
        $sql[] = "INSERT INTO `{$prefix}posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES ('6', '1', '2017-11-13 07:33:55', '2017-11-13 07:33:55', '', 'HOMEPAGE', '', 'publish', 'closed', 'closed', '', 'homepage', '', '', '2017-11-13 07:33:55', '2017-11-13 07:33:55', '', '0', '{$siteurl}/?page_id=6', '0', 'page', '', '0');";
        $sql[] = "INSERT INTO `{$prefix}posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES ('9', '1', '2017-11-13 07:38:30', '2017-11-13 07:38:30', '', 'PERSONAL STATEMENT', '', 'publish', 'closed', 'closed', '', 'personal-statement', '', '', '2017-11-13 07:38:30', '2017-11-13 07:38:30', '', '0', '{$siteurl}/?page_id=9', '0', 'page', '', '0');";
        $sql[] = "INSERT INTO `{$prefix}posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES ('11', '1', '2017-11-13 07:39:45', '2017-11-13 07:39:45', '', 'REPORTS', '', 'publish', 'closed', 'closed', '', 'reports', '', '', '2017-11-13 07:39:45', '2017-11-13 07:39:45', '', '0', '{$siteurl}/?page_id=11', '0', 'page', '', '0');";
        $sql[] = "INSERT INTO `{$prefix}posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES ('13', '1', '2017-11-13 07:40:53', '2017-11-13 07:40:53', '', 'CV', '', 'publish', 'closed', 'closed', '', 'cv', '', '', '2017-11-13 07:40:53', '2017-11-13 07:40:53', '', '0', '{$siteurl}/?page_id=13', '0', 'page', '', '0');";
        $sql[] = "UPDATE `{$prefix}options` SET `option_id`='53', `option_name`='show_on_front', `option_value`='page', `autoload`='yes' WHERE (`option_id`='53');";
        $sql[] = "UPDATE `{$prefix}options` SET `option_id`='84', `option_name`='page_on_front', `option_value`='6', `autoload`='yes' WHERE (`option_id`='84');";
        $sql[] = "INSERT INTO `{$prefix}posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES ('14', '1', '2017-11-14 07:01:32', '0000-00-00 00:00:00', ' ', '', '', 'draft', 'closed', 'closed', '', '', '', '', '2017-11-14 07:01:32', '0000-00-00 00:00:00', '', '0', '{$siteurl}/?p=14', '1', 'nav_menu_item', '', '0');";
        $sql[] = "INSERT INTO `{$prefix}posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES ('15', '1', '2017-11-14 07:01:32', '0000-00-00 00:00:00', ' ', '', '', 'draft', 'closed', 'closed', '', '', '', '', '2017-11-14 07:01:32', '0000-00-00 00:00:00', '', '0', '{$siteurl}/?p=15', '1', 'nav_menu_item', '', '0');";
        $sql[] = "INSERT INTO `{$prefix}posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES ('16', '1', '2017-11-14 07:01:32', '0000-00-00 00:00:00', ' ', '', '', 'draft', 'closed', 'closed', '', '', '', '', '2017-11-14 07:01:32', '0000-00-00 00:00:00', '', '0', '{$siteurl}/?p=16', '1', 'nav_menu_item', '', '0');";
        $sql[] = "INSERT INTO `{$prefix}posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES ('17', '1', '2017-11-14 07:01:32', '0000-00-00 00:00:00', ' ', '', '', 'draft', 'closed', 'closed', '', '', '', '', '2017-11-14 07:01:32', '0000-00-00 00:00:00', '', '0', '{$siteurl}/?p=17', '1', 'nav_menu_item', '', '0');";
        $sql[] = "INSERT INTO `{$prefix}posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES ('18', '1', '2017-11-14 07:01:32', '0000-00-00 00:00:00', ' ', '', '', 'draft', 'closed', 'closed', '', '', '', '', '2017-11-14 07:01:32', '0000-00-00 00:00:00', '', '0', '{$siteurl}/?p=18', '1', 'nav_menu_item', '', '0');";
        $sql[] = "INSERT INTO `{$prefix}posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES ('19', '1', '2017-11-14 07:01:32', '0000-00-00 00:00:00', ' ', '', '', 'draft', 'closed', 'closed', '', '', '', '', '2017-11-14 07:01:32', '0000-00-00 00:00:00', '', '0', '{$siteurl}/?p=19', '1', 'nav_menu_item', '', '0');";
        $sql[] = "INSERT INTO `{$prefix}posts` VALUES (21, 1, '2018-1-15 02:51:59', '2018-1-15 02:51:59', '[rk-pb template_id=\'1\' category=\'Outside\' order=\'ASC\' posts_per_page=\'99\']', 'COMMUNITY ENGAGEMENT', '', 'publish', 'closed', 'closed', '', 'outside-the-classroom', '', '', '2018-1-15 10:20:53', '2018-1-15 10:20:53', '', 0, '{$siteurl}/?page_id=21', 0, 'page', '', 0);";
        $sql[] = "INSERT INTO `{$prefix}posts` VALUES (23, 1, '2018-1-15 02:52:10', '2018-1-15 02:52:10', ' ', '', '', 'publish', 'closed', 'closed', '', '23', '', '', '2018-1-15 02:52:10', '2018-1-15 02:52:10', '', 0, '{$siteurl}/?p=23', 5, 'nav_menu_item', '', 0);";
        $sql[] = "UPDATE `{$prefix}posts` SET `ID`='14', `post_author`='1', `post_date`='2017-11-14 07:04:04', `post_date_gmt`='2017-11-14 07:04:04', `post_content`=' ', `post_title`='', `post_excerpt`='', `post_status`='publish', `comment_status`='closed', `ping_status`='closed', `post_password`='', `post_name`='14', `to_ping`='', `pinged`='', `post_modified`='2017-11-14 07:04:04', `post_modified_gmt`='2017-11-14 07:04:04', `post_content_filtered`='', `post_parent`='0', `guid`='{$siteurl}/?p=14', `menu_order`='1', `post_type`='nav_menu_item', `post_mime_type`='', `comment_count`='0' WHERE (`ID`='14');";
        $sql[] = "UPDATE `{$prefix}posts` SET `ID`='15', `post_author`='1', `post_date`='2017-11-14 07:04:04', `post_date_gmt`='2017-11-14 07:04:04', `post_content`=' ', `post_title`='', `post_excerpt`='', `post_status`='publish', `comment_status`='closed', `ping_status`='closed', `post_password`='', `post_name`='15', `to_ping`='', `pinged`='', `post_modified`='2017-11-14 07:04:04', `post_modified_gmt`='2017-11-14 07:04:04', `post_content_filtered`='', `post_parent`='0', `guid`='{$siteurl}/?p=15', `menu_order`='4', `post_type`='nav_menu_item', `post_mime_type`='', `comment_count`='0' WHERE (`ID`='15');";
        $sql[] = "UPDATE `{$prefix}posts` SET `ID`='17', `post_author`='1', `post_date`='2017-11-14 07:04:04', `post_date_gmt`='2017-11-14 07:04:04', `post_content`=' ', `post_title`='', `post_excerpt`='', `post_status`='publish', `comment_status`='closed', `ping_status`='closed', `post_password`='', `post_name`='17', `to_ping`='', `pinged`='', `post_modified`='2017-11-14 07:04:04', `post_modified_gmt`='2017-11-14 07:04:04', `post_content_filtered`='', `post_parent`='0', `guid`='{$siteurl}/?p=17', `menu_order`='2', `post_type`='nav_menu_item', `post_mime_type`='', `comment_count`='0' WHERE (`ID`='17');";
        $sql[] = "UPDATE `{$prefix}posts` SET `ID`='18', `post_author`='1', `post_date`='2017-11-14 07:04:04', `post_date_gmt`='2017-11-14 07:04:04', `post_content`=' ', `post_title`='', `post_excerpt`='', `post_status`='publish', `comment_status`='closed', `ping_status`='closed', `post_password`='', `post_name`='18', `to_ping`='', `pinged`='', `post_modified`='2017-11-14 07:04:04', `post_modified_gmt`='2017-11-14 07:04:04', `post_content_filtered`='', `post_parent`='0', `guid`='{$siteurl}/?p=18', `menu_order`='3', `post_type`='nav_menu_item', `post_mime_type`='', `comment_count`='0' WHERE (`ID`='18');";
        $sql[] = "INSERT INTO `{$prefix}terms` (`term_id`, `name`, `slug`, `term_group`) VALUES ('2', 'Menu 1', 'menu-1', '0');";
        $sql[] = "INSERT INTO `{$prefix}terms` (`term_id`, `name`, `slug`, `term_group`) VALUES ('3', 'Outside', 'outside', '0');";
        $sql[] = "INSERT INTO `{$prefix}term_taxonomy` (`term_taxonomy_id`, `term_id`, `taxonomy`, `description`, `parent`, `count`) VALUES ('2', '2', 'nav_menu', '', '0', '4');";
        $sql[] = "INSERT INTO `{$prefix}term_taxonomy` (`term_taxonomy_id`, `term_id`, `taxonomy`, `description`, `parent`, `count`) VALUES ('3', '3', 'category', '', '0', '0');";
        $sql[] = "INSERT INTO `{$prefix}term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES ('14', '2', '0');";
        $sql[] = "INSERT INTO `{$prefix}term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES ('15', '2', '0');";
        $sql[] = "INSERT INTO `{$prefix}term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES ('17', '2', '0');";
        $sql[] = "INSERT INTO `{$prefix}term_relationships` (`object_id`, `term_taxonomy_id`, `term_order`) VALUES ('18', '2', '0');";
        $sql[] = "INSERT INTO `{$prefix}term_relationships` VALUES (23, 2, 0);";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('2', '14', '_menu_item_type', 'post_type');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('3', '14', '_menu_item_menu_item_parent', '0');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('4', '14', '_menu_item_object_id', '6');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('5', '14', '_menu_item_object', 'page');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('6', '14', '_menu_item_target', '');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('7', '14', '_menu_item_classes', 'a:1:{i:0;s:0:\"\";}');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('8', '14', '_menu_item_xfn', '');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('9', '14', '_menu_item_url', '');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('11', '15', '_menu_item_type', 'post_type');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('12', '15', '_menu_item_menu_item_parent', '0');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('13', '15', '_menu_item_object_id', '13');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('14', '15', '_menu_item_object', 'page');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('15', '15', '_menu_item_target', '');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('16', '15', '_menu_item_classes', 'a:1:{i:0;s:0:\"\";}');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('17', '15', '_menu_item_xfn', '');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('18', '15', '_menu_item_url', '');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('20', '16', '_menu_item_type', 'post_type');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('21', '16', '_menu_item_menu_item_parent', '0');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('22', '16', '_menu_item_object_id', '6');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('23', '16', '_menu_item_object', 'page');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('24', '16', '_menu_item_target', '');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('25', '16', '_menu_item_classes', 'a:1:{i:0;s:0:\"\";}');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('26', '16', '_menu_item_xfn', '');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('27', '16', '_menu_item_url', '');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('28', '16', '_menu_item_orphaned', '1510642892');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('29', '17', '_menu_item_type', 'post_type');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('30', '17', '_menu_item_menu_item_parent', '0');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('31', '17', '_menu_item_object_id', '9');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('32', '17', '_menu_item_object', 'page');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('33', '17', '_menu_item_target', '');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('34', '17', '_menu_item_classes', 'a:1:{i:0;s:0:\"\";}');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('35', '17', '_menu_item_xfn', '');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('36', '17', '_menu_item_url', '');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('38', '18', '_menu_item_type', 'post_type');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('39', '18', '_menu_item_menu_item_parent', '0');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('40', '18', '_menu_item_object_id', '11');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('41', '18', '_menu_item_object', 'page');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('42', '18', '_menu_item_target', '');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('43', '18', '_menu_item_classes', 'a:1:{i:0;s:0:\"\";}');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('44', '18', '_menu_item_xfn', '');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('45', '18', '_menu_item_url', '');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('47', '19', '_menu_item_type', 'post_type');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('48', '19', '_menu_item_menu_item_parent', '0');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('49', '19', '_menu_item_object_id', '2');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('50', '19', '_menu_item_object', 'page');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('51', '19', '_menu_item_target', '');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('52', '19', '_menu_item_classes', 'a:1:{i:0;s:0:\"\";}');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('53', '19', '_menu_item_xfn', '');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('54', '19', '_menu_item_url', '');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES ('55', '19', '_menu_item_orphaned', '1510642892');";
        $sql[] = "INSERT INTO `{$prefix}options` (`option_name`, `option_value`, `autoload`) VALUES ('theme_mods_slimwriter', 'a:2:{s:18:\"custom_css_post_id\";i:-1;s:18:\"nav_menu_locations\";a:1:{s:7:\"primary\";i:2;}}', 'yes');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` VALUES (59, 23, '_menu_item_type', 'post_type');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` VALUES (60, 23, '_menu_item_menu_item_parent', '0');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` VALUES (61, 23, '_menu_item_object_id', '21');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` VALUES (62, 23, '_menu_item_object', 'page');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` VALUES (63, 23, '_menu_item_target', '');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` VALUES (64, 23, '_menu_item_classes', 'a:1:{i:0;s:0:\"\";}');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` VALUES (65, 23, '_menu_item_xfn', '');";
        $sql[] = "INSERT INTO `{$prefix}postmeta` VALUES (66, 23, '_menu_item_url', '');";
        $sql[] = "DELETE FROM `{$prefix}posts` where ID in ('1','2');";
        $sql[] = "DELETE FROM `{$prefix}posts` where post_title='Auto Draft';";

        foreach($sql as $v) {
            $wpdb->query($v);
        }
    }
} 