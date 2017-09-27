<?php
$classObj = $gbClass->instanceObj($class_id);

$class_data['class_name'] = trim($_REQUEST['class_name']);
$class_data['class_tag'] = trim($_REQUEST['class_tag']);

$class_data['class_status'] = ":skip:";
$ret_msg = array();

if(!$class_data['class_name']) {
    $ret_msg[] = _l("the class name are not fill in");
}

if(!$class_id && !$class_data['class_tag']) {
    $ret_msg[] = _l("the class tag are not fill in");
}

if($gbClass->checkTagUnique(trim($_REQUEST['class_tag']))) {
    $ret_msg[] = _l("the class tag already exists!");
}

if(count($ret_msg) == 0) {
    $classObj->setVars($class_data);
    $class_save_result = $classObj->update();
    $class_id = $classObj->getKeyId();
}

