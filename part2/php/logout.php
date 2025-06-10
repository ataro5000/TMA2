<?php
//destroys the session and redirects to login.
session_name('TMA2_SESSION');
session_unset();
session_start();
session_destroy();
header("Location: ../html/login.php");
exit;
