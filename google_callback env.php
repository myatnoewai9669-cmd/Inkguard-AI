<?php
session_start();

require 'config.php';
require 'env.php';

// =====================================================
// Google OAuth settings
// =====================================================
$client_id = GOOGLE_CLIENT_ID;
$client_secret = GOOGLE_CLIENT_SECRET;

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];
$subpath = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
$subpath = ($subpath === '/') ? '' : $subpath;
$redirect_uri = $protocol . $domainName . $subpath . "/google_callback.php";

// =====================================================
// Step 0: Make sure Google actually sent us a code
// =====================================================
if (!isset($_GET['code'])) {
    die("No authorization code received from Google. <a href='login.php'>Back to login</a>");
}

$code = $_GET['code'];

// =====================================================
// Step 1: Exchange the authorization code for an access token
// =====================================================
$token_url = "https://oauth2.googleapis.com/token";

$post_fields = [
    "
