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
							<h1>로지스팟 업로드가 완료되었습니다.</h1>
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

    <?php
     $logis_driver_id ='';
     $logis_driver_name='';
     $logis_car_type ='';
		$LogispotExcel = $db->DELIVERY->selectLogispotExcel($_REQUEST);
		if($LogispotExcel == SELECT_FAILED){
		}else {
			$LogispotExcel = $db->fetchDB($LogispotExcel);
			// style='display:none'


            $LogispotExcelValue=$LogispotExcel[0];
            $arrPlaces=array(); //하차지 담을 array
            $seq = 0; //배차 순서
            $logis_driver_id =$LogispotExcelValue["LOGIS_DRIVER_ID"];
            $logis_driver_name=$LogispotExcelValue["ADMIN_NAME"]; 
            $logis_car_type = $LogispotExcelValue["CAR_TYPE"]; 
            /* 상차지 */
            $temp = array( 'company_name'=> $LogispotExcelValue["CENTER_NAME"],	//string 회사명

            //custom_code	//string 화주 관리용 상하차지 고유코드

           'address' =>$LogispotExcelValue["LAU_ADDR"],	//string 주소

            //address_detail	//string 상세주소

            'type'=>'1',	//integer 위치 유형 (1: 상차지, 2: 하차지, 3: 경유지(상차), 4: 경유지(하차)

            'sequence'=>$seq,	//integer 정렬 순서

            'date' => date("Y-m-d",time()),	//string 상/하차 일자

            'time' => 'today_load',	//string 상/하차 시각

            'contact_name' => $LogispotExcelValue["ADMIN_NAME"],	//string 담당자 이름

            'contact_number' => $LogispotExcelValue["ADMIN_TEL"],	//string 담당자 연락처

            'weight' => '0',	//float 착지별 중량

            'volume' => '0',	//float 착지별 부피

            'quantity'=> '0',	//integer 물품 수량

            'amount' => '0'	//integer 착지별 주문금액
            );

            array_push( $arrPlaces, $temp);
            /* ./상차지 */

			foreach ($LogispotExcel as $LogispotExcelKey => $LogispotExcelValue) {


                /* 하차 경유지 */
                    $temp = array( 
                    
                    'company_name'=> $LogispotExcelValue["BNAME"],	//string 회사명
    
                    'custom_code' => $LogispotExcelValue["LAD_CD"],	//string 화주 관리용 상하차지 고유코드
                   
                   'address' =>$LogispotExcelValue["ADDR"],	//string 주소

                   'custom_code' =>$LogispotExcelValue["LAD_CD"],	//string 화주 관리용 상하차지 고유코드

                   //'address_detail' => '상세주소샘플',	//string 상세주소
                    
                    'memo' => $LogispotExcelValue["LAD_ISSUE"],

                    'type'=>'4',	//integer 위치 유형 (1: 상차지, 2: 하차지, 3: 경유지(상차), 4: 경유지(하차)

                    'sequence'=>$seq,	//integer 정렬 순서

                    'date' => date("Y-m-d",time()),	//string 상/하차 일자

                
                    'time' => 'today_unload',	//string 상/하차 시각

                    'contact_name' => $LogispotExcelValue["ADMIN_NAME"],//$LogispotExcelValue["ADMIN_NAME"],	//string 담당자 이름

                    'contact_number' => $LogispotExcelValue["ADMIN_TEL"], //string 담당자 연락처
                 
                
                    'weight' => '0',	//float 착지별 중량

                    'volume' => '0',	//float 착지별 부피

                    'quantity'=> '0',	//integer 물품 수량

                    'amount' => '0'	//integer 착지별 주문금액
                    );
                 array_push( $arrPlaces, $temp);

                $seq++;

                /* ./하차 경유지 */



				$KEY_NO = $LogispotExcelValue["KEY_NO"];//키번호
				// $START_DAY = $LogispotExcelValue["START_DAY"];//상차일자
				$START_DAY = date("Y-m-d",time());//상차일자
				$LAU_TIME = $LogispotExcelValue["LAU_TIME"];//상차시간
				$CENTER_NAME = $LogispotExcelValue["CENTER_NAME"];//센터명
				$LAU_ADDR = $LogispotExcelValue["LAU_ADDR"];//주소
				$ADMIN_NAME = $LogispotExcelValue["ADMIN_NAME"];//담당자명
				$ADMIN_TEL = $LogispotExcelValue["ADMIN_TEL"];//담당자번호
				// $LAU_CD = $LogispotExcelValue["LAU_CD"];//상차지코드
				$LAU_CD = "";//상차지코드
				// $LAU_ISSUE = $LogispotExcelValue["LAU_ISSUE"];//상차지특이사항
				$LAU_ISSUE = "";//상차지특이사항
				$LAU_MEMO = $LogispotExcelValue["LAU_MEMO"];//상차지메모
				// $END_DAY = $LogispotExcelValue["END_DAY"];
				$END_DAY = date("Y-m-d",time());//하차일자
				$LAD_TIME = $LogispotExcelValue["LAD_TIME"];
				$BNAME = $LogispotExcelValue["BNAME"];
				$ADDR = $LogispotExcelValue["ADDR"];

				// $LAD_NAME = $LogispotExcelValue["LAD_NAME"];
				// $LAD_TEL = $LogispotExcelValue["LAD_TEL"];
				// $LAD_CD = $LogispotExcelValue["LAD_CD"];
				// $LAUD_S = $LogispotExcelValue["LAUD_S"];
				$LAD_NAME = "";
				$LAD_TEL = "";
				$LAD_CD = "";
				$LAUD_S = "";
				$LAD_ISSUE = $LogispotExcelValue["LAD_ISSUE"];
				// $LAD_MEMO = $LogispotExcelValue["LAD_MEMO"];
				$LAD_MEMO = "";
				// $GROUP_NAME = $LogispotExcelValue["GROUP_NAME"];
				$GROUP_NAME = "본사";//그룹을 지정할수없음..본사로지정..
				// $PROD_NAME = $LogispotExcelValue["PROD_NAME"];
				$PROD_NAME = "식자재";
				$ADMIN_TEL2 = $LogispotExcelValue["ADMIN_TEL2"];
				// $TRUE_CD = $LogispotExcelValue["TRUE_CD"];
				// $LAUD_MEMO = $LogispotExcelValue["LAUD_MEMO"];
				// $PROD_NAME1 = $LogispotExcelValue["PROD_NAME1"];
				// $PROD_COUNT1 = $LogispotExcelValue["PROD_COUNT1"];
				// $PROD_NAME2 = $LogispotExcelValue["PROD_NAME2"];
				// $PROD_COUNT2 = $LogispotExcelValue["PROD_COUNT2"];
				$TRUE_CD = "";
				$LAUD_MEMO = "";
				$PROD_NAME1 = "";
				$PROD_COUNT1 = "";
				$PROD_NAME2 = "";
				$PROD_COUNT2 = "";

		}
    }

    /* 하차지 */
    $temp = array( 'company_name'=> $LogispotExcelValue["CENTER_NAME"],	//string 회사명

    //custom_code	//string 화주 관리용 상하차지 고유코드

   'address' =>$LogispotExcelValue["LAU_ADDR"],	//string 주소

    //address_detail	//string 상세주소

    'type'=>'2',	//integer 위치 유형 (1: 상차지, 2: 하차지, 3: 경유지(상차), 4: 경유지(하차)

    'sequence'=>$seq,	//integer 정렬 순서

    'date' => date("Y-m-d",time()),	//string 상/하차 일자

    'time' => 'today_unload',	//string 상/하차 시각

    'contact_name' => $LogispotExcelValue["ADMIN_NAME"],	//string 담당자 이름

    'contact_number' => $LogispotExcelValue["ADMIN_TEL"],	//string 담당자 연락처

    'weight' => '0',	//float 착지별 중량

    'volume' => '0',	//float 착지별 부피

    'quantity'=> '0',	//integer 물품 수량

    'amount' => '0'	//integer 착지별 주문금액
    );

    array_push( $arrPlaces, $temp);
    $flag=0;
    $seq++;

    /* ./하차지 */

