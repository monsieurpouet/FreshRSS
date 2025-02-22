<?php

define('BCRYPT_COST', 9);

Minz_Configuration::register('default_system', join_path(FRESHRSS_PATH, 'config.default.php'));
Minz_Configuration::register('default_user', join_path(FRESHRSS_PATH, 'config-user.default.php'));

function checkRequirements($dbType = '') {
	$php = version_compare(PHP_VERSION, '5.6.0') >= 0;
	$minz = file_exists(join_path(LIB_PATH, 'Minz'));
	$curl = extension_loaded('curl');
	$pdo_mysql = extension_loaded('pdo_mysql');
	$pdo_sqlite = extension_loaded('pdo_sqlite');
	$pdo_pgsql = extension_loaded('pdo_pgsql');
	$message = '';
	switch ($dbType) {
		case 'mysql':
			$pdo_sqlite = $pdo_pgsql = true;
			$pdo = $pdo_mysql;
			break;
		case 'sqlite':
			$pdo_mysql = $pdo_pgsql = true;
			$pdo = $pdo_sqlite;
			break;
		case 'pgsql':
			$pdo_mysql = $pdo_sqlite = true;
			$pdo = $pdo_pgsql;
			break;
		case '':
			$pdo = $pdo_mysql || $pdo_sqlite || $pdo_pgsql;
			break;
		default:
			$pdo_mysql = $pdo_sqlite = $pdo_pgsql = true;
			$pdo = false;
			$message = 'Invalid database type!';
			break;
	}
	$pcre = extension_loaded('pcre');
	$ctype = extension_loaded('ctype');
	$fileinfo = extension_loaded('fileinfo');
	$dom = class_exists('DOMDocument');
	$xml = function_exists('xml_parser_create');
	$json = function_exists('json_encode');
	$mbstring = extension_loaded('mbstring');
	$data = DATA_PATH && is_writable(DATA_PATH);
	$cache = CACHE_PATH && is_writable(CACHE_PATH);
	$users = USERS_PATH && is_writable(USERS_PATH);
	$favicons = is_writable(join_path(DATA_PATH, 'favicons'));
	$http_referer = is_referer_from_same_domain();

	return array(
		'php' => $php ? 'ok' : 'ko',
		'minz' => $minz ? 'ok' : 'ko',
		'curl' => $curl ? 'ok' : 'ko',
		'pdo-mysql' => $pdo_mysql ? 'ok' : 'ko',
		'pdo-sqlite' => $pdo_sqlite ? 'ok' : 'ko',
		'pdo-pgsql' => $pdo_pgsql ? 'ok' : 'ko',
		'pdo' => $pdo ? 'ok' : 'ko',
		'pcre' => $pcre ? 'ok' : 'ko',
		'ctype' => $ctype ? 'ok' : 'ko',
		'fileinfo' => $fileinfo ? 'ok' : 'ko',
		'dom' => $dom ? 'ok' : 'ko',
		'xml' => $xml ? 'ok' : 'ko',
		'json' => $json ? 'ok' : 'ko',
		'mbstring' => $mbstring ? 'ok' : 'ko',
		'data' => $data ? 'ok' : 'ko',
		'cache' => $cache ? 'ok' : 'ko',
		'users' => $users ? 'ok' : 'ko',
		'favicons' => $favicons ? 'ok' : 'ko',
		'http_referer' => $http_referer ? 'ok' : 'ko',
		'message' => $message ?: 'ok',
		'all' => $php && $minz && $curl && $pdo && $pcre && $ctype && $dom && $xml &&
		         $data && $cache && $users && $favicons && $http_referer && $message == '' ? 'ok' : 'ko'
	);
}

function generateSalt() {
	return sha1(uniqid(mt_rand(), true).implode('', stat(__FILE__)));
}

function checkDb() {
	$conf = FreshRSS_Context::$system_conf;
	$db = $conf->db;
	if (empty($db['pdo_options'])) {
		$db['pdo_options'] = [];
	}
	$db['pdo_options'][PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
	$dbBase = isset($db['base']) ? $db['base'] : '';

	$db['base'] = '';	//First connection without database name to create the database
	Minz_ModelPdo::$usesSharedPdo = false;
	$databaseDAO = FreshRSS_Factory::createDatabaseDAO();
	$databaseDAO->create();

	$db['base'] = $dbBase;	//New connection with the database name
	$databaseDAO = FreshRSS_Factory::createDatabaseDAO();
	Minz_ModelPdo::$usesSharedPdo = true;
	return $databaseDAO->testConnection();
}

function deleteInstall() {
	$path = join_path(DATA_PATH, 'do-install.txt');
	@unlink($path);
	return !file_exists($path);
}
