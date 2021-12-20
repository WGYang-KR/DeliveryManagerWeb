<?php
$localURL = '../../';

session_start();

if (!isset($_SESSION['admin_id'])) {
  echo "<meta http-equiv='refresh' content='0;url=" . $localURL . "admin_login.php'>";
  exit;
} else {
  $admin_id = $_SESSION['admin_id'];
}

require_once '../php/includes/DbOperation.php';
$db = new DbOperation();

$condition = 'new_con';
include $localURL . 'head.php';

if ($condition != 'new_con') {
  require($localURL . 'admin_sidebar.php');
}

$prevPage = $_SERVER['HTTP_REFERER'];         // 전 페이지
$get_order_no = $_GET['order_no'];            // 주문번호
$get_order_cond_cd = $_GET['order_cond_cd'];  // 주문상태코드
$get_seller_id = $_GET['seller_id'];          // 유통사 ID
$pass = "9999";
$vbnk_true = false;                           // 가상계좌 결제확인
$request_url = (empty($_SESSION["REQUEST_URI"])) ? './order_list.php' : $_SESSION["REQUEST_URI"];
$day = $_GET['con_tm'];
$con_tm = $_GET['con_tm'];

$tm_date = time();
$currentDateTime = date("Y-m-d H:i:s");

?>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

<style type="text/css">
  iframe {
    display: none;
  }

  #memoUpdate,
  #adminMemoUpdate {
    width: 100%;
    height: 80%;
  }

  .half {
    float: left;
    width: 50%;
    border-spacing: 0;
    border-collapse: collapse;
  }

  .main-text {
    text-align: center;
    font-weight: bold;
  }

  .submit_area {
    text-align: right;
  }