//$tempData = array('$order_id' => 'TU755909'); //required integer 오더 ID
$dataForList = '{ "start_date":"2021-10-22","end_date":"2021-10-22" }';

$arrPays = array(

        'method'=>'0', //결제방법 (0: 선착불, 1: 계산서, 2: N/A, 3: 카드결제)
        'payment_type'=>'0' //운임유형 (0: 일반, 1: 회차비, 2: 대기비, 3: 경유비, 6: 회수비, 7: 수작업, 5: 기타)

);

$car_type_code='6';
$car_ton='1';
if($logis_car_type=='탑차') {
  $car_type_code='6';
  $car_ton='1';
} else if($logis_car_type=='오토바이') {
  $car_type_code='0';
  $car_ton='MOTO';
} else {
  $car_type_code='6';
  $car_ton='1';
}
/*데이터 맵핑*/
$data = array(

    'car_type'=>$car_type_code, //required integer 차종 6=탑 7=냉동 8=냉장
  
    'car_ton'=>$car_ton, // required string 톤수

    //car_air_suspension	//boolean 무진동 여부

    //car_lift	// boolean 리프트 여부

    'is_round'=>'0', //required integer 왕복 여부(true = 1, false = 0)

    'is_mix' => '0', //required integer 혼적 여부(true = 1, false = 0)

    //is_daily	// integer 일대 여부(true = 1, false = 0)

    'client_affiliate_id'=>'5616', //required integer 화주 그룹 ID 5616 //개발계 임시 화주그룹id=3155


    'carrier_team_id'=>NULL, // integer 운송사 팀 ID //143808 딜랩 04구5036, 144016 조범구 04구4417, 145661 로빈 04구0530, 147389 헐크 04구0530

    'user_driver_id'=> $logis_driver_id, //로빈

    //remark	//text 전달사항

    //memos	//Array of objects
    'places'=> $arrPlaces, //required Array of objects
    //shared_orders //Array of objects

    'platform'=>'4', //integer 접수채널 (0: Web(Windows), 1: Web(Mac), 2: Android, 3: iOS 4: API)

    'freight_name'=>'식자재', //required string물품명

    //freight_weight //float 물품중량

    //total_order_amount	//integer 총 주문금액

    'pays'=> $arrPays,  // required Array of objects (OrderPay model)


   // min_contract_fee	//integer 최소계약운임

    //max_contract_fee	//integer 최대계약운임

    //no_receipt_by_client	//boolean 인수증 미발행 여부 (0: 발행, 1: 미발행)

    //inbound_channel	//string 접수채널 (tel, messenger, email, bulk_upload, online)

    //order_items	 //object 오더 상품 내역
    );



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

