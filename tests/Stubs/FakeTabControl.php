<?php

namespace Itsonix\LinkHub\Tests\Stubs;

// itsonix: Stand-in fuer Bitrix' globale CAdminTabControl — ui/options_view.php ruft nur
// BeginNextTab()/Buttons()/End() auf, hier als reine No-Ops statt echtem Tab-HTML.
class FakeTabControl
{
	public function BeginNextTab(): void
	{
	}

	public function Buttons(): void
	{
	}

	public function End(): void
	{
	}
}
