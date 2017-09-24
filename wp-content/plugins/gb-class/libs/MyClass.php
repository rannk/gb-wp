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
    /**
     * 判断是否有权限可以创建blog
     * @param $user_id
     * @return bool
     */
    public function canCreateBlog($user_id) {
        if(!current_user_can("create_blog")) {
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
            if ( ! is_super_admin( $user_id ) && !(get_user_option( 'primary_blog', $user_id )>1)) {
                update_user_option( $user_id, 'primary_blog', $id, true );
                add_user_to_blog($id, $user_id, 'contributor');
                //添加老师id到blog
                add_user_to_blog($id, $this->teacher_id, 'editor');

            }
        } else {
            wp_die( $id->get_error_message() );
        }
    }

    public function getClassStudents($class_id) {
        global $wpdb;

        $class_id = ceil($class_id);
        if($class_id == 0)
            return;

        $sql = "select u.* from " . $wpdb->prefix."users u inner join
            "  . $wpdb->prefix ."usermeta um1 on um1.user_id=u.ID
            where um1.meta_key='study_class' and um1.meta_value=" . $class_id;
    }
} 