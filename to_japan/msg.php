<?php

require('function.php');

debug('>>>>>>>>>>>>>>>>>>>>>>>>連絡板画面処理開始');

debugLogStart();

//処理
$partnerUserId = '';
$partnerUserInfo = '';
$myUserInfo = '';
$productInfo = '';

//取得できている
$m_id = (!empty($_GET['m_id'])) ? $_GET['m_id'] : '';

$viewData = getMsgsAndBord($m_id);
debug('取得した$viewDataデータ：'.print_r($viewData,true));

if(empty($viewData)){
  error_log('エラー発生:指定ページに不正な値が入りました');
  header("Location:mypage.php");
  exit;
}

$productInfo = getProductOne($viewData[0]['product_id']);
debug('取得したDBデータ：'.print_r($productInfo,true));

if(empty($productInfo)){
  error_log('エラー発生:商品情報が取得できませんでした');
  header("Location:mypage.php");
  exit;
}

$dealUserIds[] = $viewData[0]['sale_user'];
$dealUserIds[] = $viewData[0]['buy_user'];
if(($key = array_search($_SESSION['user_id'], $dealUserIds)) !== false) {
    unset($dealUserIds[$key]);
}
$partnerUserId = array_shift($dealUserIds);
debug('取得した相手のユーザーID：'.$partnerUserId);

if(isset($partnerUserId)){
  $partnerUserInfo = getUser($partnerUserId);
}

if(empty($partnerUserInfo)){
  error_log('エラー発生:相手のユーザー情報が取得できませんでした');
  header("Location:mypage.php");
  exit;
}

$myUserInfo = getUser($_SESSION['user_id']);
debug('取得したユーザデータ：'.print_r($partnerUserInfo,true));

if(empty($myUserInfo)){
  error_log('エラー発生:自分のユーザー情報が取得できませんでした');
  header("Location:mypage.php");
  exit;
}

