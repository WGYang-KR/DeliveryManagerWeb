<? session_start();
//error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
//ini_set("display_errors", 1); ?>
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
	<script src="<?php echo $localURL ?>plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
  <script>
		<?php require('../../js/admin/custom.js'); ?>
		function searchPositionFn(param){
			var form = document.searchForm;
			if (!param) {}else {
				form.searchDate.value = $(param).val();
			}

			form.action = "./logispotList.php";
			form.submit();
		}

		/*로지스팟 API 이용 오더등록*/
		function registerToLogispot(param) {
			var form = document.searchForm;
			if (!param) {}else {
				form.searchDate.value = $(param).val();
			}

			form.action = "./logispotList_handler.php";
			form.submit();

		}
    // 초기화
    function on_reset(){
      location.href = "./prod_cd_list.php";
    }

    // 유통코드등록요청
    function reg_code() {
      var form = document.searchForm;
      form.action = "../../prods/prod_list.php";
      form.submit();
    }

    // MS 상품 수정
    function view(code){
      var form = document.searchForm;
      form.prod_cd.value = code;
      form.action = "./prod_cd_edit.php";
      form.submit();
    }

    // MS 유통코드등록
    function mapping(code){
      var form = document.searchForm;
      form.prod_cd.value = code;
      form.action = "./prod_cd_matching.php";
      form.submit();
    }

		// 업장 옵션 선택
		function packUpdate(param,prod_cd){
      // if ($(param).val() == "" && columnName !== "DELIV_GROUP_CD") {
      //   return;
      // }else {
        var post_list = {
          "PACKING_NO":$(param).val(),
          "PROD_CD":prod_cd
        }
        // return alert(JSON.stringify(post_list));
        $.ajax({
          url: "../php/api/delivery/packUpdate.php",
          type: "post",
          data: post_list,
          async: false,
          error: function() {
            alert("오류발생..");
            window.location.reload(true);
          },
          success: function(resultData) {
            if (resultData == "success") {
              alert("수정성공");
            }else {
              alert("수정실패");
            }
            window.location.reload(true);
          }
        })
      // }
    }
  </script>

  <style>
      table {
        margin: auto;
        text-align: center;
      }
  </style>
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
							<h1>로지스팟 배차 등록</h1>
						</div>
					</div>
				</div>
			</section>

      <!-- Main content -->
			<section class="content">
        <div class="card">
					<!-- <div class="card-body">

					</div> -->
          <div class="card-header">
            <form name='searchForm' id='searchForm' method="get" action="./prod_cd_list.php">
              <input type="hidden" name="prod_cd">
							<div class="form-group">
								<div class="row">
									<div class="col-md-3 col-sm-6 col-12">
										<input type="date" class="form-control" id="searchDate" name="searchDate" value="<?php echo $searchDate; ?>" onchange="searchPositionFn(this);">
									</div>
								</div>
							</div>
							<div class="form-group row col-md-3">
								<select class='custom-select' name="sqcCenterCd" onchange="searchPositionFn();">
									<!-- <option value="ALL" <?// echo ($sqcCenterCd == 'ALL') ? 'selected' : ''; ?>>
										전체업장
									</option> -->
									<option value="none" <? echo ($sqcCenterCd == 'none') ? 'selected' : ''; ?>>
										센터미배정
									</option>
									<?php
									$selectCenterCd = $db->DELIVERY->selectCenterCd(null);

									if ($selectCenterCd == SELECT_FAILED) {
									} else {
										$selectCenterCd = $db->fetchDB($selectCenterCd);
										foreach ($selectCenterCd as $CenterKey => $CenterValue) {
											$CENTER_CD = $CenterValue["CENTER_CD"];
											$CENTER_NAME = $CenterValue["CENTER_NAME"];
												echo "<option value='$CENTER_CD'";
												echo ($sqcCenterCd == "$CENTER_CD") ? 'selected' : '';
												echo "> $CENTER_NAME </option>";
										}
									}
									?>
								</select>
							</div>
							<div class="form-group row col-md-3">
								<select class='custom-select' name="sqcGroupCd" onchange="searchPositionFn();" >
									<?php
									$selectDvGroupCd = $db->DELIVERY->selectDvGroupCd($_REQUEST);

									if ($selectDvGroupCd == SELECT_FAILED) {
										echo "<option value='ALL'>배송그룹</option>";
									} else {?>
										<!-- <option value="ALL" <? //echo ($sqcGroupCd == 'ALL') ? 'selected' : ''; ?>>
											전체
										</option> -->
										<option value="none" <? echo ($sqcGroupCd == 'none') ? 'selected' : ''; ?>>
											그룹미배정
										</option>
									<?
										$selectDvGroupCd = $db->fetchDB($selectDvGroupCd);
										foreach ($selectDvGroupCd as $DvGroupKey => $DvGroupValue) {
											// DELIV_GROUP_CD, DELIV_GROUP_NAME, DELIV_GROUP_CONT, CENTER_CD, ADMIN_ID, USE_YN
											$DELIV_GROUP_CD = $DvGroupValue["DELIV_GROUP_CD"];
											$DELIV_GROUP_NAME = $DvGroupValue["DELIV_GROUP_NAME"];

											if ($sqcGroupCd == "$DELIV_GROUP_CD") {
												$excelGroup = $DELIV_GROUP_NAME;
												$downYN = "display:block";
											}

												echo "<option value='$DELIV_GROUP_CD'";
												echo ($sqcGroupCd == "$DELIV_GROUP_CD") ? 'selected' : '';
												echo "> $DELIV_GROUP_NAME </option>";
										}
									}
									?>
								</select>
							</div>
							<div class="row">
								<div class="col-sm-2" style="<?echo $downYN;?>">
									<div class="area_buttonproduct product">
										<button type='button' onclick="registerToLogispot();"
											class="btn btn-block btn-info">
										로지스팟 업로드</button>
									</div>
								</div>
								<!-- 엑셀다운로드 -->
								<div class="col-sm-2" style="<?echo $downYN;?>">
									<div class="area_buttonproduct product">
										<button type='button' onclick="tableToExcel('excel_table','<?echo date("Y-m-d",time());?>','');"
											class="btn btn-block btn-info">
											<i class="fas fa-print"></i> 엑셀 다운로드</button>
									</div>
								</div>
								

								<!-- 엑셀 업로드 / 엑셀 유효성 검사 -->
								<div class="col-sm-6">
									<form method="post" action="./excel/excel_user.php" enctype="multipart/form-data" class="clearfix excel_form">
										<div class="row">
											<div class="col-sm-9">
												<div class="form-group">
													<div class="custom-file">
														<input type="hidden" name="cust" value="<?php echo $cust ?>">
														<input type="hidden" name="row_num" value="<?php echo $row_num; ?>">
														<!-- <input type="file" class="custom-file-input" name='image[]' id='image'
															accept='accept' onchange="func(event);">
														<label class="custom-file-label" for="image">엑셀 파일 선택</label> -->
														<input type='file' class='custom-file-input' onchange="readExcel(this,'readExcelUp','./excel/excel_packing.php')" id="coupon_file">
														<label class="custom-file-label" for='coupon_file'>엑셀파일선택</label>
													</div>
												</div>
											</div>
											<div class="col-sm-3">
												<input type="button" value="엑셀업로드수정" id="readExcelUp" class="btn btn-block btn-secondary">
											</div>
										</div>
									</form>
								</div>
							</div>
            </form>

          </div>
		  <!-- ./card-header -->
		  <div class="card-footer">
		 
		  </div>

 
			
        </div>
      </section>

    </div>
    <!-- /.card-footer -->
    <?php
		$LogispotExcel = $db->DELIVERY->selectLogispotExcel($_REQUEST);
		if($LogispotExcel == SELECT_FAILED){
		}else {
			$LogispotExcel = $db->fetchDB($LogispotExcel);
			// style='display:none'
			$excel_table = "<table id='excel_table'>
			<thead>
			<tr>
			<th>key*</th>
			<th>상차일자*</th>
			<th>상차시간*</th>
			<th>상차회사명*</th>
			<th>상차주소*</th>
			<th>상차담당자이름</th>
			<th>상차담당자번호</th>
			<th>상차지고유코드</th>
			<th>상차지 특이사항</th>
			<th>상차지 메모</th>
			<th>하차일자*</th>
			<th>하차시간*</th>
			<th>하차회사명*</th>
			<th>하차주소*</th>
			<th>하차담당자이름</th>
			<th>하차담당자번호</th>
			<th>하차지 고유코드</th>
			<th>하차/상차  구분</th>
			<th>하차지 특이사항</th>
			<th>하차지 메모</th>
			<th>그룹명*</th>
			<th>전체물품명*</th>
			<th>기사(차주) 전화번호*</th>
			<th>자체관리코드</th>
			<th>내부메모</th>
			<th>물품명1</th>
			<th>물품개수1</th>
			<th>물품명2</th>
			<th>물품개수2</th>
			</tr>
			</thead>
			<tbody>";
			foreach ($LogispotExcel as $LogispotExcelKey => $LogispotExcelValue) {

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
				$LAD_CD = $LogispotExcelValue["LAD_CD"];
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

					$excel_table .=	" <tr>";
															$excel_table .=	"
															<td>$KEY_NO</td>
															<td>$START_DAY</td>
															<td>$LAU_TIME</td>
															<td>$CENTER_NAME</td>
															<td>$LAU_ADDR</td>
															<td>$ADMIN_NAME</td>
															<td>$ADMIN_TEL</td>
															<td>$LAU_CD</td>
															<td>$LAU_ISSUE</td>
															<td>$LAU_MEMO</td>
																									";
															$excel_table .=	"
															<td>$END_DAY</td>
															<td>$LAD_TIME</td>
															<td>$BNAME</td>
															<td>$ADDR</td>
															<td>$LAD_NAME</td>
															<td>$LAD_TEL</td>
															<td>$LAD_CD</td>
															<td>$LAUD_S</td>
															<td>$LAD_ISSUE</td>
															<td>$LAD_MEMO</td>
																									";
															$excel_table .=	"
															<td>$GROUP_NAME</td>
															<td>$PROD_NAME</td>
															<td>$ADMIN_TEL2</td>
															<td>$TRUE_CD</td>
															<td>$LAUD_MEMO</td>
															<td>$PROD_NAME1</td>
															<td>$PROD_COUNT1</td>
															<td>$PROD_NAME2</td>
															<td>$PROD_COUNT2</td>
														</tr>";
			}

				$excel_table .=	"	</tbody>
												</table>";
		}
		///엑셀출력
		echo "$excel_table";

		include $localURL . 'footer.php' ?>

  </div>
  <?php include $localURL . 'footer_script.php' ?>
	<script lang="javascript" src="../../js/xlsx.core.min.js"></script>
  <script type="text/javascript" src="../../js/FileSaver.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.3.1.min.js" type="text/javascript" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
  <script src="../../js/webflow.js" type="text/javascript"></script>
  <script src="../../js/custom.js" type="text/javascript"></script>
	<script src="../../js/lib/alertify.min.js"></script>
  <link rel="stylesheet" href="../../css/themes/alertify.core.css"/>
	<link rel="stylesheet" href="../../css/themes/alertify.default.css"/>
  <script>
		// 검색
		$("#prod").change(function() {
			this_cd = $(this).val();
			$.ajax({
				url: "../../php/api/select_class_cd.php",
				type: "get",
				data: "class_cd=" + this_cd,
				async: false,
				error: function() {
					alert('사이트 접속에 문제로 오류발생');
				},
				success: function(retultDatas) {
					$("#class_cd").html(retultDatas);
					var this2_cd = $("#class_cd").val();
					$.ajax({
						url: "../../php/api/select_class_detail_cd.php",
						type: "get",
						data: "class_cd=" + this2_cd,
						error: function() {
							alert('사이트 접속에 문제로 오류발생');
						},
						success: function(retultDatas) {
							$("#class_detail_cd").html(retultDatas);
							$("#searchForm").submit();
						}
					});
				}
			});
		});

		// 검색
		$("#class_cd").change(function() {
			this_cd = $(this).val();
			$.ajax({
				url: "../../php/api/select_class_detail_cd.php",
				type: "get",
				data: "class_cd=" + this_cd,
				async: false,
				error: function() {
					alert('사이트 접속에 문제로 오류발생');
				},
				success: function(retultDatas) {
					$("#class_detail_cd").html(retultDatas);
					$("#searchForm").submit();
				}
			});
		});

		// 검색
		$("#class_detail_cd").change(function() {
			$("#searchForm").submit();
		});

    // 이미지 보기
    $("#not_image").change(function() {
      $("#searchForm").submit();
    });

		// 엑셀 다운로드 시작
		function tableToExcel(id,center,group) {
			var a  = center+'_'+group;//~~센터_~~그룹
			var b  = center+'_'+group+'_배차요청.xlsx';
			// sheet 속성 외에 raw속성 true 일때 문제로판단!
			var wb = XLSX.utils.table_to_book(document.getElementById(id), {sheet:a,raw:true});
			var wbout = XLSX.write(wb, {bookType:'xlsx', bookSST:true, type: 'binary', cellDates:'d' });
			saveAs(new Blob([s2ab(wbout)],{type:"application/octet-stream"}),b);
		}
		function s2ab(s) {
			var buf = new ArrayBuffer(s.length);
			var view = new Uint8Array(buf);
			for (var i = 0; i<s.length; i++) view[i] = s.charCodeAt(i) & 0xFF;
			return buf;
		}
		// 엑셀 다운로드 끝

	
  </script>

</body>
</html>
