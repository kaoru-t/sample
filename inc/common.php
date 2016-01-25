<?php
/*
 * 開発用ツールズサービス クライアント用WEBアプリケーション
 */
/*
 * このファイルは全てのphpファイルから最初に実行されて、
 * 以下の機能を提供します。
 *   1. 基本的な変数を初期化します。
 *   2. ユーザー認証を行います。
 */
session_start();
require_once("sql_devtools.php");
require_once("string_utilities.php");

// スステムログディレクトリパス
define("LOG_DIR", "C:/Project/php/devtools/rel/log/");
//define("LOG_DIR", "/var/www/uni/log/");

// 1ページあたりの件数
define("PAGE_COUNT", 5);

$GLOBALS['ERR_STATUS'] = array('0' => '未検証', '1' => 'OK', '2' => 'NG', '3' => 'NG対応');


/*
 * パスワードのチェックを行う。
 */
function check_password($user_id, $pass) {
    //$tmp = phpinfo();
    //error_exit($tmp);
    $arr = find_user($user_id, $pass);

    if ($arr == false) {
        return false;
    } else {
        //session_start();
        if (!isset($_SESSION['user_id'])) {
            //global $user_id;
            //$_SESSION["perm"] = $arr["perm"];
            $_SESSION['user_info'] = $arr;
            //同じページ内で処理する場合(?)、session_register では登録した変数が空になる
            //session_register(perm);
            //$user = $_SESSION["user_id"];
            //error_exit($user);
        }
        return true;
    }
}

function login_new($auth_user, $auth_pass) {
    if (check_password($auth_user, $auth_pass)) {
        $user = login_check();
        setcookie("aid", $auth_user, 60 * 60 * 10); //クッキー名、保存内容、時間を指定 
        redirect("/dev/top.php");
        //print "ログイン成功";
        //redirect("02-01.php3?" . SID);
    } else {
        $error_msg = array();
        $error_msg[] = "パスワードが違います。";
        return $error_msg;
    }
}

function login_check() {
    // セッション変数 user_info が無ければ
    if (isset($_SESSION['user_info'])) {
        //print "登録されています。<br>";
        $user = $_SESSION['user_info'];
        return $user;
    } else {
        return false;
    }

}

/*
 * 指定したメッセージ表示を表示して処理を終了する。
 */
function error_msg_exit($msg) {
echo <<<EOT
<html>
    <head>
        <title>Developer Tools</title>
        <meta http-equiv="Content-Type" content="text/html; charset=euc-jp">
    </head>
    <body>
EOT;
        show_error_msg($msg);
echo <<<EOT
    </body>
</html>
EOT;
exit;
}

/*
 * 指定したメッセージ表示を表示して処理を終了する。
 */
function error_exit($msg) {
echo <<<EOT
<html>
    <head>
        <title>開発用サービス</title>
        <meta http-equiv="Content-Type" content="text/html; charset=euc-jp">
    </head>
    <body>
        {$msg}
    </body>
</html>
EOT;
exit;
}

/*
 * メッセージ表示
 */
function jdialog($msg) {
    header("CONTENT-STYLE-TYPE: text/html; charset=euc-jp");
echo <<<J_DIALOG
<script type="test/javascript">
<!--
    alert("{$msg}");
    history.back();
//-->>
</script>
J_DIALOG;
}

/**
 * リダイレクト
 */
function redirect($url) {
    //error_exit($url);
    if (!strstr($url, ':')) {
        if (substr($url, 0, 1) != '/') {
            $url = dirname($_SERVER['REQUEST_URI']) . "/" . $url;
        }
        $port = ($_SERVER['SERVER_PORT'] == 80 ? "" : ":".$_SERVER['SERVER_PORT']);
        $url = "http://" . $_SERVER['SERVER_NAME'] . $port . $url;
    }
    header("Location: $url");
    exit;
}


define("DEBUG",1);

function debugprint($msg){
    if (DEBUG) echo "debug:".$msg;
}

