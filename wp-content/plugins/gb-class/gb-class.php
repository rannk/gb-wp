<?php
/**
 * Plugin Name: student class manage
 * Description: student class manage
 * Version: 1.0
 * Author: Rannk Deng
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 2, as published by the Free Software Foundation.  You may NOT assume
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */
require_once "libs/MyClass.php";
require_once "libs/lang.php";
require_once "libs/GbClass.php";
require_once "libs/PageOp.php";

define("_GB_PAGE_NUM", 10);
define("_GB_CAP", 'gb_my_class'); //编辑操作的权限名称
define("_GB_TEACHER_ROLE", 'editor');

//插件启用时检测数据库
register_activation_hook( __FILE__, 'gb_class_install');
function gb_class_install() {
    global $wpdb;
    $sql = "CREATE TABLE IF NOT EXISTS `gb_class` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `class_name` varchar(200) NOT NULL DEFAULT '',
      `class_tag` varchar(100) NOT NULL,
      `student_count` int(11) NOT NULL DEFAULT '0',
      `class_status` tinyint(4) NOT NULL DEFAULT '1',
      PRIMARY KEY (`id`),
      UNIQUE KEY `class_tag` (`class_tag`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4";
    $wpdb->query($sql);
}

add_action( 'admin_menu', 'class_menu' );

function class_menu() {
    add_menu_page(_l("Class Manage"), _l("Class Manage"), "administrator", "gb_class_manage", "gb_class_manage", '', 2);
    add_submenu_page("gb_class_manage", _l("My Class"), _l("My Class"), _GB_CAP, 'gb_my_class', 'gb_my_class');
    add_submenu_page("gb_class_manage", _l("Students"), _l("Students"), "administrator", 'gb_students', 'gb_students');
}

function gb_class_manage() {
    $gbClass = new GbClass();

    if($_REQUEST['action'] == "change_teacher") {
        $ret_msg = array();

        $account = trim($_REQUEST['teacher_account']);
        $class_id = ceil($_REQUEST['class_id']);
        $original_teacher_id = "";

        if(!$account) {
            $ret_msg[] = _l("Please fill in the teacher's login account");
        }

        if(count($ret_msg) == 0) {
            $id = $gbClass->checkTeacherAccount($account);
            if(!$id) {
                $ret_msg[] = _l("This is not teacher's account!");
            }
        }

        if(count($ret_msg) == 0) {
            $classObj = $gbClass->instanceObj($class_id);
            if(!$classObj->actived()) {
                $ret_msg[] = _l("The class is not exist!");
            }
            $original_teacher_info = $gbClass->getClassTeacher($class_id);
            $original_teacher_id = $original_teacher_info['ID'];
        }



        if(count($ret_msg) == 0) {
            $gbClass->removeTeacherCab($id);
            $gbClass->removeTeacherCab($original_teacher_id, true);
            $gbClass->updateTeacherCabForClassStudents($id, $class_id);
            update_user_meta($id, "teach_class", $classObj->getKeyId());
            gotoUrl("/wp-admin/admin.php?page=gb_class_manage&_s_page=" . $_REQUEST['_s_page']);
        }
    }

    if($_REQUEST['action'] == "add_student") {
        $account = trim($_REQUEST['student_account']);
        $class_id = ceil($_REQUEST['class_id']);

        if(!$account) {
            $ret_msg[] = _l("Please fill in the student's login account");
        }

        if(count($ret_msg) == 0) {
            $classObj = $gbClass->instanceObj($class_id);
            if(!$classObj->actived()) {
                $ret_msg[] = _l("The class is not exist!");
            }
        }

        if(count($ret_msg) == 0) {
            $user_id = $gbClass->getUserIdByAccount($account);
            if(!$user_id) {
                $ret_msg[] = _l("Please fill in the right account");
            }
        }

        if(count($ret_msg) == 0) {
            $original_class_arr = get_user_meta($user_id, "study_class");
            update_user_meta($user_id, "study_class", $class_id);
            $gbClass->setClassStudentCounts($class_id);
            $original_teacher_info = $gbClass->getClassTeacher($class_id);
            $original_teacher_id = $original_teacher_info['ID'];
            $gbClass->addUserBlogCabForTeacher($original_teacher_id, $user_id);

            // original class operation
            $gbClass->setClassStudentCounts($original_class_arr[0]);
            $original_teacher_info = $gbClass->getClassTeacher($original_class_arr[0]);
            $original_teacher_id = $original_teacher_info['ID'];
            $gbClass->removeUserBlogCabFromTeacher($original_teacher_id, $user_id);
            gotoUrl("/wp-admin/admin.php?page=gb_class_manage&_s_page=" . $_REQUEST['_s_page']);
        }
    }

    if( $_REQUEST['class_id'] > 0) {
        $myClass = new MyClass();
        $class_id = $_REQUEST['class_id'];
        $classObj = $gbClass->instanceObj($class_id);

        if($_REQUEST['action'] == "set_visit_pwd") {
            require_once("visitPwd.php");
        }

        $students = $myClass->getClassStudents($class_id);

        if(!$classObj->actived()) {
            gotoUrl("/wp-admin/admin.php?page=gb_class_manage");
            return;
        }

        if($_REQUEST['action'] == "update_class") {
            require_once ("saveClass.php");
        }

        require_once "view/list-class.php";
        return;
    }

    if($_REQUEST['action'] == "save_class") {
        require_once ("saveClass.php");
    }

    $page = (ceil($_REQUEST['_s_page'] == 0))?1:ceil($_REQUEST['_s_page']);
    $start_num = ($page-1) * _GB_PAGE_NUM;
    $class_lists = $gbClass->getLists($start_num, _GB_PAGE_NUM);
    $pageOp = new PageOp($page, $gbClass->getListsTotal(), _GB_PAGE_NUM, "");
    require_once "view/manage-class.php";
}

function gb_my_class() {
    $myClass = new MyClass();
    $gbClass = new GbClass();

    $teacher_meta = get_user_meta(get_current_user_id());
    $class_id = $teacher_meta["teach_class"][0];

    if($_REQUEST['action'] == "create_blog") {
        $user_id = $_REQUEST['user_id'];
        if($myClass->canCreateBlog($user_id)) {
            $userObj = get_user_to_edit($user_id);
            $myClass->createBlog($user_id, $userObj->get("user_login"), $userObj->get("display_name"));
        }
    }

    $class_save_result = false;
    if($_REQUEST['action'] == "save_class") {
        require_once ("saveClass.php");
        if($class_save_result) {
            $gbClass->changeClassTeacher(get_current_user_id());
        }
    }

    if($_REQUEST['action'] == "set_visit_pwd") {
        if($myClass->canChangeVisitPwd($_REQUEST['user_id'])) {
            require_once("visitPwd.php");
        }
    }


    $classObj = $gbClass->instanceObj($class_id);

    $students = $myClass->getClassStudents($class_id);

    require_once "view/my-class.php";
}


function gb_students() {
    $myClass = new MyClass();

    if($_REQUEST['action'] == "create_blog") {
        $user_id = $_REQUEST['user_id'];
        if($myClass->canCreateBlog($user_id)) {
            $userObj = get_user_to_edit($user_id);
            if($myClass->setTeacherIdByStudentId($user_id)) {
                $myClass->createBlog($user_id, $userObj->get("user_login"), $userObj->get("display_name"));
            }else {
                $ret_msg[] = "create blog was failed! please try again.";
            }
        }else {
            $ret_msg[] = "you don't have permission to do this.";
        }
    }


    if($_REQUEST['action'] == "set_visit_pwd") {
        if($myClass->canChangeVisitPwd($_REQUEST['user_id'])) {
            require_once("visitPwd.php");
        }else {
            $ret_msg[] = "you don't have permission to do this.";
        }
    }

    $page = (ceil($_REQUEST['_s_page'] == 0))?1:ceil($_REQUEST['_s_page']);

    $results = $myClass->getStudentsByCond($_REQUEST, $page, _GB_PAGE_NUM);

    $pageOp = new PageOp($page, $results['total'], _GB_PAGE_NUM, $results['cond_query']);

    require_once "view/gb-students.php";
}

function gotoUrl($url) {
    echo "<script>document.location='" . $url . "'</script>";
}

add_action( 'wp', 'need_password' );

function need_password() {
    $gbClass = new GbClass();

    $confirm_window = <<<html
<form action="" method="post" id="post_pwd">
<input id="given_pwd" name="given_pwd" value="" type="hidden">
</form>
<script language="javascript">

function show_pwd() {
    var pwd = prompt("{message}");
    if(pwd) {
        document.getElementById("given_pwd").value = pwd;
        document.getElementById("post_pwd").submit();
    }else {
        show_pwd();
    }
}
show_pwd();
</script>
html;

    // main blog don't need pwd
    $blog_id = get_current_blog_id();
    if($blog_id == 1)
        return;

    if($_SESSION['blog_' . $blog_id. "_sec"]) {
        return;
    }

    $user_visit_pwd = $gbClass->getVisitPwdByBlogId($blog_id);

    // if user not set pwd
    if(!$user_visit_pwd) {
        return;
    }

    // 用户自己博客不需要密码
    $user_id = get_current_user_id();

    if($user_id) {
        $user_blog_id = get_user_meta($user_id, "primary_blog");
        if($user_blog_id[0] == $blog_id) {
            return;
        }
    }

    if(!$_POST['given_pwd']) {
        $confirm_window = str_replace("{message}", _l("Please fill in the password for visiting this blog!"), $confirm_window);
        echo $confirm_window;
        exit;
    }

    if($_POST['given_pwd'] == $user_visit_pwd) {
        $_SESSION['blog_' . $blog_id. "_sec"] = $user_visit_pwd;
    }else {
        $confirm_window = str_replace("{message}", _l("Password was wrong, please fill again!"), $confirm_window);
        echo $confirm_window;
        exit;
    }
}
add_action( 'admin_menu', 'set_visit_password_menu' );

function set_visit_password_menu() {
    add_submenu_page("profile.php", _l("Blog Visit Password"), _l("Blog Visit Password"), "contributor", "user_set_visit_password", "user_set_visit_password");

}

function user_set_visit_password() {
    $user_id = get_current_user_id();
    if($_POST['Submit']) {
        $password = trim($_POST['visit_pwd']);
        update_user_meta($user_id, "gb_visit_pwd", $password);
    }
    $password = get_user_meta($user_id, "gb_visit_pwd");
    require_once("view/set-visit-password.php");
}
