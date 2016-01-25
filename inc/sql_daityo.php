<?php
/*
 * 開発用ツールズサービス クライアント用WEBアプリケーション
 *
 */
require_once("sql_common.php");
require_once("sql_devtools.php");

function find_user($auth_id, $pass) {

    $dbh = get_connection($GLOBALS['db_access_info_daityo']);
    if ($dbh == false) {
        error_exit("データベースに接続できません。理由： $php_errormsg");
    } else {
        //error_exit("データベースに接続できました。");
    }

    $sql = "select employeecode, employeenamekana, employeenamekanji, password, categorycode, inoutflag, email" .
            " from employee" .
            " where (employeecode = " . bind_param($auth_id, PRAM_STRING) . ")" .
            " and (password = " . bind_param($pass, PRAM_STRING) . ")";

    output_log ($sql);
    $res = pg_query($dbh, $sql);
    if ($res == false) {
        error_exit("SQLの実行に失敗しました。理由： $php_errormsg");
    } else {
        $num = pg_num_rows($res);
        if ($num == 0) {
            return false;
        } else {
            $arr = pg_fetch_array($res, 0, PGSQL_ASSOC);
            return $arr;
        }
    }
}


function list_users() {
    $dbh = get_connection($GLOBALS['db_access_info_daityo']);
    if ($dbh == false) {
        error_exit("データベースに接続できません。理由： $php_errormsg");
    } else {
        //error_exit("データベースに接続できました。");
    }

    $sql = "select employeecode, employeenamekana, employeenamekanji, password, categorycode, inoutflag, email" .
           " from employee" .
           " where (status <> " . bind_param(1, PRAM_STRING) . ")" .
           " and categorycode in ('005', '007')" .
           " and (employeecode <> '998' and employeecode <> '999')" .
           " order by employeecode";

    $res = pg_query($dbh, $sql);
    if ($res == false) {
        error_exit("SQLの実行に失敗しました。理由： $php_errormsg");
    } else {
        $list = pg_fetch_all($res);
        //$list = array();
        //while($rc = pg_fetch_array($res)) {
        //    array_push($list, $rc);
        //}        
        return $list;
    }
}
