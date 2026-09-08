#!/bin/sh
# itsonix: baut aus dem Modul-Quellordner itsonix.linkhub/ das installierbare
# itsonix.linkhub.tar.gz — Bitrix erwartet für "Modul aus Datei installieren"
# (Marketplace > Lokale Module) ein tar.gz mit dem Modul-Ordner im Archiv-Root.
set -eu
cd "$(dirname "$0")"

rm -f itsonix.linkhub.tar.gz
tar -czf itsonix.linkhub.tar.gz itsonix.linkhub

echo "Erstellt: $(pwd)/itsonix.linkhub.tar.gz"
