#!/usr/bin/env python3
"""Recolour a family image's outer jacket without regenerating it.

Seventy-seven published products name a colour in their title. Generating a
separate render per colour costs a credit each and risks drifting from the
construction; rotating the hue of the jacket keeps the geometry, the shading and
the strand detail byte-identical and only moves the colour.

The jacket is selected by hue plus a vertical cut-off, because the inner pairs
of a network cable repeat the jacket's colour and must not move with it.

Usage:
  python3 scripts/recolor-family-image.py <origen.webp> <destino.webp> \\
      --hue-min 170 --hue-max 260 --below 0.45 --target amarillo

Colours: negro blanco rojo azul verde amarillo gris naranja cafe violeta
"""

import argparse
import subprocess
import sys
import tempfile
from pathlib import Path

import numpy as np

# Target hue in degrees, plus saturation and value multipliers. Black and white
# are not hues, so they are handled by collapsing saturation instead.
TARGETS = {
    'rojo':     {'hue': 0.0,   'sat': 1.0,  'val': 1.0},
    'naranja':  {'hue': 25.0,  'sat': 1.0,  'val': 1.05},
    'amarillo': {'hue': 52.0,  'sat': 1.0,  'val': 1.15},
    'verde':    {'hue': 130.0, 'sat': 1.0,  'val': 0.95},
    'azul':     {'hue': 215.0, 'sat': 1.0,  'val': 1.0},
    'violeta':  {'hue': 280.0, 'sat': 1.0,  'val': 0.95},
    'cafe':     {'hue': 22.0,  'sat': 0.85, 'val': 0.55},
    'gris':     {'hue': 0.0,   'sat': 0.0,  'val': 0.75},
    'negro':    {'hue': 0.0,   'sat': 0.0,  'val': 0.22},
    'blanco':   {'hue': 0.0,   'sat': 0.0,  'val': 1.35},
}


def read_ppm(path):
    """Minimal binary PPM (P6) reader — avoids a Pillow dependency."""
    with open(path, 'rb') as handle:
        data = handle.read()

    fields = []
    offset = 0
    while len(fields) < 4:
        while data[offset:offset + 1].isspace():
            offset += 1
        if data[offset:offset + 1] == b'#':
            while data[offset:offset + 1] not in (b'\n', b''):
                offset += 1
            continue
        start = offset
        while not data[offset:offset + 1].isspace():
            offset += 1
        fields.append(data[start:offset])

    if fields[0] != b'P6':
        raise ValueError('El decodificador solo acepta PPM binario (P6)')

    width, height = int(fields[1]), int(fields[2])
    offset += 1  # single whitespace byte after maxval
    pixels = np.frombuffer(data[offset:offset + width * height * 3], dtype=np.uint8)
    return pixels.reshape(height, width, 3)


def write_ppm(path, array):
    with open(path, 'wb') as handle:
        handle.write(b'P6\n%d %d\n255\n' % (array.shape[1], array.shape[0]))
        handle.write(array.astype(np.uint8).tobytes())


def rgb_to_hsv(rgb):
    rgb = rgb.astype(np.float32) / 255.0
    maxc = rgb.max(axis=-1)
    minc = rgb.min(axis=-1)
    span = maxc - minc

    hue = np.zeros_like(maxc)
    nonzero = span > 1e-6
    red, green, blue = rgb[..., 0], rgb[..., 1], rgb[..., 2]

    mask = nonzero & (maxc == red)
    hue[mask] = (60.0 * ((green[mask] - blue[mask]) / span[mask])) % 360.0
    mask = nonzero & (maxc == green)
    hue[mask] = 60.0 * ((blue[mask] - red[mask]) / span[mask]) + 120.0
    mask = nonzero & (maxc == blue)
    hue[mask] = 60.0 * ((red[mask] - green[mask]) / span[mask]) + 240.0

    sat = np.zeros_like(maxc)
    sat[maxc > 1e-6] = span[maxc > 1e-6] / maxc[maxc > 1e-6]
    return hue, sat, maxc


def hsv_to_rgb(hue, sat, val):
    hue = np.mod(hue, 360.0) / 60.0
    sector = np.floor(hue).astype(np.int32) % 6
    frac = hue - np.floor(hue)

    p = val * (1.0 - sat)
    q = val * (1.0 - sat * frac)
    t = val * (1.0 - sat * (1.0 - frac))

    options = [
        (val, t, p), (q, val, p), (p, val, t),
        (p, q, val), (t, p, val), (val, p, q),
    ]
    out = np.zeros(hue.shape + (3,), dtype=np.float32)
    for index, (r, g, b) in enumerate(options):
        mask = sector == index
        out[mask, 0], out[mask, 1], out[mask, 2] = r[mask], g[mask], b[mask]
    return np.clip(out * 255.0, 0, 255)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('source')
    parser.add_argument('dest')
    parser.add_argument('--target', required=True, choices=sorted(TARGETS))
    parser.add_argument('--hue-min', type=float, default=170.0)
    parser.add_argument('--hue-max', type=float, default=260.0)
    parser.add_argument('--min-sat', type=float, default=0.25)
    parser.add_argument('--below', type=float, default=0.0,
                        help='Only touch pixels below this fraction of the height '
                             '(0 = whole image). Protects inner conductors that '
                             'repeat the jacket colour.')
    parser.add_argument('--quality', type=int, default=85)
    args = parser.parse_args()

    with tempfile.TemporaryDirectory() as tmp:
        raw = Path(tmp) / 'in.ppm'
        out = Path(tmp) / 'out.ppm'
        subprocess.run(['dwebp', args.source, '-ppm', '-o', str(raw)],
                       check=True, capture_output=True)

        rgb = read_ppm(raw)
        hue, sat, val = rgb_to_hsv(rgb)

        mask = (hue >= args.hue_min) & (hue <= args.hue_max) & (sat >= args.min_sat)
        if args.below > 0:
            rows = np.arange(rgb.shape[0])[:, None] >= int(rgb.shape[0] * args.below)
            mask &= rows

        if not mask.any():
            sys.exit(f'Ningun pixel coincide con el rango de tono {args.hue_min}-{args.hue_max}.')

        spec = TARGETS[args.target]
        hue = hue.copy()
        sat = sat.copy()
        val = val.copy()
        hue[mask] = spec['hue']
        sat[mask] = np.clip(sat[mask] * spec['sat'], 0.0, 1.0)
        val[mask] = np.clip(val[mask] * spec['val'], 0.0, 1.0)

        write_ppm(out, hsv_to_rgb(hue, sat, val))
        subprocess.run(['cwebp', '-q', str(args.quality), '-quiet', str(out),
                        '-o', args.dest], check=True, capture_output=True)

    share = 100.0 * mask.sum() / mask.size
    print(f'{args.dest}  color={args.target}  pixeles cambiados={share:.1f}%')


if __name__ == '__main__':
    main()
