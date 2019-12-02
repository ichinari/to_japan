<?php
//================================
// ログ
//================================

  ini_set('log_errors','on');
  ini_set('error_log','php.log');

//================================
// デバッグ
//================================

  $debug_flg = true;

  function debug($str){
    global $debug_flg;
    if(!empty($debug_flg)){
      error_log('デバッグ：'.$str);
    }
  }

//================================
// セッション準備・セッション有効期限を延ばす
//================================

  session_save_path("/var/tmp/");

  ini_set('session.gc_maxlifetime', 60*60*24*30);

  ini_set('session.cookie_lifetime ', 60*60*24*30);

  session_start();

  session_regenerate_id();

//================================
// デバッグ開始関数
//================================

  function debugLogStart(){
    debug('>>>>>>>>>>>>>>>>>>>>>>>>画面表示処理開始');
    debug('セッションID：'.session_id());
    debug('セッション変数の中身：'.print_r($_SESSION,true));
    debug('現在日時タイムスタンプ：'.time());
    if(!empty($_SESSION['login_date']) && !empty($_SESSION['login_limit'])){
      debug( 'ログイン期限日時タイムスタンプ：'.( $_SESSION['login_date'] + $_SESSION['login_limit'] ) );
    }
  }

//================================
// 定数
//================================

  define('MSG01','入力必須です');
  define('MSG02', 'Emailの形式で入力してください');
  define('MSG03','パスワード（再入力）が合っていません');
  define('MSG04','半角英数字のみご利用いただけます');
  define('MSG05','6文字以上で入力してください');
  define('MSG06','255文字以内で入力してください');
  define('MSG07','エラーが発生しました。しばらく経ってからやり直してください。');
  define('MSG08', 'そのEmailは既に登録されています');
  define('MSG09', 'メールアドレスまたはパスワードが違います');
  define('MSG10', '電話番号の形式が違います');
  define('MSG11', '郵便番号の形式が違います');
  define('MSG12', '古いパスワードが違います');
  define('MSG13', '古いパスワードと同じです');
  define('MSG14', '文字で入力してください');
  define('MSG15', '正しくありません');
  define('MSG16', '有効期限が切れています');
  define('MSG17', '半角数字のみご利用いただけます');
  define('SUC01', 'パスワードを変更しました');
  define('SUC02', 'プロフィールを変更しました');
  define('SUC03', 'メールを送信しました');
  define('SUC04', '登録しました');
  define('SUC05', '購入しました！相手と連絡を取りましょう！');

//================================
// グローバル変数
//================================

  $err_msg = array();

//================================
// バリデーション関数
//================================

  function validRequired($str, $key){
    if($str === ''){
      global $err_msg;
      $err_msg[$key] = MSG01;
    }
  }
  function validEmail($str){
    if(!preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $str)){
      global $err_msg;
      $err_msg['email'] = MSG02;
    }
  }

  function validEmailDup($email){
    global $err_msg;


    try {

      $dbh = dbConnect();

      $sql = 'SELECT count(*) FROM users WHERE email = :email AND delete_flg = 0';
      $data = array(':email' => $email);

      $stmt = queryPost($dbh, $sql, $data);

      $result = $stmt->fetch(PDO::FETCH_ASSOC);

      if(!empty(array_shift($result))){
        $err_msg['email'] = MSG08;
      }
    } catch (Exception $e) {
      error_log('エラー発生:' . $e->getMessage());
      $err_msg['common'] = MSG07;
    }
  }

  function validMatch($str1, $str2, $key){
    if($str1 !== $str2){
      global $err_msg;
      $err_msg[$key] = MSG03;
    }
  }

  function validMinLen($str, $key, $min = 6){
    if(mb_strlen($str) < $min){
      global $err_msg;
      $err_msg[$key] = MSG05;
    }
  }

  function validMaxLen($str, $key, $max = 256){
    if(mb_strlen($str) > $max){
      global $err_msg;
      $err_msg[$key] = MSG06;
    }
  }

  function validHalf($str, $key){
    if(!preg_match("/^[a-zA-Z0-9]+$/", $str)){
      global $err_msg;
      $err_msg[$key] = MSG04;
    }
  }

  function validTel($str, $key){
    if(!preg_match("/0\d{1,4}\d{1,4}\d{4}/", $str)){
      global $err_msg;
      $err_msg[$key] = MSG10;
    }
  }

  function validZip($str, $key){
    if(!preg_match("/^\d{7}$/", $str)){
      global $err_msg;
      $err_msg[$key] = MSG11;
    }
  }

  function validNumber($str, $key){
    if(!preg_match("/^[0-9]+$/", $str)){
      global $err_msg;
      $err_msg[$key] = MSG17;
    }
  }

  function validLength($str, $key, $len = 8){
    if( mb_strlen($str) !== $len ){
      global $err_msg;
      $err_msg[$key] = $len . MSG14;
    }
  }

  function validPass($str, $key){
    validHalf($str, $key);
    validMaxLen($str, $key);
    validMinLen($str, $key);
  }

  function validSelect($str, $key){
    if(!preg_match("/^[1-9]+$/", $str)){
      global $err_msg;
      $err_msg[$key] = MSG15;
    }
  }

  function getErrMsg($key){
    global $err_msg;
    if(!empty($err_msg[$key])){
      return $err_msg[$key];
    }
  }

