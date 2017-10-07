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

    public function checkTeacherAccount($account) {
        global $wpdb;

        $prefix = $wpdb->get_blog_prefix(1);

        $sql = "select ID from " . $prefix . "users where user_login='" . addslashes($account) . "'";
        $result = $wpdb->get_row($sql, ARRAY_A);

        if($result['ID']) {
            if(user_can($result['ID'], _GB_CAP) && user_can($result['ID'], _GB_TEACHER_ROLE)) {
                return $result['ID'];
            }
        }

        return;
    }

    public function getClassTeacher($class_id) {
        global $wpdb;

        $prefix = $wpdb->get_blog_prefix(1);
        $class_id = ceil($class_id);
        $sql = 'select u.* from '.$prefix. 'users u
                inner join '.$prefix.'usermeta um on um.user_id=u.ID where um.meta_key="teach_class" and um.meta_value='.$class_id;
        return $wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * 为新的老师添加指定班级内学生的所有博客访问权限
     * @param $original_teacher_id
     * @param $new_teacher_id
     * @param $class_id
     */
    public function updateTeacherCabForClassStudents( $new_teacher_id, $class_id) {
        global $wpdb;
        $prefix = $wpdb->get_blog_prefix(1);

        $teacher_id = ceil($new_teacher_id);
        $class_id = ceil($class_id);
        if(!$teacher_id || !$class_id) {
            return;
        }

        $sql = 'select um1.user_id, um2.meta_value from '.$prefix.'usermeta um1
              inner join '.$prefix.'usermeta um2 on um1.user_id=um2.user_id
              where um1.meta_key="study_class" and um2.meta_key="primary_blog" and um1.meta_value='.$class_id;
        $results = $wpdb->get_results($sql, ARRAY_A);

        for($i=0;$i<count($results);$i++) {
            $v = $results[$i];
            add_user_to_blog($v['meta_value'], $teacher_id, _GB_TEACHER_ROLE);
        }
    }


    public function removeTeacherCab($teacher_id, $remove_class = false) {
        global $wpdb;
        $prefix = $wpdb->get_blog_prefix(1);

        $teacher_id = ceil($teacher_id);
        $sql = "select meta_value from {$prefix}usermeta where meta_key='teach_class' and user_id=$teacher_id";
        $result =  $wpdb->get_row($sql, ARRAY_A);
        $class_id = $result['meta_value'];

        $blog_arr = get_user_meta($teacher_id, "primary_blog");

        $sql = 'select um1.user_id, um2.meta_value from '.$prefix.'usermeta um1
              inner join '.$prefix.'usermeta um2 on um1.user_id=um2.user_id
              where um1.meta_key="study_class" and um2.meta_key="primary_blog" and um1.meta_value='.$class_id;
        $results = $wpdb->get_results($sql, ARRAY_A);
        for($i=0;$i<count($results);$i++) {
            $v = $results[$i];

            if($blog_arr[0] == $v['meta_value']) {
                continue;
            }

            if($v['meta_value']>1) {
                remove_user_from_blog($teacher_id, $v['meta_value']);
            }
        }

        if($remove_class) {
            delete_user_meta($teacher_id, "teach_class");
        }
    }

    public function addUserBlogCabForTeacher($teacher_id, $user_id) {
        $blog_id = get_user_meta($user_id, "primary_blog");
        if($blog_id[0] > 1 && $teacher_id > 0) {
            add_user_to_blog($blog_id[0], $teacher_id, _GB_TEACHER_ROLE);
        }
    }

    public function removeUserBlogCabFromTeacher($teacher_id, $user_id) {
        $blog_id = get_user_meta($user_id, "primary_blog");
        if($blog_id[0] > 1 && $teacher_id > 0) {
            remove_user_from_blog($teacher_id, $blog_id[0]);
        }
    }

    public function getUserIdByAccount($account) {
        global $wpdb;
        $prefix = $wpdb->get_blog_prefix(1);

        $sql = "select * from {$prefix}users where user_login='".addslashes($account)."'";
        $result = $wpdb->get_row($sql, ARRAY_A);
        return $result['ID'];
    }

    public function setClassStudentCounts($class_id) {
        global $wpdb;
        $prefix = $wpdb->get_blog_prefix(1);

        $class_id = ceil($class_id);
        $sql = "select count(*) counts from {$prefix}usermeta where meta_key='study_class' and meta_value=$class_id";
        $result = $wpdb->get_row($sql, ARRAY_A);
        $sql = "update gb_class set student_count=".$result['counts']." where id=$class_id";
        $wpdb->query($sql);
    }
} 