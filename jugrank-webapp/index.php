<?php
$page = preg_replace('/\.+/', '', $_REQUEST['page']);

if (!$page) {
	$page = "JugRankSystem";
}

ob_start();
require("views/$page.php");
$content = ob_get_contents();
ob_end_clean();

include("views/page.php");

?>