</style>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<body>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

  <script type="text/javascript">
    var $localURL = '<?php echo $localURL ?>';
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
            <div class="col-sm-auto">
              <h1>주문관리 상세페이지</h1>
            </div>
            <a href='<? echo $request_url; ?>' class='btn btn-secondary'>← 뒤로가기</a>
          </div>
        </div><!-- /.container-fluid -->
      </section>
      <!-- Main content -->
      <section class="content">
        <form name='searchForm' id='searchForm' method='get' action='./order_list.php'>
          <div class="card">
            <div class="card-header">
              <?php
              if ($_SESSION['admin_type'] == "SELLER") {
                $result = $db->Order->adminOrderList($get_order_cond_cd, "order", $get_order_no, "0000-01-01", "9999-12-31", $get_seller_id, "SELLER", NULL, NULL, NULL, "ALL", $day);
              } else {
                $result = $db->Order->adminOrderList($get_order_cond_cd, "order", $get_order_no, "0000-01-01", "9999-12-31", $get_seller_id, NULL, NULL, NULL, NULL, "ALL", $day);
              }

              if ($result == SELECT_FAILED) {
                if ($get_order_cond_cd == "00") {
                  echo "<script type='text/javascript'>location.href='./order_list_detail.php?order_no=$get_order_no&order_cond_cd=01&seller_id=$get_seller_id';</script>";
                } else {
                  echo "<script type='text/javascript'>location.href='$request_url';</script>";
                }

                //검색오류시 전페이지이동
                //  * -- 출고상태 변경후에 변경전 페이지로 가게되면
                //  * -- 무조건 검색 실패하기때문에 강제로 세션에 저장되었던
                //  * -- 전페이지로 강제 이동
              } else {
                if ($_SESSION['admin_type'] == "SELLER") {
                  $result->bind_result(
                    $order_no,
                    $order_cond_cd,
                    $order_cond_name,
                    $cust_id,
                    $business_name,
                    $seller_id,
                    $seller_name,
                    $payment_pr,
                    $reg_date,
                    $order_date,
                    $wtid,
                    $deadline_tm
                  );
                } else {
                  $result->bind_result(
                    $order_no,
                    $order_cond_cd,
                    $order_cond_name,
                    $cust_id,
                    $business_name,
                    $seller_id,
                    $seller_name,
                    $payment_pr,
                    $reg_date,
                    $order_date,
                    $wtid,
                    $deadline_tm,
                    $result_coupon
                  );
                }

                while ($result->fetch()) {
                  if ($get_order_cond_cd == "05") {
                    $coupon_result = $db->Order->selectCoupon("1,0", $cust_id, $order_no, ["$seller_id", "$day", "$wtid"]);
                  } else {
                    $coupon_result = $db->Order->selectCoupon("1", $cust_id, $order_no, ["$seller_id", "$day", "$wtid"]);
                  }
              ?>

                  <!-- <input type="hidden" id="cust_id" name="cust_id" value="<?php echo $cust_id ?>" />
                  <input type="hidden" id="order_no" name="order_no" value="<?php echo $order_no ?>" />
                  <input type="hidden" id="payment_pr" name="payment_pr" value="<?php echo $payment_pr ?>" /> -->

              <?
                  if ($coupon_result == SELECT_FAILED) {
                    $COUPON_PRICE = 0;
                    $COUPON_PRICE_ALL = 0;
                    $COUPON_PRICE_SELLER = 0;
                    $coupon_list = "";
                    $clcd_coupon_use_stip = 0;
                    $COUPON_DISCOUNT_PRICE = 0;
                  } else { //11번째컬럼 쿠폰금액
                    $COUPON_PRICE_SELLER = 0;

                    $coupon_result->bind_result(
                      $COUPON_NO1,                // 쿠폰 번호
                      $COUPON_CLASS_CD,           // 쿠폰 분류 코드
                      $COUPON_USE_YN,             // 쿠폰 사용 여부
                      $COUPON_REG_DATE,           // 쿠폰 등록 날짜
                      $CUST_ID,                   // 사용자 ID
                      $COUPON_HIS_NO,             // 쿠폰 히스토리 번호
                      $COUPON_NO2,
                      $ORDER_NO,                  // 주문 번호
                      $COUPON_USE_YN,
                      $COUPON_HIS_DATE,
                      $COUPON_DISCOUNT_PRICE,     // 쿠폰 할인가
                      $COUPON_SELLER_NAME,        // 유통사먕
                      $COUPON_SELLER_ID,          // 유통사 ID
                      $clcd_coupon_class_name,    // 쿠폰 분류명
                      $clcd_coupon_cont,          // 관리자 쿠폰 내용
                      $clcd_coupon_benefit,       // 쿠폰 내용
                      $clcd_coupon_use_stip,      // 쿠폰 사용 조건
                      $clcd_coupon_start_tm,      // 쿠폰 시작 날짜
                      $clcd_coupon_end_tm,        // 쿠폰 종료 날짜
                      $clcd_COUPON_IMG            // 쿠폰 이미지
                    );
                    while ($coupon_result->fetch()) {
                      $COUPON_PRICE_ALL += $COUPON_DISCOUNT_PRICE;
                      if ($COUPON_SELLER_ID ==  $seller_id) {
                        $COUPON_PRICE_SELLER += $COUPON_DISCOUNT_PRICE;
                        $COUPON_DISCOUNT_PRICE = $result_coupon;
                        $COUPON_PRICE = $COUPON_DISCOUNT_PRICE;
                        $COUPON_DISCOUNT_PRICE_FORMET = number_format($COUPON_DISCOUNT_PRICE);
                        if ($_SESSION["admin_type"] == "SELLER") {
                          $coupon_list = "";
                        } elseif ($COUPON_DISCOUNT_PRICE == 0) {
                          echo ",";
                        } else {
                          $coupon_list = "
                            <tr class='list_item_count'>
                              <td></td>
                              <td>
                                $COUPON_NO1
                              </td>
                              <td>
                                <img src='/php/api/coupon/$clcd_COUPON_IMG' width='100px' height='100px'>
                              </td>
                              <div>
                                <td class='cou_product_title'>$clcd_coupon_class_name</td>
                                <td>$clcd_coupon_benefit</td>
                              </div>
                              <div>
                                <input type='hidden' id='cp_sum_no'>
                                <input type='hidden' id='cp_cust_id'>
                                <input type='hidden' id='cp_prod_order_cnt'>
                                <td>-$COUPON_DISCOUNT_PRICE_FORMET 원</td>
                                <td>1 개</td>
                                <td>-$COUPON_DISCOUNT_PRICE_FORMET 원 X 1 개 = <em style='font-weight: bold;'>-$COUPON_DISCOUNT_PRICE_FORMET 원</em></td>
                              </div>
                            </tr>
                          ";
                        }

                        $COUPON_NO3 = $COUPON_NO1;
                      } else {
                        $clcd_coupon_use_stip = 0;
                      }
                    }
                  }

                  if ($order_cond_cd == "01" || $order_cond_cd == "02" || $order_cond_cd == "03") {
                    if ($_SESSION['admin_type'] == "SELLER") {
                      $formet_pr = number_format($payment_pr);
                    } else {
                      $formet_pr = number_format($payment_pr - $result_coupon);
                    }
                  } else {
                    if ($_SESSION['admin_type'] == "SELLER") {
                      $formet_pr = number_format(- ($payment_pr));
                    } else {
                      $formet_pr = number_format(- ($payment_pr - $result_coupon));
                    }
                  }

                  $del_no = $db->Order->selectCustDel($cust_id);

                  if ($del_no == SELECT_FAILED) {
                    //
                  } else {
                    $del_no->bind_result($del_class_no);

                    while ($del_no->fetch()) {
                      //
                    }
                  }

                  $del_position = $db->Order->selectDelYN($cust_id);

                  if ($del_position == SELECT_FAILED) {
                    //
                  } else {
                    $del_position->bind_result($del_position_yn);

                    while ($del_position->fetch()) {
                      //
                    }
                  }

                  $yoil = array("일", "월", "화", "수", "목", "금", "토");
                  $date_day = ($yoil[date('w', strtotime($order_date))]);

                  $date = date("Y-m-d A g:i", strtotime($order_date)) . " $date_day" . "요일";
                  $ampm[0] = "AM";
                  $ampm[1] = "PM";

                  $ampm_str[0] = "오전";
                  $ampm_str[1] = "오후";

                  $date_str = str_replace($ampm, $ampm_str, $date);

                  echo "
                        <h2>$order_cond_name</h2></br>
                  ";

                  if ($_SESSION['admin_type'] == "SELLER") {
                    echo "
                        <table class='table table-bordered'>
                          <tbody>
                    ";

                    $result_cust = $db->Order->selectCust($cust_id, null, "Y", $del_class_no, $del_position_yn);

                    if ($result_cust == SELECT_FAILED) {
                      //
                    } else {
                      $result_cust->bind_result(
                        $cust_name,
                        $owner_name,
                        $addr_cd,
                        $addr_cont,
                        $tel_no,
                        $activ_yn,
                        $ad_aggr_yn,
                        $reg_date,
                        $email,       // 업장 이메일 추가
                        $sales_type,  // 업태 추가
                        $ctg_name,    // 업장 종목 추가
                        $del_cont     // 희망배송시간
                      );

                      while ($result_cust->fetch()) {
                        //
                      }
                    }

                    switch ($sales_type) {
                      case 'BUSINESS1':
                        $sales_type_nm = "개인업장";
                        break;

                      case 'BUSINESS2':
                        $sales_type_nm = "법인업장";
                        break;

                      case 'BUSINESS3':
                        $sales_type_nm = "프랜차이즈";
                        break;

                      case 'BUSINESS4':
                        $sales_type_nm = "급식업체";
                        break;

                      default:
                        $sales_type_nm = "";
                        break;
                    }

                    echo "
                            <tr>
                              <td rowspan='12' class='main-text'>식당 정보</td>
                              <td>상호명 / ID</td>
                              <td>$cust_name / $cust_id</td>
                            </tr>
                            <tr>
                              <td>배송지</td>
                              <td>$addr_cont</td>
                            </tr>
                            <tr>
                              <td>대표자</td>
                              <td>$owner_name</td>
                            </tr>
                            <tr>
                              <td>전화번호</td>
                              <td>$tel_no</td>
                            </tr>
                            <tr>
                              <td>희망배송시간</td>
                              <td>$del_cont</td>
                            </tr>
                            <tr>
                              <td>이메일</td>
                              <td>$email</td>
                            </tr>
                            <tr>
                              <td>업태</td>
                              <td>$sales_type_nm</td>
                            </tr>
                            <tr>
                              <td>종목</td>
                              <td>$ctg_name</td>
                            </tr>
                            <tr>
                              <td>주문일시</td>
                              <td>$date_str</td>
                            </tr>
                            <tr>
                              <td>총주문금액</td>
                              <td>$formet_pr" . "원</td>
                            </tr>
                            <tr>
                              <td>요청사항</td>
                    ";

                    $result_memo = $db->Order->selectOrderMemo($order_no, $seller_id);

                    if ($result_memo == SELECT_FAILED) {
                      echo "
                              <td>없음</td>
                      ";
                    } else {
                      $result_memo->bind_result($memo);

                      while ($result_memo->fetch()) {
                        if ($memo == "") {
                          echo "<td>없음</td>";
                        } else {
                          echo "<td><pre>$memo</pre></td>";
                        }
                      }
                    }

                    echo "
                            </tr>
                            <tr>
                              <td>관리자 메모</td>

                    ";

                    $admin_memo = $db->Order->selectOrderAdminMemo($cust_id);

                    if ($admin_memo == SELECT_FAILED) {
                      echo "
                              <td>없음</td>
                      ";
                    } else {
                      $admin_memo->bind_result($am_admin_no, $am_admin_id, $am_cust_id, $am_url, $am_memo, $am_reg_date);

                      while ($admin_memo->fetch()) {
                        if ($am_memo == "") {
                          echo "<td>없음</td>";
                        } else {
                          echo "<td><pre>$am_memo</pre></td>";
                        }
                      }
                    }

                    echo "</tr>";

                    echo "
                          </tbody>
                        </table>
                    ";
                  } else {
                    echo "
                        <table class='table table-bordered half'>
                          <tbody>";

                    $result_cust = $db->Order->selectCust($cust_id, "VW", "Y", $del_class_no, $del_position_yn);

                    if ($result_cust == SELECT_FAILED) {
                    } else {
                      $result_cust->bind_result(
                        $cust_name,
                        $owner_name,
                        $addr_cd,
                        $addr_cont,
                        $tel_no,
                        $activ_yn,
                        $ad_aggr_yn,
                        $reg_date,
                        $accn_no,
                        $accn_name,
                        $bn_cd,
                        $email,       // 업장 이메일 추가
                        $sales_type,  // 업태 추가
                        $ctg_name,    // 업장 종목 추가
                        $del_cont
                      );
                      while ($result_cust->fetch()) {
                        //
                      }
                    }

                    switch ($sales_type) {
                      case 'BUSINESS1':
                        $sales_type_nm = "개인업장";
                        break;

                      case 'BUSINESS2':
                        $sales_type_nm = "법인업장";
                        break;

                      case 'BUSINESS3':
                        $sales_type_nm = "프랜차이즈";
                        break;

                      case 'BUSINESS4':
                        $sales_type_nm = "급식업체";
                        break;

                      default:
                        $sales_type_nm = "";
                        break;
                    }

                    $result_memo = $db->Order->selectOrderMemo($order_no, $seller_id);

                    echo "
                            <tr>
                              <td rowspan='12' class='main-text'>식당 정보</td>
                              <td>상호명 / ID</td>
                              <td>$cust_name / $cust_id</td>
                            </tr>
                            <tr>
                              <td>배송지</td>
                              <td>$addr_cont</td>
                            </tr>
                            <tr>
                              <td>대표자</td>
                              <td>$owner_name</td>
                            </tr>
                            <tr>
                              <td>전화번호</td>
                              <td>$tel_no</td>
                            </tr>
                            <tr>
                              <td>희망배송시간</td>
                              <td>$del_cont</td>
                            </tr>
                            <tr>
                              <td>이메일</td>
                              <td>$email</td>
                            </tr>
                            <tr>
                              <td>업태</td>
                              <td>$sales_type_nm</td>
                            </tr>
                            <tr>
                              <td>종목</td>
                              <td>$ctg_name</td>
                            </tr>
                            <tr>
                              <td>주문일시</td>
                              <td>$date_str</td>
                            </tr>
                            <tr>
                              <td>총주문금액</td>
                              <td>$formet_pr" . "원</td>
                            </tr>
                            <tr>
                    ";

                    if ($result_memo == SELECT_FAILED) {
                      echo "
                              <td style='height: 150px;'>
                                요청사항 <button type='button' class='btn btn-primary' onClick='update_memo(\"$order_no\", \"$seller_id\", \"Y\")'>수정</button>
                              </td>
                              <td>
                                <textarea id='memoUpdate' style='height: 150px;' autofocus placeholder='없음'></textarea>
                              </td>
                      ";
                    } else {
                      $result_memo->bind_result($memo);
                      while ($result_memo->fetch()) {
                        echo "
                              <td style='height: 150px;'>
                                요청사항 <button type='button' class='btn btn-primary' onClick='update_memo(\"$order_no\", \"$seller_id\", \"N\")'>수정</button>
                              </td>
                        ";

                        if ($memo == "") {
                          echo "
                              <td>
                                <textarea id='memoUpdate' style='height: 150px;' autofocus placeholder='없음'></textarea>
                              </td>
                          ";
                        } else {
                          echo "
                              <td>
                                <textarea id='memoUpdate' style='height: 150px;' autofocus placeholder='없음'>$memo</textarea>
                              </td>
                          ";
                        }
                      }
                    }

                    echo "
                            </tr>
                            <tr>
                              <td style='height: 150px;'>
                                관리자 메모 <button type='button' class='btn btn-danger' onClick='update_admin_memo(\"$cust_id\")'>수정</button>
                              </td>
                    ";

                    $adminMemo = $db->Order->selectOrderAdminMemo($cust_id);

                    if ($adminMemo == SELECT_FAILED) {
                      echo "
                              <td>
                                <textarea id='adminMemoUpdate' style='height: 150px;' autofocus placeholder='없음'></textarea>
                              </td>";
                    } else {
                      $adminMemo->bind_result($am_admin_no, $am_admin_id, $am_cust_id, $am_url, $am_memo, $am_reg_date);

                      while ($adminMemo->fetch()) {
                        if ($am_memo == "") {
                          echo "
                              <td>
                                <textarea id='adminMemoUpdate' style='height: 150px;' autofocus placeholder='없음'></textarea>
                              </td>
                          ";
                        } else {
                          echo "
                              <td>
                                <textarea id='adminMemoUpdate' style='height: 150px;' autofocus placeholder='없음'>$am_memo</textarea>
                              </td>
                          ";
                        }
                      }
                    }
                    echo "
                            </tr>
                            <tr>
                              <td class='main-text'>
                                매칭 영업사원
                              </td>
                              <td colspan='2'>
                    ";

                    $result_admin_info = $db->Order->selectAdmin($cust_id);

                    if ($result_admin_info == "SELECT_FAILED") {
                      echo "
                                없음
                      ";
                    } else {
                      $result_admin_info->bind_result($met_admin_id, $met_admin_name, $met_admin_tel_no);

                      while ($result_admin_info->fetch()) {
                        echo "$met_admin_name($met_admin_tel_no)";
                      }
                    }
                    echo "
                              </td>
                            </tr>
                          </tbody>
                        </table>
                    ";

                    //=================================================================

                    echo "
                        <table class='table table-bordered half' style='height: 857px'>
                          <tbody>
                    ";

                    $result_cust = $db->Order->selectCust($seller_id, null);

                    if ($result_cust == SELECT_FAILED) {
                    } else {
                      $result_cust->bind_result(
                        $sel_name,
                        $sel_owner_name,
                        $sel_addr_cd,
                        $sel_addr_cont,
                        $sel_tel_no,
                        $sel_activ_yn,
                        $sel_ad_aggr_yn,
                        $sel_reg_date
                      );
                      while ($result_cust->fetch()) {
                        //
                      }
                    }
                    echo "
                            <tr>
                              <td rowspan='4' class='main-text'>유통업체 정보</td>
                              <td>상호명 / ID</td>
                              <td>$sel_name / $seller_id</td>
                            </tr>
                            <tr>
                              <td>소재지</td>
                              <td>$sel_addr_cont</td>
                            </tr>
                            <tr>
                              <td>대표자</td>
                              <td>$sel_owner_name</td>
                            </tr>
                            <tr>
                              <td>전화번호</td>
                              <td>$sel_tel_no</td>
                            </tr>
                            <tr>
                              <td class='main-text'>
                              카드결제여부
                              </td>
                              <td colspan='2'>
                    ";

                    if ($wtid == "" || empty($wtid)) {
                      echo "N";
                    } else {
                      if (strpos($wtid, "INIMX_VBNK") !== false || strpos($wtid, "StdpayVBNK") !== false) {
                        $wtid = explode('@', $wtid)[0];
                        echo "무통장입금";
                      } else {
                        echo "Y";
                      }
                    }

                    $prod_frame = $db->Order->selectWtidTotal($wtid);
                    if ($prod_frame == SELECT_FAILED) {
                      $prod_pr = 0;
                    } else {
                      $prod_frame->bind_result($prod_pr);
                      while ($prod_frame->fetch()) {
                        $prod_pr = $prod_pr - $COUPON_PRICE_ALL;
                      }
                    }

                    //total_price
                    $min_pr = $db->Order->selectOrderTotal($order_no, $seller_id);

                    if ($min_pr == SELECT_FAILED) {
                    } else {
                      $min_pr->bind_result($total_price);
                      while ($min_pr->fetch()) {
                        $total_price = $total_price - $COUPON_PRICE_ALL;
                      }
                    }
                    echo "
                              </td>
                            </tr>
                          </tbody>
                        </table>
                    ";
                  }
                }
              }
              ?>
            </div>
            <div class="card-body">
              <?php
              //아이템리스트출력	=====================================================
              if ($prod_pr > 0) {
                //
              } else {
                $prod_pr = 0;
              }
              if ($_SESSION['admin_type'] == "SELLER") {
                $orderlist = $db->Order->selectOrderDetailAdmin($order_no, $seller_id, "SELLER", $day);
              } else {
                $orderlist = $db->Order->selectOrderDetailAdmin($order_no, $seller_id, NULL, $day);
              }
              if ($orderlist == SELECT_FAILED) {
                //
              } else {
                $count_number = 1;
                $taxfree_y_price = 0;
                $taxfree_n_price = 0;

                $orderlist->bind_result(
                  $prod_cd,
                  $prod_name,
                  $origin_name,
                  $prod_wgt,
                  $prod_price,
                  $prod_order_cnt,
                  $fact_name,
                  $sale_unit,
                  $order1,//오더넘버
                  $order2,//오더상품넘버
                  $sel,
                  $sel_pcd,
                  $order_deadline_tm,
                  $taxfree_yn,
                  $point_order_yn,
                  $order_item_memo,
                  $ARRIVE_DATE,
                  $stn_cond_name,
                  $picking_yn
                );

                $selectOneOrderCust = $db->Order->selectFirstOrder($cust_id, $order_date);

                if ($selectOneOrderCust == SELECT_FAILED) {
                  $ord_style = "<span class='text-danger'>[첫주문]</span>";
                } else {
                  $selectOneOrderCust->bind_result($ord_count, $DELIV_POSITION, $text_color);

                  while ($selectOneOrderCust->fetch()) {
                    $deliv_style = "";

                    if (isset($DELIV_POSITION)) {
                      if (0 == $ord_count) { //첫주문
                        $ord_style = "<strong class='text-primary'>[첫주문 " . $DELIV_POSITION . "고객]</strong>";
                      } else { //첫주문아님
                        $ord_style = "<strong class='text-danger'>[" . $DELIV_POSITION . "고객]</strong>";
                      }
                    } else {
                      if (0 == $ord_count) { //첫주문
                        $ord_style = "<strong class='text-primary'>[첫주문 고객]</strong>";
                      } else { //첫주문아님
                        $ord_style = "";
                      }
                    }
                  }
                }

                //가상계좌 결제여부
                $vbnk_msg = "";

                if (strpos($wtid, "INIMX_VBNK") !== false || strpos($wtid, "StdpayVBNK") !== false) {
                  $vbnk_msg = "[■가상계좌■]";
                  $vbnk_msg_show = "style='display:none';";
                  $refund_accn_no = "$accn_no";
                  $refund_accn_name = "$accn_name";
                  $bankCode = "$bn_cd";
                  $vbnk_true = true;

                  if (isset($refund_accn_no) && $refund_accn_no !== "") {
                    $vbnk_msg_show = "";
                  }
                }

                echo "
                  <table class='table table-bordered' style='margin-top: 25px'>
                    <thead>
                      <tr>
                        <th>번호</th>
                        <th>상품코드</th>
                        <th>이미지</th>
                        <th>상품명</th>
                        <th>상품상세</th>
                        <th>상품가격</th>
                        <th>수량</th>
                        <th>총금액</th>
                        <th>메모</th>
                        <th>피킹정보</th>
                      </tr>
                    </thead>
                    <tbody>
                ";

                $d_day_item = "false"; // 배송완료여부

                while ($orderlist->fetch()) {

                  $count_text = "[$count_number]";

                  if ($taxfree_yn == "1") {
                    $taxfree_y_price += ($prod_price * $prod_order_cnt);
                  } else {
                    $taxfree_n_price += ($prod_price * $prod_order_cnt);
                  }

                  $prod_img = $prod_name;
                  $prod_price_won = number_format($prod_price);
                  $order_price_won = number_format($prod_price * $prod_order_cnt);

                  $prod_wgt = "$fact_name / $prod_wgt";

                  if ($origin_name == '') {
                    //
                  } else {
                    $prod_name = "$prod_name($origin_name)";
                  }

                  if ($cnt_before == 12) {
                    $prod_order_cnts = "$prod_order_cnt";
                  } else {
                    $prod_order_cnts = "$prod_order_cnt";
                  }

                  $prod_order_cnts = "$prod_order_cnt";

                  if ($order_deadline_tm < 2) {
                    //
                  } else {
                    $d_day_item = "true";
                    $prod_wgt .= "/ D-$order_deadline_tm";
                  }

                  if ($point_order_yn == 1) {
                    $point_value = "/소수점발주가능"; //임시주석
                    $sale_unit = "point";
                  } else {
                    $point_value = ""; //임시주석
                    $sale_unit = "";
                  }

                  /*
                  if (empty($order_item_memo) || !isset($order_item_memo)) {
                    //
                  } else {
                    $order_item_memo = "/ $order_item_memo";
                  }
                  */

                  $select_Oneprod_check = $db->Order->select_Oneprod_check($prod_cd, $sel, $order_date);
                  if ($select_Oneprod_check == SELECT_FAILED) {
                    $one_count = 0;
                  } else {
                    $select_Oneprod_check->bind_result($one_count);
                    while ($select_Oneprod_check->fetch()) {
                      $one_count;
                    }
                  }

                  if ($one_count == 1) {
                    $one_count_text = "<strong class='text-danger'>(첫발주)</strong><br/>";
                  } else {
                    $one_count_text = "";
                  }

                  if (strpos($prod_cd, "E00") !== false) {
                    $prod_cd_Event = "<strong class='text-danger'>(특가상품)</strong><br/>";
                  } else {
                    $prod_cd_Event = "";
                  }
                  $prod_wgt .= " / $stn_cond_name";
                  echo "
                      <tr class='list_item_count'>
                        <td>
                          $count_text
                        </td>
                        <td onClick='copyToClipboard(\"$sel_pcd\");'>
                          $sel_pcd
                        </td>
                        <td>
                          <img src='../../php/api/uploads/$prod_cd.png?$tm_date' width='100px' height='100px' class='image_product'>
                        </td>
                        <td class='product_title_{$prod_cd}'>$one_count_text$prod_cd_Event$prod_name$point_value</td>
                        <td>
                          $prod_wgt
                        </td>
                  ";

                  if ($order_cond_name == "배송중") {
                    //
                    if ($_SESSION['admin_type'] == "SELLER") {
                      if ($_SESSION['admin_id'] == "dosigotgan1"
                      || $_SESSION['admin_id'] == "dosigotgan2"
                      || $_SESSION['admin_id'] == "1248531373") {
                      if ($wtid == "" || empty($wtid)) {
                        $update_item_cart = "";
                      } else {
                        $card_check = "card";
                      }
                      $update_modal = "data-toggle='modal' data-target='#updateModal'";
                      }
                      // echo "<div>";
                    } else {
                      if ($wtid == "" || empty($wtid)) {
                        $update_item_cart = "";
                      } else {
                        $card_check = "card";
                      }

                      $update_modal = "data-toggle='modal' data-target='#updateModal'";
                      // echo "<div class='update_item $sale_unit'>";
                    }
                  } else {
                    // echo "<div>";
                  }

                  echo "
                        <td>
                          $prod_price_won 원
                        </td>
                  ";

                  if ($_SESSION['admin_type'] == "SELLER") {
                    if ($_SESSION['admin_id'] == "dosigotgan1"
                    || $_SESSION['admin_id'] == "dosigotgan2"
                    || $_SESSION['admin_id'] == "1248531373") {
                      //도시곳간 특별 권한,,
                      //3가지 조건 충족할경우에만 비활성화
                      //1 . 예치금결제만한다
                      //2 . 무조건 원가결제만한다
                      //3. 쿠폰사용구매를  하지않는다.
                      echo "
                          <td onclick=\"updateItem('$prod_cd', '{$order1}_{$order2}_{$sel}', '$cust_id', '$vbnk_true', '$card_check',
                                                    '$wtid', '$pass', $prod_order_cnt,0, 0,
                                                    $prod_price, $clcd_coupon_use_stip, '$refund_accn_no', '$refund_accn_name', $get_order_no)\"
                              class='update_item $sale_unit' $update_modal>
                            $prod_order_cnts 개</br>
                          </td>
                      ";
                    }else {
                      // echo "<div>";
                      echo "
                          <td>
                            $prod_order_cnts 개</br>
                          </td>
                      ";
                    }

                  } else {
                    echo "
                        <td onclick=\"updateItem('$prod_cd', '{$order1}_{$order2}_{$sel}', '$cust_id', '$vbnk_true', '$card_check',
                                                  '$wtid', '$pass', $prod_order_cnt, $prod_pr, $total_price,
                                                  $prod_price, $clcd_coupon_use_stip, '$refund_accn_no', '$refund_accn_name', $get_order_no)\"
                            class='update_item $sale_unit' $update_modal>
                          $prod_order_cnts 개</br>
                        </td>
                    ";
                  }

                  echo "
                        <td>
                          $prod_price_won 원 X $prod_order_cnt 개 = <em style='font-weight: bold;'>$order_price_won 원</em>
                        </td>

                    ";

                  echo"
                        <td>
                          <textarea id='itemMemoUpdate$order1$order2' style='height: 60px;' autofocus placeholder='없음'>$order_item_memo</textarea></br>
                          <button type='button' class='btn btn-primary btn-xs float-right' style='margin:2px' onClick='ItemMemoUpdate(\"$order1\", \"$order2\")'>수정</button>
                        </td>
                              ";
                  echo "
                    <td>
                    피킹여부: <em style='font-weight: bold;'>$picking_yn</em></br>
                    </td>
                  ";

                  echo "
                      </div>
                    </tr>
                  ";
                    $count_number++;  //상품 목록 카운드하는 함수
                  }

                  // ==============================================================================================================================================

                  echo "
                    <div class='submit_area'>
                      <input type='hidden' id='sub_order_cond_name' name='order_cond_name'/>
                      <input type='hidden' id='sub_order_no' name='order_no'/>
                      <input type='hidden' id='sub_seller_id' name='seller_id'/>
                      <input type='hidden' id='sub_day' name='tm'/>
                      <input type='hidden' id='sub_payment_pr' name='payment_pr'/>
                      <input type='hidden' id='sub_cust_id' name='cust_id'/>
                      <input type='hidden' id='sub_coupon_no' name='coupon_no'/>
                      <input type='hidden' id='sub_wtid' name='wtid'/>
                ";

                if ($order_cond_name == '출고전') {
                  if ($_SESSION['admin_type'] == "SELLER") {
                    echo "
                      <button type='button'
                              onclick=\"locationComplete('complete', '01', '', '$order_cond_name', $order_no, '$seller_id', '$day')\"
                              class='btn btn-lg btn-info'>
                        발주하기
                      </button>
                    ";
                  } else  if ($_SESSION['admin_type'] == "MASTER" || $_SESSION['admin_type'] == "MD" || $_SESSION['admin_type'] == "MANAGER") {
                    echo "
                      <button type='button'
                              onclick=\"locationComplete('cancel', '01', '', '$order_cond_name', $order_no, '$seller_id', '$day', $payment_pr, '$cust_id', '$COUPON_NO3')\"
                              class='btn btn-lg btn-danger'>
                        취소접수
                      </button>
                      <button type='button'
                              onclick=\"locationComplete('complete', '01', '', '$order_cond_name', $order_no, '$seller_id', '$day')\"
                              class='btn btn-lg btn-info'>
                        발주하기
                      </button>
                    ";
                    if ($wtid == "" || empty($wtid)) {
                      echo "<button type='button' class='btn btn-lg bg-warning add_product' id='list_item'>상품추가주문</button>";
                    }
                  }
                } elseif ($order_cond_name == '배송중') {
                  if ($_SESSION['admin_type'] == "SELLER") {
                    echo "
                      <button type='button' onclick=\"locationComplete('complete', '02', '$d_day_item', '$order_cond_name', $order_no, '$seller_id', '$day', '', '$cust_id', '', '$wtid')\" class='btn btn-lg btn-secondary'>배송완료</button>
                    ";
                  } else  if ($_SESSION['admin_type'] == "MASTER" || $_SESSION['admin_type'] == "MD" || $_SESSION['admin_type'] == "MANAGER") {
                    echo "
                      <button type='button'
                              onclick=\"locationComplete('cancel', '02', 'false', '$order_cond_name', $order_no, '$seller_id', '$day')\"
                              class='btn btn-lg btn-danger'>
                        배송취소
                      </button>
                      <button type='button'
                              onclick=\"locationComplete('complete', '02', '$d_day_item', '$order_cond_name', $order_no, '$seller_id', '$day', '', '$cust_id', '', '$wtid')\"
                              class='btn btn-lg btn-secondary'>
                        배송완료
                      </button>
                    ";
                  }
                } elseif ($order_cond_name == '취소접수') {
                  if ($_SESSION['admin_type'] == "SELLER") {
                    //
                  } else if ($_SESSION['admin_type'] == "MASTER" || $_SESSION['admin_type'] == "MD" || $_SESSION['admin_type'] == "MANAGER") {
                    $select_total_result = $db->Order->selectWtidTotal($wtid);

                    if ($select_total_result == SELECT_FAILED) {
                      echo "
                        <button type='button'
                                onclick=\"locationComplete('cancel', '04', '', '$order_cond_name', $order_no, '$seller_id', '$day', '', '$cust_id')\"
                                class='btn btn-lg btn-info'>
                          재출고
                        </button>
                        <button type='button'
                                onclick=\"locationComplete('complete', '04', '', '$order_cond_name', $order_no, '$seller_id', '$day', $payment_pr, '$cust_id')\"
                                class='btn btn-lg btn-secondary' $vbnk_msg_show>
                          반품완료
                        </button>
                      ";
                    } else {
                      $select_total_result->bind_result($getpayment_pr);

                      while ($select_total_result->fetch()) {
                        //반품완료취소
                        $getpayment_pr = ceil($payment_pr - $COUPON_PRICE_ALL);

                        echo "
                          <iframe src='../../PHP_Sample/INIrepayhtml.php?vbnk_true=$vbnk_true&wtid=$wtid&payment_pr=$getpayment_pr&total=$prod_pr&tx_y=$taxfree_y_price
                                      &tx_n=$taxfree_n_price_tax&accn_no=$refund_accn_no&accn_name=$refund_accn_name&bankCode=$bankCode' width='500' height='500'></iframe>
                        ";
                        echo "
                          <button type='button'
                                  onclick=\"locationComplete('cancel', '04', '', '$order_cond_name', $order_no, '$seller_id', '$day', '', '$cust_id')\"
                                  class='btn btn-lg btn-info'>
                            재출고
                          </button>
                          <button type='button'
                                  onclick=\"locationComplete('complete', '04', '', '$order_cond_name', $order_no, '$seller_id', '$day', $payment_pr, '$cust_id', '', '$wtid', '$vbnk_true', '$refund_accn_name', '$refund_accn_no')\"
                                  class='btn btn-lg btn-secondary' $vbnk_msg_show>
                            반품완료
                          </button>
                        ";
                      }
                    }
                  }
                } elseif ($order_cond_name == '배송완료') {
                  if ($_SESSION['admin_type'] == "SELLER") {
                    //
                  } else if ($_SESSION['admin_type'] == "MASTER" || $_SESSION['admin_type'] == "MD" || $_SESSION['admin_type'] == "MANAGER") {
                    if (($_SESSION['admin_type'] == "MASTER" && $_SESSION['admin_id'] == "orderM") ||
                      ($_SESSION['admin_type'] == "MASTER" && $_SESSION['admin_id'] == "seulgi") ||
                      ($_SESSION['admin_id'] == "alien") ||
                      ($_SESSION['admin_type'] == "MASTER" && $_SESSION['admin_id'] == "Valkyrie") ||
                      ($_SESSION['admin_type'] == "MASTER" && $_SESSION['admin_id'] == "ryan") || $_SESSION['admin_id'] == "gwang"
                    ) {
                      if ($_SESSION['admin_id'] == "alien") {
                        echo "
                          <style type='text/css'>
                            .submit_area .css_magenta{
                              display: none;
                            }
                          </style>
                          <script type='text/javascript'>
                            $('.css_order_tit').next().click(function(){//상품수량 업데이트
                              $('.submit_area').find('.css_magenta').css('display','block');
                            })
                          </script>
                        ";
                      }
                      echo "
                        <button type='button'
                                onclick=\"locationComplete('cancel', '03', '', '$order_cond_name', $order_no, '$seller_id', '$day', '', '$cust_id')\"
                                class='btn btn-lg btn-danger'>
                          오배송
                        </button>
                      ";
                    }
                  }
                }
                echo "</div>";

                echo "<span>주문번호 : $order_no&emsp;$ord_style&emsp;<strong class='text-danger'>$vbnk_msg</strong></span></br></br>";
                // echo "$ARRIVEVIEW";
                // 21. 10. 22 유통사별 입고예정일에 따라서 입고예정일이 자동으로 변경이 되는 기능
                // 21. 11. 24 ->미작동으로 complete_order 에서 새로작업
                // $MIN_ARRIVE_DATE = date("Y-m-d", strtotime("$order_date 1 day"));
                // require_once '../php/api/sellerWeek/daytempFunction.php';
                // $oriDateString = date("Y-m-d", strtotime($order_date));//주문날짜
                // $HDate = date("H",strtotime("12:00"));//마감시간
                //
                // echo "$oriDateString,$seller_id,$con_tm,$HDate,$dbUser";
                // $MIN_ARRIVE_DATE = exDevDate($oriDateString,$seller_id,$con_tm,$HDate,$dbUser);
                // echo "$MIN_ARRIVE_DATE";
                 // $MIN_ARRIVE_DATE = exDevDate($seller_id,$con_tm,$db);

                 $MIN_ARRIVE_DATE = $ARRIVE_DATE;

                if ($_SESSION['admin_type'] == "MASTER" || $_SESSION['admin_type'] == "MANAGER" || $_SESSION['admin_type'] == "SELLER") {
                  $disabled = "disabled='disabled'";

                  if ($get_order_cond_cd == '02') {
                    $disabled = '';
                  }

                  echo "
                    <div class='input-group date col-md-3'>
                      입고예정일 : &nbsp;&nbsp;<input type='text' class='form-control datetimepicker-input' autocomplete='off' id='date' $disabled
                           onChange=\"arriveChange('$get_order_cond_cd', $get_order_no, '$get_seller_id', '$day');\"
                           min='$MIN_ARRIVE_DATE' value='$ARRIVE_DATE'/>
                      <div class='input-group-append'>
                        <div class='input-group-text'><i class='fa fa-calendar'></i></div>
                      </div>
                    </div>
                  ";
                } else {
                  echo "
                    <span>
                      입고예정일 : $ARRIVE_DATE
                    </span>
                  ";
                }
                echo "$coupon_list";

                $taxfree_n_price_tax = floor($taxfree_n_price - ($taxfree_n_price / 1.1));
                $payment_pr = $payment_pr - $COUPON_PRICE;

                if ((($seller_id == "6038111270" ||
                      $seller_id == "3128125280" ||
                      $seller_id == "1018130747" ||
                      $seller_id == "6218540790" ||
                      $seller_id == "3128125280d") && $order_cond_name == '출고전') ||
                      $_SESSION['admin_id'] == "bsm7447") {

                  echo "
                    <tr>
                      <td style='text-align: center;' colspan='8'>
                        <button type='button' class='btn btn-lg btn-secondary'
                        onclick=\"download_order_list($order_no,'$seller_id','$business_name','$seller_name','$order1', 'normal');\">
                          $business_name 주문서 다운로드[$seller_name]
                        </button>
                      </td>
                    </tr>
                  ";
                }

                //21. 10. 유통사별 주문서 다운로드 기능 추가 제작 현재 오픈할 관리자 정하지 않음.
                // 현대
                if ($seller_id == "1248531373" && $order_cond_name == '출고전' && $_SESSION['admin_type'] == 'MASTER') {
                  echo "
                    <tr>
                      <td style='text-align: center;' colspan='8'>
                        <button type='button' class='btn btn-lg btn-info'
                        onclick=\"download_order_list($order_no,'$seller_id','$business_name','$seller_name','$order1', 'hd');\">
                          {$business_name}_주문서_{$seller_name}
                        </button>
                      </td>
                    </tr>
                  ";
                }
                // 삼성
                if ($seller_id == "1258544565" && $order_cond_name == '출고전' && $_SESSION['admin_type'] == 'MASTER') {
                  echo "
                    <tr>
                      <td style='text-align: center;' colspan='8'>
                        <button type='button' class='btn btn-lg btn-info'
                        onclick=\"download_order_list($order_no,'$seller_id','$business_name','$seller_name','$order1', 'samsung');\">
                          {$business_name}_주문서_{$seller_name}
                        </button>
                      </td>
                    </tr>
                  ";
                }


                echo "
                    </tbody>
                  </table>
                ";

              }
              ?>
            </div>

            <!-- 상품 수량 변경 Modal -->
            <div class="modal fade" id="updateModal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="updateModalLabel" aria-hidden="true">
              <div class="modal-dialog" role="document">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="updateModalLabel">주문 수량 변경</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                    </button>
                  </div>
                  <div class="modal-body">
                    <input type='number' class='form-control' id='item_num' step='1' placeholder="수량입력"/></br>
                    <input type="password" class='form-control' id='item_pass' placeholder='암호입력'/>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-primary" id="update_ok">확인</button>
                    <iframe id="part_cancel" src="../../PHP_Sample/INIrepayhtml.php?vbnk_true=<? echo $vbnk_true ?>&wtid=<? echo $wtid?>&accn_no=<? echo $refund_accn_no?>&accn_name=<? echo $refund_accn_name ?>&bankCode=<? echo $bankCode?>">
                    </iframe>
                  </div>
                </div>
              </div>
            </div>
            <!-- 상품 수량 변경 Modal -->

          </div>
          <!-- /.card-body -->
        </form>
      </section>
    </div>
    <!-- /.card-footer -->
    <?php include $localURL . 'footer.php' ?>
  </div>
  <?php include $localURL . 'footer_script.php' ?>
  <div id="excel_befor">
    <!-- 엑셀 다운로드 -->
  </div>