//================================
// ログイン認証
//================================

  function isLogin(){
    if( !empty($_SESSION['login_date']) ){
      debug('ログイン済みユーザーです。');

      if( ($_SESSION['login_date'] + $_SESSION['login_limit']) < time()){
        debug('ログイン有効期限オーバーです。');

        session_destroy();
        return false;
      }else{
        debug('ログイン有効期限以内です。');
        return true;
      }

    }else{
      debug('未ログインユーザーです。');
      return false;
    }
  }

//================================
// データベース
//================================

  function dbConnect(){
    $dsn = 'mysql:dbname=output;host=localhost;charset=utf8';
    $user = 'root';
    $password = 'root';
    $options = array(
      PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    );
    $dbh = new PDO($dsn, $user, $password, $options);
    return $dbh;
  }
  function queryPost($dbh, $sql, $data){
    $stmt = $dbh->prepare($sql);
    if(!$stmt->execute($data)){
      debug('クエリに失敗しました。');
      debug('失敗したSQL：'.print_r($stmt,true));
      $err_msg['common'] = MSG07;
      return 0;
    }
    debug('クエリ成功。');
    return $stmt;
  }

  function getUser($u_id){
    debug('ユーザー情報を取得します。');
    try {
      $dbh = dbConnect();
      $sql = 'SELECT * FROM users  WHERE id = :u_id AND delete_flg = 0';
      $data = array(':u_id' => $u_id);
      $stmt = queryPost($dbh, $sql, $data);

      if($stmt){
        return $stmt->fetch(PDO::FETCH_ASSOC);
      }else{
        return false;
      }

    } catch (Exception $e) {
      error_log('エラー発生:' . $e->getMessage());
    }
  }

  function getProduct($u_id, $p_id){
    debug('商品情報を取得します。');
    debug('ユーザーID：'.$u_id);
    debug('商品ID：'.$p_id);
    try {
      $dbh = dbConnect();
      $sql = 'SELECT * FROM product WHERE user_id = :u_id AND id = :p_id AND delete_flg = 0';
      $data = array(':u_id' => $u_id, ':p_id' => $p_id);
      $stmt = queryPost($dbh, $sql, $data);

      if($stmt){
        return $stmt->fetch(PDO::FETCH_ASSOC);
      }else{
        return false;
      }

    } catch (Exception $e) {
      error_log('エラー発生:' . $e->getMessage());
    }
  }

  function getProductList($currentMinNum = 1, $category, $sort, $span = 20){
    debug('商品情報を取得します。');
    try {
      $dbh = dbConnect();
      $sql = 'SELECT id FROM product';
      if(!empty($category)) $sql .= ' WHERE category_id = '.$category;
      if(!empty($sort)){
        switch($sort){
          case 1:
            $sql .= ' ORDER BY price ASC';
            break;
          case 2:
            $sql .= ' ORDER BY price DESC';
            break;
        }
      }
      $data = array();
      $stmt = queryPost($dbh, $sql, $data);
      $rst['total'] = $stmt->rowCount();
      $rst['total_page'] = ceil($rst['total']/$span);
      if(!$stmt){
        return false;
      }

      $sql = 'SELECT * FROM product';
      if(!empty($category)) $sql .= ' WHERE category_id = '.$category;
      if(!empty($sort)){
        switch($sort){
          case 1:
            $sql .= ' ORDER BY price ASC';
            break;
          case 2:
            $sql .= ' ORDER BY price DESC';
            break;
        }
      }
      $sql .= ' LIMIT '.$span.' OFFSET '.$currentMinNum;
      $data = array();
      debug('SQL：'.$sql);
      $stmt = queryPost($dbh, $sql, $data);

      if($stmt){
        $rst['data'] = $stmt->fetchAll();
        return $rst;
      }else{
        return false;
      }

    } catch (Exception $e) {
      error_log('エラー発生:' . $e->getMessage());
    }
  }

  function getProductOne($p_id){
    debug('商品情報を取得します。');
    debug('商品ID：'.$p_id);
    try {
      $dbh = dbConnect();
      $sql = 'SELECT p.id, p.name, p.comment, p.price, p.pic1, p.pic2, p.pic3, p.user_id, p.create_date, p.update_date, c.name AS category
              FROM product AS p LEFT JOIN category AS c ON p.category_id = c.id WHERE p.id = :p_id AND p.delete_flg = 0 AND c.delete_flg = 0';
      $data = array(':p_id' => $p_id);
      $stmt = queryPost($dbh, $sql, $data);

      if($stmt){
        return $stmt->fetch(PDO::FETCH_ASSOC);
      }else{
        return false;
      }

    } catch (Exception $e) {
      error_log('エラー発生:' . $e->getMessage());
    }
  }

  function getMyProducts($u_id){
    debug('自分の商品情報を取得します。');
    debug('ユーザーID：'.$u_id);
    try {
      $dbh = dbConnect();
      $sql = 'SELECT * FROM product WHERE user_id = :u_id AND delete_flg = 0';
      $data = array(':u_id' => $u_id);
      $stmt = queryPost($dbh, $sql, $data);

      if($stmt){
        return $stmt->fetchAll();
      }else{
        return false;
      }
    } catch (Exception $e) {
      error_log('エラー発生:' . $e->getMessage());
    }
  }

  function getMsgsAndBord($id){
    debug('msg情報を取得します。');
    debug('掲示板ID：'.$id);
    try {
      $dbh = dbConnect();
      $sql = 'SELECT * FROM message AS m RIGHT JOIN bord AS b ON b.id = m.bord_id WHERE b.id = :id ORDER BY send_date ASC';
      $data = array(':id' => $id);
      $stmt = queryPost($dbh, $sql, $data);

      if($stmt){
        return $stmt->fetchAll();
      }else{
        return false;
      }

    } catch (Exception $e) {
      error_log('エラー発生:' . $e->getMessage());
    }
  }

  function getMyMsgsAndBord($u_id){
    debug('自分のmsg情報を取得します。');

    try {
      $dbh = dbConnect();
      $sql = 'SELECT * FROM bord AS b WHERE b.sale_user = :id OR b.buy_user = :id AND b.delete_flg = 0';
      $data = array(':id' => $u_id);

      $stmt = queryPost($dbh, $sql, $data);
      $rst = $stmt->fetchAll();
      if(!empty($rst)){
        foreach($rst as $key => $val){
          $sql = 'SELECT * FROM message WHERE bord_id = :id AND delete_flg = 0 ORDER BY send_date DESC';
          $data = array(':id' => $val['id']);
          $stmt = queryPost($dbh, $sql, $data);
          $rst[$key]['msg'] = $stmt->fetchAll();
        }
      }

      if($stmt){
        return $rst;
      }else{
        return false;
      }

    } catch (Exception $e) {
      error_log('エラー発生:' . $e->getMessage());
    }
    debug('自分のmsg情報を取得します');
  }

  function getCategory(){
    debug('カテゴリー情報を取得します。');
    try {
      $dbh = dbConnect();
      $sql = 'SELECT * FROM category';
      $data = array();
      $stmt = queryPost($dbh, $sql, $data);

      if($stmt){
        return $stmt->fetchAll();
      }else{
        return false;
      }

    } catch (Exception $e) {
      error_log('エラー発生:' . $e->getMessage());
    }
  }

  function isLike($u_id, $p_id){
    debug('お気に入り情報があるか確認します。');
    debug('ユーザーID：'.$u_id);
    debug('商品ID：'.$p_id);
    try {
      $dbh = dbConnect();
      $sql = 'SELECT * FROM `like` WHERE product_id = :p_id AND user_id = :u_id';
      $data = array(':u_id' => $u_id, ':p_id' => $p_id);
      $stmt = queryPost($dbh, $sql, $data);

      if($stmt->rowCount()){
        debug('お気に入りです');
        return true;
      }else{
        debug('特に気に入ってません');
        return false;
      }

    } catch (Exception $e) {
      error_log('エラー発生:' . $e->getMessage());
    }
  }

  function getMyLike($u_id){
    debug('自分のお気に入り情報を取得します。');
    debug('ユーザーID：'.$u_id);
    try {
      $dbh = dbConnect();
      $sql = 'SELECT * FROM `like` AS l LEFT JOIN product AS p ON l.product_id = p.id WHERE l.user_id = :u_id';
      $data = array(':u_id' => $u_id);
      $stmt = queryPost($dbh, $sql, $data);

      if($stmt){
        return $stmt->fetchAll();
      }else{
        return false;
      }

    } catch (Exception $e) {
      error_log('エラー発生:' . $e->getMessage());
    }
  }

