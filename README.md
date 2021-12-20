# DeliveryManagerWeb
JSON 송수신 사용. 로지스팟 배차등록
![image](https://user-images.githubusercontent.com/72122503/146716672-570279b6-4b6c-429b-92aa-75a811423398.png)
![image](https://user-images.githubusercontent.com/72122503/146717006-7cbe2cf0-b3b0-4937-a533-ca89bd081d80.png)
![image](https://user-images.githubusercontent.com/72122503/146717838-d1a29a8f-81f5-4cb0-b52d-49c631f25396.png)


## 사용 케이스

1. (완료) 배차등록
배송일자(ex 2021-11-23), 배송센터(ex 성동), 센터그룹(ex 성동A)를 지정하여 해당하는 배송지 목록으로 로지스팟에 배차 등록
2. (완료)자사 주문 확인 페이지에서 배송상태 정보 조회.

## 로직 및 파일/DB 수정사항 정리

### 1. **배차 등록**

1. 로직
    1. 자사 DB에서 배차 등록할 하차지(고객사) 배열 생성.
    2. 테이블 'TB_DELIV_GROUP_CD' 에 있는 매칭 정보를 토대로 기사와 차량종류 지정.
     (ADMIN_ID: 배송기사, DELIV_GROUP_CONT: 차량종류)
    3. 테이블 'TB_ADMIN'를 조회하여 로지스팟고유기사ID 지정.
    (LOGIS_DRIVER_ID: 로지스팟고유기사ID ) 
    4. 테이블 'TB_CENTER_CD'의 센터 주소를 상차지 주소로 지정.
    5. 위 정보로 로지스팟 배차 등록. 
2. 작업파일:
    
    admin\delivery\logispotList_handler.php
    
    admin\php\includes\DbOperationDELIVERY.php
    
3. DB수정사항:
    - TB_ADMIN에 'LOGIS_DRIVER_ID' 컬럼 추가
        - 쿼리: ADD COLUMN `LOGIS_DRIVER_ID` VARCHAR(20) NULL DEFAULT NULL AFTER `LOGIS_DRIVER_ID`;
    - TB_ORDER에 'LOGIS_ORD_ID' 컬럼 추가
        - 쿼리: ADD COLUMN `LOGIS_ORD_ID` VARCHAR(20) NULL DEFAULT NULL AFTER `LOGIS_ORD_ID`;
    - TB_ORDER_ITEM에 'LOGIS_ORD_PLACES_ID' 컬럼 추가.
        - 쿼리: ADD COLUMN `LOGIS_ORD_PLACES_ID` VARCHAR(20) NULL DEFAULT NULL AFTER `LOGIS_ORD_PLACES_ID`;
    - TB_CENTER_CD에  'CENTER_ADDR' 칼럼 추가.
        - 쿼리: ADD COLUMN CENTER_ADDR VARCHAR(200) NOT NULL AFTER CENTER_NAME;
        - CENTER_ADDR에 센터 주소 정보 추가
            - 성동센터 서울특별시 성동구 성수동1가 708
            - 광진센터 서울특별시 광진구 아차산로33길 49
            - 마포센터 서울시 마포구 창전동 5-137
            

### **2. 배송 완료**

1. 로직
    1. 로지스팟에서 '배송완료' 이벤트를 실시간으로 받을 수 있는 방법은 없음.
    2. 필요시마다 배송상태를 조회하여 업데이트해야함.
    3. 주문관리 - 주문상세내역 관리 화면에서 TMS 상태 조회하여 표시하도록 작업.
    4. order_id와 seller_id로 LOGIS_ORD_ID, LOGIS_ORD_PLACES_ID 획득.
    5. 로지스팟 API와 JSON 통신으로 배송상태 조회.
2. 작업파일: 
    
    admin\php\includes\DbOperationDELIVERY.php
    admin\order\order_list.php
