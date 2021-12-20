<?php session_start();

error_reporting(E_ALL & E_NOTICE & E_STRICT);
ini_set("display_errors", 1); 
/*  
    파일명 : logispotList_handler.php
    기능: 로지스팟 오더등록 기능 실행.
*/
?>

<!DOCTYPE html>
<html lang="kr" dir="ltr">
<head>
	<?php
    $localURL = '../../';


    if (!isset($_SESSION['admin_id'])) {
      echo "<meta http-equiv='refresh' content='0;url=" . $localURL . "admin_login.php'>";
      exit;
    } else {

    }

    require_once '../php/includes/DbOperation.php';
    $db = new DbOperation();

    $imgTime = time();
    $seller_id = $_SESSION['seller_id'];
    $search_textfield = (empty($_GET['search_textfield'])) ? '' : $_GET['search_textfield'];

		// $searchDate = (empty($_REQUEST['searchDate'])) ? date("Y-m-d",time()) : $_REQUEST['searchDate'];
		$searchDate = (empty($_REQUEST['searchDate'])) ? date("Y-m-d",time()) : $_REQUEST['searchDate'];

    $class = (empty($_GET['prod_cd_1'])) ? 'ALL' : $_GET['prod_cd_1'];
    $class_cd = (empty($_GET['prod_cd_2'])) ? 'ALL' : $_GET['prod_cd_2'];
    $class_cd_detail = (empty($_GET['prod_cd_3'])) ? 'ALL' : $_GET['prod_cd_3'];
    $option = (empty($_GET['option'])) ? 'ALL' : $_GET['option'];
    $image = (empty($_GET['image'])) ? 'ALL' : $_GET['image'];
		$sqcCenterCd = (empty($_REQUEST['sqcCenterCd'])) ?  "none" : $_REQUEST['sqcCenterCd'];
		$sqcGroupCd = (empty($_REQUEST['sqcGroupCd'])) ?  "none" : $_REQUEST['sqcGroupCd'];

    $_SESSION["REQUEST_URI"] = $_SERVER["REQUEST_URI"];

    $condition = 'new_con';
    include $localURL . 'head.php';

    if ($condition != 'new_con') {
      require($localURL . 'admin_sidebar.php');   //사이드바SellerList()
    }

  ?>

	<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
	


</head>

<body>
  <div class="wrapper">
    <!-- Navbar -->
		<?php include $localURL . 'navbar.php' ?>

    <!-- Main Sidebar Container -->
    <?php include $localURL . 'new_sidebar.php' ?>

    <div class="content-wrapper">
      <section class='content-header'>
				<div class='container-fluid'>
					<div class='row mb-2'>
						<div class='col-sm-6'>
							<h1>로지스팟 배차 상세 조회</h1>
						</div>
					</div>
				</div>
			</section>

      <!-- Main content -->
	 <section class="content">
        <div class="card">
			<div class="card-body">
                <button type='button' class='btn btn-primary' onclick="history.back();">뒤로가기</button>
            </div>
        </div>
      </section>

    </div>
<?
/* 접속 토큰 생성 */
function MakeAccessToken() {
        
        //보낼 json문
        $jsonData = '{"client_id":"412910CEB26D62C0", "client_secret":"99f99849b6b119a0d47dfdabc2c5b452","grant_type":"client_credentials"}'; 
        // 보낼 url
       $url ="https://api.logi-spot.com/api/v1/oauth/access_token";
        
        $ch = curl_init(); // curl초기화
        curl_setopt($ch, CURLOPT_URL, $url); 				//URL지정
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);		//요청결과를 문자열로 반환
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);      	//connection timeout 10초 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);   	//원격 서버의 인증서가 유효한지 검사 안함
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);    //POST data
        curl_setopt($ch, CURLOPT_POST, true);              //true시 post 전송 
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=UTF-8'));

        $response = curl_exec($ch); //curl 실행
        curl_close($ch); //curl종료

        return json_decode($response);

}

/* 배차 상세 조회 */
 function AskOrderInfo($token, $order_id) {

    
  $ch = curl_init('https://api.logi-spot.com/api/v1/orders/'.$order_id.''); // Initialise cURL
  //$ch =curl_init('https://api.test-spot.com/api/v1/orders/{TU755909}');
  //$post = http_build_query($post);
  
  //$post = str_urlencode($post); 
  $post = json_encode($order_id); // Encode the data array into a JSON string
  
  $authorization = "Authorization: Bearer ".$token; // Prepare the authorisation token
  //var_dump($token);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization )); // Inject the token into the header
  //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded' , $authorization )); // Inject the token into the header
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, 1); // Specify the request method as POST
  curl_setopt($ch, CURLOPT_POSTFIELDS, $post); // Set the posted fields
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
  $result = curl_exec($ch); // Execute the cURL statement
  curl_close($ch); // Close the cURL connection
  return json_decode($result,true); // Return the received data

}


//ex 오더번호55841,주문번호283597에 대한 배송 완료 정보 얻기
$order_no = '55841';
$seller_id = '1798601236';
//$order_item_no = '283597';

//LOGIS_ORD_ID, LOGIS_ORD_PLACES_ID 얻기
$result = $db->DELIVERY->GetLogisID($order_no, $seller_id);
if($result == "SELECT_FAILED"){
  echo "로지스팟 정보 로딩 실패";
}else {
  $result = $db->fetchDB($result);
  var_export($result);
  echo "LOGIS_ORD_ID:".$result[0]['LOGIS_ORD_ID']." LOGIS_ORD_PLACES_ID:".$result[0]['LOGIS_ORD_PLACES_ID'];


  //해당 배차 상세 조회
  $token = MakeAccessToken()->token; // Get your token from a cookie or database
  $request = AskOrderInfo($token,$result[0]['LOGIS_ORD_ID']);
  
  // LOGIS_ORD_PLACES_CODE 와 일치하는 배열 찾아서 배송체크
  $DidFinishDeliv=false; //true => 배송완료, false => 미배송
  foreach( $request['places'] as $key => $value) {
    //하차지 일 때만
    if($value['order_places_type']=='4') {
  
      if($value['order_places_id'] == $result[0]['LOGIS_ORD_PLACES_ID']) {

        if($value['order_places_complete_time'] != NULL ) {
          $DidFinishDeliv=true;
        }

      }
    }
  }

  var_export($DidFinishDeliv);
  
}






		include $localURL . 'footer.php';     
        $prevPage = $_SERVER['HTTP_REFERER'];
// 변수에 이전페이지 정보를 저장

header('location:'.$prevPage);
// 페이지 이동 ?>

  </div>
  <!--./content-wrapper-->
  <?php include $localURL . 'footer_script.php' ?>
	<script lang="javascript" src="../../js/xlsx.core.min.js"></script>
  <script type="text/javascript" src="../../js/FileSaver.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.3.1.min.js" type="text/javascript" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
  <script src="../../js/webflow.js" type="text/javascript"></script>
  <script src="../../js/custom.js" type="text/javascript"></script>
	<script src="../../js/lib/alertify.min.js"></script>
  <link rel="stylesheet" href="../../css/themes/alertify.core.css"/>
	<link rel="stylesheet" href="../../css/themes/alertify.default.css"/>
 
</body>
</html>
