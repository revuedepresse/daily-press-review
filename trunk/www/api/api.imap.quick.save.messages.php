<?php
/**
*************
* Changes log
*
*************
* 2011 03 06
*************
* 
* Get mailboxes to be imported from pattern
*
* (branch 0.1 :: revision :: 575)
* (trunk :: revision :: 125)
* 
*************
* 2011 03 05
*************
* 
* Start revising the message saving methods from the IMAP API
* for optimizing performances 
*
* (branch 0.1 :: revision :: 570)
* (trunk :: revision :: 89-98)
*
*/

$class_message = $class_application::getMessageClass();

$mailboxes_tool = $class_message::getMailboxes( '/^/');
$class_message::import( '*', $mailboxes_tool );

//$mailboxes_api = $class_message::getMailboxes( '/^API/');
//$class_message::import( '*', $mailboxes_api );

//$mailboxes_saas = $class_message::getMailboxes( '/^saas ::/');
//$class_message::import( '*', $mailboxes_saas );

//$mailboxes_social_networks = $class_message::getMailboxes( '/^social networ/');
//$class_message::import( '*', $mailboxes_social_networks );

//$mailboxes_development = $class_message::getMailboxes( '/^development/');
//$class_message::import( '*', $mailboxes_development );

//$mailboxes_organization = $class_message::getMailboxes( '/^organization/');
//$class_message::import( '*', $mailboxes_organization );

//$mailboxes_language = $class_message::getMailboxes( '/^language/');
//$class_message::import( '*', $mailboxes_language );

//$mailboxes_microblogging = $class_message::getMailboxes( '/^microblogging/');
//$class_message::import( '*', $mailboxes_microblogging );

//$mailboxes_tool = $class_message::getMailboxes( '/^tool/');
//$class_message::import( '*', $mailboxes_tool );

//$mailboxes_server_administration = $class_message::getMailboxes( '/^server administration/');
//$class_message::import( '*', $mailboxes_server_administration );
//
//$mailboxes_news = $class_message::getMailboxes( '/^news/');
//$class_message::import( '*', $mailboxes_news );
//
//$mailboxes_social_bookmarking = $class_message::getMailboxes( '/^social bookmarking/');
//$class_message::import( '*', $mailboxes_social_bookmarking );

//$class_message::import();

//$class_message::import( '*', array( 'interests' ) );
//
//$class_message::import( '*', array( 'photography' ) );