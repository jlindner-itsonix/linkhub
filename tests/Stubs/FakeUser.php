<?php

namespace Itsonix\LinkHub\Tests\Stubs;

// itsonix: Stand-in fuer die globale $USER (CUser) — install/index.php prueft nur IsAdmin().
class FakeUser
{
	private bool $isAdmin;

	public function __construct(bool $isAdmin)
	{
		$this->isAdmin = $isAdmin;
	}

	public function IsAdmin(): bool
	{
		return $this->isAdmin;
	}
}
