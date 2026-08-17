<?php

session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $sessionStaffID = $_SESSION['staffID'] ?? null;
    $currentEmail   = $_SESSION['email'] ?? null;
    $newEmail = trim($_POST['email'] ?? '');
    $fname    = trim($_POST['fname'] ?? '');
    $lname    = trim($_POST['lname'] ?? '');
    $dob      = trim($_POST['dob'] ?? '');
    $rawPhone = trim($_POST['ph'] ?? '');
    $phone    = '+61-' . str_replace('+61-', '', $rawPhone);
    $address = trim($_POST['addy'] ?? '');
    $oldPassword     = $_POST['password'] ?? null;
    $newPassword     = $_POST['new-password'] ?? null;
    $confirmPassword = $_POST['confirm-password'] ?? null;
    $profilePictureData = null;

    if (
        isset($_FILES['profile_picture'])
        && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK
    ) {
        $file = $_FILES['profile_picture'];

        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file($file['tmp_name']);

        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        if (in_array($mimeType, $allowedTypes)) {
            $imageData = file_get_contents($file['tmp_name']);


            if ($imageData !== false) {
                $profilePictureData =
                    'data:'
                    . $mimeType
                    . ';base64,'
                    . base64_encode($imageData);
            }
        }
    }

    if ($sessionStaffID) {
        $whereClause = "WHERE staffID = ?";
        $identifier  = $sessionStaffID;
    } else {
        $whereClause = "WHERE email = ?";
        $identifier  = $currentEmail;
    }

    if ($profilePictureData !== null) {
        $sql = "
            UPDATE staff
            SET
                email = ?,
                fname = ?,
                lname = ?,
                dob = ?,
                phone = ?,
                address = ?,
                profile_picture = ?
            $whereClause
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ssssssss",
            $newEmail,
            $fname,
            $lname,
            $dob,
            $phone,
            $address,
            $profilePictureData,
            $identifier
        );
    } else {
        $sql = "
            UPDATE staff
            SET
                email = ?,
                fname = ?,
                lname = ?,
                dob = ?,
                phone = ?,
                address = ?
            $whereClause
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "sssssss",
            $newEmail,
            $fname,
            $lname,
            $dob,
            $phone,
            $address,
            $identifier
        );
    }

    if ($stmt->execute()) {
        $_SESSION['email'] = $newEmail;

        $_SESSION['message'] = "Your account has been updated successfully!";

        if (
            !empty($oldPassword)
            && !empty($newPassword)
            && !empty($confirmPassword)
        ) {
            $sql_pw_check = "
                SELECT password_hash
                FROM staff
                WHERE email = ?
            ";

            $stmt_pw_check = $conn->prepare($sql_pw_check);

            $stmt_pw_check->bind_param(
                "s",
                $newEmail
            );

            $stmt_pw_check->execute();

            $stmt_pw_check->bind_result(
                $currentPasswordHash
            );

            $stmt_pw_check->fetch();
            $stmt_pw_check->close();

            if (password_verify($oldPassword, $currentPasswordHash)) {
                if ($newPassword === $confirmPassword) {
                    $hashedNewPassword = password_hash(
                        $newPassword,
                        PASSWORD_DEFAULT
                    );


                    $sql_update_pw = "
                        UPDATE staff
                        SET password_hash = ?
                        WHERE email = ?
                    ";

                    $stmt_update_pw = $conn->prepare($sql_update_pw);

                    $stmt_update_pw->bind_param(
                        "ss",
                        $hashedNewPassword,
                        $newEmail
                    );

                    if ($stmt_update_pw->execute()) {
                        $_SESSION['message'] .=
                            " Your password has been updated successfully!";
                    } else {
                        $_SESSION['message'] .=
                            " Error updating password.";
                    }

                    $stmt_update_pw->close();
                } else {
                    $_SESSION['message'] .=
                        " New passwords do not match.";
                }
            } else {
                $_SESSION['message'] =
                    "Incorrect old password. Account details updated, but password was not changed.";
            }
        }
    } else {
        $_SESSION['message'] =
            "Error updating account: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: ../account.php");
    exit();
}

?>