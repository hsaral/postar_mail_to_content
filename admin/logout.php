<?php
require_once __DIR__ . '/_bootstrap.php';
session_destroy();
redirect_to('admin/login.php');
