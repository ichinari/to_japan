<?php

  debug('>>>>>>>>>>>>>>>>>>>>>>>>ログイン認証処理開始');

  if( !empty($_SESSION['login_date']) ){
    debug('ログイン済みユーザーです。');


    if( ($_SESSION['login_date'] + $_SESSION['login_limit']) < time()){
      debug('ログイン有効期限オーバーです。');


      session_destroy();

      header("Location:login.php");
      exit;
    }else{
      debug('ログイン有効期限以内です。');
      $_SESSION['login_date'] = time();

      if(basename($_SERVER['PHP_SELF']) === 'login.php'){
        debug('マイページへ遷移します。');
        header("Location:mypage.php");
        exit;
      }
    }

  }else{
    debug('未ログインユーザーです。');
    if(basename($_SERVER['PHP_SELF']) !== 'login.php'){
      header("Location:login.php");
      exit;
    }
  }

  debug('>>>>>>>>>>>>>>>>>>>>>>>>ログイン認証処理終了');