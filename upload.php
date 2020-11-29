<?php
session_start();
if (!isset($_SESSION['previous_upload'])) {
    $_SESSION['previous_upload'] = date('mm-dd-YYYY');
    $_SESSION['accumulated_size'] = 0;
}

function report_error(string $msg): string {
    return json_encode(array("error" => true, "msg" => $msg));
}
function report_success(string $file): string {
    return json_encode(array("error" => false, "file" => $file));
}

if (
    !isset($_FILES['images']['error']) ||
    is_array($_FILES['images']['error'])
) {
    echo report_error('Invalid parameters.');
} else {
    // Check $_FILES['images']['error'] value.
    if ($_FILES['images']['error'] === UPLOAD_ERR_OK) {
        // You should also check filesize here.
        if ($_FILES['images']['size'] > 4 * 1024 * 1024) {
            echo report_error('Exceeded filesize limit.');
        } else {
            $today = date('mm-dd-YYYY');
            if ($today !== $_SESSION['previous_upload']) {
                $_SESSION['accumulated_size'] = 0;
            }
            if ($today === $_SESSION['previous_upload'] &&
                $_SESSION['accumulated_size'] + $_FILES['images']['size'] > 200 * 1024 * 1024) {
                echo report_error('Exceeded daily limit.');
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                if (false === $ext = array_search(
                        $finfo->file($_FILES['images']['tmp_name']),
                        array(
                            'jpg' => 'image/jpeg',
                            'png' => 'image/png',
                            'gif' => 'image/gif',
                            'webp' => 'image/webp',
                        ), true
                    )) {
                    echo report_error('Invalid file format?.');
                } else {
                    $to_sha1 = sprintf("%s_%d", @sha1_file($_FILES['images']['tmp_name']), time());
                    $file_sha1 = sha1($to_sha1);
                    $file_save_to = sprintf('./uploads/%s.%s',
                        $file_sha1,
                        $ext
                    );
                    if (!move_uploaded_file(
                        $_FILES['images']['tmp_name'],
                        $file_save_to
                    )) {
                        echo report_error('Failed to move uploaded file.');
                    } else {
                        require_once "imgdao.php";
                        $img_db = new ImageObject();
                        $file_sha1_bin = sha1($to_sha1, true);
                        $uuid = substr(sha1($file_sha1_bin), 0, 14);
                        $result = $img_db->saveToDatabase($uuid, $file_save_to);
                        if (is_null($result)) {
                            echo report_success($uuid);
                            $_SESSION['previous_upload'] = $today;
                            $_SESSION['accumulated_size'] += $_FILES['images']['size'];
                        } else {
                            echo report_error($result);
                        }
                    }
                }
            }
        }
    } else {
        switch ($_FILES['images']['error']) {
            case UPLOAD_ERR_NO_FILE:
                echo report_error('No file sent.');
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                echo report_error('Exceeded filesize limit.');
                break;
            default:
                echo report_error('Unknown errors.');
                break;
        }
    }
}

