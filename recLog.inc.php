<?php

define( "E_INFO" , 0 );
if(!defined("E_ERROR")){define( "E_ERROR", 2 );};


function reclog( $message , $level = E_INFO ) {
	
	try {
		$log = new DBRecord( LOG_TBL );
		
		$log->logtime = date("Y-m-d H:i:s");
		$log->level = $level;
		$log->message = $message;
	}
	catch( Exception $e ) {
		// 
	}
}

?>
