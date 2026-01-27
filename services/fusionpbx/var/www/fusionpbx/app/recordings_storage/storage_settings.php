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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http get values and set them as php variables
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//check the permission
	if ((if_group("superadmin") || if_level("admin"))) {
		// access granted
	} else {
		var_dump($_SESSION["user"]);
		echo if_level("admin");
		echo "access denied";
		exit;
	}

//add paging
	require_once "resources/paging.php";

//include the header
	require_once "resources/header.php";
	$document['title'] = $text['title-storage-settings'];

//begin the content
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td align='left'>\n";
	echo "			<b>".$text['header_storage_settings']."</b>\n";
	echo "			<br /><br />\n";
	echo "			".$text['description-storage-settings']."\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>";
	echo "<br />\n";

	$sql = "select count(*) as num_rows from storage_settings ";
	$sql .= "where ( ";
	$sql .= " domain_uuid = '".$domain_uuid."'  ";
	$sql .= " or domain_uuid is null ";
	$sql .= ") ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$num_rows = count($result);
	unset ($prep_statement, $result, $sql);

	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "";
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

	$sql = "select * from storage_settings ";
	$sql .= "where ( ";
	$sql .= " domain_uuid = '".$domain_uuid."'  ";
	$sql .= " or domain_uuid is null ";
	$sql .= ") ";
	$sql .= "order by ".((strlen($order_by) > 0) ? $order_by." ".$order." " : "name asc ");
	$sql .= "limit ".$rows_per_page." offset ".$offset." ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<th>No.</th>\n";
	echo th_order_by('name', $text['label-name'], $order_by, $order);
	echo th_order_by('protocol', $text['label-protocol'], $order_by, $order);
	echo th_order_by('storage_provider', $text['label-storage-provider'], $order_by, $order);
	echo th_order_by('bucket_name', $text['label-bucket-name'], $order_by, $order);
	echo th_order_by('access_key', $text['label-access-key'], $order_by, $order);
	echo th_order_by('max_retention_days', $text['label-max-retention-days'], $order_by, $order);
	echo th_order_by('status', $text['label-status'], $order_by, $order);
	echo th_order_by('is_active', $text['label-is-active'], $order_by, $order);
	echo "<td class='list_control_icons'>";
	if (if_group("superadmin") || if_level("admin")) {
		echo "<a href='storage_settings_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
	}
	echo "</td>\n";
	echo "</tr>\n";

	if ($result_count > 0) {
		$i = 1 + ($page * $rows_per_page);
		foreach($result as $row) {
			$tr_link = (permission_exists('storage_settings_edit')) ? "href='storage_settings_edit.php?id=".$row['id']."'" : null;
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$i++."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['name'])."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['protocol'])."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['storage_provider'])."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['bucket_name'])."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			echo "		<span id='access_key_short_".$row['id']."'>".substr(escape($row['access_key']), 0, 10)."******</span>";
			echo "		<span id='access_key_full_".$row['id']."' style='display:none;'>".escape($row['access_key'])."</span>";
			echo "		<button onclick=\"toggleAccessKey('".$row['id']."')\">".$text['button-show']."</button>";
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['max_retention_days'])."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['status'])."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['is_active'] ? '✔' : '')."</td>\n";
			echo "	<td valign='top' align='right' class='tr_link_void'>";
			if (if_group("superadmin") || if_level("admin")) {
				echo "<a href='storage_settings_edit.php?id=".escape($row['id'])."' alt='".$text['button-edit']."'>".$v_link_label_edit."</a>";
			}
			if (if_group("superadmin") || if_level("admin")) {
				echo "<a href='storage_settings_delete.php?id=".escape($row['id'])."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";

			$c = ($c==0) ? 1 : 0;
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "</table>\n";

	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>".$paging_controls."</td>\n";
	echo "		<td class='list_control_icons'>";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	echo "<br />\n";

//include the footer
	require_once "resources/footer.php";

// Javascript function to toggle access key visibility
?>
<script type="text/javascript">
	function toggleAccessKey(id) {
		var short_key = document.getElementById('access_key_short_' + id);
		var full_key = document.getElementById('access_key_full_' + id);
		if (short_key.style.display === 'none') {
			short_key.style.display = 'inline';
			full_key.style.display = 'none';
		} else {
			short_key.style.display = 'none';
			full_key.style.display = 'inline';
		}
	}
</script>
