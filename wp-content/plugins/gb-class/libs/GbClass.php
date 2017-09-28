<?php
require_once "DbSet.php";

class GbClass {

    var $classObj;

    public function __construct() {

    }

    public function getLists($start = 0, $number = 0) {
        global $wpdb;

        $prefix = $wpdb->get_blog_prefix(1);

        $sql = "select c.*,t1.* from gb_class c
                left join (select u.*, um.meta_value class_id from ".$prefix."users u inner join ".$prefix."usermeta um on um.user_id=u.ID where um.meta_key='teach_class') t1 on t1.class_id=c.id
                where c.class_status=1";

        if($number > 0) {
            $sql .= " limit " . ceil($start) . ",$number";
        }
        $results = $wpdb->get_results($sql, ARRAY_A);

        return $results;

    }

    public function getListsTotal() {
        global $wpdb;
        $prefix = $wpdb->get_blog_prefix(1);
        $sql = "select count(c.id) counts from gb_class c
                left join (select u.*, um.meta_value class_id from ".$prefix."users u inner join ".$prefix."usermeta um on um.user_id=u.ID where um.meta_key='teach_class') t1 on t1.class_id=c.id
                where c.class_status=1";

        $results = $wpdb->get_row($sql, ARRAY_A);
        return $results['counts'];
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