/*
 * 引数のディレクトリの 1つ上の階層までのフルパスを返す
 */
function get_parent_path($dir) {

    $path = getcwd();
    if (strpos($path, $dir) !== false) {
        $path = substr($path, 0, strrpos($path, $dir));
    }
    return $path;
}


function show_error_msg_output($msgs) {
    if (isset($msgs) && is_array($msgs)) {

        foreach($msgs as $key => $msg) {
            if (is_array($msg)) {
                show_error_msg_output($msg);
            } else {
                print "<b><font color=\"#FF0000\">・$msg ($key)</font></b><br>";
            }
        }
    }
}

/*
 * 引数で渡されたエラーメッセージを表示する
 */
function show_error_msg($msg) {
    if (isset($msg) && count($msg) > 0) {
        print "<ul id=\"caution\">";
        foreach($msg as $tmp) {
            print "<li>" . $tmp . "</li>";
        }
        print "</ul>";
    }
}

/*
 * 引数名のパラメータ値を返す
 */
function get_parameter($name, $value = NULL) {

    if (isset($_REQUEST[$name])) {
        return $_REQUEST[$name];
    } else {
        return $value;
    }
}

/*
 * データを整形する。
 */
function normalizeData($data) {

    if ($data) {
        // alphabetを英字に、全角スペースを半角に、半角カタカナを全角に。
        $data = mb_convert_kana($data, "asKV", "euc-jp");
        // alphabetを英字に、全角スペースを半角に、全角を半角カタカナに。
        //$data = mb_convert_kana($data, "ask", "euc-jp");
        // 前後のスペースを取る。
        $data = rtrim(ltrim($data));
        // 連続したスペースを一つに
        $data = preg_replace("/ +/i", " ", $data);
    }
    return $data;
}

function logout() {

    if (isset($_SESSION['user_info'])) {
        unset($_SESSION['user_info']);
        redirect("/dev/login.php");
    } else {
        $err_msg = "ログアウトに失敗しました。";
        error_exit($err_msg);
    }
}


function format_date($date) {
    list($yyyy, $mm, $dd) = explode('-', $date);

    return $yyyy . "年" . (int)$mm . "月" . (int)$dd . "日";
}


function show_caution($msg) {
    echo "<br><span id=\"caution\">$msg</span>";
}

function validate_product_info($product_id, &$productInfo) {
    $err_msg = array();

    if (strlen($product_id) > 0 && is_numeric($product_id)) {
        $productInfo = load_product($product_id);
        if ($productInfo == false) {
            array_push($err_msg, "プロダクトID が不正です。");
        }
    } else {
        array_push($err_msg, "プロダクトID が不正です。");
    }
    return $err_msg;
}

function validate_tool_info($tool_id, &$toolInfo) {
    $err_msg = array();

    if (strlen($tool_id) > 0 && is_numeric($tool_id)) {
        $toolInfo = load_tool($tool_id);
        if ($toolInfo == false) {
            array_push($err_msg, "ツールID が不正です。");
        }
    } else {
        array_push($err_msg, "ツールID が不正です。");
    }
    return $err_msg;
}


function validate_proctools_info(&$tool_id, &$product_id) {
    $err_msg = array();
    
    $product_id = get_parameter('product_id');
    $tool_id = get_parameter('tool_id');

    $productInfo;
    $err_msg_temp = validate_product_info($product_id, $productInfo);
    
    foreach($err_msg_temp as $tmp) {
        array_push($err_msg, $tmp);
    }

    $toolInfo;
    $err_msg_temp = validate_tool_info($tool_id, $toolInfo);

    foreach($err_msg_temp as $tmp) {
        array_push($err_msg, $tmp);
    }

    //if (!isset($err_msg)) {
        $_REQUEST['product_id'] = $product_id;
        $_REQUEST['tool_id'] = $tool_id;
    //}
    
    return $err_msg;
}

/*
 * pager back
 */
