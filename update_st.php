<?php
/**
 * update students blog, add a new community engagement page
 */

define('DB_NAME', 'wordpress_t1');

/** MySQL数据库用户名 */
define('DB_USER', 'root');

/** MySQL数据库密码 */
define('DB_PASSWORD', '');

/** MySQL主机 */
define('DB_HOST', 'localhost');

$con = @mysql_connect(DB_HOST,DB_USER,DB_PASSWORD);
if (!$con)
{
    die('Could not connect: ' . mysql_error());
}

mysql_select_db(DB_NAME, $con);

$sql = "select um2.meta_value blog from wp_usermeta um1 inner join wp_usermeta um2 on um1.user_id=um2.user_id 
where um1.meta_key='study_class' and um2.meta_key='primary_blog' and um2.meta_value>1";

$result = mysql_query($sql, $con);
while($v = mysql_fetch_array($result)) {
    $table = 'wp_' . $v['blog'] ."_terms";
    $prefix = 'wp_' . $v['blog'] . "_";
    if(!mysql_num_rows(mysql_query("SHOW TABLES LIKE '". $table."'"))==1) {
        continue;
    }

    $sql = "select * from $table where slug='outside'";
    if(mysql_num_rows(mysql_query($sql))>0) {
        continue;
    }

    $sql = "INSERT INTO `{$prefix}terms` (`name`, `slug`, `term_group`) VALUES ('Outside', 'outside', '0');";
    mysql_query($sql);
    $cate_id = mysql_insert_id();
    $sql = "INSERT INTO `{$prefix}term_taxonomy` (`term_taxonomy_id`, `term_id`, `taxonomy`, `description`, `parent`, `count`) VALUES ($cate_id, $cate_id, 'category', '', '0', '0');";
    mysql_query($sql);

    $sql = "INSERT INTO `{$prefix}posts` (`post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES ( 1, '2018-1-15 02:51:59', '2018-1-15 02:51:59', '[rk-pb template_id=\'1\' category=\'Outside\' order=\'ASC\' posts_per_page=\'99\']', 'COMMUNITY ENGAGEMENT', '', 'publish', 'closed', 'closed', '', 'outside-the-classroom', '', '', '2018-1-15 10:20:53', '2018-1-15 10:20:53', '', 0, '{$siteurl}/?page_id=21', 0, 'page', '', 0);";
    mysql_query($sql);
    $post_id = mysql_insert_id();
    $sql = "INSERT INTO `{$prefix}posts` (`post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES (1, '2018-1-15 02:52:10', '2018-1-15 02:52:10', ' ', '', '', 'publish', 'closed', 'closed', '', '23', '', '', '2018-1-15 02:52:10', '2018-1-15 02:52:10', '', 0, '{$siteurl}/?p=23', 5, 'nav_menu_item', '', 0);";
    mysql_query($sql);
    $menu_id = mysql_insert_id();
    mysql_query("update {$prefix}posts set post_name=$menu_id where ID=$menu_id");
    $sql_[] = "INSERT INTO `{$prefix}postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES ($menu_id, '_menu_item_type', 'post_type');";
    $sql_[] = "INSERT INTO `{$prefix}postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES ($menu_id, '_menu_item_menu_item_parent', '0');";
    $sql_[] = "INSERT INTO `{$prefix}postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES ($menu_id, '_menu_item_object_id', '{$post_id}');";
    $sql_[] = "INSERT INTO `{$prefix}postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES ($menu_id, '_menu_item_object', 'page');";
    $sql_[] = "INSERT INTO `{$prefix}postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES ($menu_id, '_menu_item_target', '');";
    $sql_[] = "INSERT INTO `{$prefix}postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES ($menu_id, '_menu_item_classes', 'a:1:{i:0;s:0:\"\";}');";
    $sql_[] = "INSERT INTO `{$prefix}postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES ($menu_id, '_menu_item_xfn', '');";
    $sql_[] = "INSERT INTO `{$prefix}postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES ($menu_id, '_menu_item_url', '');";

    foreach($sql_ as $ss) {
        mysql_query($ss);
    }

    $sql = "INSERT INTO `{$prefix}term_relationships` VALUES ($menu_id, 2, 0);";
    mysql_query($sql);

    echo "blog " . $v['blog'] . " updated \n";
}
