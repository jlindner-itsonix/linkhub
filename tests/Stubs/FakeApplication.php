<?php

namespace Itsonix\LinkHub\Tests\Stubs;

// itsonix: Stand-in fuer die globale $APPLICATION (CMain) — EventHandler::onProlog() ruft nur
// AddHeadString() auf, hier eingesammelt statt ins echte Bitrix-Head geschrieben.
class FakeApplication
{
	public array $headStrings = [];

	public function AddHeadString($html, $double = false): void
	{
		$this->headStrings[] = $html;
	}
}