/* 데이터 등록 */
function get_request($token, $post) {


    $ch = curl_init('https://api.logi-spot.com/api/v1/orders'); // Initialise cURL
    //$ch =curl_init('https://api.test-spot.com/api/v1/orders/{TU755909}');
    //$post = http_build_query($post);

    //$post = str_urlencode($post);
    //$post = json_encode($post); // Encode the data array into a JSON string

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
    return json_decode($result); // Return the received data

 }

 function RegOrderLogispot($token, $post) {


    $ch = curl_init('https://api.logi-spot.com/api/v1/orders'); // Initialise cURL
    //$ch =curl_init('https://api.test-spot.com/api/v1/orders/{TU755909}');
    //$post = http_build_query($post);

    //$post = str_urlencode($post);
    $post = json_encode($post); // Encode the data array into a JSON string

    $authorization = "Authorization: Bearer ".$token; // Prepare the authorisation token
    //var_dump($token);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization )); // Inject the token into the header
    //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded' , $authorization )); // Inject the token into the header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1); // Specify the request method as POST
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post); // Set the posted fields
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    $result = curl_exec($ch); // Execute the cURL statement
    curl_close($ch); // Close the cURL connection
    return json_decode($result); // Return the received data

 }

 function AskOrderInfo($token, $post) {

    
  $ch = curl_init('https://api.logi-spot.com/api/v1/orders/'.$post.''); // Initialise cURL
  //$ch =curl_init('https://api.test-spot.com/api/v1/orders/{TU755909}');
  //$post = http_build_query($post);
  
  //$post = str_urlencode($post); 
  $post = json_encode($post); // Encode the data array into a JSON string
  
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



$token = MakeAccessToken()->token; // Get your token from a cookie or database

$request = RegOrderLogispot($token,$data);

//배차번호 추출
$logis_order_id = $request->id;
if($logis_order_id != NULL) {
  echo "배차 성공";

  //TB_ORDER 테이블 각 오더에 배차번호 갱신
  $result=$db->DELIVERY->setLogispotOrderID($_REQUEST, $logis_order_id);
  if($result == true) {
  //echo "setLogispotOrderID() 성공";
  } else {
  //echo "setLogispotOrderID() 실패";
  }

  /*TB_ORDER_ITEM 테이블 각 오더아이템에 LOGIS_ORD_PLACES_ID 갱신 */
  //order_places_id 추출 위해 배차 상세정보 조회.
  $request = AskOrderInfo($token, $logis_order_id);
  echo '<pre>' . var_export($request, true) . '</pre>';

  //foreach(해당날짜, 해당센터, 해당센터그룹 모든 상품에 대해 반복)
  //조회된 주문아이템에서 cust_id가 places_custom_code와 일치하면 order_places_id 등록.
  //$result =$db->DELIVERY->setLogisPlacesID($_REQUEST,$order_places_id,$places_custom_code);
  foreach( $request['places'] as $key => $value) {
    //하차지 일 때만
    if($value['order_places_type']=='4') {
    
      $result = $db->DELIVERY->UpdateORD_PLACES_ID($_REQUEST, $value['places_custom_code'], $value['order_places_id'] );
      if($result == true) {
        //echo "UpdateORD_PLACES_ID() 성공";
      } else {
        //echo "UpdateORD_PLACES_ID() 실패";
      }
    }
  }

} else {
  echo "배차 실패";
}





		include $localURL . 'footer.php';     
    $prevPage = $_SERVER['HTTP_REFERER'];


header('location:'.$prevPage);
// 페이지 이동 
?>

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
