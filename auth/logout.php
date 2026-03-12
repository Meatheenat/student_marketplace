<?php
session_start();
$_SESSION = [];          // ล้างค่าทั้งหมดออกจาก memory ก่อน
session_unset();         // unset ตัวแปร session ทั้งหมด
session_destroy();       // ถึงค่อย destroy
header("Location: ../pages/index.php");
exit();
?>