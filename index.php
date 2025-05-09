
<?php
include("header.html");
include("index.html");
if (isset($_GET["submiter"])) {
    echo $_GET["search"];
}
include("footer.html");

?>