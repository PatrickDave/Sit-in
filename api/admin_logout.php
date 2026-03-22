<?php
session_start();
session_destroy();
header("Location: ../html/admin/adminLogin.html");
exit();
?>