</body>

<script lang="javascript" src="../../js/xlsx.core.min.js"></script>
<script type="text/javascript" src="../../js/FileSaver.min.js"></script>

<script type="text/javascript">
  function copyToClipboard(val) {
    var t = document.createElement("textarea");
    document.body.appendChild(t);
    t.value = val;
    t.select();
    document.execCommand('copy');
    document.body.removeChild(t);
    // alert(val+'복사 완료');
  }
  $("#date").datepicker({
    monthNames: ['1월(JAN)', '2월(FEB)', '3월(MAR)', '4월(APR)', '5월(MAY)', '6월(JUN)',
      '7월(JUL)', '8월(AUG)', '9월(SEP)', '10월(OCT)', '11월(NOV)', '12월(DEC)'
    ],
    changeYear: false,
    dateFormat: 'yy-mm-dd'
  });

  // 특정날짜들 배열
  var blank_pattern = /^\s+|\s+$/g;

  function updateItem(up_prod_cd, up_sum_no, up_cust_id, up_vbnk_true, up_card_check, up_wtid, up_password, up_prod_order_cnt,
                      up_prod_pr, up_total_price, up_prod_item_pr, up_card_stip, up_refund_accn_no, up_refund_accn_name, up_order_no) {

    var list_prod_cd = up_prod_cd
    var sum_no       = up_sum_no;
    var cust_id      = up_cust_id;
    var vbnk_true    = up_vbnk_true;
    var card_check   = up_card_check;
    var point        = $(".update_item").hasClass("point");
    var wtid         = up_wtid;
    var password     = up_password;

    if (point) { //소수점발주상품일때
      var prod_order_cnt = (up_prod_order_cnt * 1).toFixed(2); // 상품 개수
    } else {
      var prod_order_cnt = up_prod_order_cnt
    }

    var item_num = 0;       // 상품 변경 수량
    var fix_item_num = 0;   // 상품 변경 소수점 수량 -> 정수로 변경

    $("#update_ok").off().on("click", function() {
      if (point) { //소수점발주상품일때
        item_num = ($("#item_num").val() * 1).toFixed(2);
        fix_item_num = item_num;
      } else {
        item_num = $("#item_num").val();
        fix_item_num = parseInt(item_num);
      }

      if ($("#item_pass").val() == password) {
        if (item_num.replace(blank_pattern, '') == "") {
          return alert("공백은 입력할수 없습니다.");
        }

        if (card_check == "card" && prod_order_cnt < fix_item_num) {
          return alert("카드결제는 상품추가 주문을 할 수 없습니다.");
        }

        var prod_order_cnt_msg = confirm("상품을" + prod_order_cnt + "개 에서 " + fix_item_num + "개로 변경하시겠습니까?");

        if (point) { // 소수점발주상품일때
          //
        } else { // 소수점발주상품이아닐때
          if (numCheck(fix_item_num)) {
            //
          } else {
            return alert("소수점 변경이 불가한 상품입니다.");
          }
        }

        if (prod_order_cnt_msg == true) {
          if (parseFloat(prod_order_cnt) < parseFloat(fix_item_num)) {
            fix_item_num = fix_item_num * 1;
            // 21-02-26 소수점 증/감량
            var data = sum_no + "_" + fix_item_num + "_" + cust_id + "_" + prod_order_cnt + "_" + list_prod_cd;

            // api 단에서는 history 에 찍히는 금액일치여부만 확인하면될것같음.
            $.ajax({
              url: "../php/api/order/update_item.php",
              type: "get",
              data: "sum_no=" + data,
              error: function() {
                alert('에러발생')
              },
              success: function(resultData) {
                if (resultData == "실패") {
                  //
                } else {
                  location.reload();
                }
              }
            });
          } else if (item_num.replace(blank_pattern, '') == "") {
            return alert("공백은 입력할수 없습니다.");
          } else if ($('tbody').children('.list_item_count').length == 1 && fix_item_num <= 0) {
            return alert("0이하의 숫자는 입력할수 없습니다.");
          } else if (prod_order_cnt == fix_item_num) {
            return alert("변경수량이 같습니다.");
          } else {
            fix_item_num = fix_item_num * 1;

            var data = sum_no + "_" + fix_item_num + "_" + cust_id + "_" + prod_order_cnt + "_" + list_prod_cd;

            var cancel_cnt = ((prod_order_cnt) - (fix_item_num) * 1).toFixed(2);  // 상품 취소 개수
            var prod_pr = up_prod_pr; //전체금액
            var total_price = parseInt(up_total_price); // 총 주문액
            var payment_pr = (Math.ceil(prod_pr) - Math.ceil(prod_pr - (up_prod_item_pr * cancel_cnt))); //취소액

            var card_stip = up_card_stip;
            var pp_pr = (total_price + card_stip - payment_pr);
            var refund_accn_no = up_refund_accn_no;
            var refund_accn_name = up_refund_accn_name;

            var total = Math.ceil(prod_pr) - payment_pr;

            if (card_stip >= pp_pr && <? echo $COUPON_PRICE_SELLER; ?> !== 0) {
              return alert("쿠폰 취소한도(" + comma(card_stip) + "원)초과 >> 초과금액 : " + comma(pp_pr - card_stip));
            }

            if (card_check == "card") {
              var product_title = $(".product_title_" + list_prod_cd).text();

              if (wtid == "") {
                location.href = href;
              } else {
                if (vbnk_true) {
                  // 21-02-26 소수점 증/감량
                  // 총결제 취소금액만 넘겨주면 될것같음
                  var part_cancel_msg = confirm(product_title + " 상품 " + cancel_cnt + "개를 취소 하시겠습니까?\n예금주 : " + refund_accn_name + "\n계좌번호 : " + refund_accn_no);

                  if(part_cancel_msg == true){
                    $("#part_cancel").contents().find("input[name=price]").attr('value', payment_pr);
                    $("#part_cancel").contents().find("input[name=confirm_price]").attr('value', total);

                    $("#part_cancel").contents().find("#iframe_btn").trigger("click"); // 아이프레임 내부 이벤트 실행

                    $("#part_cancel").on('load', function() { // 아이프레임이 로딩됬을떄 실행되는로직
                      if ($("#part_cancel").contents().find("body").html() == "00") {
                        data = data + "_" + card_check;

                        // 21-02-26 소수점 증/감량
                        // api 단에서는 history 에 찍히는 금액일치여부만 확인하면될것같음.
                        $.ajax({
                          url: "../php/api/order/delete_item.php",
                          type: "get",
                          data: "sum_no=" + data,
                          error: function() {
                            alert('에러발생')
                          },
                          success: function(resultData) {
                            if (resultData == "실패") {
                              alert("오류발생 관리자에게 문의");
                            } else {
                              alert("[가상계좌]반품처리되었습니다");
                              kakaoMessage(up_order_no, cust_id, payment_pr, "order_cancel");
                              window.location.reload();
                            }
                          }
                        });
                      } else {
                        alert("[가상계좌]반품실패하였습니다.");
                        alert($("#part_cancel").contents().find("body").html());
                        window.location.reload();
                      }
                    })
                  }
                } else {
                  // 21-02-26 소수점 증/감량
                  // 총결제 취소금액만 넘겨주면 될것같음
                  var part_cancel_msg = confirm(product_title + " 상품 " + cancel_cnt + "개를 카드취소 하시겠습니까?");

                  if(part_cancel_msg == true){
                    $("#part_cancel").contents().find("input[name=price]").attr('value', payment_pr);//취소금액
                    $("#part_cancel").contents().find("input[name=confirm_price]").attr('value', total);//남은금액

                    $("#part_cancel").contents().find("#iframe_btn").trigger("click"); // 아이프레임 내부 이벤트 실행

                    $("#part_cancel").on('load', function() { // 아이프레임이 로딩됬을떄 실행되는로직
                      if ($("#part_cancel").contents().find("#ResultCode").val() == "00") {
                        data = data + "_" + card_check;
                        $.ajax({
                          url: "../php/api/order/delete_item.php",
                          type: "get",
                          data: "sum_no=" + data,
                          error: function() {
                            alert('에러발생');
                          },
                          success: function(resultData) {
                            if (resultData == "실패") {
                              alert("오류발생 관리자에게 문의");
                            } else {
                              alert("[카드]반품처리되었습니다");
                              kakaoMessage(up_order_no, cust_id, payment_pr, "order_cancel");
                              window.location.reload();
                            }
                          }
                        });
                      } else {

                        var order_info = {};
                        ///
                        // $cancelType = "FULL";// -FULL : 전체취소- PARTIAL : 부분취소
                        // $orgTradeKey = "1795300394781";//주문시 생성된 주문번호179520100524- TID : 결제 응답으로 부여받은 tid 값- ORDER_NUMB : 결제 요청 시 생성한 가맹점 주문번호
                        // $cancelTotalAmount = 4378;
                        //<iframe src="../../PHP_Sample/INIrepayhtml.php?vbnk_true=&amp;
                        // wtid=179530039478&amp;payment_pr=4378&amp;total=4378&amp;tx_y=0
                        // &amp;tx_n=&amp;accn_no=&amp;accn_name=&amp;bankCode=" width="500" height="500"></iframe>
                        order_info =
                        {
                          "ORDER_NO" : "<?echo $order_no;?>",
                          "PAYMENT_HIS_CD" : "SC",
                          "cancelType" : "PARTIAL",
                          "orgTradeKey" : wtid,
                          "cancelTotalAmount" : payment_pr,
                          "orgTradeDate" : "<?echo date("Ymd", strtotime($order_date));?>"
                        }
                        // return alert(JSON.stringify(order_info));
                        $.ajax({
                          url: "../../pg/ksnet/noncertCancel.php",
                          data: order_info,
                          async: false,
                          type: "post",
                          error: function() {
                            alert("[카드]반품실패하였습니다");
                            alert($("#part_cancel").contents().find("#ResultMsg").val());
                            window.location.reload();
                          },
                          success: function(resultDatas) {
                            if (resultDatas == "Success") {
                              data = data + "_" + card_check;
                              $.ajax({
                                url: "../php/api/order/delete_item.php",
                                type: "get",
                                data: "sum_no=" + data,
                                async: false,
                                error: function() {
                                  alert('에러발생');
                                },
                                success: function(resultData) {
                                  if (resultData == "실패") {
                                    alert("오류발생 관리자에게 문의.");
                                  } else {
                                    alert("[카드]반품처리되었습니다.");
                                    kakaoMessage(up_order_no, cust_id, payment_pr, "order_cancel");
                                    window.location.reload();
                                  }
                                }
                              });
                            }else {
                              alert("[카드]반품실패하였습니다.");
                              alert(resultDatas);
                              window.location.reload();
                            }
                          }
                        });
                        ///
                      }
                    })
                  }
                }
              }
            } else {
              $.ajax({
                url: "../php/api/order/delete_item.php",
                type: "get",
                data: "sum_no=" + data,
                error: function() {
                  alert('에러발생')
                },
                success: function(resultData) {
                  if (resultData == "실패") {
                    //
                  } else {
                    kakaoMessage(up_order_no, cust_id, payment_pr, "order_cancel");
                    location.reload();
                  }
                }
              });
            }

          }
        } else {
          window.location.reload();
        }
      } else {
        return alert("비밀번호가 틀렸습니다.");
      }
    });
  }

  // 상품 추가주문
  $(document).on("click", ".add_product", function() {
    var cw = screen.availWidth; // 화면 넓이
    var ch = screen.availHeight; // 화면 높이
    var sw = 1000; // 띄울 창의 넓이
    var sh = 800; // 띄울 창의 높이
    var ml = ((cw - sw) / 2); // 가운데 띄우기위한 창의 x위치
    var mt = (ch - sh) / 2; // 가운데 띄우기위한 창의 y위치
    var order_no = "<?php echo $get_order_no; ?>";
    var cust_id = "<?php echo $cust_id; ?>";
    var seller_id = "<?php echo $get_seller_id; ?>";
    var tm = "<?php echo $day; ?>";
    var open = window.open('./order_product.php?order_no=' + order_no + '&cust_id=' + cust_id + '&seller_id=' + seller_id
                          + '&tm=' + tm, 'tst', 'width=' + sw + ',height=' + sh + ',top=' + mt + ',left=' + ml + ',resizable=no, scrollbars=yes');
  });

  // 입고날짜 변경
  function arriveChange(order_cond_cd, order_no, seller_id, con_tm) {
    if (order_cond_cd !== "02") {
      alert("배송중 상태에서만 변경가능");
      // location.reload(true);
      return false;
    }

    var order_info = {
      "arrive": $("#date").val(),
      "order_no": order_no,
      "seller_id": seller_id,
      "tm": con_tm
    }

    $.ajax({
      url: "../php/api/order/change_arrive.php",
      data: order_info,
      type: "post",
      error: function() {
        alert('사이트 접속에 문제로 오류발생');
      },
      success: function(resultDatas) {
        if (resultDatas == "true") {
          alert($("#date").val() + " 으로 변경완료");
        } else {
          alert("변경실패");
        }
      }
    });
  }

  /*JS 에서 10000 -> 10,000 형태로 바꿔주는 펑션*/
  function comma(num) {
    num = parseInt(num);
    var len, point, str;

    num = num + "";
    point = num.length % 3;
    len = num.length;

    str = num.substring(0, point).trim();
    while (point < len) {
      if (str != "") str += ",";
      str += num.substring(point, point + 3);
      point += 3;
    }
    return str;
  }


  function numCheck(obj) {
    var num_check = /^[0-9]*$/;
    if (!num_check.test(obj)) {
      return false;
    }
    return true;
  }

  // 2021-07-09 상품 상태 변경
  function locationComplete(status, nowcode, complete, order_cond_name, order_no, seller_id, day, payment_pr, cust_id, coupon_no, wtid, vbnk_true, refund_accn_name, refund_accn_no) {

    // 한글체크
    const reg = /^[가-힣]+$/;
    var order_info = {}
    if (order_cond_name == null || order_cond_name.trim() == '' || !reg.test(order_cond_name) || nowcode == null || nowcode.trim() == '') {
      alert('에러가 발생했습니다.');
      window.location.reload();
    } else {
      var form = document.searchForm;

      var iframe_form = $(".submit_area").find("iframe");

      form.sub_order_cond_name.value = order_cond_name;     // 상태명
      form.sub_order_no.value        = order_no;            // 주문번호
      form.sub_seller_id.value       = seller_id;           // 유통사 ID
      form.sub_day.value             = day;                 // 마감일
      form.sub_payment_pr.value      = payment_pr;          // 주문 금액
      form.sub_cust_id.value         = cust_id;             // 식당 ID
      form.sub_coupon_no.value       = coupon_no;           // 쿠폰번호
      form.sub_wtid.value            = wtid;                // 원거래 ID

      form.action = "../php/api/order/" + status + "_order.php";

      if (nowcode == "02" && complete == "true") {
        var confirmYN = confirm("배송완료 하시겠습니까?\n확인버튼을 누르면 변경됩니다\n[D-2 이상상품인지 확인해주세요]");
      } else if (complete == "false") {
        var confirmYN = true;
      } else {
        var confirmYN = false;
      }

      if (confirmYN == true || nowcode !== "02") {
        var list = {
          "order_no": order_no,
          "seller_id": seller_id
        };

        $.ajax({
          url: "../php/api/order/get_order_cond.php",
          type: "POST",
          data: list,
          error: function() {
            alert('사이트 접속에 문제로 오류발생');
          },
          success: function(resultDatas) {
            switch (nowcode) {
              case "01":
                if (resultDatas == "01") {
                  form.submit();
                } else {
                  resultswitch(resultDatas);
                }
                break;
              case "02":
                if (resultDatas == "02") {
                  form.submit();
                } else {
                  resultswitch(resultDatas);
                }
                break;
              case "03":
                if (resultDatas == "03") {
                  form.submit();
                } else {
                  resultswitch(resultDatas);
                }
                break;
              case "04":
                if (resultDatas == "04") {
                  if (status == "complete") {
                    if (wtid == "" || payment_pr == 0) {
                      kakaoMessage(order_no, cust_id, payment_pr, "order_return");
                      form.submit();
                    } else {
                      if (vbnk_true) {
                        var cancel_msg = confirm("해당 주문을\n예금주 : " + refund_accn_name + "\n계좌번호 : " + refund_accn_no + " 으로 환불하시겠습니까?");

                        if(cancel_msg == true){
                          iframe_form.contents().find("#iframe_btn").trigger("click"); //아이프레임 내부 이벤트 실행

                          iframe_form.on('load', function() { // 아이프레임이 로딩됬을떄 실행되는로직
                            if (iframe_form.contents().find("body").html() == "00") {
                              alert("[가상계좌]환불처리되었습니다");
                              kakaoMessage(order_no, cust_id, payment_pr, "order_return");
                              form.submit();
                            } else {
                              alert("[가상계좌]환불실패하였습니다.");
                              alert(iframe_form.contents().find("body").html());
                              window.location.reload();
                            }
                          })
                        }
                      } else {
                        var cancel_msg = confirm("해당 주문을 카드취소하시겠습니까?");

                        if (cancel_msg == true) {
                          iframe_form.contents().find("#iframe_btn").trigger("click"); //아이프레임 내부 이벤트 실행

                          iframe_form.on('load', function() { // 아이프레임이 로딩됬을떄 실행되는로직
                            if (iframe_form.contents().find("#ResultCode").val() == "00") { // 성공
                              alert("[카드]반품처리되었습니다");
                              kakaoMessage(order_no, cust_id, payment_pr, "order_return");
                              form.submit();
                            } else {  // 실패
                              ///
                              // $cancelType = "FULL";// -FULL : 전체취소- PARTIAL : 부분취소
                              // $orgTradeKey = "1795300394781";//주문시 생성된 주문번호179520100524- TID : 결제 응답으로 부여받은 tid 값- ORDER_NUMB : 결제 요청 시 생성한 가맹점 주문번호
                              // $cancelTotalAmount = 4378;
                              //<iframe src="../../PHP_Sample/INIrepayhtml.php?vbnk_true=&amp;
                              // wtid=179530039478&amp;payment_pr=4378&amp;total=4378&amp;tx_y=0
                              // &amp;tx_n=&amp;accn_no=&amp;accn_name=&amp;bankCode=" width="500" height="500"></iframe>
                              //**********전체취소************
                              // order_info =
                              // {
                              //   "cancelType" : "FULL",
                              //   "orgTradeKey" : wtid
                              //   // "cancelTotalAmount" : "<//?echo $getpayment_pr;?>"전체취소에는 취소가격이 필요없음
                              // }
                              //**********전체취소************
                              order_info =
                              {
                                "ORDER_NO" : "<?echo $order_no;?>",
                                "PAYMENT_HIS_CD" : "SC",
                                "cancelType" : "PARTIAL",
                                "orgTradeKey" : wtid,
                                "cancelTotalAmount" : "<?echo $getpayment_pr;?>",
                                "orgTradeDate" : "<?echo date("Ymd", strtotime($order_date));?>"
                              }
                              // if ("<?//echo $admin_id;?>" == "orderM") {
                              //   alert(order_info.cancelTotalAmount);
                              // }
                              console.log(JSON.stringify(order_info));
                              // return false;
                              $.ajax({
                                url: "../../pg/ksnet/noncertCancel.php",
                                data: order_info,
                                type: "post",
                                error: function() {
                                  alert("[카드]반품실패하였습니다.");
                                  alert(iframe_form.contents().find("#ResultMsg").val());
                                  window.location.reload();
                                },
                                success: function(resultDatas) {
                                  if (resultDatas == "Success") {
                                    alert("[카드]반품처리되었습니다");
                                    kakaoMessage(order_no, cust_id, payment_pr, "order_return");
                                    form.submit();
                                  }else {
                                    alert("[카드]반품실패하였습니다.");
                                    alert(resultDatas);
                                    alert(iframe_form.contents().find("#ResultMsg").val());
                                    window.location.reload();
                                  }
                                }
                              });
                              ///

                            }
                          })
                        }
                      }
                    }
                  } else {
                    form.submit();
                  }
                } else {
                  resultswitch(resultDatas);
                }
                break;
              case "05":
                if (resultDatas == "05") {
                  form.submit();
                } else {
                  resultswitch(resultDatas);
                }
                break;

              default:

            }
          }
        });
      } else {
        //
      }
    }
  }

  function resultswitch(resultDatas) {
    switch (resultDatas) {
      case "01":
        alert("출고전 상품입니다.");
        window.location.reload();
        break;
      case "02":
        alert("이미 배송중 상품입니다.");
        window.location.reload();
        break;
      case "03":
        alert("이미 배송완료 상품입니다.");
        window.location.reload();
        break;
      case "04":
        alert("이미 취소접수 상품입니다.");
        window.location.reload();
        break;
      case "05":
        alert("이미 반품완료 상품입니다.");
        window.location.reload();
        break;
      default:

    }
  }

  function download_order_list(order_no, order_seller_id, download_name,
            download_seller_name, download_order_no, excelType) {
    var urlStr = "../php/api/order/download_order_list.php";
    if (excelType == "normal") {
      urlStr = "../php/api/order/download_order_list.php";
    }else if (excelType == "hd") {
      urlStr = "../php/api/order/download_order_list_hd.php";
    }else if (excelType == "samsung") {
      urlStr = "../php/api/order/download_order_list_samsung.php";
    }
    var order_info = {
      "order_no": order_no,
      "seller_id": order_seller_id,
      "download_name": download_name
    };

    $.ajax({
      url: urlStr,
      data: order_info,
      type: "post",
      error: function() {
        alert('사이트 접속에 문제로 오류발생');
      },
      success: function(resultDatas) {
        $("#maskbody").css("display", "block");
        $("#excel_befor").html(resultDatas);
        console.log(resultDatas);

        var wb = XLSX.utils.table_to_book(document.getElementById("download_order_list", {
          raw: true
        }), {
          sheet: download_name + " 주문서_" + download_seller_name + "_" + download_order_no
        });

        var wbout = XLSX.write(wb, {
          bookType: 'xlsx',
          bookSST: true,
          type: 'binary',
          cellDates: 'd'
        });

        var exsel = saveAs(new Blob([s2ab(wbout)], {
          type: "application/octet-stream"
        }), download_name + ' 주문서_' + download_seller_name + "_" + download_order_no + '.xlsx');
        $("#maskbody").css("display", "none");
      }
    });
  }

  function s2ab(s) {
    var buf = new ArrayBuffer(s.length);
    var view = new Uint8Array(buf);
    for (var i = 0; i < s.length; i++) view[i] = s.charCodeAt(i) & 0xFF;
    return buf;
  }

  function update_memo(order_no, seller_id, new_yn) {
    var memo = $("#memoUpdate").val();

    var ids = {
      "order_no"        : order_no,
      "seller_id"       : seller_id,
      "memo"            : memo,
      "new_yn"          : new_yn,
      "SCRIPT_FILENAME" : "<? echo $_SERVER["SCRIPT_FILENAME"]; ?>"
    };

    var upClick = confirm("수정하시겠습니까?");

    if (upClick) {
      $.ajax({
        url: "../php/api/order/update_memo.php",
        data: ids,
        type: "post",
        error: function() {
          alert('사이트 접속에 문제로 오류발생');
        },
        success: function(resultDatas) {
          if (resultDatas == "UPDATE_COMPLETED") {
            alert("수정완료");
          } else {
            alert("수정실패하였습니다");
            window.location.reload();
          }
        }
      });
    }
  }

  function update_admin_memo(cust_id) {
    var memo = $("#adminMemoUpdate").val();

    var ids = {
      "CUST_ID": cust_id,
      "MEMO": memo,
      "URL": "<? echo $_SERVER["SCRIPT_FILENAME"]; ?>"
    };

    var upClick = confirm("수정하시겠습니까?");

    if (upClick) {
      $.ajax({
        url: "../php/api/order/update_admin_memo.php",
        data: ids,
        type: "post",
        error: function() {
          alert('사이트 접속에 문제로 오류발생');
        },
        success: function(resultDatas) {
          if (resultDatas == "INSERT_COMPLETED") {
            alert("수정완료");
          } else {
            alert("수정실패하였습니다");
            window.location.reload();
          }
        }
      });
    }
  }

  function ItemMemoUpdate(order_no, order_item_no){
    var item_memo = $("#itemMemoUpdate"+order_no+order_item_no).val();

    var ids = {
      "ORDER_NO": order_no,
      "ORDER_ITEM_NO": order_item_no,
      "ITEM_MEMO": item_memo
    };

    var upClick = confirm("수정하시겠습니까?");

    if (upClick) {
      $.ajax({
        url: "../php/api/order/item_memo_update.php",
        data: ids,
        type: "POST",
        error: function() {
          alert('사이트 접속에 문제로 오류발생');
        },
        success: function(resultDatas) {
          console.log(resultDatas)
          if (resultDatas == "UPDATE_COMPLETED") {
            alert("수정완료");
          } else {
            alert("수정실패하였습니다");
            window.location.reload();
          }
        }
      });
    }
  }

  function kakaoMessage(order_no, cust_id, cancel_price, template_code) {
    const datas = {
      "order_no"      : order_no,
      "cust_id"       : cust_id,
      "cancel_price"  : cancel_price,
      "template_code" : template_code
    };

    $.ajax({
      type    : "POST",
      data		: datas,
      url     : "../../php/api/bizM/kakao_message.php",
      error   : function() {
        console.log("카카오톡 메세지 발송 실패");
      },
      success : function(resultData) {
        console.log(resultData);
      }
    });
  }
</script>

</html>
