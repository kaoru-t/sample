<?php
require_once("sql_common.php");
/*
 * 開発用ツールズサービス クライアント用WEBアプリケーション
 *
 */
/*
 * このファイルはデータベースを扱うの機能を提供します。
 */

define("SQL_INSERT_TITLE_INFO", "insert into t_title_info" .
                                " (client_id, theater_id, title_id, info_id, info_type, info_value)" .
                                " values (");

define("PRAM_STRING", 1);
define("PRAM_NOT_STRING", 0);
define("MODE_ADD", 0);
define("MODE_EDIT", 1);

define("PRODUCT_UK", 1);
define("PRODUCT_UM_C", 2);
define("PRODUCT_UM_J", 3);


/*
 * 検索条件の作成
 */
function create_search_sql($searchInfo) {
    /*** 検索条件 ***/

    //var_dump($searchInfo);
    //$fiels = array(FLD_OCCURRENCE_PLACE, FLD_SCREEN_ID, FLD_ERR_CONTENT, FLD_CAUTION, FLD_VERSION, FLD_CORRECTIONS);
    
    // 空白 [ \t\n\r\f] または全角空白で分割
    $key_words = mb_convert_encoding($searchInfo["key_word"], "EUC-JP", "auto");
   
    //print "$key_words";
    // キーワードを整形
    $key_words = normalizeData($searchInfo["key_word"]);
    // 全角スペースを半角スペースに変換。(↑その他の変換も含め上記メソッドを使用)
    //$key_words  = str_replace('　', ' ', $key_words);
    //$keyword = mb_convert_kana($keyword, 's'); <-- 全角スペースを半角スペースに変換するにはこの方法でもOK
    $words = preg_split("/[\s,]+/", $key_words, -1, PREG_SPLIT_NO_EMPTY);
    
    //var_dump($words);
    $sql_param_search = "";
    //print "count: " . count($words);
    if (count($words) > 0 && $words[0] <> null) { 
        foreach($searchInfo as $key => $val) {
            if ($key == "key_word") {continue;}
            if (strlen($sql_param_search) > 0) {$sql_param_search .= " OR ";}
            $sql_param_word = "";
            foreach ($words as $word) {
                if (strlen($sql_param_word) > 0) {$sql_param_word .= " AND ";}
                $sql_param_word .= "$key " . bind_like($word);
            }
            $sql_param_search .= "(" . $sql_param_word . ")";
        }
    }
    return $sql_param_search;
}

function create_download_sql($formData) {

    $sql ="";

    //SQL 構築
    
    if ($formData['tool_id'] == 1) {
        // 不具合表リスト

//        $sql = "SELECT t1.bug_id, t4.product_ver_name, t1.bug_reporter_id, t5.employeenamekanji AS bug_reporter" . "\n" .
//               ", t1.occurrence_date, t1.occurrence_place" . "\n" .
//               ", t1.screen_id, t1.error_content, t1.caution, t1.version, t1.error_level, t2.level_name, t1.release_time" . "\n" .
//               ", t1.person_in_charge_id, t6.employeenamekanji AS person_in_charge, t1.modifier_id, t7.employeenamekanji AS modifier, t1.modify_date" . "\n" .
//               ", t1.status, t3.status_name" . "\n" .
//                /*
//                , CASE WHEN t1.status = 0 THEN '未検証'
//                       WHEN t1.status = 1 THEN 'OK'
//                       WHEN t1.status = 2 THEN 'NG'
//                       WHEN t1.status = 3 THEN 'NG対応'
//                       ELSE '-'
//                       END as status_name
//                */
//                ", t1.corrections, t1.check_date, t1.checker_id, t8.employeenamekanji AS checker" . "\n" .
//                ", t1.product_version" . "\n" .
        
        $sql = "SELECT t1.bug_id, t4.product_ver_name, t5.employeenamekanji AS bug_reporter" . "\n" .
               ", t1.occurrence_date, t1.occurrence_place" . "\n" .
               ", t1.screen_id, t1.error_content, t1.caution, t1.version, t2.level_name" . "\n" .
               ", t6.employeenamekanji AS person_in_charge, t7.employeenamekanji AS modifier, t1.modify_date" . "\n" .
               ", t3.status_name" . "\n" .
                /*
                , CASE WHEN t1.status = 0 THEN '未検証'
                       WHEN t1.status = 1 THEN 'OK'
                       WHEN t1.status = 2 THEN 'NG'
                       WHEN t1.status = 3 THEN 'NG対応'
                       ELSE '-'
                       END as status_name
                */
                ", t1.corrections, t1.check_date, t8.employeenamekanji AS checker" . "\n" .
               " FROM t_bug_list t1" . "\n" .
               " INNER JOIN t_error_level t2 ON t2.level_id = t1.error_level" . "\n" .
               " INNER JOIN t_status t3 ON t3.status_id = t1.status" . "\n" .
               " LEFT OUTER JOIN t_product_version t4 on t4.product_id = t1.product_id and t4.product_ver_id = t1.product_version" . "\n" .
               " LEFT OUTER JOIN" . "\n" .
                 "(" . "\n" .
                   "SELECT t2.* FROM dblink('conn1', 'select employeecode, employeenamekanji from employee')" . "\n" .
                   " AS t2(employeecode character(3), employeenamekanji character varying(20))" . "\n" .
                 ") t5" . "\n" .
               " ON t5.employeecode = t1.bug_reporter_id" . "\n" .
               " LEFT OUTER JOIN" . "\n" .
                 "(" . "\n" .
                   "SELECT t2.* FROM dblink('conn1', 'select employeecode, employeenamekanji from employee')" . "\n" .
                   " AS t2(employeecode character(3), employeenamekanji character varying(20))" . "\n" .
                 ") t6" . "\n" .
               " ON t6.employeecode = t1.person_in_charge_id" . "\n" .
               " LEFT OUTER JOIN" . "\n" .
                 "(" . "\n" .
                   "SELECT t2.* FROM dblink('conn1', 'select employeecode, employeenamekanji from employee')" . "\n" .
                   " AS t2(employeecode character(3), employeenamekanji character varying(20))" . "\n" .
                 ") t7" . "\n" .
               " ON t7.employeecode = t1.modifier_id" . "\n" .
               " LEFT OUTER JOIN" . "\n" .
                 "(" . "\n" .
                   "SELECT t2.* FROM dblink('conn1', 'select employeecode, employeenamekanji from employee')" . "\n" .
                   " AS t2(employeecode character(3), employeenamekanji character varying(20))" . "\n" .
                 ") t8" . "\n" .
               " ON t8.employeecode = t1.checker_id" . "\n" .
               " WHERE t1.product_id = " . bind_param($formData['product_info'][FLD_PRODUCT_ID], PRAM_NOT_STRING) . "\n" .
               " AND t1.tool_id = " . bind_param($formData[FLD_TOOLTYPE_ID], PRAM_NOT_STRING) . "\n";
              
          if (isset($formData['product_ver_id']) && $formData['product_ver_id'] == 1) {
              $sql .= " AND product_ver_id = " . bind_param($formData[FLD_PRODUCT_VER_ID], PRAM_NOT_STRING) . "\n";
          }
          
          if ($formData['is_period'] == 1) {
              $sql .= " AND occurrence_date >= to_timestamp(" . bind_param($formData['from_date'], PRAM_STRING) . ", 'YYYY-MM-DD')" . "\n" .
                      " AND occurrence_date <= to_timestamp(" . bind_param($formData['to_date'], PRAM_STRING) . ", 'YYYY-MM-DD')" . "\n";
          }
          $sql .= " ORDER BY bug_id;";
          
    } else {
        // 修正表リスト
/*
        $sql = "SELECT t1.modify_id, t3.product_ver_name, t1.regist_date, t1.division, t1.cause_modify, t1.repository, t1.project_name" . "\n" .
               ", t2.class_name, t2.method_name, t2.source_comment" . "\n" .
               ", t1.unit_test, t1.modifier_id, t4.employeenamekanji AS modifier, t1.reg_repositor_date, t1.overall_test, t1.tester_id" . "\n" .
               ", t5.employeenamekanji AS tester, t1.test_date" . "\n" .
*/
        $sql = "SELECT t1.modify_id, t3.product_ver_name, t1.regist_date, t1.division, t1.cause_modify, t1.repository, t1.project_name" . "\n" .
               ", t2.class_name, t2.method_name, t2.source_comment" . "\n" .
               ", t1.unit_test, t4.employeenamekanji AS modifier, t1.reg_repositor_date, t1.overall_test" . "\n" .
               ", t5.employeenamekanji AS tester, t1.test_date" . "\n" .
               " FROM t_modify_list t1" . "\n" .
               " INNER JOIN t_modify_source t2 ON t2.modify_id = t1.modify_id" . "\n" .
               " LEFT OUTER JOIN t_product_version t3 ON t3.product_id = t1.product_id AND t3.product_ver_id = t1.product_version" . "\n" .
               " LEFT OUTER JOIN" . "\n" .
                 "(" . "\n" .
                    "SELECT t2.* FROM dblink('conn1', 'select employeecode, employeenamekanji from employee')" . "\n" .
                    " AS t2(employeecode character(3), employeenamekanji character varying(20))" . "\n" .
                 ") t4 ON t4.employeecode = t1.modifier_id" . "\n" .
               " LEFT OUTER JOIN" . "\n" .
                 "(" . "\n" .
                    "SELECT t2.* FROM dblink('conn1', 'select employeecode, employeenamekanji from employee')" . "\n" .
                    " AS t2(employeecode character(3), employeenamekanji character varying(20))" . "\n" .
                 ") t5 ON t5.employeecode = t1.tester_id" . "\n" .
               " LEFT OUTER JOIN" . "\n" .
                 "(" . "\n" .
                    "SELECT t2.* FROM dblink('conn1', 'select employeecode, employeenamekanji from employee')" . "\n" .
                    " AS t2(employeecode character(3), employeenamekanji character varying(20))" . "\n" .
                 ") t6 ON t6.employeecode = t1.editor_id" . "\n" .
               " WHERE t1.product_id = " . bind_param($formData['product_info'][FLD_PRODUCT_ID], PRAM_NOT_STRING) . "\n" .
               " AND t1.tool_id = " . bind_param($formData[FLD_TOOLTYPE_ID], PRAM_NOT_STRING) . "\n";
               
          if (isset($formData['product_ver_id']) && $formData['product_ver_id'] == 1) {
              $sql .= " AND product_ver_id = " . bind_param($formData[FLD_PRODUCT_VER_ID], PRAM_NOT_STRING) . "\n";
          }
          
          if ($formData['is_period'] == 1) {
              $sql .= " AND regist_date >= to_timestamp(" . bind_param($formData['from_date'], PRAM_STRING) . ", 'YYYY-MM-DD')" . "\n" .
                      " AND regist_date <= to_timestamp(" . bind_param($formData['to_date'], PRAM_STRING) . ", 'YYYY-MM-DD')" . "\n";
          }
          $sql .= " ORDER BY modify_id;";               
    
    }
          
    return $sql;
}

