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

# Colour variants, when they exist, beat the base family image. A title that
# promises "naranja" and a photo showing blue is the one case the "imagen de
# referencia" note does not excuse, so those products get their own file.
COLOURS = {
  'negro'    => /\bnegro\b|\bblack\b|\bBLK\b/i,
  'blanco'   => /\bblanco\b|\bwhite\b|\bWHT\b/i,
  'rojo'     => /\brojo\b|\bred\b/i,
  'azul'     => /\bazul\b|\bblue\b/i,
  'verde'    => /\bverde\b|\bgreen\b|\bGRN\b/i,
  'amarillo' => /\bamarillo\b|\byellow\b/i,
  'gris'     => /\bgris\b|\bgray\b|\bgrey\b|\bGRY\b/i,
  'naranja'  => /\bnaranja\b|\borange\b/i,
  'cafe'     => /\bcaf[eé]\b|\bbrown\b/i,
  'violeta'  => /\bvioleta\b|\bpurple\b/i,
}.freeze

def colour_image(repo_root, family_slug, title)
  hit = COLOURS.find { |_, pattern| title =~ pattern }
  return [nil, nil] if hit.nil?

  path = Dir[File.join(repo_root, "data/images/familia-#{family_slug}--#{hit[0]}.{webp,jpg,jpeg}")].first
  path ? [path, hit[0]] : [nil, nil]
end
rows = CSV.read(map_path, headers: true, encoding: 'UTF-8').select { |row| row['familia'] == family_slug }
abort "El mapa no tiene productos para la familia '#{family_slug}'." if rows.empty?

out_dir = File.join('/private/tmp', "surtilec-family-image-batch-#{batch_id}", family_slug)
images_dir = File.join(out_dir, 'images')
FileUtils.mkdir_p(images_dir)

family_label = rows.first['familia_etiqueta'].to_s.strip
family_label = family_slug if family_label.empty?
colour_used = Hash.new(0)
digests = {}

header = %w[sku image_file image_title alt_text source_url rights_status rights_reference image_match_status sha256]

CSV.open(File.join(out_dir, 'manifest.csv'), 'w', encoding: 'UTF-8') do |csv|
  csv << header

  rows.each do |row|
    sku = row['sku'].to_s.strip
    next if sku.empty?

    variant_path, variant_colour = colour_image(repo_root, family_slug, row['titulo'].to_s)
    chosen = variant_path || source_image

    # The batch id is part of the filename so a re-shot family lands on new
    # attachments instead of silently reusing the previous batch's file, which
    # the importer matches on `_surtilec_batch_image_file`.
    image_file = "familia-#{family_slug}-#{batch_id}-#{sku}#{File.extname(chosen).downcase}"
    FileUtils.cp(chosen, File.join(images_dir, image_file))
    colour_used[variant_colour] += 1 if variant_colour

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
      digests[chosen] ||= Digest::SHA256.file(chosen).hexdigest,
    ]
  end
end

puts "Familia #{family_slug}: #{rows.size} productos"
puts "Imagen base: #{File.basename(source_image)}"
unless colour_used.empty?
  puts "Variantes de color: " + colour_used.sort_by { |_, n| -n }
                                           .map { |c, n| "#{c}=#{n}" }.join(', ')
end
puts "Lote: #{out_dir}"
