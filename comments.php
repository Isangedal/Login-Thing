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

        // Python: if __name__ == '__main__': 
        if (!count(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))) {
            header("location: .?error=404");
        }

        if (isset($_GET["includefromgroup"])) {
            $group = getTable($conn, "groups", ["id", $_GET["includefromgroup"]]);

            if ($group == null) {
                header("location: groups");
                exit();
            }

            $access = false;
            foreach (explode(",", $group["members"]) as $v) {
                if ($v == $_SESSION["id"] || $_SESSION["rank"] >= 2) {
                    $access = true;
                    break;
                }
            }
            if (!$access) {
                header("location: groups");
                exit();
            }
        }
        elseif (isset($_GET["includefrompm"])) {
            $exit = true;

            foreach (getTable($conn, "friends", "", True) as $v) {
                if ($v["user1"] == $_GET["u"] && $v["user2"] == $_SESSION["id"] || $v["user2"] == $_GET["u"] && $v["user1"] == $_SESSION["id"] || $_SESSION["rank"] > 2) {
                    $exit = false;
                    break;
                }
            }

            if ($exit) {
                header("location: .?error=notfriend");
                exit();
            }

            $pming = getTable($conn, "users", ["id", $_GET["includefrompm"]]);
        }

        if (isset($_SESSION["uid"])) {
            if (!isset($_GET["includefromprofile"]) && !isset($_GET["includefromgroup"]) && !isset($_GET["includefrompm"])) {
                echo "<h4 class='center'>Welcome back, " . $_SESSION["uid"] . "</h4>";

                $muted = null;

                foreach (getTable($conn, "mutes", "", true) as $v) {
                    if ($v["target"] == $_SESSION["id"]) $muted = $v;
                }

                if ($settings->enable_posting_comments) {

                    if ($muted) {
                        echo "<p>You have been muted by " . getTable($conn, "users", ["id", $muted["muter"]])["uid"]  . " at " . $muted["date"] . "</p>";
                    } else {

                        ?>

                        <form action="includes/comments/comment" method="post">
                            <?php

                                if (isset($_GET["reply"])) {
                                    echo "<input name=\"replyid\" type=hidden value=\"" . $_GET["reply"] . "\" /><input name=\"message\" id=\"inputmsg\" style=\"line-height: 3.3em\" placeholder=\"Reply...\" />";
                                } else {
                                    echo "<input name=\"message\" id=\"inputmsg\" style=\"line-height: 3.3em\" placeholder=\"What's on your mind?\" />";
                                }

                            ?>

                            <button class="button" type="submit" name="submit">Post</button>
                        </form>

                        <?php

                    }


                }
                else {
                    echo "<p>Posting comments is temporarily disabled.</p>";
                }

            }
            elseif (isset($_GET["includefromgroup"])) {
                $group = getTable($conn, "groups", ["id", $_GET["includefromgroup"]]);

                echo "<h4 class='center'>You're viewing group " . $group["name"] . "</h4>";

                echo "<h6 class='center'>Comment \"!help\" for a list of commands.</h6>";

                if ($settings->enable_posting_comments) {


                    ?>

                    <form action="includes/groups/comment" method="post">

                        <?php

                            echo "<input name=\"groupid\" type=hidden value=\"" . $_GET["includefromgroup"] . "\" />";

                            if (isset($_GET["reply"])) {
                                echo "<input name=\"replyid\" type=hidden value=\"" . $_GET["reply"] . "\" /><input name=\"message\" id=\"inputmsg\" style=\"line-height: 3.3em\" placeholder=\"Reply...\" />";
                            } else {
                                echo "<input name=\"message\" id=\"inputmsg\" style=\"line-height: 3.3em\" placeholder=\"What's on your mind?\" />";
                            }

                        ?>

                        <button class="button" type="submit" name="submit">Post</button></br></br>

                    </form>

                    <?php


                }
                else {
                    echo "<p>Posting comments is temporarily disabled.</p>";
                }

            }
            elseif (isset($_GET["includefrompm"])) {
                echo "<h4 class='center'>You're private messaging " . $pming["uid"] . "</h4>";

                if ($settings->enable_posting_comments) {


                    ?>

                    <form action="includes/pm/comment" method="post">

                        <?php

                            echo "<input name=\"user\" type=hidden value=\"" . $_GET["includefrompm"] . "\" />";
                            echo "<input name=\"message\" id=\"inputmsg\" style=\"line-height: 3.3em\" placeholder=\"What's on your mind?\" />";


                        ?>

                        <button class="button" type="submit" name="submit">Post</button>

                    </form>

                    <?php


                }
                else {
                    echo "<p>Posting comments is temporarily disabled.</p>";
                }

            }

            if (!$settings->enable_likes && !isset($_GET["includefromgroup"]) && !isset($_GET["includefrompm"])) {
                echo "<p>Likes are temporarily disabled.</p>";
            }
            
            if ($settings->enable_viewing_comments) {

                $has = false;

                echo "</br></br><div class=\"comments\">";

                if (!isset($_GET["includefromprofile"]) && !isset($_GET["includefromgroup"]) && !isset($_GET["includefrompm"])) {

                    $friend_comments = [];

                    $friend_comments_h2_displayed = false;

                    foreach (mysqli_fetch_all(getTable($conn, "messages")) as $res) {
                        $friend_comment = false;
                        $has = true;

                        foreach (mysqli_fetch_all(getTable($conn, "friends")) as $res2) {
                            if ($res2[1] == $_SESSION["id"] && $res2[2] == $res[2] || $res2[1] == $res[2] && $res2[2] == $_SESSION["id"]) {
                                $friend_comment = true;
                                array_push($friend_comments, $res[0]);
                                break;
                            }
                        }

                        # Random comments
                        if (!$friend_comment) {
                            continue;
                        }

                        if (!$friend_comments_h2_displayed) {
                            echo "<h2>Friend Comments:</h2>";
                            $friend_comments_h2_displayed = true;
                        }

                        echo "<div id=" . $res[0] . " class=\"comment\" tabindex=\"-1\">";

                        $contin = true;

                        if (isset($_GET["hashtag"])) {    
                            $contin = false;

                            foreach (explode(" ", $res[1]) as $v) {
                                if (strtolower(urldecode($_GET["hashtag"])) == strtolower(substr($v, 1))) {
                                    $contin = true;
                                }
                            }
                        }

                        if (isset($_GET["mentions"])) {    
                            $contin = false;

                            foreach (explode(" ", $res[1]) as $v) {
                                if (strtolower(urldecode($_GET["mentions"])) == strtolower(substr($v, 1))) {
                                    $contin = true;
                                }
                            }
                        }

                        if (!$contin) continue;

                        if (getTable($conn, "users", ["id", $res[2]]) == null) {
                            if ($_SESSION["rank"] > 0) {
                                echo "<h2>[" . $res[0] . "] [Account Deleted]:</h2>";
                            } else {
                                echo "<h2>[Account Deleted]:</h2>";
                            }
                        } else {
                            $user = getTable($conn, "users", ["id", $res[2]]);

                            $muted = "";

                            foreach (getTable($conn, "mutes", "", true) as $v) {
                                if ($v["target"] == $user["id"]) $muted = " (Muted)";
                            }

                            $banned = "";

                            foreach (getTable($conn, "bans", "", true) as $v) {
                                if ($v["target"] == $user["id"]) $banned = " (Banned)";
                            }

                            if ($_SESSION["rank"] < 1) $muted = "";

                            $verified = ($user["verified"] ? "<p style=\"display: inline;color: #ccaa00;\" title=\"Verified\">✔</p>" : "");
                            $commentId = ($_SESSION["rank"] > 0 ? "[" . $res[0] . "] " : "");
                            $admin = ($user["rank"] >= 1 ? " <img src=\"img/admin.png\" alt=\"" . rankFromNum($user["rank"]) . "\" title=\"" . rankFromNum($user["rank"]) . "\">" : "");
                            $isyou = ($_SESSION["id"] == $res[2] ? " (You)" : "");

                            echo "<h2>" . $commentId . "<a style=\"color: green;\" href=\"user?u=" . $res[2] . "\">" . $user["uid"] . "</a>" . $admin . $verified . $muted . $banned . $isyou . ":</h2>";
                        
                        }

                        $isgroup = (isset($_GET["includefromgroup"]) ? "groups?g=" . $_GET["includefromgroup"] . "&" : ".?");
                        
                        if ($isgroup == ".?") {
                            $ispm = (isset($_GET["includefrompm"]) ? "pm?u=" . $_GET["includefrompm"] . "&" : ".?");
                            $isgroup = "";
                        }
                        
                        $result = "";
                        foreach (explode(" ", $res[1]) as $v) {
                            if (str_starts_with($v, "@")) {
                                $user = getTable($conn, "users", ["uid", substr($v, 1)]);
                                if ($user) {
                                    $result .= "<a style=\"color: green;\" href=\"user?u=" . $user["id"] . "\">" . $v . "</a>";
                                } else {
                                    $result .= $v;
                                }
                            }
                            elseif (str_starts_with($v, "#")) {
                                $result .= "<a style=\"color: red;\" href=\"" . $isgroup . $ispm . "hashtag=" . urlencode(strtolower(substr($v, 1))) . "\">" . $v . "</a>";
                            }
                            else {
                                $result .= $v;
                            }
                            $result .= " ";
                        }

                        $replyTo = "";

                        $isgroup = (isset($_GET["includefromgroup"]) ? "groups?g=" . $_GET["includefromgroup"] : ".");
                        $isgroup2 = (isset($_GET["includefromgroup"]) ? 3 : 4);

                        if ($res[$isgroup2] != null && !isset($_GET["includefrompm"])) {
                            $replyTo = "[<a href=\"" . $isgroup . "#" . $res[$isgroup2] . "\">Reply To</a>] ";
                        }

                        echo "<p>" . $replyTo . $result . "</p>";

                        if (!isset($_GET["includefromgroup"]) && !isset($_GET["includefrompm"])) {
                            echo "<p class=\"commentlikes\">" . $res[3] . " Likes"  . "</p>";

                            if ($settings->enable_likes) {
                                if ($_SESSION["id"] == $res[2] || $_SESSION["rank"] >= 2) {
                                    echo "<form action=\"includes/comments/like\" method=\"post\"><a href=\"?reply=" . $res[0] . "\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Reply</a><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><input type=hidden name=\"return\" value=\".?\"> <button type=\"submit\" name=\"submit\" class=\"button\">Like</button> <button type=\"submit\" name=\"delete\" class=\"button\">Delete</button></form>";
                                }
                                elseif ($_SESSION["rank"] == 1) {
                                    echo "<form action=\"includes/comments/like\" method=\"post\"><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><input type=hidden name=\"return\" value=\".?\"><a href=\"?reply=" . $res[0] . "\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Reply</a> <button type=\"submit\" name=\"submit\" class=\"button\">Like</button> <button type=\"submit\" name=\"delete\" class=\"button\">Request Delete</button></form>";
                                }
                                else {
                                    echo "<form action=\"includes/comments/like\" method=\"post\"><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><input type=hidden name=\"return\" value=\".?\"><a href=\"?reply=" . $res[0] . "\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Reply</a> <button type=\"submit\" name=\"submit\" class=\"button\">Like</button></form>";
                                }
                            } else {
                                if ($_SESSION["id"] == $res[2] || $_SESSION["rank"] >= 2) {
                                    echo "<form action=\"includes/comments/like\" method=\"post\"><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><input type=hidden name=\"return\" value=\".?\"><a href=\"?reply=" . $res[0] . "\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Reply</a> <button type=\"submit\" name=\"delete\" class=\"button\">Delete</button></form>";
                                }
                                elseif ($_SESSION["rank"] == 1) {
                                    echo "<form action=\"includes/comments/like\" method=\"post\"><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><input type=hidden name=\"return\" value=\".?\"><a href=\"?reply=" . $res[0] . "\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Reply</a> <button type=\"submit\" name=\"delete\" class=\"button\">Request Delete</button></form>";
                                }
                                else {
                                    echo "<form action=\"includes/comments/like\" method=\"post\"><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><input type=hidden name=\"return\" value=\".?\"><a href=\"?reply=" . $res[0] . "\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Reply</a></form>";
                                }
                            }
                        }
                        elseif (isset($_GET["includefromgroup"]) && !isset($_GET["includefrompm"])) {
                            $mod = false;
                            foreach (explode(",", getTable($conn, "groups", ["id", $_GET["includefromgroup"]])["mods"]) as $v) {
                                if ($v == $_SESSION["id"]) {
                                    $mod = true;
                                    break;
                                }
                            }

                            if ($_SESSION["id"] == $res[2] || $_SESSION["rank"] >= 2 || $group["author"] == $_SESSION["id"] || $mod) echo "<form action=\"includes/groups/deleteComment\" method=\"post\"><input type=hidden name=\"groupid\" value=" . $res[4] . "><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><a href=\"?g=" . $_GET["includefromgroup"] . "&reply=" . $res[0] . "\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Reply</a> <button type=\"submit\" name=\"delete\" class=\"button\">Delete</button></form>";
                            else echo "<form action=\"includes/groups/deleteComment\" method=\"post\"><input type=hidden name=\"groupid\" value=" . $res[4] . "><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><a href=\"?g=" . $_GET["includefromgroup"] . "&reply=" . $res[0] . "\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Reply</a></form>";
                        }
                        elseif (isset($_GET["includefrompm"]) && !isset($_GET["includefromgroup"])) {
                            if ($_SESSION["id"] == $res[2] || $_SESSION["rank"] >= 2) echo "<form action=\"includes/pm/deleteComment\" method=\"post\"><input type=hidden name=\"user\" value=" . $res[3] . "><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><button type=\"submit\" name=\"delete\" class=\"button\">Delete</button></form>";
                            else echo "<form action=\"includes/pm/deleteComment\" method=\"post\"><input type=hidden name=\"user\" value=" . $res[3] . "><input type=hidden name=\"commentId\" value=" . $res[0] . "></input></form>";
                        }

                        echo "</div>";

                    }

                    if ($has) {

                        $comments_h2_displayed = false;

                        foreach (mysqli_fetch_all(getTable($conn, "messages")) as $res) {

                            # Change the second argument in random_int to change how often a comment should appear
                            # 1,2 = 50%    Chance
                            # 1,3 = 33.33% Chance
                            # 1,4 = 25%    Chance
                            $rnd = random_int(1, $settings->random_comment_chance);

                            $rnd = ($settings->enable_random_comments ? $rnd : 0);

                            if ($rnd == 1 || in_array($res[0], $friend_comments)) {
                                continue;
                            }

                            if (!$comments_h2_displayed && !in_array($res[0], $friend_comments)) {
                                echo "<h2>Comments:</h2>";
                                $comments_h2_displayed = true;
                            }

                            echo "<div id=" . $res[0] . " class=\"comment\" tabindex=\"-1\">";

                            $contin = true;

                            if (isset($_GET["hashtag"])) {    
                                $contin = false;

                                foreach (explode(" ", $res[1]) as $v) {
                                    if (strtolower(urldecode($_GET["hashtag"])) == strtolower(substr($v, 1))) {
                                        $contin = true;
                                    }
                                }
                            }

                            if (isset($_GET["mentions"])) {    
                                $contin = false;

                                foreach (explode(" ", $res[1]) as $v) {
                                    if (strtolower(urldecode($_GET["mentions"])) == strtolower(substr($v, 1))) {
                                        $contin = true;
                                    }
                                }
                            }

                            if (!$contin) continue;

                            $has = true;

                            if (getTable($conn, "users", ["id", $res[2]]) == null) {
                                if ($_SESSION["rank"] > 0) {
                                    echo "<h2>[" . $res[0] . "] [Account Deleted]:</h2>";
                                } else {
                                    echo "<h2>[Account Deleted]:</h2>";
                                }
                            } else {
                                $user = getTable($conn, "users", ["id", $res[2]]);

                                $muted = "";

                                foreach (getTable($conn, "mutes", "", true) as $v) {
                                    if ($v["target"] == $user["id"]) $muted = " (Muted)";
                                }

                                $banned = "";

                                foreach (getTable($conn, "bans", "", true) as $v) {
                                    if ($v["target"] == $user["id"]) $banned = " (Banned)";
                                }

                                if ($_SESSION["rank"] < 1) $muted = "";

                                $verified = ($user["verified"] ? "<p style=\"display: inline;color: #ccaa00;\" title=\"Verified\">✔</p>" : "");
                                $commentId = ($_SESSION["rank"] > 0 ? "[" . $res[0] . "] " : "");
                                $admin = ($user["rank"] >= 1 ? " <img src=\"img/admin.png\" alt=\"" . rankFromNum($user["rank"]) . "\" title=\"" . rankFromNum($user["rank"]) . "\">" : "");
                                $isyou = ($_SESSION["id"] == $res[2] ? " (You)" : "");

                                echo "<h2>" . $commentId . "<a style=\"color: green;\" href=\"user?u=" . $res[2] . "\">" . $user["uid"] . "</a>" . $admin . $verified . $muted . $banned . $isyou . ":</h2>";
                            
                            }

                            $isgroup = (isset($_GET["includefromgroup"]) ? "groups?g=" . $_GET["includefromgroup"] . "&" : ".?");
                            
                            if ($isgroup == ".?") {
                                $ispm = (isset($_GET["includefrompm"]) ? "pm?u=" . $_GET["includefrompm"] . "&" : ".?");
                                $isgroup = "";
                            }
                            
                            $result = "";
                            foreach (explode(" ", $res[1]) as $v) {
                                if (str_starts_with($v, "@")) {
                                    $user = getTable($conn, "users", ["uid", substr($v, 1)]);
                                    if ($user) {
                                        $result .= "<a style=\"color: green;\" href=\"user?u=" . $user["id"] . "\">" . $v . "</a>";
                                    } else {
                                        $result .= $v;
                                    }
                                }
                                elseif (str_starts_with($v, "#")) {
                                    $result .= "<a style=\"color: red;\" href=\"" . $isgroup . $ispm . "hashtag=" . urlencode(strtolower(substr($v, 1))) . "\">" . $v . "</a>";
                                }
                                else {
                                    $result .= $v;
                                }
                                $result .= " ";
                            }

                            $replyTo = "";

                            $isgroup = (isset($_GET["includefromgroup"]) ? "groups?g=" . $_GET["includefromgroup"] : ".");
                            $isgroup2 = (isset($_GET["includefromgroup"]) ? 3 : 4);

                            if ($res[$isgroup2] != null && !isset($_GET["includefrompm"])) {
                                $replyTo = "[<a href=\"" . $isgroup . "#" . $res[$isgroup2] . "\">Reply To</a>] ";
                            }

                            echo "<p>" . $replyTo . $result . "</p>";

                            if (!isset($_GET["includefromgroup"]) && !isset($_GET["includefrompm"])) {
                                echo "<p class=\"commentlikes\">" . $res[3] . " Likes"  . "</p>";

                                if ($settings->enable_likes) {
                                    if ($_SESSION["id"] == $res[2] || $_SESSION["rank"] >= 2) {
                                        echo "<form action=\"includes/comments/like\" method=\"post\"><a href=\"?reply=" . $res[0] . "\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Reply</a><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><input type=hidden name=\"return\" value=\".?\"> <button type=\"submit\" name=\"submit\" class=\"button\">Like</button> <button type=\"submit\" name=\"delete\" class=\"button\">Delete</button></form>";
                                    }
                                    elseif ($_SESSION["rank"] == 1) {
                                        echo "<form action=\"includes/comments/like\" method=\"post\"><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><input type=hidden name=\"return\" value=\".?\"><a href=\"?reply=" . $res[0] . "\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Reply</a> <button type=\"submit\" name=\"submit\" class=\"button\">Like</button> <button type=\"submit\" name=\"delete\" class=\"button\">Request Delete</button></form>";
                                    }
                                    else {
                                        echo "<form action=\"includes/comments/like\" method=\"post\"><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><input type=hidden name=\"return\" value=\".?\"><a href=\"?reply=" . $res[0] . "\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Reply</a> <button type=\"submit\" name=\"submit\" class=\"button\">Like</button></form>";
                                    }
                                } else {
                                    if ($_SESSION["id"] == $res[2] || $_SESSION["rank"] >= 2) {
                                        echo "<form action=\"includes/comments/like\" method=\"post\"><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><input type=hidden name=\"return\" value=\".?\"><a href=\"?reply=" . $res[0] . "\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Reply</a> <button type=\"submit\" name=\"delete\" class=\"button\">Delete</button></form>";
                                    }
                                    elseif ($_SESSION["rank"] == 1) {
                                        echo "<form action=\"includes/comments/like\" method=\"post\"><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><input type=hidden name=\"return\" value=\".?\"><a href=\"?reply=" . $res[0] . "\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Reply</a> <button type=\"submit\" name=\"delete\" class=\"button\">Request Delete</button></form>";
                                    }
                                    else {
                                        echo "<form action=\"includes/comments/like\" method=\"post\"><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><input type=hidden name=\"return\" value=\".?\"><a href=\"?reply=" . $res[0] . "\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Reply</a></form>";
                                    }
                                }
                            }
                            elseif (isset($_GET["includefromgroup"]) && !isset($_GET["includefrompm"])) {
                                $mod = false;
                                foreach (explode(",", getTable($conn, "groups", ["id", $_GET["includefromgroup"]])["mods"]) as $v) {
                                    if ($v == $_SESSION["id"]) {
                                        $mod = true;
                                        break;
                                    }
                                }

                                if ($_SESSION["id"] == $res[2] || $_SESSION["rank"] >= 2 || $group["author"] == $_SESSION["id"] || $mod) echo "<form action=\"includes/groups/deleteComment\" method=\"post\"><input type=hidden name=\"groupid\" value=" . $res[4] . "><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><a href=\"?g=" . $_GET["includefromgroup"] . "&reply=" . $res[0] . "\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Reply</a> <button type=\"submit\" name=\"delete\" class=\"button\">Delete</button></form>";
                                else echo "<form action=\"includes/groups/deleteComment\" method=\"post\"><input type=hidden name=\"groupid\" value=" . $res[4] . "><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><a href=\"?g=" . $_GET["includefromgroup"] . "&reply=" . $res[0] . "\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Reply</a></form>";
                            }
                            elseif (isset($_GET["includefrompm"]) && !isset($_GET["includefromgroup"])) {
                                if ($_SESSION["id"] == $res[2] || $_SESSION["rank"] >= 2) echo "<form action=\"includes/pm/deleteComment\" method=\"post\"><input type=hidden name=\"user\" value=" . $res[3] . "><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><button type=\"submit\" name=\"delete\" class=\"button\">Delete</button></form>";
                                else echo "<form action=\"includes/pm/deleteComment\" method=\"post\"><input type=hidden name=\"user\" value=" . $res[3] . "><input type=hidden name=\"commentId\" value=" . $res[0] . "></input></form>";
                            }

                            echo "</div>";

                        }
                    }

                } else {

                    $isgroup = (isset($_GET["includefromgroup"]) ? "group" : "");
                    $ispm = (isset($_GET["includefrompm"]) ? "private" : "");

                    echo "<h2>Comments:</h2>";

                    foreach (mysqli_fetch_all(getTable($conn, $isgroup . $ispm . "messages")) as $res) {
                        if (isset($_GET["includefromprofile"]) && $res[2] != $_GET["includefromprofile"]) {
                            continue;
                        }
                        elseif (isset($_GET["includefromgroup"]) && $res[4] != $_GET["includefromgroup"]) {
                            continue;
                        }
                        elseif (isset($_GET["includefrompm"])) {
                            if ($res[3] == $_SESSION["id"] && $res[2] == $_GET["includefrompm"] || $res[3] == $_GET["includefrompm"] && $res[2] == $_SESSION["id"]) {}
                            else continue;
                        }

                        echo "<div id=" . $res[0] . " class=\"comment\" tabindex=\"-1\">";

                        $contin = true;

                        if (isset($_GET["hashtag"])) {    
                            $contin = false;

                            foreach (explode(" ", $res[1]) as $v) {
                                if (strtolower(urldecode($_GET["hashtag"])) == strtolower(substr($v, 1))) {
                                    $contin = true;
                                }
                            }
                        }

                        if (isset($_GET["mentions"])) {    
                            $contin = false;

                            foreach (explode(" ", $res[1]) as $v) {
                                if (strtolower(urldecode($_GET["mentions"])) == strtolower(substr($v, 1))) {
                                    $contin = true;
                                }
                            }
                        }

                        if (!$contin) continue;

                        $has = true;

                        if (getTable($conn, "users", ["id", $res[2]]) == null) {
                            if ($_SESSION["rank"] > 0) {
                                echo "<h2>[" . $res[0] . "] [Account Deleted]:</h2>";
                            } else {
                                echo "<h2>[Account Deleted]:</h2>";
                            }
                        } else {
                            $user = getTable($conn, "users", ["id", $res[2]]);

                            $muted = "";

                            foreach (getTable($conn, "mutes", "", true) as $v) {
                                if ($v["target"] == $user["id"]) $muted = " (Muted)";
                            }

                            $banned = "";

                            foreach (getTable($conn, "bans", "", true) as $v) {
                                if ($v["target"] == $user["id"]) $banned = " (Banned)";
                            }

                            if ($_SESSION["rank"] < 1) $muted = "";

                            $verified = ($user["verified"] ? "<p style=\"display: inline;color: #ccaa00;\" title=\"Verified\">✔</p>" : "");
                            $commentId = ($_SESSION["rank"] > 0 ? "[" . $res[0] . "] " : "");
                            $admin = ($user["rank"] >= 1 ? " <img src=\"img/admin.png\" alt=\"" . rankFromNum($user["rank"]) . "\" title=\"" . rankFromNum($user["rank"]) . "\">" : "");
                            $isyou = ($_SESSION["id"] == $res[2] ? " (You)" : "");

                            echo "<h2>" . $commentId . "<a style=\"color: green;\" href=\"user?u=" . $res[2] . "\">" . $user["uid"] . "</a>" . $admin . $verified . $muted . $banned . $isyou . ":</h2>";
                        
                        }

                        $isgroup = (isset($_GET["includefromgroup"]) ? "groups?g=" . $_GET["includefromgroup"] . "&" : ".?");
                        
                        if ($isgroup == ".?") {
                            $ispm = (isset($_GET["includefrompm"]) ? "pm?u=" . $_GET["includefrompm"] . "&" : ".?");
                            $isgroup = "";
                        }
                        
                        $result = "";
                        foreach (explode(" ", $res[1]) as $v) {
                            if (str_starts_with($v, "@")) {
                                $user = getTable($conn, "users", ["uid", substr($v, 1)]);
                                if ($user) {
                                    $result .= "<a style=\"color: green;\" href=\"user?u=" . $user["id"] . "\">" . $v . "</a>";
                                } else {
                                    $result .= $v;
                                }
                            }
                            elseif (str_starts_with($v, "#")) {
                                $result .= "<a style=\"color: red;\" href=\"" . $isgroup . $ispm . "hashtag=" . urlencode(strtolower(substr($v, 1))) . "\">" . $v . "</a>";
                            }
                            else {
                                $result .= $v;
                            }
                            $result .= " ";
                        }

                        $replyTo = "";

                        $isgroup = (isset($_GET["includefromgroup"]) ? "groups?g=" . $_GET["includefromgroup"] : ".");
                        $isgroup2 = (isset($_GET["includefromgroup"]) ? 3 : 4);

                        if ($res[$isgroup2] != null && !isset($_GET["includefrompm"])) {
                            $replyTo = "[<a href=\"" . $isgroup . "#" . $res[$isgroup2] . "\">Reply To</a>] ";
                        }

                        echo "<p>" . $replyTo . $result . "</p>";

                        if (!isset($_GET["includefromgroup"]) && !isset($_GET["includefrompm"])) {
                            echo "<p class=\"commentlikes\">" . $res[3] . " Likes"  . "</p>";

                            if ($settings->enable_likes) {
                                if ($_SESSION["id"] == $res[2] || $_SESSION["rank"] >= 2) {
                                    echo "<form action=\"includes/comments/like\" method=\"post\"><a href=\"?reply=" . $res[0] . "\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Reply</a><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><input type=hidden name=\"return\" value=\".?\"> <button type=\"submit\" name=\"submit\" class=\"button\">Like</button> <button type=\"submit\" name=\"delete\" class=\"button\">Delete</button></form>";
                                }
                                elseif ($_SESSION["id"] == $res[2] || $_SESSION["rank"] == 1) {
                                    echo "<form action=\"includes/comments/like\" method=\"post\"><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><input type=hidden name=\"return\" value=\".?\"><a href=\"?reply=" . $res[0] . "\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Reply</a> <button type=\"submit\" name=\"submit\" class=\"button\">Like</button> <button type=\"submit\" name=\"delete\" class=\"button\">Request Delete</button></form>";
                                }
                                else {
                                    echo "<form action=\"includes/comments/like\" method=\"post\"><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><input type=hidden name=\"return\" value=\".?\"><a href=\"?reply=" . $res[0] . "\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Reply</a> <button type=\"submit\" name=\"submit\" class=\"button\">Like</button></form>";
                                }
                            } else {
                                if ($_SESSION["id"] == $res[2] || $_SESSION["rank"] >= 2) {
                                    echo "<form action=\"includes/comments/like\" method=\"post\"><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><input type=hidden name=\"return\" value=\".?\"><a href=\"?reply=" . $res[0] . "\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Reply</a> <button type=\"submit\" name=\"delete\" class=\"button\">Delete</button></form>";
                                }
                                elseif ($_SESSION["id"] == $res[2] || $_SESSION["rank"] == 1) {
                                    echo "<form action=\"includes/comments/like\" method=\"post\"><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><input type=hidden name=\"return\" value=\".?\"><a href=\"?reply=" . $res[0] . "\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Reply</a> <button type=\"submit\" name=\"delete\" class=\"button\">Request Delete</button></form>";
                                }
                                else {
                                    echo "<form action=\"includes/comments/like\" method=\"post\"><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><input type=hidden name=\"return\" value=\".?\"><a href=\"?reply=" . $res[0] . "\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Reply</a></form>";
                                }
                            }
                        }
                        elseif (isset($_GET["includefromgroup"]) && !isset($_GET["includefrompm"])) {
                            $mod = false;
                            foreach (explode(",", getTable($conn, "groups", ["id", $_GET["includefromgroup"]])["mods"]) as $v) {
                                if ($v == $_SESSION["id"]) {
                                    $mod = true;
                                    break;
                                }
                            }

                            if ($_SESSION["id"] == $res[2] || $_SESSION["rank"] >= 2 || $group["author"] == $_SESSION["id"] || $mod) echo "<form action=\"includes/groups/deleteComment\" method=\"post\"><input type=hidden name=\"groupid\" value=" . $res[4] . "><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><a href=\"?g=" . $_GET["includefromgroup"] . "&reply=" . $res[0] . "\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Reply</a> <button type=\"submit\" name=\"delete\" class=\"button\">Delete</button></form>";
                            else echo "<form action=\"includes/groups/deleteComment\" method=\"post\"><input type=hidden name=\"groupid\" value=" . $res[4] . "><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><a href=\"?g=" . $_GET["includefromgroup"] . "&reply=" . $res[0] . "\" class=\"button\" style=\"font: 400 13.3333px Arial; font-size: 16px;\">Reply</a></form>";
                        }
                        elseif (isset($_GET["includefrompm"]) && !isset($_GET["includefromgroup"])) {
                            if ($_SESSION["id"] == $res[2] || $_SESSION["rank"] >= 2) echo "<form action=\"includes/pm/deleteComment\" method=\"post\"><input type=hidden name=\"user\" value=" . $res[3] . "><input type=hidden name=\"commentId\" value=" . $res[0] . "></input><button type=\"submit\" name=\"delete\" class=\"button\">Delete</button></form>";
                            else echo "<form action=\"includes/pm/deleteComment\" method=\"post\"><input type=hidden name=\"user\" value=" . $res[3] . "><input type=hidden name=\"commentId\" value=" . $res[0] . "></input></form>";
                        }

                        echo "</div>";

                    }
                }

                if (!$has) {
                    $say = (isset($_GET["includefromgroup"]) ? "This group has no comments" : "</br></br>There are no comments");
                    if (isset($_GET["includefromprofile"])) {
                        if ($_GET["includefromprofile"] == $_SESSION["id"]) $say = "You have no comments";
                        else $say = "This user has no comments";
                    }
                    echo $say;
                }

                echo "</div>";

            } else {
                echo "<p>Viewing comments is temporarily disabled.</p>";
            }

        } elseif (!isset($_SESSION["uid"])) {
            header("location: .");
        }
    ?>
</body>
</html>