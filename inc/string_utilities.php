<?php
/*
 * 開発用ツールズサービス クライアント用WEBアプリケーション
 *
 */

/*
 * 文字列中に含まれる URI をリンク文字列に置換する
 */
function replace_uri($body, $new_window = false) {
    $pattern = "/(https?|ftp):\/\/[0-9a-z_,.:;&=+*%$#!?@()~\'\/-]+/i";

    $target = "";
    if ($new_window) {
        $target = " target='_blank'";
    }

    return preg_replace($pattern,
                        "<a href='$0'$target>$0</a>",
                        $body);
/*
    return preg_replace("/\[\[(.*?):(.*?)\]\]/",
                        "<a href=\"./$2\">$1</a>",
                        $body);
*/
/*
    return preg_replace("/(http:\/\/|https:\/\/){1}([\\w\\.\\-\/:]+)/",
                        "<a href='$0'>$0</a>",
                        $body);
*/

}

?>
