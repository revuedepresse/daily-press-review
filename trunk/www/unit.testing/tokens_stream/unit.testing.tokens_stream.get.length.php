<?php

$class_dumper = $class_application::getDumperClass();
$class_tokens_stream = $class_application::getTokensStreamClass( NAMESPACE_CID );

$host = $class_tokens_stream::getHost();
$source_classes_names = '/includes/constants.classes.names.php';

$path = PROTOCOL_TOKEN . '://' . $host . $source_classes_names;

$length = $class_tokens_stream::slen( $path );

$class_dumper::log(
	__METHOD__,
	array( '[stream length]', $length ),
	$verbose_mode
);

/**
*************
* Changes log
*
*************
* 2011 10 03
*************
* 
* Implement unit test to get stream length
*
* (branch 0.1 :: revision :: 674)
* (branch 0.2 :: revision :: 379)
*
*/