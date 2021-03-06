<?php
include_once('config.php');
include_once( INSTALL_PATH . '/DBRecord.class.php' );
include_once( INSTALL_PATH . '/Smarty/Smarty.class.php' );
include_once( INSTALL_PATH . '/Settings.class.php' );
require 'Keyword.class.php';

$settings = Settings::factory();


$order = "";
$search = "";
$category_id = 0;
$station = 0;
$_date = "";

// mysql_real_escape_stringより先に接続しておく必要がある
$dbh = @mysql_connect( $settings->db_host, $settings->db_user, $settings->db_pass );

// $options = "WHERE complete='1'";
$options = "WHERE starttime < '". date("Y-m-d H:i:s")."'";	// ながら再生は無理っぽい？

if(isset( $_REQUEST['key']) ) {
	$options .= " AND autorec ='".mysql_real_escape_string(trim($_REQUEST['key']))."'";
}

if(isset( $_REQUEST['do_search'] )) {
	if( isset($_REQUEST['search'])){
		if( $_REQUEST['search'] != "" ) {
			$search = $_REQUEST['search'];
			 $options .= " AND CONCAT(title,description) like '%".mysql_real_escape_string($_REQUEST['search'])."%'";
		}
	}
	if( isset($_REQUEST['category_id'])) {
		if( $_REQUEST['category_id'] != 0 ) {
			$category_id = $_REQUEST['category_id'];
			$options .= " AND category_id = '".$_REQUEST['category_id']."'";
		}
	}
	if( isset($_REQUEST['station'])) {
		if( $_REQUEST['station'] != 0 ) {
			$station = $_REQUEST['station'];
			$options .= " AND channel_id = '".$_REQUEST['station']."'";
		}
	}
	if( isset($_REQUEST['date'])) {
		$time = @strtotime( $_REQUEST['date'] );
		if( $time > 0 ) {
			$day_start_time = date( "Y-m-d 04:00:00", $time );
			$day_end_time   = date( "Y-m-d 04:00:00", $time+60*60*24 );
            $options .= " AND starttime >= '{$day_start_time} ' AND starttime < '{$day_end_time} '";
		}
	}
}


$options .= " ORDER BY starttime DESC";
if( isset($_REQUEST['limit'])) {
	if( $_REQUEST['limit'] != 0 ) {
			$limit = (int)$_REQUEST['limit'];
			$options .= " LIMIT {$limit}";
		}
}
if( isset($_REQUEST['offset'])) {
	if( $_REQUEST['offset'] != 0 ) {
			$offset = (int)$_REQUEST['offset'];
			$options .= " OFFSET {$offset}";
		}
}

