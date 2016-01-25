<?php

    include("inc/sql_devtools.php");
    include("inc/sql_daityo.php");
    include("inc/common.php");

    $errors = array();
    $id = '';
    $formData['aid'] = '';
    $formData['pwd'] = '';
    if (isset($_POST['sbm'])) {
        $formData = parseRequest($_POST);
        $errors = validateFormData($formData);
        if (is_array($errors) && count($errors) > 0) {
        } else {
            $id = $formData['aid'];
            $pw = $formData['pwd'];
            setcookie("devid", $id, time()+60*60*24*5); // 有効期限5日間
            $errors = login_new($id, $pw);
        }
    } else {
        if (isset($_REQUEST['error_msg'])) {
            $errors = $_REQUEST['error_msg'];
        }
    }

    $users = list_users();
    
    if (strlen($formData['aid']) > 0 && $formData['aid'] <> '') {
        $id=$formData['aid'];
    } else {
        $id=$_COOKIE["devid"];   //まずクッキーを読み出して変数に格納
    }


function parseRequest($lineData) {
    $formData = array();

    $formData['aid'] = normalizeData($lineData['aid']);
    $formData['pwd'] = normalizeData($lineData['pwd']);

    return $formData;
}

/*
 * データをチェックする。
 */
function validateFormData(&$formData) {

    $error = array();

    /* 必須チェック */
    if (!$formData['aid']) $error[] = "氏名を選択してください。";
    if (!$formData['pwd']) {
        $error[] = "パスワードを入力してください。";
    } else {
        if (!preg_match("%\w%", $formData['pwd'])) {
            $error[] = "パスワードのフォーマットが不正です。";
        }
    }

    return $error;
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="ja">
<head>
    <title>開発用サービス</title>
    <meta http-equiv="content-type" content="text/html; charset=euc-jp">
    <meta http-equiv="CONTENT-STYLE-TYPE" content="text/css">
    <link rel="stylesheet" type="text/css" href="inc/style.css">
    <script language="JavaScript" type="text/javascript"><!--
    function init() { 
     document.LoginForm.pwd.focus();
    }
    //--> </script>
</head>
<body onLoad="init()">
<div id="contents">
<?=show_error_msg($errors)?>
    <center>
    <span class="Font2">開発用サービス</span><br /><br />
    <form method="post" action="login.php" name="LoginForm" autocomplete="off">
        <table border="0">
        <tr>
        <td nowrap><b>名前</b></td>
        <td>
            <?php 
                if ($users != false) { ?>
                    <select name='aid'>
                        <option value="">(あなたのお名前)</option>
            <?php
                foreach($users as $user) {
                    $selected = "";
                    if ($user[FLD_USER_ID] == $id) {
                        $selected = " selected";
                    }
                    print "<option value=\"" . $user[FLD_USER_ID] . "\"" . $selected .">" . $user[FLD_USER_NAME] . "</option>\n";
                } ?>
                    </select>
            <?php
            } else {
                    print "ユーザー情報なし";
            } ?>
        </td>
        </tr>
<!--
        <tr>
        <td nowrap><b>ユーザーID</b></td>
        <td><input type="text" name="aid" value="<?=$formData['aid']?>"></td>
        </tr>
-->
        <tr>
        <td nowrap><b>パスワード</b></td>
        <td><input type="password" name="pwd" size="20"></td>
        </tr>
        </table>
        <br>
        <input type="hidden" name="act" value="post">
        <input type="submit" name="sbm" value=" ログイン ">
    </form>
    </center>
</div>
</body>
</html>
