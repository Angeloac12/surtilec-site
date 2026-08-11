#!/usr/bin/env ruby
# Pick one reference photograph per visual family.
#
# The family render is generated image-to-image from a real product photo, not
# from the family name — generating from the name alone produced constructions
# that did not match the product at all. This file decides which photo each
# family is generated from, and records how much that photo can be trusted.
#
# Preference order, best first:
#   1. fabricante  — the URL is on a manufacturer domain (Belden, Nexans,
#                    Southwire, Procables). The product is what it claims to be.
#   2. distribuidor — a Colombian distributor that carries the same factory line.
#   3. web         — a third-party retailer photo matched by the earlier Serper
#                    run. The match is fuzzy and its own note says "Revisar", so
#                    these must be eyeballed before the render is imported.
#
# Usage: ruby scripts/build-family-reference-picks.rb [ruta-resultado-imagenes]
# Writes: data/product-image-family-reference.csv

require 'csv'

repo_root = File.expand_path('..', __dir__)
results_path = ARGV[0] || File.expand_path(
  '~/Downloads/automatizacion_imagenes_woocommerce/resultado_imagenes.csv'
)
map_path = File.join(repo_root, 'data/product-image-family-map.csv')
out_path = File.join(repo_root, 'data/product-image-family-reference.csv')

abort "No existe el inventario de imágenes: #{results_path}" unless File.file?(results_path)
abort "No existe el mapa de familias: #{map_path}" unless File.file?(map_path)

FABRICANTE = /belden\.com|southwire\.com|nexans\.co|procables|centelsa|prysmian|generalcable|lapp|igus|helukabel/i
DISTRIBUIDOR = /ineldec|electroservimos|interelectricas|ingecomsas|nalelectricos/i

def tier(url)
  return 'fabricante' if url =~ FABRICANTE
  return 'distribuidor' if url =~ DISTRIBUIDOR

  'web'
end

RANK = { 'fabricante' => 0, 'distribuidor' => 1, 'web' => 2 }.freeze

results = CSV.read(results_path, headers: true, col_sep: ';', encoding: 'bom|utf-8')
by_sku = {}
results.each { |row| by_sku[row['sku'].to_s.strip] = row }

families = CSV.read(map_path, headers: true, encoding: 'UTF-8').group_by { |row| row['familia'] }

CSV.open(out_path, 'w', encoding: 'UTF-8') do |csv|
  csv << %w[
    familia familia_etiqueta productos confianza_referencia sku_referencia
    nombre_referencia url_imagen_referencia dominio estado_revision nota_origen
  ]

  families.sort_by { |_, rows| -rows.size }.each do |slug, rows|
    # No filter_map here: macOS ships Ruby 2.6 and it landed in 2.7.
    candidates = rows.map do |row|
      source = by_sku[row['sku'].to_s.strip]
      next nil if source.nil?

      url = source['url_imagen_fabricante'].to_s.strip
      next nil unless url.start_with?('http')

      { row: row, source: source, url: url, tier: tier(url) }
    end.compact

    if candidates.empty?
      csv << [slug, rows.first['familia_etiqueta'], rows.size, 'ninguna', '', '', '', '', 'buscar_en_internet', '']
      next
    end

    # Best tier wins; inside a tier prefer the product whose title is longest,
    # since a fuller title was matched against a fuller source title.
    pick = candidates.min_by { |c| [RANK[c[:tier]], -c[:row]['titulo'].to_s.length] }

    # A manufacturer photo is trusted; anything else has to be looked at.
    review = pick[:tier] == 'fabricante' ? 'listo' : 'revisar_antes_de_generar'

    csv << [
      slug,
      rows.first['familia_etiqueta'],
      rows.size,
      pick[:tier],
      pick[:row]['sku'],
      pick[:row]['titulo'],
      pick[:url],
      pick[:url][%r{//([^/]+)}, 1],
      review,
      pick[:source]['fuente_imagen'].to_s[0, 80],
    ]
  end
end

picks = CSV.read(out_path, headers: true, encoding: 'UTF-8')
by_tier = picks.group_by { |row| row['confianza_referencia'] }
puts "#{picks.size} familias → #{out_path}"
by_tier.sort_by { |_, rows| -rows.size }.each do |tier_name, rows|
  products = rows.sum { |row| row['productos'].to_i }
  puts "#{rows.size.to_s.rjust(3)} familias  #{products.to_s.rjust(5)} productos  #{tier_name}"
end
