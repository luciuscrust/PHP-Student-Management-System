<?php

declare(strict_types=1);

session_start();

$basePath = 'http://localhost/PHP-Student-Management-System';
$logoutUrl = $basePath . '/sms-api/auth/logout';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$absoluteLogoutUrl = $scheme . '://' . $host . $logoutUrl;

$cookieHeader = '';
if (!empty($_COOKIE[session_name()])) {
	$cookieHeader = session_name() . '=' . $_COOKIE[session_name()];
}

$ch = curl_init($absoluteLogoutUrl);
curl_setopt_array($ch, [
	CURLOPT_POST            => true,
	CURLOPT_RETURNTRANSFER  => true,
	CURLOPT_HEADER          => false,
	CURLOPT_TIMEOUT         => 10,
	CURLOPT_HTTPHEADER      => array_filter([
		'Accept: application/json',
		$cookieHeader ? ('Cookie: ' . $cookieHeader) : null,
	]),
]);

$responseBody = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
unset($ch);

$ok = false;
if ($curlErr === '' && $httpCode >= 200 && $httpCode < 300) {
	$json = json_decode((string)$responseBody, true);
	$ok = is_array($json) && !empty($json['success']);
}

// Local "logout" fallback if the logout route fails
if (!$ok) {
	$_SESSION = [];
	if (ini_get("session.use_cookies")) {
		$params = session_get_cookie_params();
		setcookie(
			session_name(),
			'',
			time() - 42000,
			$params["path"],
			$params["domain"],
			$params["secure"],
			$params["httponly"]
		);
	}
	session_destroy();
}

header('Location: ' . $basePath . '/sms/index.php');
exit;
