<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <?php
        $_GET["webname"] = basename(__FILE__, '.php');
        include_once 'header.php';

        if (!isset($_SESSION["rank"])) {
            header("location: .?error=nologin");
            exit();
        }

        if ($_SESSION["rank"] < 1) {
            header("location: .?error=permsreq");
            exit();
        }

        echo "<h1>Moderation Tools</h1>";

        echo "<p>You are currently " . (strpos("aeiou", strtolower(rankFromNum($_SESSION["rank"])[0])) !== false ? "an" : "a") . " " . rankFromNum($_SESSION["rank"]) . ".</p></br>";

        $usernameValue = (isset($_GET["u"]) ? $_GET["u"] : "");
        $selectedValue = (isset($_GET["sel"]) ? $_GET["sel"] : "");

        if ($_SESSION["rank"] == 1) {

            echo "<h1>Mod Panel</h1>";

            if ($settings->enable_mod_panel) {

                ?>

                    <form action="includes/staff/mod" method="post">
                        <input type="text" name="username" placeholder="Username..." value="<?= $usernameValue ?>"></br></br>
                        <select name="action" size="6">
                            <option value="0" <?php if ($selectedValue == "user"): ?> selected="selected" <?php endif; ?> >Set User</option>
                            <option value="1" <?php if ($selectedValue == "mod"): ?> selected="selected" <?php endif; ?> >Set Moderator</option>
                            <option value='2' <?php if ($selectedValue == "admin"): ?> selected="selected" <?php endif; ?> >Set Admin</option>
                            <option value='3' <?php if ($selectedValue == "delc"): ?> selected="selected" <?php endif; ?> >Delete Comment</option>
                            <option value='4' <?php if ($selectedValue == "unm"): ?> selected="selected" <?php endif; ?> >(Un)Mute</option>
                            <option value="-1" <?php if ($selectedValue == "unb"): ?> selected="selected" <?php endif; ?> >(Un)Ban</option>
                        </select></br></br>
                        <button type="submit" name="submit" class="button">Confirm</button>
                    </form>

                <?php

            } else {
                echo "<p>Mod Panel is temporarily disabled.</p>";
            }

        }
        elseif ($_SESSION["rank"] >= 2) {
            
            if (isset($_GET["suggestions"])) {
                
                echo "<h1>Suggestions</h1>";

                if (!$settings->enable_suggestions) {
                    echo "<p>Suggestions are temporarily disabled.</p>";
                    exit();
                }

                echo "<p>Click a suggestion to view it</p>";

                $result = getTable($conn, "modsuggestions");

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        if (isset($_GET["id"])) {
                            if ($row["id"] == $_GET["id"]) {

                                if ($row["type"] == "DeleteComment") {
                                    $msg = getTable($conn, "messages", ["id", $row["targetsUid"]]);
                                    echo "<h4><a href=\".#" . $row["id"] . "\" target=\"_blank\" style=\"text-decoration: none; color: green;\">Comment</a> - Type: Delete Comment - Suggester: <a href=\"user?u=" . $row["suggester"] . "\" target=\"_blank\" style=\"text-decoration: none; color: green;\">" . getTable($conn, "users", ["id", $row["suggester"]])["uid"] . "</a></h4>";
                                    echo "<a class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\" href='includes/staff/approveSuggestion?uid=" . $row["targetsUid"] . "&id=" . $row["id"] . "&type=DeleteComment'>Accept</a> <a class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\" href='includes/staff/refuseSuggestion?uid=" . $row["targetsUid"] . "&id=" . $row["id"] . "&type=DeleteComment'>Deny</a>";
                                }
                                elseif ($row["type"] == "(Un)Mute") {
                                    echo "<h4>Username: <a href=\"user?u=" . getTable($conn, "users", ["uid", $row["targetsUid"]])["id"] . "\" target=\"_blank\" style=\"text-decoration: none; color: green;\">" . $row["targetsUid"] . "</a> - Type: " . $row["type"] . " - Suggester: <a href=\"user?u=" . $row["suggester"] . "\" target=\"_blank\" style=\"text-decoration: none; color: green;\">" . getTable($conn, "users", ["id", $row["suggester"]])["uid"] . "</a></h4><br>";
                                    echo "<a class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\" href='includes/staff/approveSuggestion?uid=" . $row["targetsUid"] . "&id=" . $row["id"] . "&type=" . $row["type"] . "'>Accept</a> <a class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\" href='includes/staff/refuseSuggestion?uid=" . $row["targetsUid"] . "&id=" . $row["id"] . "&type=" . $row["type"] . "'>Deny</a>";
                                }
                                elseif ($row["type"] == "-1") {
                                    echo "<h4>Username: <a href=\"user?u=" . getTable($conn, "users", ["uid", $row["targetsUid"]])["id"] . "\" target=\"_blank\" style=\"text-decoration: none; color: green;\">" . $row["targetsUid"] . "</a> - Type: (Un)Ban - Suggester: <a href=\"user?u=" . $row["suggester"] . "\" target=\"_blank\" style=\"text-decoration: none; color: green;\">" . getTable($conn, "users", ["id", $row["suggester"]])["uid"] . "</a></h4><br>";
                                    echo "<a class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\" href='includes/staff/approveSuggestion?uid=" . $row["targetsUid"] . "&id=" . $row["id"] . "&type=" . $row["type"] . "'>Accept</a> <a class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\" href='includes/staff/refuseSuggestion?uid=" . $row["targetsUid"] . "&id=" . $row["id"] . "&type=" . $row["type"] . "'>Deny</a>";
                                }
                                else {
                                    echo "<h4>Username: <a href=\"user?u=" . getTable($conn, "users", ["uid", $row["targetsUid"]])["id"] . "\" target=\"_blank\" style=\"text-decoration: none; color: green;\">" . $row["targetsUid"] . "</a> - Type: " . rankFromNum($row["type"]) . " - Suggester: <a href=\"user?u=" . $row["suggester"] . "\" target=\"_blank\" style=\"text-decoration: none; color: green;\">" . getTable($conn, "users", ["id", $row["suggester"]])["uid"] . "</a></h4><br>";
                                    echo "<a class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\" href='includes/staff/approveSuggestion?uid=" . $row["targetsUid"] . "&id=" . $row["id"] . "&type=" . $row["type"] . "'>Accept</a> <a class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\" href='includes/staff/refuseSuggestion?uid=" . $row["targetsUid"] . "&id=" . $row["id"] . "&type=" . $row["type"] . "'>Deny</a>";
                                }

                                exit();

                            }
                            
                        } else {
                            if ($row["type"] == "DeleteComment") {
                                $msg = getTable($conn, "messages", ["id", $row["targetsUid"]]);
                                echo "<h4><a href=\".#" . $row["id"] . "\" target=\"_blank\" style=\"text-decoration: none; color: green;\">Comment</a><a style='color: black;text-decoration:none;' href='?suggestions&id=" . $row["id"] . "'> - Type: Delete Comment - Suggester: <a href=\"user?u=" . $row["suggester"] . "\" target=\"_blank\" style=\"text-decoration: none; color: green;\">" . getTable($conn, "users", ["id", $row["suggester"]])["uid"] . "</a></a></h4><br>";

                            }
                            elseif ($row["type"] == "(Un)Mute") {
                                echo "<h4>Username: <a href=\"user?u=" . getTable($conn, "users", ["uid", $row["targetsUid"]])["id"] . "\" target=\"_blank\" style=\"text-decoration: none; color: green;\">" . $row["targetsUid"] . "</a> - Type: " . $row["type"] . " - Suggester: <a href=\"user?u=" . $row["suggester"] . "\" target=\"_blank\" style=\"text-decoration: none; color: green;\">" . getTable($conn, "users", ["id", $row["suggester"]])["uid"] . "</a></h4><br>";
                                echo "<a class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\" href='includes/staff/approveSuggestion?uid=" . $row["targetsUid"] . "&id=" . $row["id"] . "&type=" . $row["type"] . "'>Accept</a> <a class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\" href='includes/staff/refuseSuggestion?uid=" . $row["targetsUid"] . "&id=" . $row["id"] . "&type=" . $row["type"] . "'>Deny</a>";
                            }
                            else {
                                echo "<h4><a style='color: black;text-decoration:none;' href='?suggestions&id=" . $row["id"]. "'>Username: <a href=\"user?u=" . getTable($conn, "users", ["uid", $row["targetsUid"]])["id"] . "\" target=\"_blank\" style=\"text-decoration: none; color: green;\">" . $row["targetsUid"] . "</a><a style='color: black;text-decoration:none;' href='?suggestions&id=" . $row["id"]. "'> - Type: " . rankFromNum($row["type"]) . " - Suggester: <a href=\"user?u=" . $row["suggester"] . "\" target=\"_blank\" style=\"text-decoration: none; color: green;\">" . getTable($conn, "users", ["id", $row["suggester"]])["uid"] . "</a></a></h4><br>";
                            }
                        }

                    }
                } else {
                    echo "<h4>0 suggestions</h4>";
                }

                exit();
                
            }
            elseif (isset($_GET["reports"])) {
    
                if (!$settings->enable_report) {
                    echo "<p>Reporting is temporarily disabled.</p>";
                    exit();
                }
    
                echo "<h1>Reports</h1>";
                echo "<p>Click a report to view it</p>";
    
                $result = getTable($conn, "reports");
    
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $details = ($row["otherreason"] != '') ? " - Details: " . $row["otherreason"] : '';

                        if (isset($_GET["id"])) {
                            if ($row["id"] == $_GET["id"]) {
    
                                echo "<h4>Username: <a href=\"user?u=" . $row["target"] . "\" target=\"_blank\" style=\"text-decoration: none; color: green;\">" . getTable($conn, "users", ["id", $row["target"]])["uid"] . "</a> - Reason: " . $row["reason"] . " - Reporter: <a href=\"user?u=" . $row["reporter"] . "\" target=\"_blank\" style=\"text-decoration: none; color: green;\">" . getTable($conn, "users", ["id", $row["reporter"]])["uid"] . "</a> " . $details . " </h4><br>";
                                echo "<a class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\" href='includes/staff/approveReport?target=" . $row["target"] . "&id=" . $row["id"] . "&reason=" . $row["reason"] . "&action=-1" . "'>Ban</a> ";
                                echo "<a class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\" href='includes/staff/approveReport?target=" . $row["target"] . "&id=" . $row["id"] . "&reason=" . $row["reason"] . "&action=4" . "'>Mute</a> ";
                                echo "<a class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\" href='includes/staff/refuseReport?target=" . $row["target"] . "&id=" . $row["id"] . "&reason=" . $row["reason"] . "'>Ignore</a> ";
                                exit();
    
                            }
                            
                        } else {
                            echo "<h4><a style='color: black;text-decoration:none;' href='?reports&id=" . $row["id"]. "'>Username: <a href=\"user?u=" . $row["target"] . "\" target=\"_blank\" style=\"text-decoration: none; color: green;\">" . getTable($conn, "users", ["id", $row["target"]])["uid"] . "</a><a style='color: black;text-decoration:none;' href='?reports&id=" . $row["id"] . "'> - Reason: " . $row["reason"] . " - Reporter: <a href=\"user?u=" . $row["reporter"] . "\" target=\"_blank\" style=\"text-decoration: none; color: green;\">" . getTable($conn, "users", ["id", $row["reporter"]])["uid"] . "</a> <a style='color: black;text-decoration:none;' href='?reports&id=" . $row["id"]. "'>" . $details . " </a></h4><br>";
                        }
    
                    }
                } else {
                    echo "<h4>0 reports</h4>";
                }
    
                exit();
            } elseif (isset($_GET["appeals"])) {
                if (!$settings->enable_appeal) {
                    echo "<p>Appeals are temporarily disabled.</p>";
                    exit();
                }
    
                echo "<h1>Appeals</h1>";
                echo "<p>Click an appeal to view it</p>";
    
                $result = getTable($conn, "appeals");
    
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $details = ($row["otherreason"] != '') ? " - Details: " . $row["otherreason"] : '';

                        if (isset($_GET["id"])) {
                            if ($row["id"] == $_GET["id"]) {
    
                                echo "<h4>Username: <a href=\"user?u=" . $row["appealer"] . "\" target=\"_blank\" style=\"text-decoration: none; color: green;\">" . getTable($conn, "users", ["id", $row["appealer"]])["uid"] . "</a> - Reason: " . $row["reason"] . "</a> " . $details . " </h4><br>";
                                
                                if ($row["punishment"] == "0") {
                                    echo "<a class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\" href='includes/staff/approveAppeal?target=" . $row["appealer"] . "&id=" . $row["id"] . "&reason=" . $row["reason"] . "&action=-1" . "'>Unban</a> ";
                                } elseif ($row["punishment"] == "1") {
                                    echo "<a class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\" href='includes/staff/approveAppeal?target=" . $row["appealer"] . "&id=" . $row["id"] . "&reason=" . $row["reason"] . "&action=4" . "'>Unmute</a> ";
                                }
                                echo "<a class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\" href='includes/staff/refuseAppeal?target=" . $row["appealer"] . "&id=" . $row["id"] . "&reason=" . $row["reason"] . "'>Ignore</a> ";
                                exit();
    
                            }
                            
                        } else {
                            echo "<h4><a style='color: black;text-decoration:none;' href='?appeals&id=" . $row["id"]. "'>Username: <a href=\"user?u=" . $row["appealer"] . "\" target=\"_blank\" style=\"text-decoration: none; color: green;\">" . getTable($conn, "users", ["id", $row["appealer"]])["uid"] . "</a><a style='color: black;text-decoration:none;' href='?appeals&id=" . $row["id"] . "'> - Reason: " . $row["reason"] . "</a> <a style='color: black;text-decoration:none;' href='?appeals&id=" . $row["id"]. "'>" . $details . " </a></h4><br>";
                        }
    
                    }
                } else {
                    echo "<h4>0 reports</h4>";
                }

                exit();
            } else {
                if (!$settings->enable_report) {
                    echo "<p>Reporting is temporarily disabled.</p>";
                } else {
                    echo "<a href=\"?reports\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Reports</a> ";
                }
                if (!$settings->enable_appeal) {
                    echo "<p>Appeals are temporarily disabled.</p>";
                } else {
                    echo "<a href=\"?appeals\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Appeals</a> ";
                }
    
                echo "<a href=\"?suggestions\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Suggestions</a>";
            }
        
            echo "<h1>Admin Panel</h1>";

            if ($_SESSION["rank"] >= 3) $size = 8;
            else $size = 6;
            
            if ($settings->enable_admin_panel) {
                ?>

                    <form action="includes/staff/admin" method="post">
                        <input type="text" name="username" placeholder="Username..." value="<?= $usernameValue ?>"></br></br>
                        <select name="action" size="<?= $size ?>">
                            <option value="0" <?php if ($selectedValue == "user"): ?> selected="selected" <?php endif; ?> >Set User</option>
                            <option value="1" <?php if ($selectedValue == "mod"): ?> selected="selected" <?php endif; ?>>Set Moderator</option>
                            <option value="3" <?php if ($selectedValue == "delc"): ?> selected="selected" <?php endif; ?>>Delete Comment</option>
                            <option value="6" <?php if ($selectedValue == "undel"): ?> selected="selected" <?php endif; ?>>Undelete</option>
                            <option value="5" <?php if ($selectedValue == "unm"): ?> selected="selected" <?php endif; ?>>(Un)Mute</option>
                            <option value="-1"<?php if ($selectedValue == "unb"): ?> selected="selected" <?php endif; ?>>(Un)Ban</option>
                            <?php
                                if ($_SESSION["rank"] >= 3) {
                                    echo "<option value='4' " . ($selectedValue == "unv" ? "selected=\"selected\"" : "") . ">(Un)Verify</option>";
                                    echo "<option value='2' " . ($selectedValue == "admin" ? "selected=\"selected\"" : "") . ">Set Admin</option>";
                                }
                            ?>
                        </select></br></br>
                        <button type="submit" name="submit" class="button">Confirm</button>
                    </form>

                <?php
            } else {
                echo "<p>Admin Panel is temporarily disabled.</p>";
            }
        
        }

    ?>
</body>
</html>