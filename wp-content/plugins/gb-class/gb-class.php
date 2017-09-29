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
            update_user_meta($id, "teach_class", $classObj->getKeyId());
            $gbClass->updateTeacherCabForClassStudents($original_teacher_id, $id, $class_id);
        }
    }

    if($_REQUEST['action'] == "save_class") {
        require_once ("saveClass.php");
    }

    $page = (ceil($_REQUEST['page'] == 0))?1:ceil($_REQUEST['page']);
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


    $classObj = $gbClass->instanceObj($class_id);

    $students = $myClass->getClassStudents($class_id);

    require_once "view/my-class.php";
}


