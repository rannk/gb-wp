<?php
require_once "DbSet.php";

class GbClass {

    var $classObj;

    public function __construct() {

    }

    public function getLists() {
        global $wpdb;

        $prefix = $wpdb->get_blog_prefix(1);

        $sql = "select * from gb_class where class_status=1";

        $results = $wpdb->get_results($sql, ARRAY_A);

        for($i=0;$i<count($results);$i++) {
            $ids .= $results[$i]['id'] . ",";
        }

        if($ids) {
            $ids = substr($ids, 0, -1);
            $sql = "select u.*, um.meta_value from " . $prefix . "users
                inner join " . $prefix . "usermeta um on um.user_id=users.ID
                where um.meta_key='teach_class' and um.meta_value in ($ids)";
            $t_results = $wpdb->get_results($sql, ARRAY_A);
            for($i=0;$i<count($t_results);$i++) {
                $v = $t_results[$i];
                for($j=0;$j<count($results);$j++) {
                    if($v['meta_value'] == $results[$i]['id']) {
                        $results[$i] = array_merge($results[$i], $v);
                        break;
                    }
                }
            }
        }

        return $results;

    }

    public function instanceObj($id) {
        if(!is_object($this->classObj) || !$this->classObj->actived()) {
            $this->classObj = new DbSet("gb_class", "id", $id);
        }

        return $this->classObj;
    }

    public function checkTagUnique($tag) {
        global $wpdb;

        $sql = "select id from gb_class where class_tag='".addslashes($tag)."'";
        if($this->classObj->actived()) {
            $sql .= " and id != " . $this->classObj->getKeyId();
        }

        $results = $wpdb->get_results($sql, ARRAY_A);

        if(count($results) > 0) {
            return true;
        }

        return false;
    }

    public function changeClassTeacher($id) {
        if($this->classObj->actived()) {
            update_user_meta($id, "teach_class", $this->classObj->getKeyId());
        }
    }
} 