<?php

require('function.php');

debug('>>>>>>>>>>>>>>>>>>>>>>>>パスワード再発行メール送信画面処理開始');

debugLogStart();


//画面処理

if(!empty($_POST)){
  debug('POST送信があります。');
  debug('POST情報：'.print_r($_POST,true));

  $email = $_POST['email'];

  validRequired($email, 'email');

  if(empty($err_msg)){
    debug('未入力チェックOK。');

    validEmail($email, 'email');
    validMaxLen($email, 'email');

    if(empty($err_msg)){
      debug('バリデーションOK。');

      try {
        $dbh = dbConnect();
        $sql = 'SELECT count(*) FROM users WHERE email = :email AND delete_flg = 0';
        $data = array(':email' => $email);
        $stmt = queryPost($dbh, $sql, $data);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if($stmt && array_shift($result)){
          debug('クエリ成功。DB登録あり。');
          $_SESSION['msg_success'] = SUC03;

          $auth_key = makeRandKey();

          $from = 'info@japantravel.com';
          $to = $email;
          $subject = '【パスワード再発行認証】｜JAPANTRAVEL';
          $comment = <<<EOT
本メールアドレス宛にパスワード再発行のご依頼がありました。
下記のURLにて認証キーをご入力頂くとパスワードが再発行されます。

パスワード再発行認証キー入力ページ：http://localhost/output/japan_travel/passRemindRecieve.php
認証キー：{$auth_key}
※認証キーの有効期限は30分となります

認証キーを再発行されたい場合は下記ページより再度再発行をお願い致します。
http://localhost/output/japan_travel/passRemindSend.php

////////////////////////////////////////
ウェブカツマーケットカスタマーセンター
URL  http://japantravel.com/
E-mail info@japantravel.com
////////////////////////////////////////
EOT;
          sendMail($from, $to, $subject, $comment);

          $_SESSION['auth_key'] = $auth_key;
          $_SESSION['auth_email'] = $email;
          $_SESSION['auth_key_limit'] = time()+(60*30);
          debug('セッション変数の中身：'.print_r($_SESSION,true));

          header("Location:passRemindRecieve.php");
          exit;
        }else{
          debug('クエリに失敗したかDBに登録のないEmailが入力されました。');
          $err_msg['common'] = MSG07;
        }

      } catch (Exception $e) {
        error_log('エラー発生:' . $e->getMessage());
        $err_msg['common'] = MSG07;
      }
    }
  }
}
debug('>>>>>>>>>>>>>>>>>>>>>>>>パスワード再発行メール送信画面処理終了');

?>
<?php
$siteTitle = 'パスワード再発行メール送信';
require('head.php');
?>

  <body class="page-signup page-1colum">

    <!-- メニュー -->
    <?php
    require('header.php');
    ?>

    <!-- メインコンテンツ -->
    <div id="contents" class="site-width">

      <!-- Main -->
      <section id="main" >

        <div class="form-container">

          <form action="" method="post" class="form">
           <p>ご指定のメールアドレス宛にパスワード再発行用のURLと認証キーをお送り致します。</p>
           <div class="area-msg">
            <?php
            if(!empty($err_msg['common'])) echo $err_msg['common'];
            ?>
          </div>
            <label class="<?php if(!empty($err_msg['email'])) echo 'err'; ?>">
              Email
              <input type="text" name="email" value="<?php echo getFormData('email'); ?>">
            </label>
            <div class="area-msg">
              <?php
              if(!empty($err_msg['email'])) echo $err_msg['email'];
              ?>
            </div>
            <div class="btn-container">
              <input type="submit" class="btn btn-mid" value="送信する">
            </div>
          </form>
        </div>
        <a href="mypage.php">&lt; マイページに戻る</a>
      </section>

    </div>

    <!-- footer -->
    <?php
    require('footer.php');
    ?>