<?php

require('function.php');

debug('>>>>>>>>>>>>>>>>>>>>>>>>パスワード再入力認証キー入力画面処理開始');

debugLogStart();


if(empty($_SESSION['auth_key'])){
  header("Location:passRemindSend.php");
  exit;
}

//処理
if(!empty($_POST)){
  debug('POST送信があります。');
  debug('POST情報：'.print_r($_POST,true));

  $auth_key = $_POST['token'];

  validRequired($auth_key, 'token');

  if(empty($err_msg)){
    debug('未入力チェックOK。');

    validLength($auth_key, 'token');
    validHalf($auth_key, 'token');

    if(empty($err_msg)){
      debug('バリデーションOK。');

      if($auth_key !== $_SESSION['auth_key']){
        $err_msg['common'] = MSG15;
      }
      if(time() > $_SESSION['auth_key_limit']){
        $err_msg['common'] = MSG16;
      }

      if(empty($err_msg)){
        debug('認証OK。');

        $pass = makeRandKey();

        try {
          $dbh = dbConnect();
          $sql = 'UPDATE users SET password = :pass WHERE email = :email AND delete_flg = 0';
          $data = array(':email' => $_SESSION['auth_email'], ':pass' => password_hash($pass, PASSWORD_DEFAULT));
          $stmt = queryPost($dbh, $sql, $data);

          if($stmt){
            debug('クエリ成功。');

            $from = 'info@Tojapan.com';
            $to = $_SESSION['auth_email'];
            $subject = '【パスワード再発行完了】｜TOJAPAN';
            $comment = <<<EOT
本メールアドレス宛にパスワードの再発行を致しました。
下記のURLにて再発行パスワードをご入力頂き、ログインください。

ログインページ：http://localhost/output/to_japan/login.php
再発行パスワード：{$pass}
※ログイン後、パスワードのご変更をお願い致します

////////////////////////////////////////
ジャパントラベルカスタマーセンター
URL  http://Tojapan.com/
E-mail info@Tojapan.com
////////////////////////////////////////
EOT;
            sendMail($from, $to, $subject, $comment);

            session_unset();
            $_SESSION['msg_success'] = SUC03;
            debug('セッション変数の中身：'.print_r($_SESSION,true));

            header("Location:login.php");

          }else{
            debug('クエリに失敗しました。');
            $err_msg['common'] = MSG07;
          }

        } catch (Exception $e) {
          error_log('エラー発生:' . $e->getMessage());
          $err_msg['common'] = MSG07;
        }
      }
    }
  }
}
?>
<?php
$siteTitle = 'パスワード再発行認証';
require('head.php');
?>

  <body class="page-signup page-1colum">

    <!-- メニュー -->
    <?php
      require('header.php');
    ?>
    <p id="js-show-msg" style="display:none;" class="msg-slide">
      <?php echo getSessionFlash('msg_success'); ?>
    </p>

    <!-- メインコンテンツ -->
    <div id="contents" class="site-width">

      <!-- Main -->
      <section id="main" >

        <div class="form-container">

          <form action="" method="post" class="form">
            <p>ご指定のメールアドレスお送りした【パスワード再発行認証】メール内にある「認証キー」をご入力ください。</p>
            <div class="area-msg">
             <?php if(!empty($err_msg['common'])) echo $err_msg['common']; ?>
            </div>
            <label class="<?php if(!empty($err_msg['token'])) echo 'err'; ?>">
              認証キー
              <input type="text" name="token" value="<?php echo getFormData('token'); ?>">
            </label>
            <div class="area-msg">
             <?php if(!empty($err_msg['token'])) echo $err_msg['token']; ?>
            </div>
            <div class="btn-container">
              <input type="submit" class="btn btn-mid" value="再発行する">
            </div>
          </form>
        </div>
        <a href="passRemindSend.php">&lt; パスワード再発行メールを再度送信する</a>
      </section>

    </div>

    <!-- footer -->
    <?php
    require('footer.php');
    ?>