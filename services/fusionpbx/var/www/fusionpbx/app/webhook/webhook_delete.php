<?php

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('access_control_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the id
	if (count($_GET)>0) {
		$id = check_str($_GET["id"]);
		$event_name = check_str($_GET["evname"]);
	}
//delete the data
	if (strlen($id)>0 || strlen($event_name)>0) {
		try{
			$sql = "delete from v_event_domain where event_name='${event_name}' and domain_uuid='${id}';";
			$db->exec(check_sql($sql));
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			unset($sql);
		}
		catch(Exception $err){
			messages::add($err);
			header('Location: webhook.php');
		}
	}

//redirect the user
	messages::add($text['message-delete']);
	header('Location: webhook.php');
	return;
?>
