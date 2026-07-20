<?php
session_start();

require 'config.php';
require 'env.php';

// ---- Google OAuth settings ----
$client_id = GOOGLE_CLIENT_ID;

// Dynamically compute the redirect URI
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];
$subpath = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
$subpath = ($subpath === '/') ? '' : $subpath;

$redirect_uri = $protocol . $domainName . $subpath . "/google_callback.php";

$scope = "email profile";

$auth_url = "https://accounts.google.com/o/oauth2/v2/auth"
    . "?client_id=" . urlencode($client_id)
    . "&redirect_uri=" . urlencode($redirect_uri)
    . "&response_type=code"
    . "&scope=" . urlencode($scope)
    . "&access_type=online"
    . "&prompt=select_account";

header("Location: " . $auth_url);
exit();
?>