function pager_back($condition) {
    $wcnt = count($condition['target_obj']);
    if ($condition['target_obj'] !== FALSE && $wcnt > 0) {
        $page = $condition['page'];
        $product_id = $condition['product_id'];
        $tool_id = $condition['tool_id'];
        $tmpCnt = $condition['tmpCnt'];
        $page_name = $condition['page_name'];
        $list_count = $condition['list_count'];
        
        $param;
        $param .= (isset($condition[FLD_BUG_REPORTER_ID]) && strlen(($condition[FLD_BUG_REPORTER_ID])) > 0) ? "&" . FLD_BUG_REPORTER_ID . "=" . $condition[FLD_BUG_REPORTER_ID] : "";
        $param .= (isset($condition[FLD_PERSON_IN_CHARGE_ID]) && strlen(($condition[FLD_PERSON_IN_CHARGE_ID])) > 0) ? "&" . FLD_PERSON_IN_CHARGE_ID . "=" . $condition[FLD_PERSON_IN_CHARGE_ID] : "";
        $param .= (isset($condition[FLD_MODIFIER_ID]) && strlen(($condition[FLD_MODIFIER_ID])) > 0) ? "&" . FLD_MODIFIER_ID . "=" . $condition[FLD_MODIFIER_ID] : "";
        $param .= (isset($condition[FLD_CHECKER_ID]) && strlen(($condition[FLD_CHECKER_ID])) > 0) ? "&" . FLD_CHECKER_ID . "=" . $condition[FLD_CHECKER_ID] : "";
        $param .= (isset($condition[FLD_STATUS]) && strlen(($condition[FLD_STATUS])) > 0) ? "&" . DATA_ERROR . FLD_STATUS . "=" . $condition[FLD_STATUS] : "";
        $param .= (isset($condition["error_level"]) && strlen(($condition["error_level"])) > 0) ? "&" . DATA_ERROR . "error_level=" . $condition["error_level"] : "";
        $param .= (isset($condition[FLD_TESTER_ID]) && strlen(($condition[FLD_TESTER_ID])) > 0) ? "&" . FLD_TESTER_ID . "=" . $condition[FLD_TESTER_ID] : "";
        $param .= (isset($condition[FLD_PRODUCT_VER_ID]) && strlen(($condition[FLD_PRODUCT_VER_ID])) > 0) ? "&" . FLD_PRODUCT_VER . "=" . $condition[FLD_PRODUCT_VER_ID] : "";
        
        //print "<div id=\"ch_list\">";
        if ($page > 1) {
            $tmpPage = $page - 1;
            print "<a href=\"/dev/$page_name?product_id=$product_id&tool_id=$tool_id&cnt=0&page=1$param\">先頭</a> | ";
            print "<a href=\"/dev/$page_name?product_id=$product_id&tool_id=$tool_id&cnt=$tmpCnt&page=$tmpPage$param\"><< 前の" . $list_count . "件</a> | "; 
        } else {
            $list_count = $condition['list_count'];
            print "<span id=\"not_exist\">先頭 | << 前の" . $list_count . "件</span> | ";
        }
        //print "</div>";
    } else {
        //$wcnt = 0;
    }
}

/*
 * pager forward
 */
