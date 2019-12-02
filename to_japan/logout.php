<?php
require('function.php');

debug('>>>>>>>>>>>>>>>>>>>>>>>>ログアウト画面処理開始');

debugLogStart();

debug('ログアウトします。');
session_destroy();
debug('ログインページへ遷移します。');
header("Location:login.php");
exit;

debug('>>>>>>>>>>>>>>>>>>>>>>>>ログアウト画面処理終了');
