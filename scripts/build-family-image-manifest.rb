#!/usr/bin/env ruby
# Build the upload batch for one visual family.
#
# Takes the reviewed family map and the single approved image for a family, and
# emits one manifest row plus one image file per SKU. The bytes are identical
# across the family on purpose — the point of the family policy — but each
# product gets its own attachment so it can carry its own alt text and its own
# image URL. A shared attachment would collapse 130 products onto whichever alt
# text was written last.
#
# Usage:
#   ruby scripts/build-family-image-manifest.rb <familia-slug> [lote]
#
# Reads:
#   data/product-image-family-map.csv   (Fase 1)
#   data/images/familia-<slug>.<ext>    (Fase 2, the approved image)
#   data/product-image-family-rights.csv (rights record per family)
# Writes:
#   /private/tmp/surtilec-family-image-batch-<lote>/<slug>/manifest.csv
#   /private/tmp/surtilec-family-image-batch-<lote>/<slug>/images/*

require 'csv'
require 'digest'
require 'fileutils'

MATCH_STATUS = 'imagen_de_familia_referencia'

repo_root = File.expand_path('..', __dir__)
family_slug = ARGV[0]
batch_id = ARGV[1] || Time.now.strftime('%Y%m%d')

abort 'Uso: ruby scripts/build-family-image-manifest.rb <familia-slug> [lote]' if family_slug.nil? || family_slug.empty?

map_path = File.join(repo_root, 'data/product-image-family-map.csv')
rights_path = File.join(repo_root, 'data/product-image-family-rights.csv')
abort "No existe el mapa de familias: #{map_path}" unless File.file?(map_path)
abort "No existe el registro de derechos: #{rights_path}" unless File.file?(rights_path)

rights = CSV.read(rights_path, headers: true, encoding: 'UTF-8')
              .find { |row| row['familia'] == family_slug }
abort "La familia '#{family_slug}' no tiene fila en #{rights_path}." if rights.nil?

unless %w[autorizada propia].include?(rights['estado_derechos'])
  abort "La familia '#{family_slug}' tiene estado_derechos='#{rights['estado_derechos']}'. " \
        'Solo se importan familias en autorizada o propia.'
end
if rights['referencia_derechos'].to_s.strip.empty?
  abort "La familia '#{family_slug}' no tiene referencia_derechos registrada."
end

source_image = Dir[File.join(repo_root, "data/images/familia-#{family_slug}.{webp,jpg,jpeg}")].first
abort "No existe la imagen de familia data/images/familia-#{family_slug}.(webp|jpg)" if source_image.nil?

extension = File.extname(source_image).downcase
rows = CSV.read(map_path, headers: true, encoding: 'UTF-8').select { |row| row['familia'] == family_slug }
abort "El mapa no tiene productos para la familia '#{family_slug}'." if rows.empty?

out_dir = File.join('/private/tmp', "surtilec-family-image-batch-#{batch_id}", family_slug)
images_dir = File.join(out_dir, 'images')
FileUtils.mkdir_p(images_dir)

digest = Digest::SHA256.file(source_image).hexdigest
family_label = rows.first['familia_etiqueta'].to_s.strip
family_label = family_slug if family_label.empty?

header = %w[sku image_file image_title alt_text source_url rights_status rights_reference image_match_status sha256]

CSV.open(File.join(out_dir, 'manifest.csv'), 'w', encoding: 'UTF-8') do |csv|
  csv << header

  rows.each do |row|
    sku = row['sku'].to_s.strip
    next if sku.empty?

    # The batch id is part of the filename so a re-shot family lands on new
    # attachments instead of silently reusing the previous batch's file, which
    # the importer matches on `_surtilec_batch_image_file`.
    image_file = "familia-#{family_slug}-#{batch_id}-#{sku}#{extension}"
    FileUtils.cp(source_image, File.join(images_dir, image_file))

    # Alt format from docs/image-rights-workflow.md. The brand segment is
    # dropped rather than faked when the product carries no brand term.
    brand = row['marca'].to_s.strip
    alt = if brand.empty?
            "#{row['titulo']}, referencia #{sku} - Surtilec"
          else
            "#{row['titulo']}, marca #{brand}, referencia #{sku} - Surtilec"
          end

    csv << [
      sku,
      image_file,
      "#{family_label} - Surtilec",
      alt,
      '', # source_url stays in the private rights register, never in the batch
      rights['estado_derechos'],
      rights['referencia_derechos'],
      MATCH_STATUS,
      digest,
    ]
  end
end

puts "Familia #{family_slug}: #{rows.size} productos"
puts "Imagen origen: #{source_image} (sha256 #{digest[0, 16]}…)"
puts "Lote: #{out_dir}"
