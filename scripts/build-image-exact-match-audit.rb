#!/usr/bin/env ruby
# Build a private audit for the authorized image batch before any product is published.
#
# Usage:
#   ruby scripts/build-image-exact-match-audit.rb [manifest.csv] [output.csv]

require 'csv'
require 'fileutils'

repo_root = File.expand_path('..', __dir__)
manifest_path = ARGV[0] || '/private/tmp/surtilec-authorized-image-batch-20260803/manifest.csv'
queue_path = File.join(repo_root, 'data/product-image-rights-queue.csv')
output_path = ARGV[1] || File.join(repo_root, 'data/product-image-exact-match-audit.csv')

abort("No existe el manifiesto: #{manifest_path}") unless File.file?(manifest_path)
abort("No existe la cola de imagenes: #{queue_path}") unless File.file?(queue_path)

manifest = CSV.read(manifest_path, headers: true)
queue = CSV.read(queue_path, headers: true, col_sep: ';')
queue_by_sku = queue.each_with_object({}) { |row, index| index[row['sku']] = row }

source_url_counts = Hash.new(0)
manifest.each do |row|
  source_url = queue_by_sku[row['sku']]&.[]('enlace_imagen_directa').to_s.strip
  source_url_counts[source_url] += 1 unless source_url.empty?
end

headers = %w[
  sku
  nombre_tecnico_seo
  image_file
  sha256
  image_match_status_original
  estado_auditoria
  accion_recomendada
  url_imagen_reutilizada_en_skus
  observaciones
]

rows = manifest.map do |row|
  sku = row['sku'].to_s.strip
  queue_row = queue_by_sku[sku] || {}
  source_url = queue_row['enlace_imagen_directa'].to_s.strip
  exact = row['image_match_status'].to_s.strip == 'coincidencia_exacta_revisada'
  reused_count = source_url.empty? ? 0 : source_url_counts[source_url]

  if exact
    audit_status = 'coincidencia_exacta_revisada'
    action = 'conservar_imagen_y_validar_antes_de_publicar'
  else
    audit_status = reused_count > 1 ? 'no_coincidencia_exacta_fuente_reutilizada' : 'coincidencia_exacta_no_demostrada'
    action = 'retirar_imagen_destacada_y_conservar_adjunto_en_revision'
  end

  [
    sku,
    queue_row['nombre_tecnico_seo'].to_s.strip,
    row['image_file'].to_s.strip,
    row['sha256'].to_s.strip,
    row['image_match_status'].to_s.strip,
    audit_status,
    action,
    reused_count,
    queue_row['observaciones'].to_s.strip,
  ]
end

FileUtils.mkdir_p(File.dirname(output_path))
CSV.open(output_path, 'w', write_headers: true, headers: headers) do |csv|
  rows.each { |row| csv << row }
end

counts = rows.group_by { |row| row[5] }.transform_values(&:length)
puts "Auditoria escrita: #{output_path}"
counts.each { |status, count| puts "#{status}: #{count}" }