/*
 * プロダクトテーブルより、プロダクトID に一致するプロダクト情報を返す
 */
function load_product($productid) {
    $dbh = get_connection($GLOBALS['db_access_info_devtools']);
    if ($dbh == false) {
        error_exit("データベースに接続できません。理由： $php_errormsg");
    } else {
        //error_exit("データベースに接続できました。");
    }
    
    $sql = "select product_id, product_name, product_kana" .
           " from t_product" .
           " where product_id = " . bind_param($productid, PRAM_NOT_STRING) .
           " order by product_id";

    $res = pg_query($dbh, $sql);
    if ($res == false) {
        error_exit("SQLの実行に失敗しました。理由： $php_errormsg");
    } else {
        $num = pg_num_rows($res);
        if ($num == 0) {
            return false;
        } else {
            $arr = pg_fetch_array($res, 0, PGSQL_ASSOC);
            //error_exit("ccc " . $arr[FLD_THEATER_NAME]);
            return $arr;
        }
    }
}


/*
 * プロダクトテーブルより、プロダクト情報のリストを返す
 */
function list_product() {
    $dbh = get_connection($GLOBALS['db_access_info_devtools']);
    if ($dbh == false) {
        error_exit("データベースに接続できません。理由： $php_errormsg");
    } else {
        //error_exit("データベースに接続できました。");
    }
    
    $sql = "select product_id, product_name, product_kana" .
           " from t_product" . 
           " order by product_id";

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

/*
 * プロダクトバージョンテーブルより、プロダクトバージョン情報のリストを返す
 */
function list_product_ver($productid) {
    $dbh = get_connection($GLOBALS['db_access_info_devtools']);
    if ($dbh == false) {
        error_exit("データベースに接続できません。理由： $php_errormsg");
    } else {
        //error_exit("データベースに接続できました。");
    }

    $sql = "select product_id, product_ver_id, product_ver_name, product_ver_kana" .
           " from t_product_version" .
           " where product_id = " . bind_param($productid, PRAM_NOT_STRING) .
           " order by product_ver_id";

    $res = pg_query($dbh, $sql);
    if ($res == false) {
        error_exit("SQLの実行に失敗しました。理由： $php_errormsg");
    } else {
        $list = pg_fetch_all($res);
        return $list;
    }
}


/*
 * ツールテーブルより、ツールID に一致するツール情報を返す
 */
function load_tool($toolid) {
    $dbh = get_connection($GLOBALS['db_access_info_devtools']);
    if ($dbh == false) {
        error_exit("データベースに接続できません。理由： $php_errormsg");
    } else {
        //error_exit("データベースに接続できました。");
    }
    
    $sql = "select tool_id, tool_name" .
           " from t_tool" .
           " where tool_id = ". bind_param($toolid, PRAM_NOT_STRING) .
           " order by tool_id";

    $res = pg_query($dbh, $sql);
    if ($res == false) {
        error_exit("SQLの実行に失敗しました。理由： $php_errormsg");
    } else {
        $num = pg_num_rows($res);
        if ($num == 0) {
            return false;
        } else {
            $arr = pg_fetch_array($res, 0, PGSQL_ASSOC);
            //error_exit("ccc " . $arr[FLD_THEATER_NAME]);
            return $arr;
        }
    }
}

/*
 * ツールテーブルより、ツールリストを返す
 */
function list_tool() {
    $dbh = get_connection($GLOBALS['db_access_info_devtools']);
    if ($dbh == false) {
        error_exit("データベースに接続できません。理由： $php_errormsg");
    } else {
        //error_exit("データベースに接続できました。");
    }
    
    $sql = "select tool_id, tool_name" .
           " from t_tool" .
           " order by tool_id";

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

/*
 * エラーレベルリストを返す
 */
function list_level() {
    $dbh = get_connection($GLOBALS['db_access_info_devtools']);
    if ($dbh == false) {
        error_exit("データベースに接続できません。理由： $php_errormsg");
    } else {
        //error_exit("データベースに接続できました。");
    }

    $sql = "select level_id, level_name" .
           " from t_error_level" .
           " order by level_id";

    $res = pg_query($dbh, $sql);
    if ($res == false) {
        error_exit("SQLの実行に失敗しました。理由： $php_errormsg");
    } else {
        $list = pg_fetch_all($res);
        return $list;
    }
}

/*
 * 不具合情報をロード
 */
function load_error($productid, $tool_id, $bug_id) {
    $dbh = get_connection($GLOBALS['db_access_info_devtools']);
    if ($dbh == false) {
        error_exit("データベースに接続できません。理由： $php_errormsg");
    } else {
        //error_exit("データベースに接続できました。");
    }

    // status は t_bug_list ではなく t_modify_list を参照するよう変更したため、t_bug_list のフィールドは削除
    // 代わりに、t_modify_list から参照
    $sql = "select t1.bug_id, t1.tool_id, t1.product_id, t1.bug_reporter_id, t1.occurrence_date, t1.occurrence_place" .
           ", t1.screen_id, t1.error_content, t1.caution, t1.version,t1. error_level, t1.release_time" .
           ", t1.person_in_charge_id, t1.modifier_id, t1.modify_date, t1.corrections, t1.check_date, t1.checker_id" .
           ", t1.product_version" .
           ", t2.level_name, t3.product_ver_name, t4.status" .
           " from t_bug_list t1" .
           " inner join t_error_level t2 on t2.level_id = t1.error_level" .
           " left outer join t_product_version t3 on t3.product_id = t1.product_id and t3.product_ver_id = t1.product_version" .
           " LEFT OUTER JOIN t_modify_list t4 on t4.bug_id = t1.bug_id and t4.product_id = t1.product_id" .
           " where t1.product_id = " . bind_param($productid, PRAM_NOT_STRING) .
           " and t1.tool_id = " . bind_param($tool_id, PRAM_NOT_STRING) .
           " and t1.bug_id = " . bind_param($bug_id, PRAM_NOT_STRING) .
           " order by t1.bug_id";

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
            //error_exit("ccc " . $arr[FLD_THEATER_NAME]);
            return $arr;
        }
    }
}

function download_list_error($condition) {

}

/*
 * 不具合票をリスト
 */
