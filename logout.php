<?php
session_start();
session_unset();
session_destroy();
header("Location: login.html"); // Volta para a tela de login
exit();
