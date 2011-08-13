#!/usr/bin/env php
<?php
$constants = get_defined_constants();
$vars      = get_defined_vars();

require 'config.php';

$configed_constants = get_defined_constants();
//condig.php で追加されただけを取出す
$config_const = array_diff($configed_constants, $constants);
//
//$config_const["RECORD_MODE"] = $RECORD_MODE;
echo json_encode( $config_const);

