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

add_action( 'admin_menu', 'class_menu' );

function class_menu() {
    add_menu_page("班级管理", "班级管理", "administrator", "gb_class_manage", "gb_class_manage", '', 2);
    add_submenu_page("gb_class_manage",'我的班级','我的班级', 'publish_posts', 'gb_my_class', 'gb_my_class');
}

function gb_class_manage() {
    echo "class manage";
}
function gb_my_class() {
    $myClass = new MyClass();
    if($_REQUEST['action'] == "create_blog") {
        $user_id = $_REQUEST['user_id'];
        if($myClass->canCreateBlog($user_id)) {
            $userObj = get_user_to_edit($user_id);
            $myClass->createBlog($user_id, $userObj->get("user_login"), $userObj->get("display_name"));
        }
    }

    $teacher_meta = get_user_meta(get_current_user_id());
    $class_id = $teacher_meta["teach_class"][0];

    $students = $myClass->getClassStudents($class_id);

    require_once "view/my-class.php";
}

