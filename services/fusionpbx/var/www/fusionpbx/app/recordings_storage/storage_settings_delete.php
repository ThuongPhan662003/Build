<?php

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
    if ((if_group("superadmin") || if_level("admin"))) {
        // access granted
    } else {
        echo "access denied";
        exit;
    }

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the id
	if (count($_GET)>0) {
		$id = check_str($_GET["id"]);
	}
//delete the data
	if (strlen($id)>0) {
		try{
            $domain_uuid = $_SESSION["domain_uuid"];
			$sql = "delete from storage_settings where id='${id}' AND domain_uuid='${domain_uuid}';";
			$db->exec(check_sql($sql));
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			unset($sql);
		}
		catch(Exception $err){
			messages::add($err);
			header('Location: storage_settings.php');
		}
	}

//redirect the user
	messages::add($text['message-delete']);
	header('Location: storage_settings.php');
	return;
?>
