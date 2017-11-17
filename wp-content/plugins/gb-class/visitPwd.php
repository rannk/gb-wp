<?php
/**
 * User: Rannk
 * @email rannk@163.com
 * Date: 2017/11/17
 * Time: 10:52
 */

$student_id = ceil($_REQUEST['user_id']);
$visit_pwd = trim($_REQUEST['visit_pwd']);

if($student_id == 0) {
    $ret_msg[] = _l("You didn't select the student");
}

if(!$visit_pwd) {
    $ret_msg[] = _l("please fill in the visit password");
}

if(count($ret_msg) == 0) {
    update_user_meta($student_id, "gb_visit_pwd", $visit_pwd);
}