function list_error($condition) {
    $dbh = get_connection($GLOBALS['db_access_info_devtools']);
    if ($dbh == false) {
        error_exit("データベースに接続できません。理由： $php_errormsg");
    } else {
        //error_exit("データベースに接続できました。");
    }

    if (isset($condition[FLD_BUG_REPORTER_ID]) && strlen($condition[FLD_BUG_REPORTER_ID]) > 0) {
        $sql_param .= " AND t1.bug_reporter_id = " . bind_param($condition[FLD_BUG_REPORTER_ID], PRAM_STRING);
    }
    if (isset($condition[FLD_PERSON_IN_CHARGE_ID]) && strlen($condition[FLD_PERSON_IN_CHARGE_ID]) > 0) {
        $sql_param .= " AND t1.person_in_charge_id = " . bind_param($condition[FLD_PERSON_IN_CHARGE_ID], PRAM_STRING);
    }
    if (isset($condition[FLD_MODIFIER_ID]) && strlen($condition[FLD_MODIFIER_ID]) > 0) {
        $sql_param .= " AND t1.modifier_id = " . bind_param($condition[FLD_MODIFIER_ID], PRAM_STRING);
    }
    if (isset($condition[FLD_CHECKER_ID]) && strlen($condition[FLD_CHECKER_ID]) > 0) {
        $sql_param .= " AND t1.checker_id = " . bind_param($condition[FLD_CHECKER_ID], PRAM_STRING);
    }
    if (isset($condition[FLD_STATUS]) && strlen($condition[FLD_STATUS]) > 0) {
        $sql_param .= " AND t8.status = " . bind_param($condition[FLD_STATUS], PRAM_NOT_STRING);
    }    
    if (isset($condition["error_level"]) && strlen($condition["error_level"]) > 0) {
        $sql_param .= " AND t1.error_level = " . bind_param($condition["error_level"], PRAM_NOT_STRING);
    }
    if (isset($condition[FLD_PRODUCT_VER_ID]) && strlen($condition[FLD_PRODUCT_VER_ID]) > 0) {
        $sql_param .= " AND t1.product_version = " . bind_param($condition[FLD_PRODUCT_VER_ID], PRAM_NOT_STRING);
    }    

    /*** 検索条件 ***/
    $searchInfo = $condition["searchInfo"];
    $sql_search_param .= create_search_sql($searchInfo);
    $sql_param .= strlen($sql_search_param) > 0 ? " AND " . $sql_search_param : "";
/*    
    //var_dump($searchInfo);
    $fiels = array(FLD_OCCURRENCE_PLACE, FLD_SCREEN_ID, FLD_ERR_CONTENT, FLD_CAUTION, FLD_VERSION, FLD_CORRECTIONS);
    // 空白 [ \t\n\r\f] または全角空白で分割
    $words = preg_split("/[\s|　]+/", $searchInfo["key_word"]);
    //var_dump($words);
    $sql_param_search = "";
    //print "count: " . count($words);
    if (count($words) > 0 && $words[0] <> null) { 
        foreach($searchInfo as $key => $val) {
            if ($key == "key_word") {continue;}
            if (strlen($sql_param_search) > 0) {$sql_param_search .= " OR ";}
            $sql_param_word = "";
            foreach ($words as $word) {
                if (strlen($sql_param_word) > 0) {$sql_param_word .= " AND ";}
                $sql_param_word .= "$key " . bind_like($word);
            }
            $sql_param_search .= "(" . $sql_param_word . ")";
        }
        if (strlen($sql_param_search)) {$sql_param .= " AND " . $sql_param_search;}
    }
*/

    $sql_param_sub = " WHERE t1.product_id = " . bind_param($condition["product_id"], PRAM_NOT_STRING) .
                     " AND t1.tool_id = " . bind_param($condition["tool_id"], PRAM_NOT_STRING) .
                     $sql_param;

    $order = "";
    if ($condition["sort"] == 1) {
        $order = " DESC";
    }
    // status は t_bug_list ではなく t_modify_list を参照するよう変更したため、t_bug_list のフィールドは削除
    // 代わりに、t_modify_list から参照
    $sql = "SELECT t1.bug_id, t1.tool_id, t1.product_id, t1.bug_reporter_id, t1.occurrence_date, t1.occurrence_place" . "\n" .
                ", t1.screen_id, t1.error_content, t1.caution, t1.version, t1.error_level, t1.release_time" . "\n" .
                ", t1.person_in_charge_id, t1.modifier_id, t1.modify_date, t1.corrections, t1.check_date, t1.checker_id" . "\n" .
                ", t1.entry_time, t1.product_version" . "\n" .
                ", t2.level_name, t3.mod_total_rec_count, t5.mod_rec_count" . "\n" .
                ", t4.shipping_day, t4.title, t4.color" . "\n" .
                ", t7.product_ver_name" . "\n" .
                ", CASE WHEN t8.status IS NULL THEN 0" . "\n" .
                "  ELSE t8.status" . "\n" .
                "  END as status" . "\n" .
           " FROM t_bug_list t1" . "\n" .
           " INNER JOIN t_error_level t2 on t2.level_id = t1.error_level" . "\n" .
           " INNER JOIN (" . "\n" .
               " SELECT COUNT(t6.bug_id) as mod_rec_count, t6.product_id, t6.tool_id  FROM". "\n" .
                   " (SELECT t1.bug_id, t1.product_id, t1.tool_id  FROM t_bug_list t1" . "\n" .
                    " LEFT OUTER JOIN t_modify_list t8 on t8.bug_id = t1.bug_id and t8.product_id = t1.product_id" . "\n" .
                    $sql_param_sub .  "\n" .
                    " ORDER BY t1.bug_id" . $order . "\n" .
                    " LIMIT " . $condition['list_count'] . " OFFSET " . $condition["cnt"] . ") t6" . "\n" .
               " GROUP BY tool_id, product_id" .  "\n" .
           " ) t5" . "\n" .
           " ON t5.product_id = t1.product_id AND t5.tool_id = t1.tool_id" . "\n" .
           " INNER JOIN (" . "\n" .
                " SELECT count(bug_id) AS mod_total_rec_count, product_id, tool_id" . "\n" .
                " FROM t_bug_list" . "\n" .
                " GROUP BY tool_id, product_id" . "\n" .
           ") t3" . "\n" .
           " ON t3.product_id = t1.product_id AND t3.tool_id = t1.tool_id" . "\n" .
           " INNER JOIN (" . "\n" .
               " SELECT tA.shipping_day, tC.title, tC.color, tB.entry_time, tB.bug_id, tB.product_id  FROM t_bug_list tB" . "\n" .
               " LEFT OUTER JOIN (" . "\n" .
                   " SELECT MIN (tY.shipping_day) AS shipping_day, tX.entry_time, tX.bug_id, tX.product_id FROM t_bug_list tX" . "\n" .
                   " INNER JOIN t_send_out tY ON tY.product_id = tX.product_id" . "\n" .
                   " WHERE tY.shipping_day >= tX.entry_time" . "\n" .
                   " GROUP BY tX.entry_time, tX.bug_id, tX.product_id" . "\n" .
                   " ORDER BY tX.bug_id" . "\n" .
               " ) tA ON tA.bug_id = tB.bug_id AND tA.product_id = tB.product_id" . "\n" .
               " LEFT OUTER JOIN t_send_out tC" . "\n" .
               " ON tC.shipping_day = tA.shipping_day" . "\n" .
               " ORDER BY tB.bug_id" . "\n" .
           ") t4" . "\n" .
           " ON t4.product_id = t1.product_id AND t4.bug_id = t1.bug_id" . "\n" .
           " LEFT OUTER JOIN t_product_version t7 on t7.product_id = t1.product_id and t7.product_ver_id = t1.product_version" . "\n" .
           " LEFT OUTER JOIN t_modify_list t8 on t8.bug_id = t1.bug_id and t8.product_id = t1.product_id" . "\n";

    $sql_param = " WHERE t1.product_id = " . bind_param($condition["product_id"], PRAM_NOT_STRING) .
                 " AND t1.tool_id = " . bind_param($condition["tool_id"], PRAM_NOT_STRING) . $sql_param;
    $sql .= $sql_param;

    $sql .=  " ORDER BY t1.bug_id" . $order . " LIMIT " . $condition['list_count'] . " OFFSET " . $condition["cnt"];

//    pg_set_client_encoding($dbh, "UNICODE");
//    $encoding = pg_client_encoding($dbh);
//    print "Client encoding is: " . $encoding . "\n";

    //print($sql);
    output_log ($sql);
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


/*
 * 修正情報をロード
 */
function load_modify($productid, $tool_id, $modify_id) {
    $dbh = get_connection($GLOBALS['db_access_info_devtools']);
    if ($dbh == false) {
        error_exit("データベースに接続できません。理由： $php_errormsg");
    } else {
        //error_exit("データベースに接続できました。");
    }
    
    $sql = "select t1.modify_id, t1.tool_id, t1.product_id, t1.regist_date, t1.bug_id, t1.division, t1.cause_modify" .
           ", t1.repository, t1.project_name, t1.unit_test, t1.modifier_id, t1.reg_repositor_date, t1.overall_test" .
           ", t1.tester_id, t1.test_date, t1.product_version, t1.status" .
           ", t2.product_ver_name" .
           " from t_modify_list t1" .
           " left outer join t_product_version t2 on t2.product_id = t1.product_id and t2.product_ver_id = t1.product_version" .
           " where t1.product_id = " . bind_param($productid, PRAM_NOT_STRING) .
           " and t1.tool_id = " . bind_param($tool_id, PRAM_NOT_STRING) .
           " and t1.modify_id = " . bind_param($modify_id, PRAM_STRING);

    output_log($sql);
    $res = pg_query($dbh, $sql);
    if ($res == false) {
        error_exit("SQLの実行に失敗しました。理由： $php_errormsg");
    } else {
        $num = pg_num_rows($res);
        if ($num == 0) {
            return false;
        } else {
            $arr = pg_fetch_array($res, 0, PGSQL_ASSOC);
            //error_exit("ccc " . $arr[FLD_THEATER_NAME]);
            return $arr;
        }
    }
}


/*
 * 修正情報をロード
 * modify_id ではなく bug_id をキーに検索
 */
function load_modify_bug($productid, $tool_id, $bug_id) {
    $dbh = get_connection($GLOBALS['db_access_info_devtools']);
    if ($dbh == false) {
        error_exit("データベースに接続できません。理由： $php_errormsg");
    } else {
        //error_exit("データベースに接続できました。");
    }
    
    $sql = "select modify_id, tool_id, product_id, regist_date, bug_id, division, cause_modify" .
           ", repository, project_name, unit_test, modifier_id, reg_repositor_date, overall_test" .
           ", tester_id, test_date" .
           " from t_modify_list" .
           " where product_id = " . bind_param($productid, PRAM_NOT_STRING) .
           " and tool_id = " . bind_param($tool_id, PRAM_NOT_STRING) .
           " and bug_id = " . bind_param($bug_id, PRAM_STRING);
           
    $res = pg_query($dbh, $sql);
    if ($res == false) {
        error_exit("SQLの実行に失敗しました。理由： $php_errormsg");
    } else {
        $num = pg_num_rows($res);
        if ($num == 0) {
            return false;
        } else {
            $arr = pg_fetch_array($res, 0, PGSQL_ASSOC);
            //error_exit("ccc " . $arr[FLD_THEATER_NAME]);
            return $arr;
        }
    }
}

/*
 * 修正表のみをリスト
 * (ソース変更テーブルは含まない)
 */
function list_modify_only($condition) {
    $dbh = get_connection($GLOBALS['db_access_info_devtools']);
    if ($dbh == false) {
        error_exit("データベースに接続できません。理由： $php_errormsg");
    } else {
        //error_exit("データベースに接続できました。");
    }
    
    $order = "";
    if ($sort == 1) {
        $order = " DESC";
    }
    
    $sql = "SELECT * FROM t_modify_list" .
           " WHERE product_id = " . bind_param($condition["product_id"], PRAM_NOT_STRING) .
           " AND tool_id = " . bind_param($condition["tool_id"], PRAM_NOT_STRING);

    if (isset($condition[FLD_MODIFIER_ID]) && strlen($condition[FLD_MODIFIER_ID]) > 0) {
        $sql .= " and modifier_id = " . bind_param($condition[FLD_MODIFIER_ID], PRAM_STRING);
    }
    if (isset($condition[FLD_TESTER_ID]) && strlen($condition[FLD_TESTER_ID]) > 0) {
        $sql .= " and tester_id = " . bind_param($condition[FLD_TESTER_ID], PRAM_STRING);
    }

    $order = "";
    if ($condition["sort"] == 1) {
        $order = " DESC";
    }
    $sql .= " ORDER BY modify_id " . $order . " limit " . $condition['list_count'] . " offset " . $condition["cnt"];
    //print($sql);
    
    output_log ($sql);
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

/*
 * 修正票をリスト
 * (ソース変更テーブルの内容を含む)
 */
function list_modify($condition) {
    $dbh = get_connection($GLOBALS['db_access_info_devtools']);
    if ($dbh == false) {
        error_exit("データベースに接続できません。理由： $php_errormsg");
    } else {
        //error_exit("データベースに接続できました。");
    }
    
    $order = "";
    if ($condition["sort"] == 1) {
        $order = " DESC";
    }

    $sql_param;
    $sql_param = " WHERE product_id = " . bind_param($condition["product_id"], PRAM_NOT_STRING) . "\n" .
                 " AND tool_id = " . bind_param($condition["tool_id"], PRAM_NOT_STRING) . "\n";
    if (isset($condition[FLD_MODIFIER_ID]) && strlen($condition[FLD_MODIFIER_ID]) > 0) {
        $sql_param .= strlen($sql_param) > 0 ? " AND " : " WHERE ";
        $sql_param .= " modifier_id = " . bind_param($condition[FLD_MODIFIER_ID], PRAM_STRING);
    }
    if (isset($condition[FLD_TESTER_ID]) && strlen($condition[FLD_TESTER_ID]) > 0) {
        $sql_param .= strlen($sql_param) > 0 ? " AND " : " WHERE ";
        $sql_param .= "tester_id = " . bind_param($condition[FLD_TESTER_ID], PRAM_STRING);
    }
    if (isset($condition[FLD_PRODUCT_VER_ID]) && strlen($condition[FLD_PRODUCT_VER_ID]) > 0) {
        $sql_param .= strlen($sql_param) > 0 ? " AND " : " WHERE ";
        $sql_param .= "product_version = " . bind_param($condition[FLD_PRODUCT_VER_ID], PRAM_NOT_STRING);
    }
    if (isset($condition[FLD_STATUS]) && strlen($condition[FLD_STATUS]) > 0) {
        $sql_param .= strlen($sql_param) > 0 ? " AND " : " WHERE ";
        $sql_param .= "status = " . bind_param($condition[FLD_STATUS], PRAM_NOT_STRING);
    }


    /*** 検索条件 ***/
    $searchInfo = $condition["searchInfo"];
    $sql_search_param .= create_search_sql($searchInfo);
    //$sql_param .= strlen($sql_search_param) > 0 ? (strlen($sql_param) > 0 ? " AND " : " WHERE ") . $sql_search_param : "";
    
    $sql = "SELECT t4.rec_count, t3.*, t4.class_name, t4.method_name, t4.source_comment, t5.mod_rec_count, t7.mod_total_rec_count, t8.product_ver_name FROM" . "\n" .
//-->           " (SELECT * from t_modify_list " . "\n" .
           " (SELECT * from t_modify_list " . $sql_param . 
            " ORDER BY modify_id" . $order . 
//-->             " LIMIT " . $condition['list_count'] . " OFFSET " . $condition["cnt"] .
            ") t3" . "\n" .
           " LEFT OUTER JOIN (" . "\n" .
               " SELECT t2.rec_count, t1.*" . "\n" .
               " FROM t_modify_source t1" . "\n" .
               " INNER JOIN (SELECT COUNT(modify_source_id) AS rec_count, modify_id, tool_id, product_id FROM t_modify_source GROUP BY modify_id, tool_id, product_id) t2" . "\n" .
               " ON t2.modify_id = t1.modify_id AND t2.tool_id = t1.tool_id AND t2.product_id = t1.product_id" . "\n" .
           ") t4" . "\n" .
           " ON t4.modify_id = t3.modify_id AND t4.tool_id = t3.tool_id AND t4.product_id = t3.product_id" . "\n" .
           " INNER JOIN (" . "\n" .
               " SELECT count(t6.modify_id) as mod_rec_count, t6.product_id, t6.tool_id  FROM" . "\n" .
//-->                   " (SELECT modify_id, product_id, tool_id  from t_modify_list" . "\n" .
                   " (SELECT modify_id, product_id, tool_id  from t_modify_list" . $sql_param . 
                    " ORDER BY modify_id" . $order . "\n" .
                     " LIMIT " . $condition['list_count'] . " OFFSET " . $condition["cnt"] .
                    ") t6" . "\n" .
               " GROUP BY tool_id, product_id" . "\n" .
           " ) t5" . "\n" .
           " ON t5.product_id = t3.product_id AND t5.tool_id = t3.tool_id" . "\n" .
           " INNER JOIN (" . "\n" .
                " SELECT count(modify_id) AS mod_total_rec_count, product_id, tool_id" . "\n" .
                " FROM t_modify_list" . "\n" . 
                " WHERE product_id = " . bind_param($condition["product_id"], PRAM_NOT_STRING) . "\n" .
                " AND tool_id = " . bind_param($condition["tool_id"], PRAM_NOT_STRING) . "\n" .
                " GROUP BY tool_id, product_id" . "\n" .
           ") t7" . "\n" .
           " ON t7.product_id = t3.product_id AND t7.tool_id = t3.tool_id" . "\n" .
           " LEFT OUTER JOIN t_product_version t8 on t8.product_id = t3.product_id and t8.product_ver_id = t3.product_version" . "\n" .
           " WHERE t3.product_id = " . bind_param($condition["product_id"], PRAM_NOT_STRING) . "\n" .
           " AND t3.tool_id = " . bind_param($condition["tool_id"], PRAM_NOT_STRING) . "\n";

    if (isset($condition[FLD_MODIFIER_ID]) && strlen($condition[FLD_MODIFIER_ID]) > 0) {
        $sql .= " AND t3.modifier_id = " . bind_param($condition[FLD_MODIFIER_ID], PRAM_STRING);
    }
    if (isset($condition[FLD_TESTER_ID]) && strlen($condition[FLD_TESTER_ID]) > 0) {
        $sql .= " AND t3.tester_id = " . bind_param($condition[FLD_TESTER_ID], PRAM_STRING);
    }
    if (isset($condition[FLD_PRODUCT_VER_ID]) && strlen($condition[FLD_PRODUCT_VER_ID]) > 0) {
        $sql .= " AND t3.product_version = " . bind_param($condition[FLD_PRODUCT_VER_ID], PRAM_NOT_STRING);
    }
    if (isset($sql_search_param) && strlen($sql_search_param) > 0) {
        $sql .= " AND " . $sql_search_param;
    }

    $sql .= " ORDER BY t3.modify_id " . $order . " , t4.modify_source_id" . 
            " LIMIT " . $condition['list_count'] . " OFFSET " . $condition["cnt"];
    //print($sql);
    
    output_log ($sql);
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

/*
 * 修正ソースをリスト
 */
function list_modify_source($productid, $tool_id, $modify_id) {
    $dbh = get_connection($GLOBALS['db_access_info_devtools']);
    if ($dbh == false) {
        error_exit("データベースに接続できません。理由： $php_errormsg");
    } else {
        //error_exit("データベースに接続できました。");
    }
    
    $sql = "SELECT modify_id, tool_id, product_id, modify_source_id, class_name, method_name, source_comment FROM t_modify_source" .
           " WHERE product_id = " . bind_param($productid, PRAM_NOT_STRING) .
           " AND tool_id = " . bind_param($tool_id, PRAM_NOT_STRING) .
           " AND modify_id = " . bind_param($modify_id, PRAM_NOT_STRING) .
           " ORDER BY modify_source_id";

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

/*
 * 発送日をリスト
 */
function list_send_day($condition) {
    $dbh = get_connection($GLOBALS['db_access_info_devtools']);
    if ($dbh == false) {
        error_exit("データベースに接続できません。理由： $php_errormsg");
    } else {
        //error_exit("データベースに接続できました。");
    }

    $order = "";
    if (isset($condition["sort"]) && $condition["sort"] == 1) {
        $order = " DESC";
    }
    
    $sql = "SELECT product_id, send_id, shipping_day, title, color" .
           " FROM t_send_out" . 
           " WHERE product_id = " . bind_param($condition["product_id"], PRAM_NOT_STRING) .
           " ORDER BY shipping_day " . $order;

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


/*
 * SQL文用にパラメータを加工する
 * $param: パラメータ
 * $flag: パラメータが数値か文字列かの判別フラグ
 * $is_where: where 句の条件として使用するか、update, insert の value の
 *            パラメータとして使用するかの判別 デフォルトは false
 * where 句で使用するときは 定数 「IS_WHERE」を第3引数に指定してください。
 *
 */
function bind_param ($param, $flag, $is_where = false) {
    if (!$is_where && (is_null($param) || strlen($param) == 0)) {
        return $param = "null";
    } elseif ($is_where && (is_null($param) || strlen($param) == 0)) {
        return $param = "''";
    } else {
        /* 2004/04/28
        magic_quotes_gpc = on の設定により以下の処理はコメントアウト
        $param = str_replace("'", "''", $param);
        */
        /* 2004/07/08
        magic_quotes_gpc = off にしたので以下の処理が復活
        */

        /* 2004/08/02 */
        /* \マークもエスケープする必要があるので、addslashes に変更 */
        $param = addslashes($param);
        //$param = str_replace("'", "''", $param);
        if ($flag == PRAM_STRING) {
            return $param = "'" . $param . "'";
        } else {
            return $param;
        }
    }
}

/*
 * like 文用にSQL文字列を作成して返す。
 */
function bind_like($param) {
    // バックスラッシュ(\) を検索する場合は、
    // 文の中でバックスラッシュを4つ記述する必要があります。

    if (!preg_match("/(\\+)/", $param)) {
        $param = str_replace("\\", "\\\\", $param);
    }

    if ($param != null) {
        //エスケープ
        $param = str_replace("%", "\\%", $param);
        $param = str_replace("_", "\\_", $param);
    }
    $param = addslashes($param);
    return "like '%" . $param ."%'";
}

/*
 * 引数の SQL に一致する、レコードが存在するかどうかを調べる。
 */
function exist_rec($dbh, $sql) {

//error_exit($sql);
    $res = pg_query($dbh, $sql);
    if ($res == false) {
        error_exit("SQLの実行に失敗しました。理由： $php_errormsg");
    } else {
        $num = pg_num_rows($res);
        if ($num == 0) {
            return false;
        } else {
            return true;
        }
    }
}


function setBoolean($value) {
    if ($value != NULL && strlen($value) > 0) {
        return "true";
    } else {
        return "false";
    }
}


/*
 * シーケンスより ID を生成して返す。
 */
function get_sequence($dbh, $sql) {
    $res = pg_query($dbh, $sql);
    if ($res == false) {
        return false;
        error_exit("SQLの実行に失敗しました。理由： $php_errormsg");
    } else {
        $num = pg_num_rows($res);
        if ($num == 0) {
            error_exit("id をシーケンスより作成できませんでした。");
        } else {
            $id = pg_fetch_array($res, 0, PGSQL_ASSOC);
            return $id;
        }
    }
}

function output_log ($str) {
    $yyyymmdd = date('Ymd');
    $log_file = LOG_DIR . "log_$yyyymmdd.txt";
    if(!file_exists($log_file)) {
        @chmod($log_file, 0666);
    }
    $fp = fopen($log_file, "a");
    if ($fp) {
        $time = date("Y-m-d H:i:s");
        fputs($fp, "$time\n $str\n");
        fclose($fp);
        @chmod($log_file, 0666);
    } else {
        error_exit("ログファイルのオープンに失敗しました。");
    }
}



/*
 * 発送日ID を返す
 */
function getSendID($dbh, $productID) {

    switch ($productID) {
        case PRODUCT_UK:
            $sql = "select nextval('seq_bug_id_uk')";
            break;
        case PRODUCT_UM_C:
            $sql = "select nextval('seq_bug_id_c')";
            break;
        case PRODUCT_UM_J:
            $sql = "select nextval('seq_bug_id_j')";
            break;
    }
    return get_sequence($dbh, $sql);
}

/*
 * 修正票No. を返す
 */
function getModifyID($dbh, $productID) {

    switch ($productID) {
        case PRODUCT_UK:
            $sql = "select nextval('seq_modify_id_uk')";
            break;
        case PRODUCT_UM_C:
            $sql = "select nextval('seq_modify_id_um_c')";
            break;
        case PRODUCT_UM_J:
            $sql = "select nextval('seq_modify_id_um_j')";
            break;
    }
    return get_sequence($dbh, $sql);
}

/*
 * 不具合表No. を返す
 */
function getBubID($dbh, $productID) {

    switch ($productID) {
        case PRODUCT_UK:
            $sql = "select nextval('seq_bug_id_uk')";
            break;
        case PRODUCT_UM_C:
            $sql = "select nextval('seq_bug_id_um_c')";
            break;
        case PRODUCT_UM_J:
            $sql = "select nextval('seq_bug_id_um_j')";
            break;
    }
    return get_sequence($dbh, $sql);
}

/*
 * 不具合情報登録
 */
function do_add_bug_info($dbh, $user, $formData) {

    $productID = $formData['product_info'][FLD_PRODUCT_ID];
    $squence = getBubID($dbh, $productID);
    $bug_id = $squence[FLD_SEQ_NEXTVAL];

    if (!preg_match("/^\d+$/", $bug_id)) {
        error_exit("不具合表No.が不正です。");
    }

    $bug_info = $formData['errInfo'];
    $bug_info[FLD_PRODUCT_VER] = isset($bug_info[FLD_PRODUCT_VER]) ? $bug_info[FLD_PRODUCT_VER] : "0";
    
    // status は t_bug_list ではなく t_modify_list を参照するよう変更したため、t_bug_list のフィールドは削除
    $sql = "insert into t_bug_list" .
            " (bug_id, tool_id, product_id, bug_reporter_id, occurrence_date, occurrence_place" .
           ", screen_id, error_content, caution, version, error_level, release_time" .
           ", person_in_charge_id, modifier_id, modify_date, corrections, check_date, checker_id, editor_id, product_version)" .
            " values (" .
            bind_param($bug_id, PRAM_NOT_STRING) . "," .
            bind_param($formData[FLD_TOOLTYPE_ID], PRAM_NOT_STRING) . "," .
            bind_param($productID, PRAM_NOT_STRING) . "," .
            bind_param($bug_info[FLD_BUG_REPORTER_ID], PRAM_STRING) . "," .
            bind_param($bug_info[FLD_OCCURRENCE_DATE], PRAM_STRING) . "," .
            bind_param($bug_info[FLD_OCCURRENCE_PLACE], PRAM_STRING) . "," .
            bind_param($bug_info[FLD_SCREEN_ID], PRAM_STRING) . "," .
            bind_param($bug_info[FLD_ERR_CONTENT], PRAM_STRING) . "," .
            bind_param($bug_info[FLD_CAUTION], PRAM_STRING) . "," .
            bind_param($bug_info[FLD_VERSION], PRAM_STRING) . "," .
            bind_param($bug_info[FLD_ERR_LEVEL], PRAM_NOT_STRING) . "," .
            bind_param($bug_info[FLD_RELEASE_TIME], PRAM_STRING) . "," .
            bind_param($bug_info[FLD_PERSON_IN_CHARGE_ID], PRAM_STRING) . "," .
            bind_param($bug_info[FLD_MODIFIER_ID], PRAM_STRING) . "," .
            bind_param($bug_info[FLD_MODIFY_DATE], PRAM_STRING) . "," .
//            bind_param($bug_info[FLD_STATUS], PRAM_NOT_STRING) . "," .
            bind_param($bug_info[FLD_CORRECTIONS], PRAM_STRING) . "," .
            bind_param($bug_info[FLD_CHECK_DATE], PRAM_STRING) . "," .
            bind_param($bug_info[FLD_CHECKER_ID], PRAM_STRING) . "," .
            bind_param($user[FLD_USER_ID], PRAM_STRING) . "," .
            bind_param($bug_info[FLD_PRODUCT_VER], PRAM_NOT_STRING) .
            ")";

    //error_exit($sql);
    //return false;
    output_log ($sql);
    $res = pg_query($dbh, $sql);
    if ($res == false) {
        return false;
        //error_exit("SQLの実行に失敗しました。理由： $php_errormsg");
    } else {
        return true;
    }
}

/*
 * 不具合情報更新
 */
function do_edit_bug_info($dbh, $user, $formData) {

    $bug_info = $formData['errInfo'];

    $bug_info[FLD_PRODUCT_VER] = isset($bug_info[FLD_PRODUCT_VER]) ? $bug_info[FLD_PRODUCT_VER] : "0";
    
    $sql = "update t_bug_list set" .
            " bug_reporter_id = " . bind_param($bug_info[FLD_BUG_REPORTER_ID], PRAM_STRING) . "," .
            " occurrence_date = " . bind_param($bug_info[FLD_OCCURRENCE_DATE], PRAM_STRING) . "," .
            " occurrence_place = " . bind_param($bug_info[FLD_OCCURRENCE_PLACE], PRAM_STRING) . "," .
            " screen_id = " . bind_param($bug_info[FLD_SCREEN_ID], PRAM_STRING) . "," .
            " error_content = " . bind_param($bug_info[FLD_ERR_CONTENT], PRAM_STRING) . "," .
            " caution = " . bind_param($bug_info[FLD_CAUTION], PRAM_STRING) . "," .
            " version = " . bind_param($bug_info[FLD_VERSION], PRAM_STRING) . "," .
            " error_level = " . bind_param($bug_info[FLD_ERR_LEVEL], PRAM_NOT_STRING) . "," .
            " release_time = " . bind_param($bug_info[FLD_RELEASE_TIME], PRAM_STRING) . "," .
            " person_in_charge_id = " . bind_param($bug_info[FLD_PERSON_IN_CHARGE_ID], PRAM_STRING) . "," .
            " modifier_id = " . bind_param($bug_info[FLD_MODIFIER_ID], PRAM_STRING) . "," .
            " modify_date = " . bind_param($bug_info[FLD_MODIFY_DATE], PRAM_STRING) . "," .
//            " status = " . bind_param($bug_info[FLD_STATUS], PRAM_NOT_STRING) . "," .
            " corrections = " . bind_param($bug_info[FLD_CORRECTIONS], PRAM_STRING) . "," .
            " check_date = " . bind_param($bug_info[FLD_CHECK_DATE], PRAM_STRING) . "," .
            " checker_id = " . bind_param($bug_info[FLD_CHECKER_ID], PRAM_STRING) . "," .
            " editor_id = " . bind_param($user[FLD_USER_ID], PRAM_STRING) . "," .
            " product_version = " . bind_param($bug_info[FLD_PRODUCT_VER], PRAM_NOT_STRING) .
            " where (bug_id=" . bind_param($bug_info[FLD_ERR_ID], PRAM_NOT_STRING) .
            " and tool_id = " . bind_param($formData[FLD_TOOLTYPE_ID], PRAM_NOT_STRING) .
            " and product_id = " . bind_param($formData['product_info'][FLD_PRODUCT_ID], PRAM_NOT_STRING) . ")";

    //print "sql: " . $sql . "<br>";
    //return true;
    output_log ($sql);
    $res = pg_query($dbh, $sql);
    if ($res == false) {
        return false;
        //error_exit("SQLの実行に失敗しました。理由： $php_errormsg");
    } else {
        return true;
    }
}

/*
 * 不具合情報更新
 * (不具合表に紐づく修正表を登録/変更した場合の不具合表の訂正内容を更新)
 */
function do_edit_bug_info_refrect_result($dbh, $user, $formData) {

    $modifyInfo = $formData['modifyInfo'];
    if (!isset($modifyInfo[FLD_ERR_ID]) || !strlen($modifyInfo[FLD_ERR_ID])) {
        return true;
    }
    $bug_info = $formData['errInfo'];

    // 不具合表なのでツールID は 1
    // status は t_bug_list ではなく t_modify_list を参照するよう変更したため、t_bug_list のフィールドは削除
    $sql = "update t_bug_list set" .
//            " status = " . bind_param($bug_info[FLD_STATUS], PRAM_NOT_STRING) . "," .
            " corrections = " . bind_param($bug_info[FLD_CORRECTIONS], PRAM_STRING) . "," .
            " check_date = " . bind_param($bug_info[FLD_CHECK_DATE], PRAM_STRING) . "," .
            " checker_id = " . bind_param($bug_info[FLD_CHECKER_ID], PRAM_STRING) .
            " where (bug_id=" . bind_param($modifyInfo[FLD_ERR_ID], PRAM_NOT_STRING) .
            " and tool_id = 1" .
            " and product_id = " . bind_param($formData['product_info'][FLD_PRODUCT_ID], PRAM_NOT_STRING) . ")";

    //print "sql: " . $sql . "<br>";
    //return true;
    output_log ($sql);
    $res = pg_query($dbh, $sql);
    if ($res == false) {
        return false;
        //error_exit("SQLの実行に失敗しました。理由： $php_errormsg");
    } else {
        return true;
    }
}

/*
 * 不具合情報削除(関連する修正表があればそれも削除)
 */
function do_delete_bug_info($dbh, $user, $formData) {

    if(!do_delete_bug($dbh, $user, $formData)) {
        //output_log("add_sche false");
        return false;
    }

    $bug_info = $formData['errInfo'];
    $bug_id = $bug_info[FLD_ERR_ID];
    
    // 不具合ID から対応する修正表情報をロード
    $modifyInfo = load_modify_bug($formData['product_info'][FLD_PRODUCT_ID], 2, $bug_id);

    // 不具合表が存在すれば削除
    if($modifyInfo != null && count($modifyInfo) > 0) {
        $formData['modifyInfo'] = $modifyInfo;
        // 不具合表側からの削除の為、ツールID は修正表を示す 2 に設定し直す。
        $formData[FLD_TOOLTYPE_ID] = 2;
        if(!do_delete_modify($dbh, $user, $formData)) {
            //output_log("add_info false");
            return false;
        }
    }

    return true;
}

/*
 * 不具合情報削除
 */
function do_delete_bug($dbh, $user, $formData) {

    $bug_info = $formData['errInfo'];
    
    $sql = "delete from t_bug_list" .
            " where (bug_id=" . bind_param($bug_info[FLD_ERR_ID], PRAM_NOT_STRING) .
            " and tool_id = " . bind_param($formData[FLD_TOOLTYPE_ID], PRAM_NOT_STRING) .
            " and product_id = " . bind_param($formData['product_info'][FLD_PRODUCT_ID], PRAM_NOT_STRING) . ")";

    //print "sql: " . $sql . "<br>";
    //return false;
    output_log ($sql);
    $res = pg_query($dbh, $sql);
    if ($res == false) {
        return false;
        //error_exit("SQLの実行に失敗しました。理由： $php_errormsg");
    } else {
        return true;
    }
}

/*
 * 修正情報、修正ソースの登録を行う。
 */
function do_add_modify($dbh, $user, $formData) {

    $productID = $formData['product_info'][FLD_PRODUCT_ID];
    
    $squence = getModifyID($dbh, $productID);
    $modify_id = $squence[FLD_SEQ_NEXTVAL];
    
    $formData['modifyInfo'][FLD_MOD_ID] = $modify_id;

    if(!do_add_modify_info($dbh, $user, $formData)) {
        //output_log("add_sche false");
        return false;
    }
    $is_package = TRUE;
    if(!do_edit_source_info($dbh, $user, $formData)) {
        //output_log("add_info false");
        return false;
    }
    // 不具合表の訂正内容を更新
    if(!do_edit_bug_info_refrect_result($dbh, $user, $formData)) {
        //output_log("add_info false");
        return false;
    }
    return true;
}

/*
 * 修正情報登録
 */
function do_add_modify_info($dbh, $user, $formData) {

    $productID = $formData['product_info'][FLD_PRODUCT_ID];

    $modifyInfo = $formData['modifyInfo'];    
    if (!preg_match("/^\d+$/", $modifyInfo[FLD_MOD_ID])) {
        error_exit("修正表No.が不正です。");
    }
    
    $modifyInfo[FLD_PRODUCT_VER] = isset($modifyInfo[FLD_PRODUCT_VER]) ? $modifyInfo[FLD_PRODUCT_VER] : "0";
    
    // status は t_bug_list ではなく t_modify_list を参照するよう変更したため、t_modify_list に status フィールドを追加
    $sql = "insert into t_modify_list" .
            " (modify_id, tool_id, product_id, regist_date, bug_id, division, cause_modify" .
           ", repository, project_name, unit_test, modifier_id, reg_repositor_date, overall_test" .
           ", tester_id, test_date, editor_id, product_version, status)" .
            " values (" .
            bind_param($modifyInfo[FLD_MOD_ID], PRAM_NOT_STRING) . "," .
            bind_param($formData[FLD_TOOLTYPE_ID], PRAM_NOT_STRING) . "," .
            bind_param($productID, PRAM_NOT_STRING) . "," .
            bind_param($modifyInfo[FLD_REGIST_DATE], PRAM_STRING) . "," .
            bind_param($modifyInfo[FLD_ERR_ID], PRAM_STRING) . "," .
            bind_param($modifyInfo[FLD_DIVISION], PRAM_STRING) . "," .
            bind_param($modifyInfo[FLD_CAUSE_MODIFY], PRAM_STRING) . "," .
            bind_param($modifyInfo[FLD_REPOSITORY], PRAM_STRING) . "," .
            bind_param($modifyInfo[FLD_PROJECT_NAME], PRAM_STRING) . "," .
            bind_param($modifyInfo[FLD_UNIT_TEST], PRAM_STRING) . "," .
            bind_param($modifyInfo[FLD_MODIFIER_ID], PRAM_STRING) . "," .
            bind_param($modifyInfo[FLD_REG_REPOSITOR_DATE], PRAM_STRING) . "," .
            bind_param($modifyInfo[FLD_OVERALL_TEST], PRAM_STRING) . "," .
            bind_param($modifyInfo[FLD_TESTER_ID], PRAM_STRING) . "," .
            bind_param($modifyInfo[FLD_TEST_DATE], PRAM_STRING) . "," .
            bind_param($user[FLD_USER_ID], PRAM_STRING) . "," .
            bind_param($modifyInfo[FLD_PRODUCT_VER], PRAM_NOT_STRING) . "," .
            bind_param($modifyInfo[FLD_STATUS], PRAM_NOT_STRING) .
            ")";

    //error_exit($sql);
    //return false;
    output_log ($sql);
    $res = pg_query($dbh, $sql);
    if ($res == false) {
        return false;
        //error_exit("SQLの実行に失敗しました。理由： $php_errormsg");
    } else {
        return true;
    }
}

/*
 * 修正情報、修正ソースの更新を行う。
 */
function do_edit_modify($dbh, $user, $formData) {

    $productID = $formData['product_info'][FLD_PRODUCT_ID];
    
    if(!do_edit_modify_info($dbh, $user, $formData)) {
        //output_log("add_sche false");
        return false;
    }

    if(!do_edit_source_info($dbh, $user, $formData)) {
        //output_log("add_info false");
        return false;
    }
    
    // 不具合表の訂正内容を更新
    if(!do_edit_bug_info_refrect_result($dbh, $user, $formData)) {
        //output_log("add_info false");
        return false;
    }    
    return true;

}


/*
 * 修正情報更新
 */
function do_edit_modify_info($dbh, $user, $formData) {

    $productID = $formData['product_info'][FLD_PRODUCT_ID];

    $modifyInfo = $formData['modifyInfo'];    
    if (!preg_match("/^\d+$/", $modifyInfo[FLD_MOD_ID])) {
        error_exit("修正表No.が不正です。");
    }
    
    $modifyInfo[FLD_PRODUCT_VER] = isset($modifyInfo[FLD_PRODUCT_VER]) ? $modifyInfo[FLD_PRODUCT_VER] : "0";
    
    $sql = "update t_modify_list set" .
            " regist_date = " . bind_param($modifyInfo[FLD_REGIST_DATE], PRAM_STRING) . "," .
            " bug_id = " . bind_param($modifyInfo[FLD_ERR_ID], PRAM_STRING) . "," .
            " division = " . bind_param($modifyInfo[FLD_DIVISION], PRAM_STRING) . "," .
            " cause_modify = " . bind_param($modifyInfo[FLD_CAUSE_MODIFY], PRAM_STRING) . "," .
            " repository = " . bind_param($modifyInfo[FLD_REPOSITORY], PRAM_STRING) . "," .
            " project_name = " . bind_param($modifyInfo[FLD_PROJECT_NAME], PRAM_STRING) . "," .
            " unit_test = " . bind_param($modifyInfo[FLD_UNIT_TEST], PRAM_STRING) . "," .
            " modifier_id = " . bind_param($modifyInfo[FLD_MODIFIER_ID], PRAM_STRING) . "," .
            " reg_repositor_date = " . bind_param($modifyInfo[FLD_REG_REPOSITOR_DATE], PRAM_STRING) . "," .
            " overall_test = " . bind_param($modifyInfo[FLD_OVERALL_TEST], PRAM_STRING) . "," .
            " tester_id = " . bind_param($modifyInfo[FLD_TESTER_ID], PRAM_STRING) . "," .
            " test_date = " . bind_param($modifyInfo[FLD_TEST_DATE], PRAM_STRING) . "," .
            " editor_id = " . bind_param($user[FLD_USER_ID], PRAM_STRING) . "," .
            " product_version = " . bind_param($modifyInfo[FLD_PRODUCT_VER], PRAM_NOT_STRING) . "," .
            " status = " . bind_param($modifyInfo[FLD_STATUS], PRAM_NOT_STRING) .
            " where (modify_id=" . bind_param($modifyInfo[FLD_MOD_ID], PRAM_STRING) .
            " and tool_id = " . bind_param($formData[FLD_TOOLTYPE_ID], PRAM_NOT_STRING) .
            " and product_id = " . bind_param($formData['product_info'][FLD_PRODUCT_ID], PRAM_NOT_STRING) . ")";

    //error_exit($sql);
    //return false;
    output_log ($sql);
    $res = pg_query($dbh, $sql);
    if ($res == false) {
        return false;
        //error_exit("SQLの実行に失敗しました。理由： $php_errormsg");
    } else {
        return true;
    }
}


/*
 * 修正情報、修正ソースの削除を行う。
 */
function do_delete_modify($dbh, $user, $formData) {
    if(!do_delete_modify_info($dbh, $user, $formData)) {
        output_log("do_delete_modify_info false");
        return false;
    }

    if(!do_delete_modify_source_info($dbh, $user, $formData)) {
        output_log("do_delete_modify_info false");
        return false;
    }
    return true;
}

/*
 * 修正情報削除
 */
function do_delete_modify_info($dbh, $user, $formData) {

    $modifyInfo = $formData['modifyInfo'];  
    
    $sql = "delete from t_modify_list" .
            " where (modify_id=" . bind_param($modifyInfo[FLD_MOD_ID], PRAM_NOT_STRING) .
            " and tool_id = " . bind_param($formData[FLD_TOOLTYPE_ID], PRAM_NOT_STRING) .
            " and product_id = " . bind_param($formData['product_info'][FLD_PRODUCT_ID], PRAM_NOT_STRING) . ")";

    //print "sql: " . $sql . "<br>";
    //return false;
    output_log ($sql);
    $res = pg_query($dbh, $sql);
    if ($res == false) {
        return false;
        //error_exit("SQLの実行に失敗しました。理由： $php_errormsg");
    } else {
        return true;
    }
}

/*
 * 修正ソース情報削除
 */
function do_delete_modify_source_info($dbh, $user, $formData) {

    $modifyInfo = $formData['modifyInfo'];  
    
    // 対象レコードを全削除する SQL
    $sql = "delete from t_modify_source" .
               " where modify_id = " . bind_param($modifyInfo[FLD_MOD_ID], PRAM_NOT_STRING) .
               " and tool_id = " . bind_param($formData[FLD_TOOLTYPE_ID], PRAM_NOT_STRING) .
               " and product_id = " . bind_param($formData['product_info'][FLD_PRODUCT_ID], PRAM_NOT_STRING);
    //           " and modify_source_id = " . bind_param($key, PRAM_STRING);

    //print "sql: " . $sql . "<br>";
    //return false;
    output_log ($sql);
    $res = pg_query($dbh, $sql);
    if ($res == false) {
        return false;
        //error_exit("SQLの実行に失敗しました。理由： $php_errormsg");
    } else {
        return true;
    }
}

/*
 * 修正ソース情報登録
 */
function do_edit_source_info($dbh, $user, $formData) {

    $sqls = create_edit_source_info_sql($dbh, $formData, $user);

    foreach($sqls as $sql) {
        $res = pg_query($dbh, $sql);
        if (!$res) {
            output_log("error sql: $sql");
            return false;
            break;
        }
    }
    return true;
}


/*
 * 修正ソース用 SQL 作成
 */
function create_edit_source_info_sql($dbh, $formData, $user) {
    $sqls = array();
    $productID = $formData['product_info'][FLD_PRODUCT_ID];

    $modifyInfo = $formData['modifyInfo'];    
    if (!preg_match("/^\d+$/", $modifyInfo[FLD_MOD_ID])) {
        error_exit("修正表No.が不正です。");
    }

    $modSourceInfo = $formData['modSourceInfo'];

    // 対象レコードを全削除する SQL
    $sql = "delete from t_modify_source" .
               " where modify_id = " . bind_param($modifyInfo[FLD_MOD_ID], PRAM_NOT_STRING) .
               " and tool_id = " . bind_param($formData[FLD_TOOLTYPE_ID], PRAM_NOT_STRING) .
               " and product_id = " . bind_param($productID, PRAM_NOT_STRING);
    array_push($sqls, $sql);               
        
    if ($modSourceInfo != null && count($modSourceInfo) > 0) {
        foreach($modSourceInfo as $key => $val) {
            // 毎回新規追加扱い
            $sql = "insert into t_modify_source" .
                    " (modify_id, tool_id, product_id, modify_source_id" .
                    ", class_name, method_name, source_comment, editor_id)" .
                    " values (" .
                    bind_param($modifyInfo[FLD_MOD_ID], PRAM_NOT_STRING) . "," .
                    bind_param($formData[FLD_TOOLTYPE_ID], PRAM_NOT_STRING) . "," .
                    bind_param($productID, PRAM_NOT_STRING) . "," .
                    bind_param($key, PRAM_STRING) . "," .
                    bind_param($val[FLD_CLASS_NAME], PRAM_STRING) . "," .
                    bind_param($val[FLD_METHOD_NAME], PRAM_STRING) . "," .
                    bind_param($val[FLD_SOURCE_COMMENT], PRAM_STRING) . "," .
                    bind_param($user[FLD_USER_ID], PRAM_STRING) .
                    ")";
        
            /*
            // 対象レコードが存在するかチェックする SQL
            $sqlTemp = "select modify_source_id from t_modify_source" .
                       " where modify_id = " . bind_param($modifyInfo[FLD_MOD_ID], PRAM_NOT_STRING) .
                       " and tool_id = " . bind_param($formData[FLD_TOOLTYPE_ID], PRAM_NOT_STRING) .
                       " and product_id = " . bind_param($productID, PRAM_NOT_STRING) .
                       " and modify_source_id = " . bind_param($key, PRAM_STRING);
                       
            $sql;
            if (exist_rec($dbh, $sqlTemp)) {
                // 対象レコードが存在すれば、update
                $sql = "update t_modify_source set" .
                       " class_name = " . bind_param($val[FLD_CLASS_NAME], PRAM_STRING) . "," .
                       " method_name = " . bind_param($val[FLD_METHOD_NAME], PRAM_STRING) . "," .
                       " source_comment = " . bind_param($val[FLD_SOURCE_COMMENT], PRAM_STRING) . "," .
                       " editor_id = " . bind_param($user[FLD_USER_ID], PRAM_STRING) .
                       " where modify_id = " . bind_param($modifyInfo, PRAM_NOT_STRING) .
                       " and tool_id = " . bind_param($formData[FLD_TOOLTYPE_ID], PRAM_NOT_STRING) .
                       " and product_id = " . bind_param($productID, PRAM_NOT_STRING) .
                       " and modify_source_id = " . bind_param($key, PRAM_STRING);
            
            } else {
                // 対象レコードが存在しなければ insert
                $sql = "insert into t_modify_source" .
                        " (modify_id, tool_id, product_id, modify_source_id" .
                        ", class_name, method_name, source_comment, editor_id)" .
                        " values (" .
                        bind_param($modifyInfo[FLD_MOD_ID], PRAM_NOT_STRING) . "," .
                        bind_param($formData[FLD_TOOLTYPE_ID], PRAM_NOT_STRING) . "," .
                        bind_param($productID, PRAM_NOT_STRING) . "," .
                        bind_param($key, PRAM_STRING) . "," .
                        bind_param($val[FLD_CLASS_NAME], PRAM_STRING) . "," .
                        bind_param($val[FLD_METHOD_NAME], PRAM_STRING) . "," .
                        bind_param($val[FLD_SOURCE_COMMENT], PRAM_STRING) . "," .
                        bind_param($user[FLD_USER_ID], PRAM_STRING) .
                        ")";
            }
            */
            array_push($sqls, $sql);
        }
    }

    return $sqls;
}


/*
 * トランザクション処理を必要とする SQL処理を実行する。
 * 引数：第1引数 実行する関数名
 *       第2引数 実行する関数の第1引数
 *       第3引数 実行する関数の第2引数
 *       第4引数 実行する関数の第3引数 (現在未使用)
 * 戻り値：第1引数の関数が成功したかどうか。
 */
function transactionProc($func, $param1, $param2, $param3 = FALSE) {
    $sqls = array();

    $dbh = get_connection($GLOBALS['db_access_info_devtools']);
    if ($dbh == false) {
        error_exit("データベースに接続できません。理由： $php_errormsg");
    }

    $sql_tran = "BEGIN";
    $res = pg_query($dbh, $sql_tran);
    if (!$res) {
        error_exit("トランザクションの開始に失敗しました。");
    }

    $flag = $func($dbh, $param1, $param2);

    if ($flag) {
        $sql_tran = "COMMIT";
    } else {
        $sql_tran = "ROLLBACK";
    }

    output_log($sql_tran);

    $rs = pg_query($dbh, $sql_tran);
    if ($rs == false) {
        error_exit("$sql_tran に失敗しました");
        return false;
    }
    return $flag;
}
?>