function pager_forward($condition, $wcnt) {

    //print "<div id=\"ch_list\">";

    if ($condition['target_obj'] !== FALSE && count($condition['target_obj']) > 0) {
        $page = $condition['page'];
        $product_id = $condition['product_id'];
        $tool_id = $condition['tool_id'];
        $tmpCnt = $condition['cnt'];
        $page_name = $condition['page_name'];
        $list_count = $condition['list_count'];
        
        $wcnt += $list_count * ($page - 1);
        $tmpPage = $page + 1;

        $param;
        $param .= (isset($condition[FLD_BUG_REPORTER_ID]) && strlen(($condition[FLD_BUG_REPORTER_ID])) > 0) ? "&" . FLD_BUG_REPORTER_ID . "=" . $condition[FLD_BUG_REPORTER_ID] : "";
        $param .= (isset($condition[FLD_PERSON_IN_CHARGE_ID]) && strlen(($condition[FLD_PERSON_IN_CHARGE_ID])) > 0) ? "&" . FLD_PERSON_IN_CHARGE_ID . "=" . $condition[FLD_PERSON_IN_CHARGE_ID] : "";
        $param .= (isset($condition[FLD_MODIFIER_ID]) && strlen(($condition[FLD_MODIFIER_ID])) > 0) ? "&" . FLD_MODIFIER_ID . "=" . $condition[FLD_MODIFIER_ID] : "";
        $param .= (isset($condition[FLD_CHECKER_ID]) && strlen(($condition[FLD_CHECKER_ID])) > 0) ? "&" . FLD_CHECKER_ID . "=" . $condition[FLD_CHECKER_ID] : "";
        $param .= (isset($condition[FLD_STATUS]) && strlen(($condition[FLD_STATUS])) > 0) ? "&" . DATA_ERROR . FLD_STATUS . "=" . $condition[FLD_STATUS] : "";
        $param .= (isset($condition["error_level"]) && strlen(($condition["error_level"])) > 0) ? "&" . DATA_ERROR . "error_level=" . $condition["error_level"] : "";
        $param .= (isset($condition[FLD_TESTER_ID]) && strlen(($condition[FLD_TESTER_ID])) > 0) ? "&" . FLD_TESTER_ID . "=" . $condition[FLD_TESTER_ID] : "";
        $param .= (isset($condition[FLD_PRODUCT_VER_ID]) && strlen(($condition[FLD_PRODUCT_VER_ID])) > 0) ? "&" . FLD_PRODUCT_VER . "=" . $condition[FLD_PRODUCT_VER_ID] : "";
        
        $rec_count = $condition['target_obj'][0]['mod_rec_count'];
        $total_rec_count = $condition['target_obj'][0]['mod_total_rec_count'];
        
        //$puls_value = $rec_count == PAGE_COUNT ? 1 : 0;

        $last_offset = ceil(($total_rec_count - $list_count) / $list_count) * $list_count;
        $last_page = ceil($total_rec_count / $list_count);
        /*
        print("total: $total_rec_count <br />");
        print("商：" . $total_rec_count / PAGE_COUNT . "<br />");
        if (($total_rec_count % PAGE_COUNT) > 0) {
            // 切り上げ
            $last_page = ceil($total_rec_count / PAGE_COUNT);
        } else {
            $last_page = ($total_rec_count / PAGE_COUNT) + 1;
        }
        */
        print "<a href=\"/dev/$page_name?product_id=$product_id&tool_id=$tool_id&cnt=$wcnt&page=$tmpPage$param\">次の" . $list_count . "件 >></a>";
        print " | <a href=\"/dev/$page_name?product_id=$product_id&tool_id=$tool_id&cnt=$last_offset&page=$last_page$param\">末尾</a>";
    } else {
        $list_count = $condition['list_count'];
        print "<span id=\"not_exist\">次の" . $list_count . "件 >> | 末尾</span>";
    }
    //print "</div>";
}

//function csvField($field, $forceQuot = false) {
function csvField($rs, $field, $col, $forceQuot = false) {


    $typeName = pg_field_type($rs, $col);

    $needQuot = $forceQuot;
    
    switch($typeName) {
    case 'varchar':
        // fall throuph 
    case 'bpchar':
        // fall throuph
    case 'text':
        // fall throuph 
    case 'timestamp':
        // fall throuph
    case 'date':
        // fall throuph
    case 'time':
        $needQuot = true;
        break;
    }

    $field = strval($field);
    $field = preg_replace("/\"/i", "\"\"", $field);
    if($needQuot || strpos($field, ",") !== false) {
        $field = "\"" . $field . "\"";
    }

    return $field;

}
?>
