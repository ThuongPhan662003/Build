<?php
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if ((if_group("superadmin") || if_level("admin"))) {
    // access granted
} else {
    echo "access denied";
    exit;
}

// add multi-lingual support
$language = new text;
$text = $language->get();

// set the action
$action = isset($_GET['id']) ? "update" : "add";
$id = isset($_GET['id']) ? check_str($_GET['id']) : null;

// get http values and set them as php variables
if (count($_POST) > 0) {
    // $domain_uuid = check_str($_POST['domain_uuid']);
    // $domain_name = check_str($_POST['domain_name']);
	$domain_uuid = $_SESSION["domain_uuid"];
	$domain_name = $_SESSION["domain_name"];
    $name = check_str($_POST['name']);
    $protocol = check_str($_POST['protocol']);
    $storage_provider = check_str($_POST['storage_provider']);
    $access_key = check_str($_POST['access_key']);
    $secret_key = check_str($_POST['secret_key']);
    $bucket_name = check_str($_POST['bucket_name']);
    $region = check_str($_POST['region']);
    $endpoint_url = check_str($_POST['endpoint_url']);
    $encryption_enabled = isset($_POST['encryption_enabled']) ? 'true' : 'false';
    $storage_class = check_str($_POST['storage_class']);
    $max_retention_days = check_str($_POST['max_retention_days']);
    $is_active = isset($_POST['is_active']) ? 'true' : 'false';
    $status = check_str($_POST['status']);
}

// validate and insert/update the database
if ($_POST && strlen($_POST["persistformvar"]) == 0) {
    $msg = '';
    if (strlen($name) == 0) { $msg .= "Please provide: Name<br>\n"; }
    if (strlen($protocol) == 0) { $msg .= "Please provide: Protocol<br>\n"; }
    if (strlen($storage_provider) == 0) { $msg .= "Please provide: Storage Provider<br>\n"; }
    
    if (strlen($msg) > 0) {
        require_once "resources/header.php";
        require_once "resources/persist_form_var.php";
        echo "<div align='center'>\n";
        echo "<table><tr><td>\n";
        echo $msg."<br />";
        echo "</td></tr></table>\n";
        persistformvar($_POST);
        echo "</div>\n";
        require_once "resources/footer.php";
        return;
    }

    if ($action == "add" && (if_group("superadmin") || if_level("admin"))) {
        $sql = "INSERT INTO storage_settings (domain_uuid, domain_name, name, protocol, storage_provider, access_key, secret_key, bucket_name, region, endpoint_url, encryption_enabled, storage_class, max_retention_days, is_active, status) ";
        $sql .= "VALUES ('$domain_uuid', '$domain_name', '$name', '$protocol', '$storage_provider', '$access_key', '$secret_key', '$bucket_name', '$region', '$endpoint_url', '$encryption_enabled', '$storage_class', '$max_retention_days', '$is_active', '$status')";
        $db->exec($sql);
        header("Location: storage_settings.php");
    }

    if ($action == "update" && (if_group("superadmin") || if_level("admin"))) {
        $sql = "UPDATE storage_settings SET ";
        $sql .= "domain_uuid = '$domain_uuid', ";
        $sql .= "domain_name = '$domain_name', ";
        $sql .= "name = '$name', ";
        $sql .= "protocol = '$protocol', ";
        $sql .= "storage_provider = '$storage_provider', ";
        $sql .= "access_key = '$access_key', ";
        $sql .= "secret_key = '$secret_key', ";
        $sql .= "bucket_name = '$bucket_name', ";
        $sql .= "region = '$region', ";
        $sql .= "endpoint_url = '$endpoint_url', ";
        $sql .= "encryption_enabled = '$encryption_enabled', ";
        $sql .= "storage_class = '$storage_class', ";
        $sql .= "max_retention_days = '$max_retention_days', ";
        $sql .= "is_active = '$is_active', ";
        $sql .= "status = '$status' ";
        $sql .= "WHERE id = '$id'";
        
        $db->exec($sql);
        
        // if is_active, deactive others
        $sql = "UPDATE storage_settings SET ";
        $sql .= "is_active = FALSE ";
        $sql .= "WHERE domain_uuid = '$domain_uuid' AND is_active = TRUE AND id <> '$id'";
        $db->exec($sql);

        header("Location: storage_settings.php");
    }
}