try{
	$rvs = DBRecord::createRecords(RESERVE_TBL, $options );
	$dw = array( "日", "月", "火", "水", "木", "金", "土" );
	$records = array();
	foreach( $rvs as $r ) {
		$cat = new DBRecord(CATEGORY_TBL, "id", $r->category_id );
		$ch  = new DBRecord(CHANNEL_TBL,  "id", $r->channel_id );
		$arr = array();
		$arr['id'] = $r->id;
		$arr['station_name'] = $ch->name;
        $_time = strtotime($r->starttime);
		$arr['starttime'] = date("m月d日",$_time )."(".$dw[date('w',$_time)].")".date("\n H:i:00 ",$_time);
		$arr['endtime'] = $r->endtime;
		$arr['asf'] = "".$settings->install_url."/viewer.php?reserve_ids[]=".$r->id;
		$arr['title'] = htmlspecialchars($r->title,ENT_QUOTES);
		$arr['description'] = htmlspecialchars($r->description,ENT_QUOTES);
		$arr['thumb'] = "<img src=\"".$settings->install_url.$settings->thumbs."/".htmlentities($r->path, ENT_QUOTES,"UTF-8").".jpg\" />";
		$arr['cat'] = $cat->name_en;
		$arr['mode'] = $RECORD_MODE[$r->mode]['name'];
        $_time = $r->viewedtime ? strtotime($r->viewedtime) : null;
		$arr['viewedtime'] = $_time ?date("m月d日",$_time )."(".$dw[date('w',$_time)].")".date("\n H:i:00 ",$_time):"";
		
		array_push( $records, $arr );
	}
	
	$crecs = DBRecord::createRecords(CATEGORY_TBL );
	$cats = array();
	$cats[0]['id'] = 0;
	$cats[0]['name'] = "すべて";
	$cats[0]['selected'] = $category_id == 0 ? "selected" : "";
	foreach( $crecs as $c ) {
		$arr = array();
		$arr['id'] = $c->id;
		$arr['name'] = $c->name_jp;
		$arr['selected'] = $c->id == $category_id ? "selected" : "";
		array_push( $cats, $arr );
	}
	
	$crecs = DBRecord::createRecords(CHANNEL_TBL );
	$stations = array();
	$stations[0]['id'] = 0;
	$stations[0]['name'] = "すべて";
	$stations[0]['selected'] = (! $station) ? "selected" : "";
	foreach( $crecs as $c ) {
		$arr = array();
		$arr['id'] = $c->id;
		$arr['name'] = $c->name;
		$arr['selected'] = $station == $c->id ? "selected" : "";
		array_push( $stations, $arr );
	}
	$recs = Keyword::createRecords(KEYWORD_TBL);
	$keywords = array();
	foreach($recs as $rec){
	    $keywords[] = $rec->keyword;
	}
	//カレンダ
	$date_list = array();
	$dw = array( "日", "月", "火", "水", "木", "金", "土" );
	$date = strtotime('last Monday');
	foreach(range(0,6) as $i ){
			$date_list[] = array( 
					"date" => date('Y-m-d',$date + 60*60*24*$i ),
					"dw" =>   $dw[date('w',$date + 60*60*24*$i )],
					"day" => date('m月d日',$date + 60*60*24*$i ),
					) ;
	}
	
	$smarty = new Smarty();
	$smarty->assign( "sitetitle" , "録画済一覧"          );
	$smarty->assign( "records"   , $records              );
	$smarty->assign( "count"     , sizeof($records)      );
	$smarty->assign( "keywords"  , $keywords             );
	$smarty->assign( "date_list" , $date_list            );
	$smarty->assign( "_date"     , $_date                );
	$smarty->assign( "search"    , $search               );
	$smarty->assign( "stations"  , $stations             );
	$smarty->assign( "cats"      , $cats                 );
	$smarty->assign( "use_thumbs", $settings->use_thumbs );
	
	$next_query_string = "";
	$prev_query_string = "";
	$next_name = "次へ＞";
	$prev_name = "＜前へ";
	$curr_name = "";
	if( isset($_REQUEST["search"] )){
		$curr_name = htmlspecialchars($_REQUEST["search"])."の検索結果"; 
		$next_name = "";
		$prev_name = "";
	
	}
	//limit/offset
	if(isset($_REQUEST["limit"])){
		$limit = (int)$_REQUEST["limit"];
		$offset = isset($_REQUEST["offset"]) ? (int) $_REQUEST["offset"]: 0 ;
        $next_query_string = "?do_search=1&limit=$limit&offset=".( $offset + $limit) ;	
        $prev_query_string = "?do_search=1&limit=$limit&offset=".( ($offset - $limit) <0 ? 0 : $offset-$limit  );	
		$next_name = "次の{$limit}件＞";
		$prev_name = "<前の{$limit}件";
	}
	//検索条件が指定されていたら、次のページを指定する。
	if( isset($_REQUEST["date"] ) ){
	    $date = $_REQUEST["date"];
		date_default_timezone_set("Asia/Tokyo"); 
		$date = strtotime($date);
		$next_day = date('Y-m-d',strtotime(" +1day ", $date));
		$prev_day = date('Y-m-d',strtotime(" -1day ", $date));
        $next_query_string = "?do_search=1&date=$next_day";	
        $prev_query_string = "?do_search=1&date=$prev_day";	
		$next_name = "次の日＞";
		$prev_name = "＜前の日";
		$curr_name = date('m月d日',$date)."の録画(".sizeof($records)."件)";

	} 
	//連続再生用
	$str = "". $settings->install_url."/viewer.php?";
	foreach($records as $record){
		$str = $str."reserve_ids[]=".$record["id"]."&";
	}

	//paging
	$smarty->assign( "play_list", $str );
	$smarty->assign( "next_link", $next_query_string);
	$smarty->assign( "prev_link", $prev_query_string);
	$smarty->assign( "next_link_name", $next_name);
	$smarty->assign( "prev_link_name", $prev_name);
	$smarty->assign( "current_name",   $curr_name);


	$smarty->display("recordedTable.html");
	
	
}
catch( exception $e ) {
	exit( $e->getMessage() );
}
?>
