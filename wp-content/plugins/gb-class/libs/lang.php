<?php
function _l($str) {
    $o_str = strtolower(trim($str));
    $o_str = preg_replace("/[^a-z0-9A-Z_\- ]/i", "", $o_str);
    if(defined("_WRITE_LANG") && _WRITE_LANG == true) {
        $_write_lang = true;
    }

    $current_lang = str_replace("\"", "", get_language_attributes());
    $current_lang = str_replace("lang=", "", $current_lang);

    if(file_exists(__DIR__ . "/../lang/" . $current_lang . ".php")) {
        include __DIR__ . "/../lang/" . $current_lang . ".php";
        for($i=0;$i<count($_langmsg);$i++) {
            $c_str = strtolower(trim($_langmsg[$i][0]));
            $c_str = preg_replace("/[^a-z0-9A-Z_\- ]/i", "", $c_str);
            if($c_str == $o_str) {
                if($_langmsg[$i][1]) {
                    return $_langmsg[$i][1];
                }else {
                    return $str;
                }

                break;
            }
        }

        // 保存未翻译的文字到lang
        if($_write_lang) {
            $fp = fopen(__DIR__ . "/../lang/" . $current_lang . ".php", "a");
            fwrite($fp, "\n" . '$_langmsg[] = array("'.$str.'", "");');
        }
    }

    return $str;
}