if(!empty($_POST)){
  debug('POST送信があります。');

  require('auth.php');

  $msg = (isset($_POST['msg'])) ? $_POST['msg'] : '';
  validMaxLen($msg, 'msg', 500);
  validRequired($msg, 'msg');

  if(empty($err_msg)){
    debug('バリデーションOKです。');

    try {
      $dbh = dbConnect();
      $sql = 'INSERT INTO message (bord_id, send_date, to_user, from_user, msg, create_date) VALUES (:b_id, :send_date, :to_user, :from_user, :msg, :date)';
      $data = array(':b_id' => $m_id, ':send_date' => date('Y-m-d H:i:s'), ':to_user' => $partnerUserId, ':from_user' => $_SESSION['user_id'], ':msg' => $msg, ':date' => date('Y-m-d H:i:s'));

      $stmt = queryPost($dbh, $sql, $data);

      if($stmt){
        $_POST = array();
        debug('連絡掲示板へ遷移します。');
        header("Location: " . $_SERVER['PHP_SELF'] .'?m_id='.$m_id);
      }

    } catch (Exception $e) {
      error_log('エラー発生:' . $e->getMessage());
      $err_msg['common'] = MSG07;
    }
  }
}
debug('>>>>>>>>>>>>>>>>>>>>>>>>連絡板画面処理終了');
?>
<?php
$siteTitle = '連絡掲示板';
require('head.php');
?>

  <body class="page-msg page-1colum">
    <style>

      .msg-info{
        background: #f6f5f4;
        padding: 15px;
        overflow: hidden;
        margin-bottom: 15px;
      }
      .msg-info .avatar{
        width: 80px;
        height: 80px;
        border-radius: 40px;
      }
      .msg-info .avatar-img{
        text-align: center;
        width: 100px;
        float: left;
      }
      .msg-info .avatar-info{
        float: left;
        padding-left: 15px;
        width: 500px;
      }
      .msg-info .product-info{
        float: left;
        padding-left: 15px;
        width: 315px;
      }
      .msg-info .product-info .left,
      .msg-info .product-info .right{
        float: left;
      }
      .msg-info .product-info .right{
        padding-left: 15px;
      }
      .msg-info .product-info .price{
        display: inline-block;
      }
      .area-bord{
        height: 500px;
        overflow-y: scroll;
        background: #f6f5f4;
        padding: 15px;
      }
      .area-send-msg{
        background: #f6f5f4;
        padding: 15px;
        overflow: hidden;
      }
      .area-send-msg textarea{
        width:100%;
        background: white;
        height: 100px;
        padding: 15px;
      }
      .area-send-msg .btn-send{
        width: 150px;
        float: right;
        margin-top: 0;
      }
      .area-bord .msg-cnt{
        width: 80%;
        overflow: hidden;
        margin-bottom: 30px;
      }
      .area-bord .msg-cnt .avatar{
        width: 5.2%;
        overflow: hidden;
        float: left;
      }
      .area-bord .msg-cnt .avatar img{
        width: 40px;
        height: 40px;
        border-radius: 20px;
        float: left;
      }
      .area-bord .msg-cnt .msg-inrTxt{
        width: 85%;
        float: left;
        border-radius: 5px;
        padding: 10px;
        margin: 0 0 0 25px;
        position: relative;
      }
      .area-bord .msg-cnt.msg-left .msg-inrTxt{
        background: #f6e2df;
      }
      .area-bord .msg-cnt.msg-left .msg-inrTxt > .triangle{
        position: absolute;
        left: -20px;
        width: 0;
        height: 0;
        border-top: 10px solid transparent;
        border-right: 15px solid #f6e2df;
        border-left: 10px solid transparent;
        border-bottom: 10px solid transparent;
      }
      .area-bord .msg-cnt.msg-right{
        float: right;
      }
      .area-bord .msg-cnt.msg-right .msg-inrTxt{
        background: #d2eaf0;
        margin: 0 25px 0 0;
      }
      .area-bord .msg-cnt.msg-right .msg-inrTxt > .triangle{
        position: absolute;
        right: -20px;
        width: 0;
        height: 0;
        border-top: 10px solid transparent;
        border-left: 15px solid #d2eaf0;
        border-right: 10px solid transparent;
        border-bottom: 10px solid transparent;
      }
      .area-bord .msg-cnt.msg-right .msg-inrTxt{
        float: right;
      }
      .area-bord .msg-cnt.msg-right .avatar{
        float: right;
      }
    </style>

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
        <div class="msg-info">
          <div class="avatar-img">
            <img src="<?php echo showImg(sanitize($partnerUserInfo['pic'])); ?>" alt="" class="avatar"><br>
          </div>
          <div class="avatar-info">
            <?php echo sanitize($partnerUserInfo['username']).' '.sanitize($partnerUserInfo['age']).'歳' ?><br>
            〒<?php echo wordwrap($partnerUserInfo['zip'], 4, "-", true); ?><br>
            <?php echo sanitize($partnerUserInfo['addr']); ?><br>
            TEL：<?php echo sanitize($partnerUserInfo['tel']); ?>
          </div>
          <div class="product-info">
            <div class="left">
              取引商品<br>
              <img src="<?php echo showImg(sanitize($productInfo['pic1'])); ?>" alt="" height="70px" width="auto" >
            </div>
            <div class="right">
              <?php echo sanitize($productInfo['name']); ?><br>
              取引金額：<span class="price">¥<?php echo number_format(sanitize($productInfo['price'])); ?></span><br>
              取引開始日：<?php echo date('Y/m/d', strtotime(sanitize($viewData[0]['create_date']))); ?>
            </div>
          </div>
        </div>
        <div class="area-bord" id="js-scroll-bottom">
        <?php
            if(!empty($viewData)){
              foreach($viewData as $key => $val){
                  if(!empty($val['from_user']) && $val['from_user'] == $partnerUserId){
        ?>
                    <div class="msg-cnt msg-left">
                      <div class="avatar">
                        <img src="<?php echo sanitize(showImg($partnerUserInfo['pic'])); ?>" alt="" class="avatar">
                      </div>
                      <p class="msg-inrTxt">
                        <span class="triangle"></span>
                        <?php echo sanitize($val['msg']); ?>
                      </p>
                      <div style="font-size:.5em;"><?php echo sanitize($val['send_date']); ?></div>
                    </div>
            <?php
                  }else{
            ?>
                    <div class="msg-cnt msg-right">
                      <div class="avatar">
                        <img src="<?php echo sanitize(showImg($myUserInfo['pic'])); ?>" alt="" class="avatar">
                      </div>
                      <p class="msg-inrTxt">
                        <span class="triangle"></span>
                        <?php echo sanitize($val['msg']); ?>
                      </p>
                      <div style="font-size:.5em;text-align:right;"><?php echo sanitize($val['send_date']); ?></div>
                    </div>
            <?php
                  }
                }
              }else{
            ?>
                <p style="text-align:center;line-height:20;">メッセージ投稿はまだありません</p>
            <?php
              }
          ?>

        </div>
        <div class="area-send-msg">
          <form action="" method="post">
            <textarea name="msg" cols="30" rows="3"></textarea>
            <input type="submit" value="送信" class="btn btn-send">
          </form>
        </div>
      </section>

      <script src="js/vendor/jquery-2.2.2.min.js"></script>

      <script>
        $(function(){
          $('#js-scroll-bottom').animate({scrollTop: $('#js-scroll-bottom')[0].scrollHeight}, 'fast');
        });
      </script>

    </div>

    <!-- footer -->
    <?php
      require('footer.php');
    ?>
