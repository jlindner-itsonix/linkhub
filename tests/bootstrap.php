<?php

// itsonix: braucht kein `composer install` — nur phpunit/phpunit selbst ist eine echte
// Composer-Dev-Abhaengigkeit (siehe composer.json). Die Modul- und Test-Klassen laden wir per
// simplem PSR-4-Autoloader von Hand, damit die Suite auch per Standalone-phpunit.phar laeuft
// (z.B. wenn `composer install` an einem Firewall/Registry-Problem scheitert).
if (is_file(__DIR__ . '/../vendor/autoload.php'))
{
	require_once __DIR__ . '/../vendor/autoload.php';
}

spl_autoload_register(function (string $class): void {
	$map = [
		'Itsonix\\LinkHub\\Tests\\' => __DIR__ . '/',
		'Itsonix\\LinkHub\\' => __DIR__ . '/../itsonix.linkhub/lib/',
	];
	foreach ($map as $prefix => $baseDir)
	{
		if (strncmp($class, $prefix, strlen($prefix)) !== 0)
		{
			continue;
		}
		$relative = substr($class, strlen($prefix));
		$file = $baseDir . str_replace('\\', '/', $relative) . '.php';
		if (is_file($file))
		{
			require_once $file;
		}
		return;
	}
});

// itsonix: Bitrix-Stubs stehen ausserhalb jeder PSR-4-Map (Namespace Bitrix\... statt
// Itsonix\LinkHub\Tests\..., bzw. globale Funktionen) — von Hand eingebunden.
foreach (glob(__DIR__ . '/Stubs/Bitrix*.php') as $stubFile)
{
	require_once $stubFile;
}
require_once __DIR__ . '/Stubs/CModule.php';
require_once __DIR__ . '/Stubs/bitrix_functions.php';
