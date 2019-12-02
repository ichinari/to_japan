<?php

require('function.php');

debug('>>>>>>>>>>>>>>>>>>>>>>>>パスワード変更画面処理開始');

debugLogStart();

require('auth.php');

//処理
$userData = getUser($_SESSION['user_id']);
debug('取得したユーザー情報：'.print_r($userData,true));

if(!empty($_POST)){
  debug('POST送信があります。');
  debug('POST情報：'.print_r($_POST,true));

  $pass_old = $_POST['pass_old'];
  $pass_new = $_POST['pass_new'];
  $pass_new_re = $_POST['pass_new_re'];

  validRequired($pass_old, 'pass_old');
  validRequired($pass_new, 'pass_new');
  validRequired($pass_new_re, 'pass_new_re');

  if(empty($err_msg)){
    debug('未入力チェックOK。');

    validPass($pass_old, 'pass_old');
    validPass($pass_new, 'pass_new');

    if(!password_verify($pass_old, $userData['password'])){
      $err_msg['pass_old'] = MSG12;
    }

    if($pass_old === $pass_new){
      $err_msg['pass_new'] = MSG13;
    }
    validMatch($pass_new, $pass_new_re, 'pass_new_re');

    if(empty($err_msg)){
      debug('バリデーションOK。');

      try {
        $dbh = dbConnect();
        $sql = 'UPDATE users SET password = :pass WHERE id = :id';
        $data = array(':id' => $_SESSION['user_id'], ':pass' => password_hash($pass_new, PASSWORD_DEFAULT));
        $stmt = queryPost($dbh, $sql, $data);


        if($stmt){
          $_SESSION['msg_success'] = SUC01;


          $username = ($userData['username']) ? $userData['username'] : '名無し';
          $from = 'info@Tojapn.com';
          $to = $userData['email'];
          $subject = 'パスワード変更通知｜TOJAPAN';
          $comment = <<<EOT
{$username}　さん
パスワードが変更されました。

////////////////////////////////////////
TO japan カスタマーセンター
URL  http://Tojapan.com/
E-mail info@Tojapan.com
////////////////////////////////////////
EOT;
          sendMail($from, $to, $subject, $comment);

          header("Location:mypage.php");
          exit;
        }

      } catch (Exception $e) {
        error_log('エラー発生:' . $e->getMessage());
        $err_msg['common'] = MSG07;
      }
    }
  }
}
?>
<?php
$siteTitle = 'パスワード変更';
require('head.php');
?>

  <body class="page-passEdit page-2colum page-logined">
    <style>
      .form{
        margin-top: 50px;
      }
    </style>

    <!-- メニュー -->
    <?php
      require('header.php');
    ?>

    <!-- メインコンテンツ -->
    <div id="contents" class="site-width">
      <h1 class="page-title">パスワード変更</h1>
      <!-- Main -->
      <section id="main" >
        <div class="form-container">
          <form action="" method="post" class="form">
           <div class="area-msg">
             <?php
             echo getErrMsg('common');
             ?>
           </div>
            <label class="<?php if(!empty($err_msg['pass_old'])) echo 'err'; ?>">
              古いパスワード
              <input type="password" name="pass_old" value="<?php echo getFormData('pass_old'); ?>">
            </label>
            <div class="area-msg">
              <?php
              echo getErrMsg('pass_old');
              ?>
            </div>
            <label class="<?php if(!empty($err_msg['pass_new'])) echo 'err'; ?>">
              新しいパスワード
              <input type="password" name="pass_new" value="<?php echo getFormData('pass_new'); ?>">
            </label>
            <div class="area-msg">
              <?php
                echo getErrMsg('pass_new');
              ?>
            </div>
            <label class="<?php if(!empty($err_msg['pass_new_re'])) echo 'err'; ?>">
              新しいパスワード（再入力）
              <input type="password" name="pass_new_re" value="<?php echo getFormData('pass_new_re'); ?>">
            </label>
            <div class="area-msg">
              <?php
              echo getErrMsg('pass_new_re');
              ?>
            </div>
            <div class="btn-container">
              <input type="submit" class="btn btn-mid" value="変更する">
            </div>
          </form>
        </div>
      </section>

      <!-- サイドバー -->
      <?php
      require('sidebar_mypage.php');
      ?>

    </div>

    <!-- footer -->
    <?php
    require('footer.php');
    ?>
