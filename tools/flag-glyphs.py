#!/usr/bin/env python3
"""
Erzeugt inc/flag-glyphs.php (Ziffern 0 bis 9 und Punkt aus Cera Pro Bold) und
assets/flag.svg (CSD-Grafik ohne festes Datum, mit Platzhalter).

Nur nötig, wenn sich die Grafik selbst ändert. Für ein neues Datum reicht das
Feld "Datum in der Grafik" im Hero-Block, dafür muss hier niemand ran.

Aufruf: python3 tools/flag-glyphs.py [Pfad/zu/Cera-Pro-Bold.otf] [Quell-SVG]
Braucht fonttools (pip install fonttools). Die Schriftdatei kommt nicht ins
Repo (Lizenz), im Theme landen nur die Umrisse von elf Zeichen.
"""
import os, re, sys
from fontTools.ttLib import TTFont
from fontTools.pens.svgPathPen import SVGPathPen

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)
font_path = sys.argv[1] if len(sys.argv) > 1 else os.path.expanduser('~/Library/Fonts/Cera-Pro-Bold.otf')
src_svg = sys.argv[2] if len(sys.argv) > 2 else os.path.join(ROOT, 'assets', 'flag.svg')

font = TTFont(font_path)
glyphs = font.getGlyphSet()
cmap = font.getBestCmap()

rows = []
for ch in '0123456789.':
    g = glyphs[cmap[ord(ch)]]
    pen = SVGPathPen(glyphs)
    g.draw(pen)
    rows.append("\t'%s' => array( 'd' => '%s', 'w' => %d )," % (ch, pen.getCommands(), g.width))

php = """<?php
/**
 * Umrisse der Ziffern 0 bis 9 und des Punkts aus Cera Pro Bold, für das Datum
 * in der CSD-Grafik. Automatisch erzeugt von tools/flag-glyphs.py, bitte nicht
 * von Hand ändern. Einheiten: Schrift-Einheiten (1000 pro em), y nach oben.
 *
 * @package csd-darmstadt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
%s
);
""" % '\n'.join(rows)
open(os.path.join(ROOT, 'inc', 'flag-glyphs.php'), 'w').write(php)

# Datums-Glyphen aus der Quell-SVG nehmen und durch den Platzhalter ersetzen
svg = open(src_svg).read()
pat = re.compile(r'\s*<path d="[^"]+" transform="translate\([\d.]+,(?:410\.8450|523\.3450)\) scale\(0\.117859,-0\.117859\)" style="fill:white;fill-rule:nonzero;" />')
found = pat.findall(svg)
if found:
    first = pat.search(svg)
    svg = pat.sub('', svg)
    svg = svg[:first.start()] + '<g id="csd-flag-date"></g>' + svg[first.start():]
    open(os.path.join(ROOT, 'assets', 'flag.svg'), 'w').write(svg)
    print('flag.svg: %d Datums-Glyphen durch Platzhalter ersetzt' % len(found))
elif '<g id="csd-flag-date"></g>' in svg:
    print('flag.svg hat schon einen Platzhalter, bleibt wie sie ist')
else:
    sys.exit('Weder Datums-Glyphen noch Platzhalter in der SVG gefunden')
print('inc/flag-glyphs.php geschrieben')
