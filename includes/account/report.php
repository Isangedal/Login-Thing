<?php

session_start();

require_once "../other/functions.php";
require_once "../other/dbh.php";

if (!isset($_SESSION["rank"]) || !isset($_POST["submit"]) || !$settings->enable_report) {
    header("location: ../../report");
    exit();
}

$reason = "None";

switch ($_POST["reason"]) {
    case 0:
        $reason = "Username";
        break;
    case 1:
        $reason = "Harassment";
        break;
    case 2:
        $reason = "Impersonation";
        break;
    case 3:
        $reason = "Threats";
        break;
    case 4:
        $reason = "Spam";
        break;
    case 5:
        $reason = "Scam";
        break;
    case 9:
        $reason = "Other";
        break;
    default:
        $reason = "None";
        break;
}


if (getTable($conn, "users", ["uid", $_POST["user"]]) != null) {
    $target = getTable($conn, "users", ["uid", $_POST["user"]]);
    
    $thing = getTable($conn, "reports", ["reporter", $_SESSION["id"]]);

    if ($thing != null && $thing["reason"] == $reason) {
        header("location: ../../appeal?error=duplicateindb");
        exit();
    }

    insertTable($conn, "reports", ["reporter" => $_SESSION["id"], "target" => $target["id"], "reason" => $reason, "otherreason" => $_POST["otherreason"]]);
    header("location: ../../report?error=none");
} else {
    header("location: ../../report?error=usernotfound");
    exit();
}