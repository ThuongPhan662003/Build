<?php
/*
	FusionPBX
	Version: MPL 1.1
	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/
	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.
	The Original Code is FusionPBX
	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2018
	the Initial Developer. All Rights Reserved.
	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
require_once "root.php";
require_once "resources/require.php";

//check permissions
require_once "resources/check_auth.php";
if (permission_exists('access_control_add') || permission_exists('access_control_edit')) {
	//access granted
} else {
	echo "access denied";
	exit;
}

//add multi-lingual support
$language = new text;
$text = $language->get();
$err_msg = array();
$pattern = "/^(http(s?):\/\/)?(((www\.)?+[a-zA-Z0-9\.\-\_]+(\.[a-zA-Z]{1,3})+.)|(\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b))(\/[a-zA-Z0-9\_\-\s\.\/\?\%\#\&\=]*)?$/i";
//action add or update
if (isset($_REQUEST["id"])) {
	$action = "update";
	$domain_uuid = check_str($_REQUEST["id"]);
} else {
	$action = "add";
}
//get http post variables and set them to php variables
if (count($_POST) > 0) {
	$webhook_answered_cb = check_str($_POST["webhook_answered_cb"]);
	$webhook_hangup_cb = check_str($_POST["webhook_hangup_cb"]);
	$webhook_ringing_cb = check_str($_POST["webhook_ringing_cb"]);
	$webhook_cdr_cb = check_str($_POST["webhook_cdr_cb"]);
	$webhook_missed_cb = check_str($_POST["webhook_missed_cb"]);

	// 
	$webhook_answered = check_str($_POST["webhook_answered"]);
	$webhook_hangup = check_str($_POST["webhook_hangup"]);
	$webhook_ringing = check_str($_POST["webhook_ringing"]);
	$webhook_cdr = check_str($_POST["webhook_cdr"]);
	$webhook_missed = check_str($_POST["webhook_missed"]);
	// 
	$webhook_answered_apikey = trim(check_str($_POST["webhook_answered_apikey"]));
	$webhook_hangup_apikey =  trim(check_str($_POST["webhook_hangup_apikey"]));
	$webhook_ringing_apikey =  trim(check_str($_POST["webhook_ringing_apikey"]));
	$webhook_cdr_apikey =  trim(check_str($_POST["webhook_cdr_apikey"]));
	$webhook_missed_apikey =  trim(check_str($_POST["webhook_missed_apikey"]));
	// 
	$webhook_ringing_uuid = check_str($_POST["webhook_ringing_uuid"]);
	$webhook_answered_uuid = check_str($_POST["webhook_answered_uuid"]);
	$webhook_hangup_uuid = check_str($_POST["webhook_hangup_uuid"]);
	$webhook_cdr_uuid = check_str($_POST["webhook_cdr_uuid"]);
	$webhook_missed_uuid = check_str($_POST["webhook_missed_uuid"]);
	$domain_name = check_str($_POST["domain_name"]);
}
// No priority
if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {
	//get the primary key
	if ($action == "update") {
		$domain_uuid = check_str($_POST["domain_uuid"]);
	}
	//add or update the database
	if ($_POST["persistformvar"] != "true") {
		if ($action == "add" && permission_exists('access_control_add')) {
			//update the database
			try {
				$sql = "";
				if (isset($webhook_answered_cb) && $webhook_answered_cb != "") {
					if (!isset($webhook_answered) || $webhook_answered == "" || !preg_match($pattern,$webhook_answered)) {
						array_push($err_msg,"Callback Url Answer");
					} else {
						if (isset($webhook_answered_uuid) && $webhook_answered_uuid != "") {
							$sql = "UPDATE v_event_domain SET callback_url ='$webhook_answered', callback_apikey='$webhook_answered_apikey' WHERE event_name= 'answered' and domain_uuid='${domain_uuid}' ;";
						} else {
							$e_domain_uuid = uuid();
							$sql = "INSERT INTO v_event_domain (event_domain_uuid, domain_uuid, domain_name, event_name, callback_url, callback_apikey)  VALUES ('$e_domain_uuid', '${domain_uuid}', '${domain_name}', 'answered', '$webhook_answered', '$webhook_answered_apikey');";
						}
						$db->exec(check_sql($sql));
					}
				} else {
					$sql = "delete from v_event_domain where event_name='answered' and domain_uuid='${domain_uuid}';";
					$db->exec(check_sql($sql));
				}
				unset($sql);

				if (isset($webhook_ringing_cb) && $webhook_ringing_cb != "") {
					if (!isset($webhook_ringing) || $webhook_ringing == "" || !preg_match($pattern,$webhook_ringing)) {
						array_push($err_msg,"Callback Url Ringing");
					} else {
						if (isset($webhook_ringing_uuid) && $webhook_ringing_uuid != "") {
							$sql = "UPDATE v_event_domain SET callback_url ='$webhook_ringing', callback_apikey='$webhook_ringing_apikey' WHERE event_name= 'ringing' and domain_uuid='${domain_uuid}' ;";
						} else {
							$e_domain_uuid = uuid();
							$sql = "INSERT INTO v_event_domain (event_domain_uuid, domain_uuid, domain_name, event_name, callback_url, callback_apikey) VALUES ('$e_domain_uuid', '${domain_uuid}', '${domain_name}', 'ringing', '$webhook_ringing', '$webhook_ringing_apikey');";
						}
						$db->exec(check_sql($sql));
					}
				} else {
					$sql = "delete from v_event_domain where event_name='ringing' and domain_uuid='${domain_uuid}';";
					$db->exec(check_sql($sql));
				}
				unset($sql);
				if (isset($webhook_hangup_cb) && $webhook_hangup_cb != "") {
					if (!isset($webhook_hangup) || $webhook_hangup == "" || !preg_match($pattern,$webhook_hangup)) {
						array_push($err_msg,"Callback Url Hangup");
					} else {
						if (isset($webhook_hangup_uuid) && $webhook_hangup_uuid != "") {
							$sql = "UPDATE v_event_domain SET callback_url ='$webhook_hangup', callback_apikey='$webhook_hangup_apikey' WHERE event_name= 'hangup' and domain_uuid='${domain_uuid}' ;";
						} else {
							$e_domain_uuid = uuid();
							$sql = "INSERT INTO v_event_domain (event_domain_uuid, domain_uuid, domain_name, event_name, callback_url, callback_apikey)  VALUES ('$e_domain_uuid', '${domain_uuid}', '${domain_name}', 'hangup', '$webhook_hangup', '$webhook_hangup_apikey');";
						}
						$db->exec(check_sql($sql));
					}
				} else {
					$sql = "delete from v_event_domain where event_name='hangup' and domain_uuid='${domain_uuid}';";
					$db->exec(check_sql($sql));
				}
				unset($sql);
				if (isset($webhook_cdr_cb) && $webhook_cdr_cb != "") {
					if (!isset($webhook_cdr) || $webhook_cdr == "" || !preg_match($pattern,$webhook_cdr)) {
						array_push($err_msg,"Callback Url Cdr");
					} else {
						if (isset($webhook_cdr_uuid) && $webhook_cdr_uuid != "") {
							$sql = "UPDATE v_event_domain SET callback_url ='$webhook_cdr', callback_apikey='$webhook_cdr_apikey' WHERE event_name= 'cdr' and domain_uuid='${domain_uuid}' ;";
						} else {
							$e_domain_uuid = uuid();
							$sql = "INSERT INTO v_event_domain (event_domain_uuid, domain_uuid, domain_name, event_name, callback_url, callback_apikey)  VALUES ('$e_domain_uuid', '${domain_uuid}', '${domain_name}', 'cdr', '$webhook_cdr', '$webhook_cdr_apikey');";
						}
						$db->exec(check_sql($sql));
					}
				} else {
					$sql = "delete from v_event_domain where event_name='cdr' and domain_uuid='${domain_uuid}';";
					$db->exec(check_sql($sql));
				}
				unset($sql);
				echo($webhook_missed_cb);
				die();
				if (isset($webhook_missed_cb) && $webhook_missed_cb != "") {
					if (!isset($webhook_missed) || $webhook_missed == "" || !preg_match($pattern,$webhook_missed)) {
						array_push($err_msg,"Callback Url Missed");
					} else {
						if (isset($webhook_cdr_uuid) && $webhook_cdr_uuid != "") {
							$sql = "UPDATE v_event_domain SET callback_url ='$webhook_missed', callback_apikey='$webhook_missed_apikey' WHERE event_name= 'missed' and domain_uuid='${domain_uuid}' ;";
						} else {
							$e_domain_uuid = uuid();
							$sql = "INSERT INTO v_event_domain (event_domain_uuid, domain_uuid, domain_name, event_name, callback_url, callback_apikey)  VALUES ('$e_domain_uuid', '${domain_uuid}', '${domain_name}', 'missed', '$webhook_missed', '$webhook_missed_apikey');";
						}
						$db->exec(check_sql($sql));
					}
				} else {
					$sql = "delete from v_event_domain where event_name='missed' and domain_uuid='${domain_uuid}';";
					$db->exec(check_sql($sql));
				}
				unset($sql);
			}  catch (Exception $err) {
				messages::add("Error : ".$err);
				//redirect the user
				header("Location: webhook_edit.php?id=${domain_uuid}");
				return;
			}
			if (!empty($err_msg)) {
				messages::add("Invalid : ".join(",",$err_msg));
				//redirect the user
				header("Location: webhook_edit.php?id=${domain_uuid}");
				return;
			}
			//add the message
			messages::add($text['message-update']);
			//redirect the user
			header("Location: webhook.php");
			return;
		} //if ($action == "add")
		if ($action == "update" && permission_exists('access_control_edit')) {
			try {
				$sql = "";
				if (isset($webhook_answered_cb) && $webhook_answered_cb != "") {
					if (!isset($webhook_answered) || $webhook_answered == "" || !preg_match($pattern,$webhook_answered)) {
						array_push($err_msg,"Callback Url Answered");
					} else {
						if (isset($webhook_answered_uuid) && $webhook_answered_uuid != "") {
							$sql = "UPDATE v_event_domain SET callback_url ='$webhook_answered', callback_apikey='$webhook_answered_apikey' WHERE event_name= 'answered' and domain_uuid='${domain_uuid}' ;";
						} else {
							$e_domain_uuid = uuid();
							$sql = "INSERT INTO v_event_domain (event_domain_uuid, domain_uuid, domain_name, event_name, callback_url, callback_apikey)  VALUES ('$e_domain_uuid', '${domain_uuid}', '${domain_name}', 'answered', '$webhook_answered', '$webhook_answered_apikey');";
						}
						$db->exec(check_sql($sql));
					}
				} else {
					$sql = "delete from v_event_domain where event_name='answered' and domain_uuid='${domain_uuid}';";
					$db->exec(check_sql($sql));
				}
				unset($sql);

				if (isset($webhook_ringing_cb) && $webhook_ringing_cb != "") {
					if (!isset($webhook_ringing) || $webhook_ringing == "" || !preg_match($pattern,$webhook_ringing)) {
						array_push($err_msg,"Callback Url Ringing");
					} else {
						if (isset($webhook_ringing_uuid) && $webhook_ringing_uuid != "") {
							$sql = "UPDATE v_event_domain SET callback_url ='$webhook_ringing', callback_apikey='$webhook_ringing_apikey' WHERE event_name= 'ringing' and domain_uuid='${domain_uuid}' ;";
						} else {
							$e_domain_uuid = uuid();
							$sql = "INSERT INTO v_event_domain (event_domain_uuid, domain_uuid, domain_name, event_name, callback_url, callback_apikey) VALUES ('$e_domain_uuid', '${domain_uuid}', '${domain_name}', 'ringing', '$webhook_ringing', '$webhook_ringing_apikey');";
						}
						$db->exec(check_sql($sql));
					}
				} else {
					$sql = "delete from v_event_domain where event_name='ringing' and domain_uuid='${domain_uuid}';";
					$db->exec(check_sql($sql));
				}
				unset($sql);
				if (isset($webhook_hangup_cb) && $webhook_hangup_cb != "") {
					if (!isset($webhook_hangup) || $webhook_hangup == "" || !preg_match($pattern,$webhook_hangup)) {
						array_push($err_msg,"Callback Url Hangup");
					} else {
						if (isset($webhook_hangup_uuid) && $webhook_hangup_uuid != "") {
							$sql = "UPDATE v_event_domain SET callback_url ='$webhook_hangup', callback_apikey='$webhook_hangup_apikey' WHERE event_name= 'hangup' and domain_uuid='${domain_uuid}' ;";
						} else {
							$e_domain_uuid = uuid();
							$sql = "INSERT INTO v_event_domain (event_domain_uuid, domain_uuid, domain_name, event_name, callback_url, callback_apikey)  VALUES ('$e_domain_uuid', '${domain_uuid}', '${domain_name}', 'hangup', '$webhook_hangup', '$webhook_hangup_apikey');";
						}
						$db->exec(check_sql($sql));
					}
				} else {
					$sql = "delete from v_event_domain where event_name='hangup' and domain_uuid='${domain_uuid}';";
					$db->exec(check_sql($sql));
				}
				unset($sql);
				if (isset($webhook_cdr_cb) && $webhook_cdr_cb != "") {
					if (!isset($webhook_cdr) || $webhook_cdr == "" || !preg_match($pattern,$webhook_cdr)) {
						array_push($err_msg,"Callback Url Cdr");
					} else {
						if (isset($webhook_cdr_uuid) && $webhook_cdr_uuid != "") {
							$sql = "UPDATE v_event_domain SET callback_url ='$webhook_cdr', callback_apikey='$webhook_cdr_apikey' WHERE event_name= 'cdr' and domain_uuid='${domain_uuid}' ;";
						} else {
							$e_domain_uuid = uuid();
							$sql = "INSERT INTO v_event_domain (event_domain_uuid, domain_uuid, domain_name, event_name, callback_url, callback_apikey)  VALUES ('$e_domain_uuid', '${domain_uuid}', '${domain_name}', 'cdr', '$webhook_cdr', '$webhook_cdr_apikey');";
						}
						$db->exec(check_sql($sql));
					}
				} else {
					$sql = "delete from v_event_domain where event_name='cdr' and domain_uuid='${domain_uuid}';";
					$db->exec(check_sql($sql));
				}
				unset($sql);
				if (isset($webhook_missed_cb) && $webhook_missed_cb != "") {
					if (!isset($webhook_missed) || $webhook_missed == "" || !preg_match($pattern,$webhook_missed)) {
						array_push($err_msg,"Callback Url Missed");
					} else {
						if (isset($webhook_missed_uuid) && $webhook_missed_uuid != "") {
							$sql = "UPDATE v_event_domain SET callback_url ='$webhook_missed', callback_apikey='$webhook_missed_apikey' WHERE event_name= 'missed' and domain_uuid='${domain_uuid}' ;";
						} else {
							$e_domain_uuid = uuid();
							$sql = "INSERT INTO v_event_domain (event_domain_uuid, domain_uuid, domain_name, event_name, callback_url, callback_apikey)  VALUES ('$e_domain_uuid', '${domain_uuid}', '${domain_name}', 'missed', '$webhook_missed', '$webhook_missed_apikey');";
						}
						$db->exec(check_sql($sql));
					}
				} else {
					$sql = "delete from v_event_domain where event_name='missed' and domain_uuid='${domain_uuid}';";
					$db->exec(check_sql($sql));
				}
				unset($sql);
			} catch (Exception $err) {
				messages::add("Error : ".$err);
				//redirect the user
				header("Location: webhook_edit.php?id=${domain_uuid}");
				return;
			}
			if (!empty($err_msg)) {
				messages::add("Invalid : ".join(",",$err_msg));
				//redirect the user
				header("Location: webhook_edit.php?id=${domain_uuid}");
				return;
			}
			//add the message
			messages::add($text['message-update']);
			//redirect the user
			header("Location: webhook.php");
			return;
		} //if ($action == "update")
	} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)
$content_string = "";
//pre-populate the form
if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
	$domain_uuid = check_str($_GET["id"]);
	$domain_name = "";
	$sql = "SELECT * FROM v_event_domain ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);

	foreach ($result as &$row) {
		if ($row["event_name"] == "ringing") {
			$webhook_ringing = $row["callback_url"];
			$webhook_ringing_uuid = $row["event_domain_uuid"];
			$webhook_ringing_apikey = $row["callback_apikey"];
			continue;
		}
		if ($row["event_name"] == "answered") {
			$webhook_answered = $row["callback_url"];
			$webhook_answered_uuid = $row["event_domain_uuid"];
			$webhook_answered_apikey = $row["callback_apikey"];
			continue;
		}
		if ($row["event_name"] == "hangup") {
			$webhook_hangup = $row["callback_url"];
			$webhook_hangup_uuid = $row["event_domain_uuid"];
			$webhook_hangup_apikey = $row["callback_apikey"];
			continue;
		}
		if ($row["event_name"] == "cdr") {
			$webhook_cdr = $row["callback_url"];
			$webhook_cdr_uuid = $row["event_domain_uuid"];
			$webhook_cdr_apikey = $row["callback_apikey"];
			continue;
		}
		if ($row["event_name"] == "missed") {
			$webhook_missed = $row["callback_url"];
			$webhook_missed_uuid = $row["event_domain_uuid"];
			$webhook_missed_apikey = $row["callback_apikey"];
			continue;
		}
	}
	$sql = "SELECT * FROM v_domains ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$domain_name = $row["domain_name"];
	}
	unset($prep_statement);
}
//show the header
require_once "resources/header.php";

//show the content
echo "<form name='frm' id='frm' method='post' action=''>\n";
echo "<table width='90%'  border='0' cellpadding='0' cellspacing='0'>\n";
echo "<tr>\n";
echo "<td align='left' width='30%' nowrap='nowrap' valign='top'><b>Event Hook</b><br><br></td>\n";
echo "<td width='70%' align='right' valign='top'>\n";
echo "	<input type='button' class='btn' name='' alt='" . $text['button-back'] . "' onclick=\"window.location='webhook.php'\" value='" . $text['button-back'] . "'>";
echo "	<input type='submit' name='submit' class='btn' value='" . $text['button-save'] . "'>";
echo "</td>\n";
echo "</tr>\n";
// 
echo "<table width='90%' style='margin:0px 20px'  border='0' cellpadding='0' cellspacing='0'>\n";
echo "<tr>\n";
// Answer
echo "<td align='left' width='10%' nowrap='nowrap' valign='top'><b>Event Ringing</b><br><br></td>\n";
echo "<td align='left' nowrap='nowrap' valign='top'><label for='webhook_ringing_cb'> <input type='checkbox' name='webhook_ringing_cb' " . ((isset($webhook_ringing_uuid) && $webhook_ringing_uuid != "") ? "checked='checked'" : null) . "/>  Enable</label> <br><br></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap' style='width:30% !important'>\n";
echo "	" . "URL" . "\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "	<input class='formfld' style='min-width:70% !important'  type='text' name='webhook_ringing' maxlength='255' value=\"" . escape($webhook_ringing) . "\">\n";
echo "<br />\n";
echo $text['description-webhook_ringing'] . "\n";
echo "</td>\n";
echo "</tr>\n";
// 
echo "</tr>\n";
echo "<tr>\n";
echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap' style='width:30% !important'>\n";
echo "	" . $text['title-webhook_ringing_apikey'] . "\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "	<input class='formfld' style='min-width:70% !important' type='text' name='webhook_ringing_apikey' maxlength='255' value=\"" . escape($webhook_ringing_apikey) . "\">\n";
echo "<br />\n";
echo $text['description-webhook_ringing_apikey'] . "\n";
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<br>\n";
// 
echo "<table width='90%' style='margin:0px 20px'  border='0' cellpadding='0' cellspacing='0'>\n";
echo "<tr>\n";

// Answer

echo "<tr style='height:10px'>\n";
echo "</tr>\n";
echo "<td align='left' width='10%' nowrap='nowrap' valign='top'><b>Event Answer</b><br><br></td>\n";
echo "<td align='left' nowrap='nowrap' valign='top'><label for='webhook_answered_cb'> <input type='checkbox' name='webhook_answered_cb' " . ((isset($webhook_answered_uuid) && $webhook_answered_uuid != "") ? "checked='checked'" : null) . "/>  Enable</label> <br><br></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap' style='width:30% !important'>\n";
echo "	" . "URL" . "\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "	<input class='formfld' style='min-width:70% !important'  type='text' name='webhook_answered' maxlength='255' value=\"" . escape($webhook_answered) . "\">\n";
echo "<br />\n";
echo $text['description-webhook_answered'] . "\n";
echo "</td>\n";
echo "</tr>\n";
// 
echo "</tr>\n";
echo "<tr>\n";
echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap' style='width:30% !important'>\n";
echo "	" . $text['title-webhook_answered_apikey'] . "\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "	<input class='formfld' style='min-width:70% !important' type='text' name='webhook_answered_apikey' maxlength='255' value=\"" . escape($webhook_answered_apikey) . "\">\n";
echo "<br />\n";
echo $text['description-webhook_answered_apikey'] . "\n";
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
// 
// Hangup
echo "<tr style='height:10px'>\n";
echo "</tr>\n";
echo "<td align='left' width='10%' nowrap='nowrap' valign='top'><b>Event Hangup</b><br><br></td>\n";
echo "<td align='left' nowrap='nowrap' valign='top'><label for='webhook_hangup_cb'> <input type='checkbox' name='webhook_hangup_cb' " . ((isset($webhook_hangup_uuid) && $webhook_hangup_uuid != "") ? "checked='checked'" : null) . "/>  Enable</label> <br><br></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap' style='width:30% !important'>\n";
echo "	" . "URL" . "\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "	<input class='formfld' style='min-width:70% !important'  type='text' name='webhook_hangup' maxlength='255' value=\"" . escape($webhook_hangup) . "\">\n";
echo "<br />\n";
echo $text['description-webhook_hangup'] . "\n";
echo "</td>\n";
echo "</tr>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap' style='width:30% !important'>\n";
echo "	" . $text['title-webhook_hangup_apikey'] . "\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "	<input class='formfld' style='min-width:70% !important' type='text' name='webhook_hangup_apikey' maxlength='255' value=\"" . escape($webhook_hangup_apikey) . "\">\n";
echo "<br />\n";
echo $text['description-webhook_hangup_apikey'] . "\n";
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
// 
echo "<table width='90%' style='margin:0px 20px'  border='0' cellpadding='0' cellspacing='0'>\n";
echo "<tr>\n";
// missed
echo "<tr style='height:10px'>\n";
echo "</tr>\n";
echo "<td align='left' width='10%' nowrap='nowrap' valign='top'><b>Event Missed</b><br><br></td>\n";
echo "<td align='left' nowrap='nowrap' valign='top'><label for='webhook_missed_cb'> <input type='checkbox' name='webhook_missed_cb' " . ((isset($webhook_missed_uuid) && $webhook_missed_uuid != "") ? " checked='checked'" : null) . "/>  Enable</label> <br><br></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap' style='width:30% !important'>\n";
echo "	" . "URL" . "\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "	<input class='formfld' style='min-width:70% !important'  type='text' name='webhook_missed' maxlength='255' value=\"" . escape($webhook_missed) . "\">\n";
echo "<br />\n";
echo $text['description-webhook_missed'] . "\n";
echo "</td>\n";
echo "</tr>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap' style='width:30% !important'>\n";
echo "	" . $text['title-webhook_missed_apikey'] . "\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "	<input class='formfld' style='min-width:70% !important' type='text' name='webhook_missed_apikey' maxlength='255' value=\"" . escape($webhook_missed_apikey) . "\">\n";
echo "<br />\n";
echo $text['description-webhook_missed_apikey'] . "\n";
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
// 
// CDR
echo "<tr style='height:10px'>\n";
echo "</tr>\n";
echo "<td align='left' width='10%' nowrap='nowrap' valign='top'><b>Event Cdr</b><br><br></td>\n";
echo "<td align='left' nowrap='nowrap' valign='top'><label for='webhook_cdr_cb'> <input type='checkbox' name='webhook_cdr_cb' " . ((isset($webhook_cdr_uuid) && $webhook_cdr_uuid != "") ? "checked='checked'" : null) . "/>  Enable</label> <br><br></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap' style='width:30% !important'>\n";
echo "	" . "URL" . "\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "	<input class='formfld' style='min-width:70% !important'  type='text' name='webhook_cdr' maxlength='255' value=\"" . escape($webhook_cdr) . "\">\n";
echo "<br />\n";
echo $text['description-webhook_cdr'] . "\n";
echo "</td>\n";
echo "</tr>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap' style='width:30% !important'>\n";
echo "	" . $text['title-webhook_cdr_apikey'] . "\n";
echo "</td>\n";
echo "<td class='vtable' align='left'>\n";
echo "	<input class='formfld' style='min-width:70% !important' type='text' name='webhook_cdr_apikey' maxlength='255' value=\"" . escape($webhook_cdr_apikey) . "\">\n";
echo "<br />\n";
echo $text['description-webhook_cdr_apikey'] . "\n";
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
// 
echo "<table width='90%' style='margin:0px 20px'  border='0' cellpadding='0' cellspacing='0'>\n";
echo "<tr>\n";

echo "<td colspan='2' align='right'>\n";
if ($action == "update") {
	echo "<input type='hidden' name='domain_uuid' value='" . escape($domain_uuid) . "'>\n";
	echo "<input type='hidden' name='webhook_ringing_uuid' value='" . escape($webhook_ringing_uuid) . "'>\n";
	echo "<input type='hidden' name='webhook_answered_uuid' value='" . escape($webhook_answered_uuid) . "'>\n";
	echo "<input type='hidden' name='webhook_hangup_uuid' value='" . escape($webhook_hangup_uuid) . "'>\n";
	echo "<input type='hidden' name='webhook_cdr_uuid' value='" . escape($webhook_cdr_uuid) . "'>\n";
	echo "<input type='hidden' name='webhook_missed_uuid' value='" . escape($webhook_missed_uuid) . "'>\n";
	echo "<input type='hidden' name='domain_name' value='" . escape($domain_name) . "'>\n";
}
echo "<br><input type='submit' name='submit' class='btn' value='" . $text['button-save'] . "'>\n";
echo "</td>\n";
echo "	</tr>";
echo "</table>";
echo "</form>";
echo "<br /><br />";

//include the footer
require_once "resources/footer.php";
