#!/bin/sh
# itsonix: baut aus dem Modul-Quellordner itsonix.linkhub/ das installierbare
# itsonix.linkhub.tar.gz — Bitrix erwartet für "Modul aus Datei installieren"
# (Marketplace > Lokale Module) ein tar.gz mit dem Modul-Ordner im Archiv-Root.
set -eu
cd "$(dirname "$0")"

rm -f itsonix.linkhub.tar.gz
find itsonix.linkhub -name '.DS_Store' -delete
# itsonix: COPYFILE_DISABLE unterdrueckt macOS' AppleDouble-Metadaten (._*-Dateien), die bsdtar
# sonst pro Datei mit ins Archiv packt — sonst landen die auch auf der Ziel-Bitrix-Box.
COPYFILE_DISABLE=1 tar -czf itsonix.linkhub.tar.gz itsonix.linkhub

echo "Erstellt: $(pwd)/itsonix.linkhub.tar.gz"
