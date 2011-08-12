<?php
include_once('config.php');
include_once( INSTALL_PATH . '/DBRecord.class.php' );
include_once( INSTALL_PATH . '/Smarty/Smarty.class.php' );
include_once( INSTALL_PATH . '/Settings.class.php' );
include_once( INSTALL_PATH . '/Keyword.class.php' );

$settings = Settings::factory();

$options = " WHERE starttime > '".date("Y-m-d H:i:s", time() + 300 )."'";

// 曜日
$weekofdays = array(
					array( "name" => "月", "id" => 0, "selected" => "" ),
					array( "name" => "火", "id" => 1, "selected" => "" ),
					array( "name" => "水", "id" => 2, "selected" => "" ),
					array( "name" => "木", "id" => 3, "selected" => "" ),
					array( "name" => "金", "id" => 4, "selected" => "" ),
					array( "name" => "土", "id" => 5, "selected" => "" ),
					array( "name" => "日", "id" => 6, "selected" => "" ),
					array( "name" => "なし", "id" => 7, "selected" => "" ),
);


$autorec_modes = $RECORD_MODE;
$autorec_modes[(int)($settings->autorec_mode)]['selected'] = "selected";

$search = "";
$use_regexp = 0;
$type = "*";
$category_id = 0;
$channel_id = 0;
$weekofday = 7;
$prgtime = 24;

// パラメータの処理
if(isset( $_REQUEST['do_search'] )) {
	if( isset($_REQUEST['search'])){
		$search = $_REQUEST['search'];
		if( isset($_REQUEST['use_regexp']) && ($_REQUEST['use_regexp']) ) {
			$use_regexp = (int)($_REQUEST['use_regexp']);
		}
	}
	if( isset($_REQUEST['type'])){
		$type = $_REQUEST['type'];
	}
	if( isset($_REQUEST['category_id'])) {
		$category_id = (int)($_REQUEST['category_id']);
	}
	if( isset($_REQUEST['station'])) {
		$channel_id = (int)($_REQUEST['station']);
	}
	if( isset($_REQUEST['weekofday']) ) {
		$weekofday = (int)($_REQUEST['weekofday']);
	}
	if( isset($_REQUEST['prgtime']) ) {
		$prgtime = (int)($_REQUEST['prgtime']);
	}
}

$do_keyword = 0;
if( ($search != "") || ($type != "*") || ($category_id != 0) || ($channel_id != 0) )
	$do_keyword = 1;
	
try{
	$precs = Keyword::search( $search, $use_regexp, $type, $category_id, $channel_id, $weekofday, $prgtime,  30 );
	
	$programs = array();
	foreach( $precs as $p ) {
		$ch  = new DBRecord(CHANNEL_TBL, "id", $p->channel_id );
		$cat = new DBRecord(CATEGORY_TBL, "id", $p->category_id );
		$arr = array();
		$arr['type'] = $p->type;
		$arr['station_name'] = $ch->name;
		$arr['starttime'] = $p->starttime;
		$arr['endtime'] = $p->endtime;
		$arr['title'] = $p->title;
		$arr['description'] = $p->description;
		$arr['id'] = $p->id;
		$arr['cat'] = $cat->name_en;
		$arr['rec'] = DBRecord::countRecords(RESERVE_TBL, "WHERE program_id='".$p->id."'");
		
		array_push( $programs, $arr );
	}
	
	$k_category_name = "";
	$crecs = DBRecord::createRecords(CATEGORY_TBL);
	$cats = array();
	$cats[0]['id'] = 0;
	$cats[0]['name'] = "すべて";
	$cats[0]['selected'] = $category_id == 0 ? "selected" : "";
	foreach( $crecs as $c ) {
		$arr = array();
		$arr['id'] = $c->id;
		$arr['name'] = $c->name_jp;
		$arr['selected'] = $c->id == $category_id ? "selected" : "";
		if( $c->id == $category_id ) $k_category_name = $c->name_jp;
		array_push( $cats, $arr );
	}
	
	$types = array();
	$types[0]['name'] = "すべて";
	$types[0]['value'] = "*";
	$types[0]['selected'] = $type == "*" ? "selected" : "";
	if( $settings->gr_tuners != 0 ) {
		$arr = array();
		$arr['name'] = "GR";
		$arr['value'] = "GR";
		$arr['selected'] = $type == "GR" ? "selected" : "";
		array_push( $types, $arr );
	}
	if( $settings->bs_tuners != 0 ) {
		$arr = array();
		$arr['name'] = "BS";
		$arr['value'] = "BS";
		$arr['selected'] = $type == "BS" ? "selected" : "";
		array_push( $types, $arr );

		// CS
		if ($settings->cs_rec_flg != 0) {
			$arr = array();
			$arr['name'] = "CS";
			$arr['value'] = "CS";
			$arr['selected'] = $type == "CS" ? "selected" : "";
			array_push( $types, $arr );
		}
	}
	
	$k_station_name = "";
	$crecs = DBRecord::createRecords(CHANNEL_TBL);
	$stations = array();
	$stations[0]['id'] = 0;
	$stations[0]['name'] = "すべて";
	$stations[0]['selected'] = (! $channel_id) ? "selected" : "";
	foreach( $crecs as $c ) {
		$arr = array();
		$arr['id'] = $c->id;
		$arr['name'] = $c->name;
		$arr['selected'] = $channel_id == $c->id ? "selected" : "";
		if( $channel_id == $c->id ) $k_station_name = $c->name;
		array_push( $stations, $arr );
	}
	$weekofdays["$weekofday"]["selected"] = "selected" ;
	
	// 時間帯
	$prgtimes = array();
	for( $i=0; $i < 25; $i++ ) {
		array_push( $prgtimes, 
			array(  "name" => ( $i == 24  ? "なし" : sprintf("%0d時～",$i) ),
					"value" => $i,
					"selected" =>  ( $i == $prgtime ? "selected" : "" ) )
		);
	}



	$smarty = new Smarty();
	$smarty->assign("sitetitle","番組検索");
	$smarty->assign("do_keyword", $do_keyword );
	$smarty->assign( "programs", $programs );
	$smarty->assign( "cats", $cats );
	$smarty->assign( "k_category", $category_id );
	$smarty->assign( "k_category_name", $k_category_name );
	$smarty->assign( "types", $types );
	$smarty->assign( "k_type", $type );
	$smarty->assign( "search" , $search );
	$smarty->assign( "use_regexp", $use_regexp );
	$smarty->assign( "stations", $stations );
	$smarty->assign( "k_station", $channel_id );
	$smarty->assign( "k_station_name", $k_station_name );
	$smarty->assign( "weekofday", $weekofday );
	$smarty->assign( "k_weekofday", $weekofdays["$weekofday"]["name"] );
	$smarty->assign( "weekofday", $weekofday );
	$smarty->assign( "weekofdays", $weekofdays );
	$smarty->assign( "autorec_modes", $autorec_modes );
	$smarty->assign( "prgtimes", $prgtimes );
	$smarty->assign( "prgtime", $prgtime );
	$smarty->display("programTable.html");
}
catch( exception $e ) {
	exit( $e->getMessage() );
}
?>
