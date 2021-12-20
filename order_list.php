<?php
header("Content-Type:text/html;charset=utf-8");
//error_reporting(E_ALL);
//ini_set("display_errors", 1);
//
$localURL = '../../';

session_start();

if (!isset($_SESSION['admin_id'])) {
  echo "<meta http-equiv='refresh' content='0;url=" . $localURL . "admin_login.php'>";
  exit;
} else {
  $admin_id = $_SESSION['admin_id'];
}

require_once '../php/includes/DbOperation.php';
require '../../php/api/url_curl_form.php';
$db = new DbOperation();

$condition = 'new_con';
include $localURL . 'head.php';

if ($condition != 'new_con') {
  require($localURL . 'admin_sidebar.php');   //사이드바SellerList()
}

$page = $_GET['page'] ?: 1;
$list = $_GET['list'] ?: 15;

// 파라미터
$occd = (empty($_GET['occd'])) ? 'ALL' : $_GET['occd'];
$search_option = (empty($_GET['search_option'])) ? 'order' : $_GET['search_option'];
$searchText = (empty($_GET['searchText'])) ? '' : $_GET['searchText'];
$sdg_get = (empty($_GET['sdg_get'])) ? 'ALL' : $_GET['sdg_get'];
$date_option = (empty($_GET['date_option'])) ? 'order' : $_GET['date_option'];
$day = (empty($_GET['day'])) ? 'ALL' : $_GET['day'];

// 시작날짜
$startDate = (empty($_GET['startDate'])) ?  date("Y-m-01", strtotime("-1 months")) : $_GET['startDate'];
// 종료날짜
$endDate = (empty($_GET['endDate'])) ?  date("Y-m-t", strtotime("now")) : $_GET['endDate'];

$arrive = date("Y-m-d", strtotime("+1 days"));

// 쿠폰 리스트 체크박스
$coupon_show = (empty($_GET['coupon_show'])) ?  "" : $_GET['coupon_show'];

$_SESSION["REQUEST_URI"] = $_SERVER["REQUEST_URI"];
?>

<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

<style>
  .ui-autocomplete {
    max-height: 200px;
    overflow-y: auto;
    /* prevent horizontal scrollbar */
    overflow-x: hidden;
  }

  /* IE 6 doesn't support max-height
    * we use height instead, but this forces the menu to always be this tall
    */
  * html .ui-autocomplete {
    height: 300px;
  }
</style>
</head>

