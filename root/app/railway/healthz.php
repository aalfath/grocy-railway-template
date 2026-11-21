<?php
// Railway health check: 200 only when Grocy's database exists, is migrated and has a user.
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
try
{
	$pdo = new PDO('sqlite:/config/data/grocy.db');
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	if ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() < 1)
	{
		throw new RuntimeException('no users');
	}
	echo "ok\n";
}
catch (Throwable $e)
{
	http_response_code(503);
	echo "unhealthy\n";
}
