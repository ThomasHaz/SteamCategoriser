<?php
$vanityform = filter_input(INPUT_GET, 'vanityformurl');
if (strlen($vanityform)) {
    header('Location: ' . $vanityform);
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Steam Categories</title>

        <link type="text/css" href="css/styles.css" rel="stylesheet">

        <?php
        $vanity = preg_replace("/[^A-Za-z0-9\_\.\~\-]/", "", filter_input(INPUT_GET, 'vanityurl'));
        if (strlen($vanity)) : // Don't attempt anything if there is no vanity url set.
            ?>
            <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
            <script>

                var totalgames = 0;
                var uid = 0;
                $(document).ready(function () {
                    var getUID = function (vanityurl) {
                        ///api/steam/get/user/id/
                        //api/steam/api.php?vanityurl=$1
                        $.get("api/steam/api.php?output=1&totalpages=1&vanityurl=" + vanityurl, function (data) {
                            var result = JSON.parse(data);
                            if (result.success === 1) {
                                $("#successful-request").removeClass("hidden");

                                totalgames = result.data.totalgames;
                                $("#totalpagecount").text(totalgames);
                                uid = result.data.steamid;
                                loadGamesNext(0, uid);
                            } else {
                                console.log(result.data);
                                $("#output-content").html("<ul><li>" + result.data.join("</li><li>") + "</li></ul>");
                            }
                        });
                    }

                    var loadGamesNext = function (page, uid) {
                        $.get("/api/steam/api.php?uid=" + uid + "&page=" + page, function (data) {
                            if (data) {
                                try {
                                    var result = JSON.parse(data);
                                    if (result.success == 1) {
                                        $('#content').append(unescape(result.data.usergame));
                                        $("#totalloaded").text(page + 1);

                                    } else {
                                        console.log(data);
                                    }
                                    if (page < totalgames - 1) {
                                        loadGamesNext(page + 1, uid);
                                    }
                                } catch (e) {
                                    //alert(e); // error in the above string (in this case, yes)!
                                    console.log(e);
                                    console.log(data);
                                }
                            }

                        });
                    };

                    getUID("<?php echo $vanity; ?>");

                    $("#vdflink").click(function (e) {
                        e.preventDefault();
                        $.get("/api/steam/api.php?uid=" + uid + "&vdf=1", function (data) {
                            //alert(data);
                            var result = JSON.parse(data);

                            if (result.success == 1) {
                                $('#vdf').append(result.data);
                                $('#copyvdfbtn').removeClass("hidden");
                            }
                        });
                        return false;
                    });
                });

                function copyvdf(elem) {
                    // create hidden text element, if it doesn't already exist
                    var targetId = "_hiddenCopyText_";
                    var isInput = elem.tagName === "INPUT" || elem.tagName === "TEXTAREA";
                    var origSelectionStart, origSelectionEnd;
                    if (isInput) {
                        // can just use the original source element for the selection and copy
                        target = elem;
                        origSelectionStart = elem.selectionStart;
                        origSelectionEnd = elem.selectionEnd;
                    } else {
                        // must use a temporary form element for the selection and copy
                        target = document.getElementById(targetId);
                        if (!target) {
                            var target = document.createElement("textarea");
                            target.style.position = "absolute";
                            target.style.left = "-9999px";
                            target.style.top = "0";
                            target.id = targetId;
                            document.body.appendChild(target);
                        }
                        target.textContent = elem.textContent;
                    }
                    // select the content
                    var currentFocus = document.activeElement;
                    target.focus();
                    target.setSelectionRange(0, target.value.length);

                    // copy the selection
                    var succeed;
                    try {
                        succeed = document.execCommand("copy");
                    } catch (e) {
                        succeed = false;
                    }
                    // restore original focus
                    if (currentFocus && typeof currentFocus.focus === "function") {
                        currentFocus.focus();
                    }

                    if (isInput) {
                        // restore prior selection
                        elem.setSelectionRange(origSelectionStart, origSelectionEnd);
                    } else {
                        // clear temporary content
                        target.textContent = "";
                    }
                    return succeed;
                }
            </script>
            <?php
        endif; //strlen(vanity) 
        ?>
    </head>
    <body>
        <div id="header">
            <div class="page-content">

                <form action="index.php" method="GET">
                    <label for="vanityurl">Please enter your <i>public</i> steam profile name:</label>
                    <input type="text" id="vanity-text" name="vanityformurl" />
                    <input type="submit" />
                </form>

            </div>
        </div>
        <?php
        if (strlen($vanity) == 0) {
            echo "<!--"; // hide the following if we don't have a vanity url passed to us.
        }
        ?>
        <div id="container">




            <div class="page-content" id="output-content">
                <div id="successful-request" class="hidden">
                    <h2>Games List</h2>

                    Loaded: <span id="totalloaded">0</span> / <span id="totalpagecount"></span>
                    <a id="vdflink" href="#">View vdf format</a>
                    <button id="copyvdfbtn" class="hidden" onclick="copyvdf(document.getElementById('vdf'))">Copy VDF</button>
                    <pre id="vdf"></pre>
                    <div id="content">

                    </div>
                    <div class="clear">&nbsp;</div>

                </div>

            </div>


        </div>

        <?php
        if (strlen($vanity) == 0) {
            echo "-->";
        }
        ?>
    </body>
</html>