<body>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

  <script type="text/javascript">
    var $localURL = '<?php echo $localURL ?>';

    // 자동완성 기능
    $(function() {
      $("#searchText").autocomplete({
        source: function(request, response) {
          $.ajax({
            type: 'get',
            url: "../../php/api/cs/cust_l.php",
            dataType: "json",
            data: {
              term: request.term
            },
            success: function(data) {
              //서버에서 json 데이터 response 후 목록에 추가
              response(
                $.map(data, function(item) { //json[i] 번째 에 있는게 item 임.
                  return {
                    // label: item+"label",    //UI 에서 보여지는 글자, 실제 검색어랑 비교 대상
                    value: item[0], //그냥 사용자 설정값?
                    // test : item+"test"    //이런식으로 사용
                  }
                })
              );
            }
          });
        }, // source 는 자동 완성 대상
        select: function(event, ui) { //아이템 선택시
          // console.log(ui.item.label);    //김치 볶음밥label
        },
        focus: function(event, ui) { //포커스 가면
          return false; //한글 에러 잡기용도로 사용됨
        },
        minLength: 1, // 최소 글자수
        autoFocus: true, //첫번째 항목 자동 포커스 기본값 false
        classes: { //잘 모르겠음
          "ui-autocomplete": "highlight"
        },
        delay: 100, //검색창에 글자 써지고 나서 autocomplete 창 뜰 때 까지 딜레이 시간(ms)
        // disabled: true, //자동완성 기능 끄기
        // position: { my : "right top", at: "right bottom" },    //잘 모르겠음
        close: function(event) { //자동완성창 닫아질때 호출
          // console.log(event);
        }
      }).autocomplete("instance")._renderItem = function(ul, item) { //요 부분이 UI를 마음대로 변경하는 부분
        return $("<li>") //기본 tag가 li로 되어 있음
          .append("<div>" + item.value + "<br></div>") //여기에다가 원하는 모양의 HTML을 만들면 UI가 원하는 모양으로 변함.
          .appendTo(ul);
      };
    });
  </script>

  <div class="wrapper">
    <!-- Navbar -->
    <?php include $localURL . 'navbar.php' ?>

    <!-- Main Sidebar Container -->
    <?php include $localURL . 'new_sidebar.php' ?>
    <div class="content-wrapper">
      <section class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1>주문관리 LIST</h1>
            </div>
          </div>
        </div><!-- /.container-fluid -->
      </section>
      <!-- Main content -->
      <section class="content">
        <form name='searchForm' id='searchForm' method='get' action='./order_list.php'>
          <div class="card">
            <div class="card-header">
              <div class="row">
                <!-- 기간 선택 -->
                <!-- <? if ($_SESSION['admin_id'] == "gwang") { ?>
                  <div class="col-md-2">
                    <select class='custom-select' id="date_option" name="date_option">
                      <option value='order' <?php echo ($date_option == 'order') ? 'selected' : ''; ?>>주문일</option>
                      <option value='arrive' <?php echo ($date_option == 'arrive') ? 'selected' : ''; ?>>입고일</option>
                    </select>
                  </div>
                <? } ?> -->
                <div class="col-md-4">
                  <div class="input-group date">
                    <input type="text" class="form-control datetimepicker-input" autocomplete="off" name="startDate" id="sInputDate" value="<?php echo $startDate ?>" />
                    <div class="input-group-append" id="sInputDateImg">
                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                    <input type="text" class="form-control datetimepicker-input" autocomplete="off" name="endDate" id="eInputDate" value="<?php echo $endDate ?>" />
                    <div class="input-group-append" id="eInputDateImg">
                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                  </div>
                </div>

                <!-- 출고상태 -->
                <div class="col-md-2">
                  <select class='custom-select' id='occd' name="occd">
                    <option value='ALL' <?php echo ($occd == 'ALL') ? 'selected' : ''; ?>>출고상태 전체</option>
                    <option value='00' <?php echo ($occd == '00') ? 'selected' : ''; ?>>입금대기</option>
                    <option value='08' <?php echo ($occd == '08') ? 'selected' : ''; ?>>가상계좌만료</option>
                    <option value='01' <?php echo ($occd == '01') ? 'selected' : ''; ?>>출고전</option>
                    <option value='02' <?php echo ($occd == '02') ? 'selected' : ''; ?>>배송중</option>
                    <option value='03' <?php echo ($occd == '03') ? 'selected' : ''; ?>>배송완료</option>
                    <option value='04' <?php echo ($occd == '04') ? 'selected' : ''; ?>>취소접수</option>
                    <option value='05' <?php echo ($occd == '05') ? 'selected' : ''; ?>>반품완료</option>
                  </select>
                </div>


                  <!-- 업장 SELECT -->
                  <div class="col-md-2">
                    <select class='custom-select' name="sdg_get" id="sdg_get">
                      <?if ($_SESSION['admin_type'] == "SELLER") {?>
                        <option value="ALL" <? echo ($sdg_get == 'ALL') ? 'selected' : ''; ?>>
                          전체업장
                        </option>
                      <?}else{?>
                        <option value="ALL" <? echo ($sdg_get == 'ALL') ? 'selected' : ''; ?>>
                          전체업장
                        </option>
                        <option value="basic" <? echo ($sdg_get == 'basic') ? 'selected' : ''; ?>>
                          일반업장
                        </option>
                        <option value="sdg" <? echo ($sdg_get == 'sdg') ? 'selected' : ''; ?>>
                          지역거점
                        </option>
                      <?}?>

                      <?php
                      $del_position = $db->Order->selectDelPosition($_SESSION["admin_type"]);

                      if ($del_position == SELECT_FAILED) {
                        //
                      } else {
                        // $del_position->bind_result($g_position);
                        // while ($del_position->fetch()) {
                        //   echo "<option value='$g_position'";
                        //   echo ($sdg_get == "$g_position") ? 'selected' : '';
                        //   echo "> $g_position </option>";
                        // }
                        $del_position = $db->fetchDB($del_position);
                        // var_dump($del_position);
                        foreach ($del_position as $del_positionKey => $del_positionValue) {
                          $g_positionText = $del_positionValue["dpKey"];
                          $g_position = $del_positionValue["dpValue"];
                          echo "<option value='$g_position'";
                          echo ($sdg_get == "$g_position") ? 'selected' : '';
                          echo "> $g_positionText </option>";
                        }
                      }
                      ?>
                    </select>
                  </div>

                  <?
                  if ($_SESSION['admin_type'] !== "SELLER") {
                  ?>
                  <div class="col-md-2">
                    <div class="icheck-primary">
                      <input type="checkbox" name="coupon_show" id="coupon_show" <? echo $coupon_show == "on" ? "checked" : "" ?>/>
                      <label for="coupon_show">쿠폰</label>
                    </div>
                  </div>
                <? } ?>
              </div>

              <!-- 검색 기준 -->
              <div class="row">
                <div class="col-md-2">
                  <select name='search_option' id='search_option' class="custom-select">
                    <option value='order' <?php echo ($search_option == 'order') ? 'selected' : ''; ?>>오더넘버</option>
                    <option value='cust' <?php echo ($search_option == 'cust') ? 'selected' : ''; ?>>식당명 / ID</option>
                    <?php if ($_SESSION['admin_type'] !== "SELLER") { ?>
                      <option value='seller' <?php echo ($search_option == 'seller') ? 'selected' : ''; ?>>유통업체명 / ID</option>
                    <? } ?>
                  </select>
                </div>

                <div class="col-md-4">
                  <div class="form-group">
                    <div class="input-group">
                      <input type="text" class="form-control" id="searchText" name="searchText" autocomplete="off" placeholder="검색" value="<?php echo $searchText ?>">
                      <div class="input-group-append">
                        <button id="searchBtn" class="btn btn-default" style="opacity: 1;"><i class="fa fa-search"></i></button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-md-2">
                  <select class='custom-select' id='day' name='day'>
                    <option value='ALL' <?php echo ($day == 'ALL') ? 'selected' : ''; ?>>입고일 전체</option>
                    <option value='1' <?php echo ($day == 1) ? 'selected' : ''; ?>>D-1</option>
                    <option value='2' <?php echo ($day == 2) ? 'selected' : ''; ?>>D-2</option>
                    <option value='3' <?php echo ($day == 3) ? 'selected' : ''; ?>>D-3</option>
                  </select>
                </div>

                <? if ($occd == '01' && ($_SESSION['admin_type'] == "MASTER" || $_SESSION['admin_id'] == "ryan" || $_SESSION['admin_id'] == "2128800601" || $_SESSION['admin_id'] == "Valkyrie"
                       || $_SESSION['admin_id'] == "1798601236" || $_SESSION['admin_id'] == "2698602125" || $_SESSION['admin_id'] == "1058750897" || $_SESSION['admin_id'] == "1231245678"
                       || $_SESSION['admin_id'] == "4838801177" || $_SESSION['admin_id'] == "7058600958" || $_SESSION['admin_id'] == "5058184391" || $_SESSION['admin_id'] == "1231256789")
                      ) { ?>
                  <div class="col-md-2">
                    <button type="button" onclick="all_order_check()" class="btn btn-block btn-primary">배송중 일괄 변경</button>
                  </div>
                <? } else if ($occd == '02' && ($_SESSION['admin_type'] == "MASTER" || $_SESSION['admin_id'] == "ryan" || $_SESSION['admin_id'] == "2128800601" || $_SESSION['admin_id'] == "Valkyrie"
                               || $_SESSION['admin_id'] == "1798601236" || $_SESSION['admin_id'] == "2698602125" || $_SESSION['admin_id'] == "1058750897" || $_SESSION['admin_id'] == "1231245678"
                               || $_SESSION['admin_id'] == "4838801177" || $_SESSION['admin_id'] == "7058600958" || $_SESSION['admin_id'] == "5058184391" || $_SESSION['admin_id'] == "1231256789")
                             ) { ?>
                  <div class="col-md-2">
                    <button type="button" onclick="all_order_check()" class="btn btn-block btn-primary">배송완료 일괄 변경</button>
                  </div>
                <? } ?>
              </div>
            </div>
            <div class="card-body">
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th>오더번호</th>
                    <th>출고상태</th>
                    <th>TMS상태</th>
                    <th>식당명/ID</th>
                    <th>유통업체명/ID</th>
                    <th>주문금액</th>
                    <th>주문날짜</th>
                    <th>상세정보</th>
                    <? if ($occd == '01' && ($_SESSION['admin_type'] == "MASTER" || $_SESSION['admin_id'] == "ryan" || $_SESSION['admin_id'] == "2128800601" || $_SESSION['admin_id'] == "Valkyrie"
                            || $_SESSION['admin_id'] == "1798601236" || $_SESSION['admin_id'] == "2698602125" || $_SESSION['admin_id'] == "1058750897" || $_SESSION['admin_id'] == "1231245678"
                            || $_SESSION['admin_id'] == "4838801177" || $_SESSION['admin_id'] == "7058600958" || $_SESSION['admin_id'] == "5058184391" || $_SESSION['admin_id'] == "1231256789")
                          ) { ?>
                      <th>
                        <label for="order_check">배송중 체크</label>
                        <input type="checkbox" name="order_check" id="order_check"/>
                      </th>
                    <? } else if ($occd == '02' && ($_SESSION['admin_type'] == "MASTER" || $_SESSION['admin_id'] == "ryan" || $_SESSION['admin_id'] == "2128800601" || $_SESSION['admin_id'] == "Valkyrie"
                                   || $_SESSION['admin_id'] == "1798601236" || $_SESSION['admin_id'] == "2698602125" || $_SESSION['admin_id'] == "1058750897" || $_SESSION['admin_id'] == "1231245678"
                                   || $_SESSION['admin_id'] == "4838801177" || $_SESSION['admin_id'] == "7058600958" || $_SESSION['admin_id'] == "5058184391" || $_SESSION['admin_id'] == "1231256789")
                                 ) { ?>
                      <th>
                        <label for="order_check">배송완료 체크</label>
                        <input type="checkbox" name="order_check" id="order_check"/>
                      </th>
                    <? } ?>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $block = 5; // 블록당 페이지수

                  $s_point = ($page - 1) * $list;

                  if ($_SESSION['admin_type'] == "SELLER") {              // 권한이 SELLER일 경우
                    $result = $db->Order->adminOrderList(
                      $occd,
                      $search_option,
                      $searchText,
                      $startDate,
                      $endDate,
                      $_SESSION['admin_id'],
                      "SELLER",
                      null,
                      null,
                      $coupon_show,
                      $sdg_get,
                      $day
                    );

                    $resultList = $db->Order->adminOrderList(
                      $occd,
                      $search_option,
                      $searchText,
                      $startDate,
                      $endDate,
                      $_SESSION['admin_id'],
                      "SELLER",
                      $s_point,
                      $list,
                      $coupon_show,
                      $sdg_get,
                      $day
                    );
                  } elseif ($_SESSION['admin_type'] == "SALES") {         // 권한이 SALES일 경우
                    $result = $db->Order->adminOrderList(
                      $occd,
                      $search_option,
                      $searchText,
                      $startDate,
                      $endDate,
                      $_SESSION['admin_id'],
                      "SALES",
                      null,
                      null,
                      $coupon_show,
                      $sdg_get,
                      $day
                    );

                    $resultList = $db->Order->adminOrderList(
                      $occd,
                      $search_option,
                      $searchText,
                      $startDate,
                      $endDate,
                      $_SESSION['admin_id'],
                      "SALES",
                      $s_point,
                      $list,
                      $coupon_show,
                      $sdg_get,
                      $day
                    );
                  } else {                                                 // 나머지
                    $result = $db->Order->adminOrderList(
                      $occd,
                      $search_option,
                      $searchText,
                      $startDate,
                      $endDate,
                      null,
                      null,
                      null,
                      null,
                      $coupon_show,
                      $sdg_get,
                      $day,
                      $date_option
                    );

                    $resultList = $db->Order->adminOrderList(
                      $occd,
                      $search_option,
                      $searchText,
                      $startDate,
                      $endDate,
                      null,
                      null,
                      $s_point,
                      $list,
                      $coupon_show,
                      $sdg_get,
                      $day,
                      $date_option
                    );
                  }

                  $num = $result->num_rows;

                  if ($resultList == SELECT_FAILED) {
                    echo "
                      <tr>
                        <td colspan='7' style='text-align: center;'>조회된 데이터가 없습니다.</td>
                      </tr>
                    ";
                  } else {
                    if ($_SESSION['admin_type'] == "SELLER") {
                      $resultList->bind_result(
                        $order_no,
                        $order_cond_cd,
                        $order_cond_name,
                        $cust_id,
                        $business_name,
                        $seller_id,
                        $seller_name,
                        $payment_pr,
                        $arrive_date,
                        $order_date,
                        $wtid,
                        $deadline_tm
                      );
                    } else {
                      $resultList->bind_result(
                        $order_no,
                        $order_cond_cd,
                        $order_cond_name,
                        $cust_id,
                        $business_name,
                        $seller_id,
                        $seller_name,
                        $payment_pr,
                        $arrive_date,
                        $order_date,
                        $wtid,
                        $deadline_tm,
                        $result_coupon
                      );
                    }

                    /* 로지스팟 배송 조회 - 토큰 */
                    // 접속 토큰 생성
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

                    function AskOrderInfo($token, $order_id) {


                      $ch = curl_init('https://api.logi-spot.com/api/v1/orders/'.$order_id.''); // Initialise cURL

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

                    $token = MakeAccessToken()->token; // Get your token from a cookie or database
                    /* ./로지스팟 배송 조회 - 토큰 */

                    $logis_cond =''; //TMS배송상태
                    $DidFinishDeliv=false; //TMS배송완료했나
                    while ($resultList->fetch()) {

                    /* 로지스팟 배송 상태 조회 */

                      //LOGIS_ORD_ID, LOGIS_ORD_PLACES_ID 얻기
                      $LogisResult = $db->DELIVERY->GetLogisID($order_no, $seller_id);
                      if($LogisResult== "SELECT_FAILED"){
                        $logis_cond = "미배차";
                      } else {
                        $LogisResult = $db->fetchDB($LogisResult);
                        if($LogisResult[0]['LOGIS_ORD_ID'] == NULL || $LogisResult[0]['LOGIS_ORD_PLACES_ID'] == NULL) {
                          $logis_cond = "미배차";
                        } else {

                            //해당 배차 상세 조회
                            $request = AskOrderInfo($token,$LogisResult[0]['LOGIS_ORD_ID']);

                            // LOGIS_ORD_PLACES_CODE 와 일치하는 배열 찾아서 배송체크
                            $DidFinishDeliv=false; //true => 배송완료, false => 미배송
                            foreach( $request['places'] as $key => $value) {
                              //하차지 일 때만
                              if($value['order_places_type']=='4') {

                                if($value['order_places_id'] == $LogisResult[0]['LOGIS_ORD_PLACES_ID']) {

                                  if($value['order_places_complete_time'] != NULL ) {
                                    $DidFinishDeliv=true;
                                  }

                                }
                              }
                            }

                              if($DidFinishDeliv == true ) {
                                $logis_cond ="배송완료";
                              } else {
                                $logis_cond ="배송중";
                              }

                        }
                      }
                      /* ./로지스팟 배송 상태 조회 */

                      //가상계좌 결제여부
                      $vbnk_msg = "";
                      $vbnk_post = false;
                      $vbnk_post_date_str = "";

                      if (strpos($wtid, "INIMX_VBNK") !== false || strpos($wtid, "StdpayVBNK") !== false) {
                        $vbnk_msg = "[■가상계좌■]";
                        $vbnk_post = true;
                      }

                      //가상계좌 결제여부
                      if ($_SESSION['admin_type'] == "SELLER") {
                        if ($order_cond_cd == "00" || $order_cond_cd == "01" || $order_cond_cd == "02" || $order_cond_cd == "03") {
                          $formet_pr = number_format($payment_pr);
                        } else {
                          $formet_pr = number_format(-$payment_pr);
                        }
                      } else {
                        if ($order_cond_cd == "00" || $order_cond_cd == "01" || $order_cond_cd == "02" || $order_cond_cd == "03") {
                          $formet_pr = number_format($payment_pr - $result_coupon);
                        } else {
                          $formet_pr = number_format(- ($payment_pr - $result_coupon));
                        }
                      }

                      if ($order_cond_cd == "02" || $order_cond_cd == "03") {
                        $ArriveView = "<br/>[$arrive_date 입고]";
                      }else {
                        $ArriveView ="";
                      }

                      $line_style = "<br/><span class='text-danger'>[D-" . $deadline_tm . " 상품]$ArriveView</span>";

                      $yoil = array("일", "월", "화", "수", "목", "금", "토");
                      $date_day = ($yoil[date('w', strtotime($order_date))]);

                      $date = date("Y-m-d A g:i", strtotime($order_date)) . " $date_day" . "요일";

                      $ampm[0] = "AM";
                      $ampm[1] = "PM";

                      $ampm_str[0] = "오전";
                      $ampm_str[1] = "오후";
                      //$wtid
                      $date_str = str_replace($ampm, $ampm_str, $date);

                      //성수업장이름
                      $SELLER_CONT_VIEW = "";
                      $selectFirstOrder = $db->Order->selectFirstOrder($cust_id, $order_date);
                      // $selectFirstOrder = SELECT_FAILED;

                      if ($selectFirstOrder == SELECT_FAILED) {
                        $ord_style = "<br/><span class='text-danger'>[첫주문]</span>";
                      } else {
                        $ord_style = "";
                        $selectFirstOrder->bind_result($ord_count, $DELIV_POSITION, $text_color, $CENTER_NAME);
                        $ccReq = array();//지역노출 파라미터
                        while ($selectFirstOrder->fetch()) {

                          $deliv_style = "";
                          if (isset($DELIV_POSITION)) {
                            //성수업장 업체명
                            $selectSdgName = $db->Order->selectSdgName($seller_id);

                            if ($selectSdgName == SELECT_FAILED) {
                            } else {
                              $selectSdgName->bind_result($SELLER_CONT);

                              while ($selectSdgName->fetch()) {
                                if (empty($SELLER_CONT) || !isset($SELLER_CONT) || $SELLER_CONT == "") {
                                } else {
                                  $SELLER_CONT_VIEW = "<br/><strong>(" . $SELLER_CONT . ")</strong>";
                                }
                              }
                            }

                            //주문고객 지역노출
                            if($CENTER_NAME == NULL) { $CENTER_NAME='미지정';}
                            if ($_SESSION["admin_type"] == "SELLER") { //유통사

                              if (0 == $ord_count) { //첫주문
                                $ord_style = "<br/><strong class='text-primary'>[첫주문 " . $CENTER_NAME . "]</strong>";
                              } else { //첫주문아님
                                $ord_style = "<br/><strong style='color: $text_color;'>[" . $CENTER_NAME . "]</strong>";
                              }

                            } else { //관리자
                              if (0 == $ord_count) { //첫주문
                                $ord_style = "<br/><strong class='text-primary'>[첫주문 " . $DELIV_POSITION . "][".$CENTER_NAME."]</strong>";
                              } else { //첫주문아님
                                $ord_style = "<br/><strong style='color: $text_color;'>[" . $DELIV_POSITION . "][".$CENTER_NAME."]</strong>";
                              }
                            }
                            //주문고객 지역노출
                          } else {
                            $SELLER_CONT_VIEW = "";
                            if (0 == $ord_count) { //첫주문
                              $ord_style = "<br/><strong class='text-primary'>[첫주문 고객]</strong>";
                            } else { //첫주문아님
                              $ord_style = "";
                            }
                          }
                        }
                      }

                      if (empty($result_coupon) || !isset($result_coupon)) {
                        $coupon_style = "";
                      } else {
                        $coupon_style = "<br/><span class='text-danger'>[쿠폰적용]</span>";
                      }

                      if(strpos($wtid, "VBNK") !== false && $order_cond_cd == '00'){
                        $whereWtid = explode("@",$wtid)[0];
                        $bn_acc_no = explode("@",$wtid)[1];

                        $fmt_reg_date = strtotime(date("Y-m-d H:i:s", strtotime("$order_date +2 days")));
                        $date_nowS = date("Y-m-d H:i:s", strtotime("now"));
                        $fmt_date_now = strtotime($date_nowS);

                        if($fmt_reg_date < $fmt_date_now){
                          $CustListPayment = $db->CustListPayment($cust_id);
                          if ($CustListPayment == SELECT_FAILED) {
                          } else {
                            $CustListPayment -> bind_result($CUST_ACCN_BN_NAME, $CUST_ACCN_NO,$DEPOSIT_BLN);
                            while($CustListPayment->fetch()) {
                              $in_his = $db->insertCancelHistory($cust_id, $order_no, $payment_pr, 0, $seller_id, 'VC', '', '');//히스토리기록

                              if ($in_his == INSERT_COMPLETED) {
                                $up_cr = $db->updateHisCR($cust_id, $order_no, $seller_id);//취소접수
                                $up_item = $db->updateOrderCompleteAdmin('08', $order_no, $seller_id);//상태변경
                                if ($up_item == UPDATE_FAILED) {
                                }else {
                                  //쿠폰반환
                                  $coupon_result = $db->Order->selectOrderCoupon(1, $cust_id, $order_no, $seller_id);

                                  if ($coupon_result == SELECT_FAILED) {
                                    $COUPON_DISCOUNT_PRICE = 0;
                                  } else {
                                    $coupon_result->bind_result(
                                      $COUPON_NO1,
                                      $COUPON_CLASS_CD,
                                      $COUPON_USE_YN,
                                      $COUPON_REG_DATE,
                                      $COUPON_CUST_ID,
                                      $COUPON_HIS_NO,
                                      $COUPON_NO2,
                                      $COUPON_ORDER_NO,
                                      $COUPON_USE_YNS,
                                      $COUPON_HIS_DATE,
                                      $COUPON_DISCOUNT_PRICE,
                                      $COUPON_SELLER_NAME,
                                      $COUPON_SELLER_ID
                                    );

                                    while ($coupon_result->fetch()) {
                                      $coupon_insert = $db->Order->insertCouponHis($COUPON_NO1, $COUPON_ORDER_NO, $COUPON_SELLER_ID, $COUPON_DISCOUNT_PRICE, 0);

                                      if ($coupon_insert == INSERT_COMPLETED) {
                                        // unset($_SESSION['coupon_no']["$seller"]);
                                      } else {
                                        // echo "$COUPON_NO1,$order_no,$seller_id,$COUPON_DISCOUNT_PRICE,0";
                                        // exit;
                                      }
                                    }
                                  }
                                  //쿠폰반환
                                }

                              }
                            }
                          }
                        }

                        //입금통보확인
                        $selectDepositAccnYN = $db->selectDepositAccnYN($whereWtid);

                        if ($selectDepositAccnYN == SELECT_FAILED) {
                          //
                        } else {
                          $selectDepositAccnYN -> bind_result($DEPOSIT_YN,$DEPOSIT_CD,$BN_CD,$REG_DATE);

                          while($selectDepositAccnYN->fetch()) {
                            if ($DEPOSIT_YN == "02") {
                              $updateVwOrder = $db->updateVwOrder($order_no,"01");
                              if ($updateVwOrder == UPDATE_COMPLETED) {
                                $orderhis = $db->insertManagerOrderHis("auto",$order_no,$seller_id,"01");
                                $body = "[$business_name] 님 입금확인 완료 되었습니다.";

                                $sendNo = "0269250515";
                                $recipientNo = array("01039754417");

                                url_curl_form($body, $sendNo, $recipientNo,"");

                                $url = "https://".$_SERVER['SERVER_NAME'] ."/php/api/bizM/kakao_message.php";

                                $post_json = array(
                                  "order_no"      => "$order_no",
                                  "cust_id"       => "$cust_id",
                                  "template_code" => "order_complete",
                                );

                                $post_data = http_build_query($post_json, '', '&');

                                $ch = curl_init();

                                curl_setopt($ch, CURLOPT_URL, $url);
                                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 응답시간 10초이내만 실행
                                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data); // 전송할 데이터
                                curl_setopt($ch, CURLOPT_POST,true);  // 전송 방식

                                $shortURL = curl_exec($ch);
                                $shortURL = curl_close($ch);
                              }
                            }
                          }
                        }
                      } else if (strpos($wtid, "VBNK") !== false && ($order_cond_cd !== "08" && $order_cond_cd !== "00")) {
                        $whereWtid = explode("@",$wtid)[0];

                        $selectDepositAccnYN = $db->selectDepositAccnYN($whereWtid);

                        if ($selectDepositAccnYN == SELECT_FAILED) {
                        } else {
                          $selectDepositAccnYN -> bind_result($DEPOSIT_YN,$DEPOSIT_CD,$BN_CD,$REG_DATE);

                          while($selectDepositAccnYN->fetch()) {
                            if ($DEPOSIT_YN == "02") {
                              $vbnk_post_date_day = ($yoil[date('w', strtotime($REG_DATE))]);
                              $vbnk_post_date = date("Y-m-d A g:i",strtotime($REG_DATE)) ." $vbnk_post_date_day"."요일 입금";

                              if ($order_cond_cd !== "08" && $order_cond_cd !== "00") {
                                $vbnk_post_date_str = str_replace($ampm,$ampm_str,$vbnk_post_date);
                              }
                            }
                          }
                        }
                      }

                      echo "
                            <tr>
                              <td>$order_no</td>
                              <td>$order_cond_name$line_style$ord_style</br></td>
                              <td>$logis_cond</td>
                              <td>
                                <strong class='text-primary'>$vbnk_msg</strong></br>
                                <strong>$business_name</strong></br>
                                $cust_id
                              </td>
                              <td>
                                <strong>$seller_name</strong>
                                $SELLER_CONT_VIEW</br>
                                $seller_id
                              </td>
                              <td>$formet_pr" . "원$coupon_style</td>
                              <td>$date_str<br/>$vbnk_post_date_str</td>
                              <td>
                                <a type='button' class='btn btn-app detail'>
                                  <i class='fas fa-edit'></i> 상세정보
                                </a>
                                <input type='hidden' value='$order_no' id='success_order_no'/>
                                <input type='hidden' value='$order_cond_cd' id='success_order_cond_cd'/>
                                <input type='hidden' value='$seller_id' id='success_seller_id'/>
                                <input type='hidden' value='$deadline_tm' id='success_con_tm'/>
                                <input type='hidden' value='$cust_id' id='success_cust_id'/>
                                <input type='hidden' value='$wtid' id='success_wtid'/>
                                <input type='hidden' value='$business_name' id='success_business_name'/>
                                <input type='hidden' value='$seller_name' id='success_seller_name'/>
                                <input type='hidden' value='$order_cond_name' id='success_order_cond_name'/>
                                <input type='hidden' value='$deadline_tm' id='success_deadline_tm'/>
                              </td>
                        ";

                        if (($occd == '01' || $occd == '02') && ($_SESSION['admin_type'] == "MASTER" || $_SESSION['admin_id'] == "ryan" || $_SESSION['admin_id'] == "2128800601" || $_SESSION['admin_id'] == "Valkyrie"
                              || $_SESSION['admin_id'] == "1798601236" || $_SESSION['admin_id'] == "2698602125" || $_SESSION['admin_id'] == "1058750897" || $_SESSION['admin_id'] == "1231245678"
                              || $_SESSION['admin_id'] == "4838801177" || $_SESSION['admin_id'] == "7058600958" || $_SESSION['admin_id'] == "5058184391" || $_SESSION['admin_id'] == "1231256789")
                            ) {
                          echo "
                              <td>
                                <div class='form-check'>
                                  <input class='form-check-input' type='checkbox' id='$order_no"."_"."$seller_id"."_"."check' name='success_check'
                                         style='width: 30px; height: 30px;'/>
                                  <label class='form-check-label' for='$order_no"."_"."$seller_id"."_"."check'></label>
                                </div>
                              </td>
                          ";
                        }

                        echo "
                            </tr>
                        ";
                    }
                  }
                  ?>
                </tbody>
              </table>
            </div>

            <!-- /.card-body -->
            <?php
            $pageNum = ceil($num / $list); // 총 페이지
            $blockNum = ceil($pageNum / $block); // 총 블록
            $nowBlock = ceil($page / $block); //페이지가 위치한 블록

            $s_page = ($nowBlock * $block) - ($block - 1);

            if ($s_page <= 1) {
              $s_page = 1;
            }

            $e_page = $nowBlock * $block;

            if ($pageNum <= $e_page) {
              $e_page = $pageNum;
            }

            $pre_page = $page > 1 ? $page - 1 : 1;
            $next_page = $page < $pageNum ? $page + 1 : $pageNum;

            $phpdefo = $_SERVER["PHP_SELF"];
            $phpself = $_SERVER["REQUEST_URI"];

            $explode = explode("?", $phpself);
            $substr = substr($explode[1], 0, 5);

            if ($phpdefo == $phpself) {
              $giho = "?";
            } else {
              if ($substr == "page=") {
                $giho = "?";
                $phpself = $phpdefo;
              } else {
                $giho = "&";
              }
            }

            // echo "출력 LIST : $num / 페이지 : $page / 한페이지당 데이터 : $list /  [$s_page / $e_page]";
            // echo "화살표 블록당 페이지수 : $block / 총페이지 : $pageNum / 총블록 : $blockNum / 페이지 위치 블록 : $nowBlock / point : $s_point";
            // echo "현재 URL : $phpself";

            $urlStr = './order_list.php';
            ?>
            <div class="card-footer clearfix">
              <ul class="pagination pagination-sm m-0 float-right">
                <li class="page-item">
                  <a class="page-link" href="<?= $phpself ?><?= $giho ?>page=1"><<</a>
                </li>
                <li class="page-item">
                  <a class="page-link" href="<?= $phpself ?><?= $giho ?>page=<?= $pre_page ?>"><</a>
                </li>
                <?php
                for ($p = $s_page; $p <= $e_page; $p++) {
                ?>
                  <li class="page-item  <?php if ($p == $page) echo "active"; ?>">
                    <a id='page<?= $p ?>' class='page-link' href="<?= $phpself ?><?= $giho ?>page=<?= $p ?>" aria-controls="datatable" data-dt-idx="3" tabindex="0"><?= $p ?></a>
                  </li>
                <?php
                }
                ?>
                <li class="page-item">
                  <a class="page-link" href="<?= $phpself ?><?= $giho ?>page=<?= $next_page ?>">></a>
                </li>
                <li class="page-item">
                  <a class="page-link" href="<?= $phpself ?><?= $giho ?>page=<?= $pageNum ?>">>></a>
                </li>
              </ul>
            </div>
          </div>
        </form>
      </section>
    </div>
    <!-- /.card-footer -->
    <?php include $localURL . 'footer.php' ?>
  </div>
  <?php include $localURL . 'footer_script.php' ?>

  <script>
    // 날짜 선택 기능
    $("#sInputDate").datepicker({
      monthNames: ['1월(JAN)', '2월(FEB)', '3월(MAR)', '4월(APR)', '5월(MAY)', '6월(JUN)',
        '7월(JUL)', '8월(AUG)', '9월(SEP)', '10월(OCT)', '11월(NOV)', '12월(DEC)'
      ],
      changeYear: false,
      dateFormat: 'yy-mm-dd'
    });

    $("#eInputDate").datepicker({
      monthNames: ['1월(JAN)', '2월(FEB)', '3월(MAR)', '4월(APR)', '5월(MAY)', '6월(JUN)',
        '7월(JUL)', '8월(AUG)', '9월(SEP)', '10월(OCT)', '11월(NOV)', '12월(DEC)'
      ],
      changeYear: false,
      dateFormat: 'yy-mm-dd'
    });

    $('#sInputDateImg').click(function(event) { // 실행하고자 하는 jQuery 코드
      $('#sInputDate').datepicker("show");
    });

    $('#eInputDateImg').click(function(event) { // 실행하고자 하는 jQuery 코드
      $('#eInputDate').datepicker("show");
    });

    // 날짜, SELECT 박스 선택 시 버튼 클릭 트리거
    $("#occd").change(function() {
      $("#searchBtn").trigger("click");
    });

    $("#sdg_get").change(function() {
      $("#searchBtn").trigger("click");
    });

    $("#sInputDate").change(function() {
      $("#searchBtn").trigger("click");
    });

    $("#eInputDate").change(function() {
      $("#searchBtn").trigger("click");
    });

    $("#date_option").change(function() {
      $("#searchBtn").trigger("click");
    });

    $("#day").change(function() {
      $("#searchBtn").trigger("click");
    });

    $(".detail").click(function() {
      var codeParent = $(this).parent();

      var order_no = codeParent.find("#success_order_no").val();
      var order_cond_cd = codeParent.find("#success_order_cond_cd").val();
      var code_seller_id = codeParent.find("#success_seller_id").val();
      var con_tm = codeParent.find("#success_con_tm").val();

      location.href = "./order_list_detail.php?order_no=" + order_no + "&order_cond_cd=" + order_cond_cd + "&seller_id=" + code_seller_id + "&con_tm=" + con_tm;
    });

    // 쿠폰 리스트 선택
    $("#coupon_show").on("click", function() {
      $("#searchBtn").trigger("click");
    });

    // 2021-08-02 배송완료 전체 체크
    $("#order_check").click(function(){
      if ($("#order_check").prop("checked")) {
          $("input[name=success_check]").prop("checked",true);
      } else {
          $("input[name=success_check]").prop("checked",false);
      }
    });

    function all_order_check(){//선택 배송완료 바꾸기

      var locate, cond_order_no, cond_seller_id, cond_cust_id, cond_wtid, cond_business_name, cond_seller_name,cond_cd,cond_name,deadline_tm, parentsTr, sum_name, list=[];

      var cnt = 0;
      var alertMsg = "";
      var alertMsgAll = "";

      $("input[name=success_check]:checked").each(function(){
        parentsTr				    = $(this).parents("tr");
        cond_order_no	      = parentsTr.find("#success_order_no").val();
        cond_seller_id 	    = parentsTr.find("#success_seller_id").val();
        cond_cust_id 		    = parentsTr.find("#success_cust_id").val();
        cond_wtid 			    = parentsTr.find("#success_wtid").val();
        cond_business_name  = parentsTr.find("#success_business_name").val();
        cond_seller_name    = parentsTr.find("#success_seller_name").val();
        cond_cd             = parentsTr.find("#success_order_cond_cd").val();
        cond_name           = parentsTr.find("#success_order_cond_name").val();
        deadline_tm           = parentsTr.find("#success_deadline_tm").val();

        sum_name = cond_business_name + "_" + cond_seller_name;

        locate = "../php/api/order/complete_order.php" +
        "?order_cond_name=" + cond_name +
        "&order_no=" + cond_order_no +
        "&seller_id=" + cond_seller_id +
        "&cust_id=" + cond_cust_id +
        "&wtid=" + cond_wtid;

        if (cond_cd == '01') {
          locate += "&tm=" + deadline_tm;
        }

        alertMsgAll += locationComplete(encodeURI(locate), cond_order_no, cond_seller_id, cond_cd, sum_name, alertMsg) + "\n";

        list[cnt] =  cond_business_name+"-> "+cond_order_no + " : " +cond_seller_name;
        cnt++;
      });

      alert(alertMsgAll);

      window.location.reload();
    }

    function locationComplete(locate, cond_order_no, cond_seller_id, nowcode, sum_name, alertMsg){
      var list = {
        "order_no"  : cond_order_no,
        "seller_id" : cond_seller_id
      };

      $.ajax({
        url : "../php/api/order/complete_location.php",
        type : "POST",
        data : list,
        async : false,
        error : function(){
          alertMsg  = "사이트 접속에 문제로" + sum_name +" 오류발생";
        },
        success : function(retultDatas){
          alertMsg = resultswitch(retultDatas, locate, sum_name, alertMsg);
        }
      });

      return alertMsg;
    }

    function resultswitch(retultDatas, locate, sum_name, alertMsg){
      switch (retultDatas) {
        case "03":
          alertMsg = sum_name +" 이미 배송완료 상품입니다.";
            break;
        case "04":
          alertMsg = sum_name +" 이미 취소접수 상품입니다.";
            break;
        case "05":
          alertMsg = sum_name +" 이미 반품완료 상품입니다.";
            break;
        default:
          $.ajax({
            url : locate,
            type : "GET",
            async : false,
            error : function(){
              alertMsg = sum_name + " ※실패※";
            },
            success : function(retultDatas){
              alertMsg = sum_name + " ※성공※";
            }
          });
      }

      return alertMsg;
    }
  </script>
</body>

</html>
<?php
mysqli_close($db->Order->conn);
// mysql_close($dbErp);
?>