// fetch existing data for update
if ($action == "update" && isset($id)) {
    $sql = "SELECT * FROM storage_settings WHERE id = '$id'";
    $prep_statement = $db->prepare($sql);
    $prep_statement->execute();
    $row = $prep_statement->fetch(PDO::FETCH_ASSOC);
    foreach ($row as $key => $value) {
        $$key = $value;
    }
    unset($prep_statement);
}

// show form
require_once "resources/header.php";
?>

<form method='post' name='frm' action=''>
    <table width='100%' border='0' cellpadding='0' cellspacing='0'>
        <tr>
            <td align='left' width='30%' nowrap><b><?php echo $action == "add" ? $text['title-storage-settings-add'] : $text['title-storage-settings-update']; ?></b><br><br></td>
            <td width='70%' align='right'><input type='submit' name='submit' class='btn' value='<?php echo $text['button-save']; ?>'></td>
        </tr>
        
        <!-- <tr>
            <td class='vncell' valign='top'>Domain UUID</td>
            <td class='vtable'><input class='formfld' type='text' name='domain_uuid' value="<?php echo escape($domain_uuid); ?>"></td>
        </tr>
        <tr>
            <td class='vncell' valign='top'>Domain Name</td>
            <td class='vtable'><input class='formfld' type='text' name='domain_name' value="<?php echo escape($domain_name); ?>"></td>
        </tr> -->
        <tr>
            <td class='vncellreq' valign='top'>Name</td>
            <td class='vtable'><input class='formfld' type='text' name='name' value="<?php echo escape($name); ?>"></td>
        </tr>
        <tr>
            <td class='vncell' valign='top'>Protocol</td>
            <td class='vtable'>
                <select class='formfld' name='protocol'>
                    <option value="sftp" <?php echo ($protocol == 'sftp') ? 'selected' : ''; ?>>SFTP</option>
                    <option value="ftp" <?php echo ($protocol == 'ftp') ? 'selected' : ''; ?>>FTP</option>
                    <option value="s3" <?php echo ($protocol == 's3') ? 'selected' : ''; ?>>S3</option>
                </select>
            </td>
        </tr>
        <tr>
            <td class='vncell' valign='top'>Storage Provider</td>
            <td class='vtable'>
                <select class='formfld' name='storage_provider'>
                    <option value="AWS S3" <?php echo ($storage_provider == 'AWS S3') ? 'selected' : ''; ?>>AWS S3</option>
                    <option value="MinIO" <?php echo ($storage_provider == 'MinIO') ? 'selected' : ''; ?>>MinIO</option>
                </select>
            </td>
        </tr>
        <tr>
            <td class='vncellreq' valign='top'>Access Key</td>
            <td class='vtable'><input class='formfld' type='text' name='access_key' value="<?php echo escape($access_key); ?>"></td>
        </tr>
        <tr>
            <td class='vncellreq' valign='top'>Secret Key</td>
            <td class='vtable'><input class='formfld' type='text' name='secret_key' value="<?php echo escape($secret_key); ?>"></td>
        </tr>
        <tr>
            <td class='vncell' valign='top'>Bucket Name</td>
            <td class='vtable'><input class='formfld' type='text' name='bucket_name' value="<?php echo escape($bucket_name); ?>"></td>
        </tr>
        <tr>
            <td class='vncell' valign='top'>Region</td>
            <td class='vtable'>
                <select class='formfld' name='region'>
                    <option value="us-east-1" <?php echo ($region == 'us-east-1') ? 'selected' : ''; ?>>US East (N. Virginia)</option>
                    <option value="us-west-1" <?php echo ($region == 'us-west-1') ? 'selected' : ''; ?>>US West (N. California)</option>
                    <option value="us-west-2" <?php echo ($region == 'us-west-2') ? 'selected' : ''; ?>>US West (Oregon)</option>
                    <option value="eu-west-1" <?php echo ($region == 'eu-west-1') ? 'selected' : ''; ?>>EU (Ireland)</option>
                    <option value="eu-central-1" <?php echo ($region == 'eu-central-1') ? 'selected' : ''; ?>>EU (Frankfurt)</option>
                    <option value="ap-southeast-1" <?php echo ($region == 'ap-southeast-1') ? 'selected' : ''; ?>>Asia Pacific (Singapore)</option>
                    <option value="ap-southeast-2" <?php echo ($region == 'ap-southeast-2') ? 'selected' : ''; ?>>Asia Pacific (Sydney)</option>
                    <option value="ap-northeast-1" <?php echo ($region == 'ap-northeast-1') ? 'selected' : ''; ?>>Asia Pacific (Tokyo)</option>
                    <option value="sa-east-1" <?php echo ($region == 'sa-east-1') ? 'selected' : ''; ?>>South America (São Paulo)</option>
                </select>
            </td>
        </tr>
        <tr>
            <td class='vncell' valign='top'>Endpoint URL</td>
            <td class='vtable'><input class='formfld' type='text' name='endpoint_url' value="<?php echo escape($endpoint_url); ?>"></td>
        </tr>
        <tr>
            <td class='vncell' valign='top'>Encryption Enabled</td>
            <td class='vtable'><input class='formfld' type='checkbox' name='encryption_enabled' <?php echo ($encryption_enabled == 'true') ? 'checked' : ''; ?>></td>
        </tr>
        <tr>
            <td class='vncell' valign='top'>Storage Class</td>
            <td class='vtable'>
                <select class='formfld' name='s3-storage-class'>
                    <option value="">None</option>
                    <option value="standard" <?php echo ($storage_class == 'standard') ? 'selected' : ''; ?>>S3 Standard</option>
                    <option value="standard-ia" <?php echo ($storage_class == 'standard-ia') ? 'selected' : ''; ?>>S3 Standard-IA</option>
                    <option value="onezone-ia" <?php echo ($storage_class == 'onezone-ia') ? 'selected' : ''; ?>>S3 One Zone-IA</option>
                    <option value="intelligent-tiering" <?php echo ($storage_class == 'intelligent-tiering') ? 'selected' : ''; ?>>S3 Intelligent-Tiering</option>
                    <option value="glacier-instant-retrieval" <?php echo ($storage_class == 'glacier-instant-retrieval') ? 'selected' : ''; ?>>S3 Glacier Instant Retrieval</option>
                    <option value="glacier-flexible-retrieval" <?php echo ($storage_class == 'glacier-flexible-retrieval') ? 'selected' : ''; ?>>S3 Glacier Flexible Retrieval</option>
                    <option value="glacier-deep-archive" <?php echo ($storage_class == 'glacier-deep-archive') ? 'selected' : ''; ?>>S3 Glacier Deep Archive</option>
                    <option value="express-onezone" <?php echo ($storage_class == 'express-onezone') ? 'selected' : ''; ?>>S3 Express One Zone</option>
                </select>
            </td>
        </tr>
        <tr>
            <td class='vncell' valign='top'>Max Retention Days</td>
            <td class='vtable'><input class='formfld' type='number' name='max_retention_days' value="<?php echo escape($max_retention_days); ?>" min="-1" max="180"></td>
        </tr>
        <tr>
            <td class='vncell' valign='top'>Status</td>
            <td class='vtable'><input class='formfld' type='text' name='status' value="<?php echo escape($status); ?>" readonly></td>
        </tr>
        <tr>
            <td class='vncell' valign='top'>Active</td>
            <td class='vtable'><input class='formfld' type='checkbox' name='is_active' <?php echo ($is_active == 'true') ? 'checked' : ''; ?>></td>
        </tr>
        <tr>
            <td colspan='2' align='right'><br><input type='submit' name='submit' class='btn' value='<?php echo $text['button-save']; ?>'></td>
        </tr>
    </table>
</form>

<?php
// show footer
require_once "resources/footer.php";
?>
