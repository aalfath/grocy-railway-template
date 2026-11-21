<?php
/**
 * First-boot bootstrap for Grocy on Railway.
 *
 * Grocy creates its schema on the first web request, and migration 0027 then seeds the
 * login admin/admin unless GROCY_HTTP_USER and GROCY_HTTP_PASSWORD are defined. Running
 * the migrations here with those constants set from GROCY_ADMIN_USERNAME and
 * GROCY_ADMIN_PASSWORD means the default password never exists on a public URL.
 *
 * Later boots only run pending migrations. The admin password is not touched again, so a
 * password changed in Grocy's UI stays changed. As a safety net, any user that still has
 * the password "admin" gets GROCY_ADMIN_PASSWORD instead.
 */

function railway_log(string $m): void
{
	fwrite(STDOUT, '[railway-bootstrap] ' . $m . PHP_EOL);
}

$dataPath = '/config/data';
$username = trim((string) getenv('GROCY_ADMIN_USERNAME')) ?: 'admin';
$password = (string) getenv('GROCY_ADMIN_PASSWORD');

if ($password === '')
{
	$password = rtrim(strtr(base64_encode(random_bytes(18)), '+/', 'xy'), '=');
	$file = '/config/initial-admin-password.txt';
	file_put_contents($file, $password . PHP_EOL);
	chmod($file, 0600);
	railway_log('GROCY_ADMIN_PASSWORD is empty: generated a password and saved it to ' . $file);
}

define('GROCY_IS_EMBEDDED_INSTALL', false);
define('GROCY_DATAPATH', $dataPath);
define('GROCY_HTTP_USER', $username);
define('GROCY_HTTP_PASSWORD', $password);

chdir('/app/www');
require_once '/app/www/packages/autoload.php';
require_once $dataPath . '/config.php';
require_once '/app/www/config-dist.php';

if (GROCY_MODE !== 'production')
{
	railway_log('WARNING: GROCY_MODE is "' . GROCY_MODE . '": Grocy turns off logins outside production mode.');
}

$fresh = !file_exists($dataPath . '/grocy.db');
\Grocy\Services\DatabaseMigrationService::GetInstance()->MigrateDatabase();
railway_log($fresh ? 'Database created; first user is "' . $username . '".' : 'Database schema is up to date.');

$db = \Grocy\Services\DatabaseService::GetInstance()->GetDbConnection();
foreach ($db->users() as $user)
{
	if (password_verify('admin', $user->password))
	{
		$user->update(['password' => password_hash($password, PASSWORD_ARGON2ID)]);
		railway_log('User "' . $user->username . '" had the default password; replaced it with GROCY_ADMIN_PASSWORD.');
	}
}
// Grocy answers the first web request after an install or update with a bare redirect to
// "/" while it resets its view cache (app.php). Do that reset here, so a deployer's first
// login form post is processed instead of being dropped.
if (GROCY_MODE !== 'dev')
{
	$viewcache = $dataPath . '/viewcache';
	if (!is_dir($viewcache))
	{
		mkdir($viewcache, 0775, true);
	}
	$hashFile = $viewcache . '/' . hash('sha256', file_get_contents('/app/www/version.json') . GROCY_BASE_URL . GROCY_BASE_PATH) . '.txt';
	if (!file_exists($hashFile))
	{
		$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewcache, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
		foreach ($it as $f)
		{
			$f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
		}
		touch($hashFile);
		railway_log('View cache reset for this Grocy version.');
	}
}

railway_log('Bootstrap complete.');