//================================
// メール送信
//================================

  function sendMail($from, $to, $subject, $comment){
      if(!empty($to) && !empty($subject) && !empty($comment)){
          mb_language("Japanese");
          mb_internal_encoding("UTF-8");
          $result = mb_send_mail($to, $subject, $comment, "From: ".$from);
          if ($result) {
            debug('メールを送信しました。');
          } else {
            debug('【エラー発生】メールの送信に失敗しました。');
          }
      }
  }

//================================
// その他
//================================

  function sanitize($str){
    return htmlspecialchars($str,ENT_QUOTES);
  }

  function getFormData($str, $flg = false){
    if($flg){
      $method = $_GET;
    }else{
      $method = $_POST;
    }
    global $dbFormData;
    global $err_msg;
    if(!empty($dbFormData)){
      if(!empty($err_msg[$str])){
        if(isset($method[$str])){
          return sanitize($method[$str]);
        }else{
          return sanitize($dbFormData[$str]);
        }
      }else{
        if(isset($method[$str]) && $method[$str] !== $dbFormData[$str]){
          return sanitize($method[$str]);
        }else{
          return sanitize($dbFormData[$str]);
        }
      }
    }else{
      if(isset($method[$str])){
        return sanitize($method[$str]);
      }
    }
  }

  function getSessionFlash($key){
    if(!empty($_SESSION[$key])){
      $data = $_SESSION[$key];
      $_SESSION[$key] = '';
      return $data;
    }
  }

  function makeRandKey($length = 8) {
      $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJLKMNOPQRSTUVWXYZ0123456789';
      $str = '';
      for ($i = 0; $i < $length; ++$i) {
          $str .= $chars[mt_rand(0, 61)];
      }
      return $str;
  }

  function uploadImg($file, $key){
    debug('画像アップロード処理開始');
    debug('FILE情報：'.print_r($file,true));

    if (isset($file['error']) && is_int($file['error'])) {
      try {
        switch ($file['error']) {
              case UPLOAD_ERR_OK:
              break;
              case UPLOAD_ERR_NO_FILE:
                  throw new RuntimeException('ファイルが選択されていません');
              break;
              case UPLOAD_ERR_INI_SIZE:
                  throw new RuntimeException('ファイルサイズが大きすぎます');

              break;
              case UPLOAD_ERR_FORM_SIZE:
                  throw new RuntimeException('ファイルサイズが大きすぎます');
              break;
              default:
                  throw new RuntimeException('その他のエラーが発生しました');
              break;
        }

        // $file['mime']の値はブラウザ側で偽装可能なので、MIMEタイプを自前でチェックする
        // exif_imagetype関数は「IMAGETYPE_GIF」「IMAGETYPE_JPEG」などの定数を返す
        $type = @exif_imagetype($file['tmp_name']);
        if (!in_array($type, [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) { // 第三引数にはtrueを設定すると厳密にチェックしてくれるので必ずつける
            throw new RuntimeException('画像形式が未対応です');
        }

        // ファイルデータからSHA-1ハッシュを取ってファイル名を決定し、ファイルを保存する
        // ハッシュ化しておかないとアップロードされたファイル名そのままで保存してしまうと同じファイル名がアップロードされる可能性があり、
        // DBにパスを保存した場合、どっちの画像のパスなのか判断つかなくなってしまう
        // image_type_to_extension関数はファイルの拡張子を取得するもの
        $path = 'uploads/'.sha1_file($file['tmp_name']).image_type_to_extension($type);
        if (!move_uploaded_file($file['tmp_name'], $path)) { //ファイルを移動する
            throw new RuntimeException('ファイル保存時にエラーが発生しました');
        }
        // 保存したファイルパスのパーミッション（権限）を変更する
        chmod($path, 0644);

        debug('ファイルは正常にアップロードされました');
        debug('ファイルパス：'.$path);
        return $path;

      } catch (RuntimeException $e) {

        debug($e->getMessage());
        global $err_msg;
        $err_msg[$key] = $e->getMessage();

      }
    }
  }

  function pagination( $currentPageNum, $totalPageNum, $link = '', $pageColNum = 5){
    if( $currentPageNum == $totalPageNum && $totalPageNum > $pageColNum){
      $minPageNum = $currentPageNum - 4;
      $maxPageNum = $currentPageNum;
    }elseif( $currentPageNum == ($totalPageNum-1) && $totalPageNum > $pageColNum){
      $minPageNum = $currentPageNum - 3;
      $maxPageNum = $currentPageNum + 1;
    }elseif( $currentPageNum == 2 && $totalPageNum > $pageColNum){
      $minPageNum = $currentPageNum - 1;
      $maxPageNum = $currentPageNum + 3;
    }elseif( $currentPageNum == 1 && $totalPageNum > $pageColNum){
      $minPageNum = $currentPageNum;
      $maxPageNum = 5;
    }elseif($totalPageNum < $pageColNum){
      $minPageNum = 1;
      $maxPageNum = $totalPageNum;
    }else{
      $minPageNum = $currentPageNum - 2;
      $maxPageNum = $currentPageNum + 2;
    }

    echo '<div class="pagination">';
      echo '<ul class="pagination-list">';
        if($currentPageNum != 1){
          echo '<li class="list-item"><a href="?p=1'.$link.'">&lt;</a></li>';
        }
        for($i = $minPageNum; $i <= $maxPageNum; $i++){
          echo '<li class="list-item ';
          if($currentPageNum == $i ){ echo 'active'; }
          echo '"><a href="?p='.$i.$link.'">'.$i.'</a></li>';
        }
        if($currentPageNum != $maxPageNum && $maxPageNum > 1){
          echo '<li class="list-item"><a href="?p='.$maxPageNum.$link.'">&gt;</a></li>';
        }
      echo '</ul>';
    echo '</div>';
  }

  function showImg($path){
    if(empty($path)){
      return 'img/sample-img.png';
    }else{
      return $path;
    }
  }

  function appendGetParam($arr_del_key = array()){
    if(!empty($_GET)){
      $str = '?';
      foreach($_GET as $key => $val){
        if(!in_array($key,$arr_del_key,true)){
          $str .= $key.'='.$val.'&';
        }
      }
      $str = mb_substr($str, 0, -1, "UTF-8");
      return $str;
    }
  }
