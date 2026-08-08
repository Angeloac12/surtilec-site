#!/usr/bin/env ruby
# Build a per-product image batch from the literal image URLs in the queue.
# The source URL stays in this private manifest and is never written to WP.

require 'csv'
require 'digest'
require 'fileutils'
require 'open3'

queue_path = ARGV[0] || 'data/product-image-rights-queue.csv'
candidate_dir = ARGV[1] || '/private/tmp/surtilec-image-candidates-20260803'
output_dir = ARGV[2] || '/private/tmp/surtilec-authorized-image-batch-20260803'

def utf8(value)
  value.to_s.encode('UTF-8', invalid: :replace, undef: :replace, replace: '')
end

def slugify(value)
  text = utf8(value).unicode_normalize(:nfkd).gsub(/\p{Mn}/, '')
  text.downcase.gsub(/[^a-z0-9]+/, '-').gsub(/\A-|\z/, '')
end

rows = CSV.read(queue_path, col_sep: ';', encoding: 'macRoman:UTF-8')
data_rows = rows[2..] || []
abort 'Queue is empty or missing its data header.' if data_rows.empty?

candidate_manifest = {}
manifest_path = File.join(candidate_dir, 'manifest.tsv')
File.foreach(manifest_path, encoding: 'UTF-8') do |line|
  parts = line.chomp.split("\t", 4)
  next unless parts.length == 4
  filename, skus, _names, url = parts
  skus.split('|').each { |sku| candidate_manifest[sku] = [filename, url] }
end

FileUtils.rm_rf(output_dir)
images_dir = File.join(output_dir, 'images')
FileUtils.mkdir_p(images_dir)

batch_rows = []
errors = []
data_rows.each_with_index do |row, index|
  sku = utf8(row[0]).strip
  title = utf8(row[1]).strip
  source = candidate_manifest[sku]
  unless source
    errors << "fila #{index + 3}: no hay imagen descargada para #{sku}"
    next
  end

  source_file = File.join(candidate_dir, source[0])
  unless File.file?(source_file)
    errors << "fila #{index + 3}: falta #{source_file}"
    next
  end

  mime_output, = Open3.capture2('file', '--mime-type', '-b', source_file)
  mime = mime_output.strip
  base = "surtilec-#{slugify(title)[0, 90]}-#{slugify(sku)}"
  output_file = if mime == 'image/png'
                  "#{base}.webp"
                elsif mime == 'image/webp'
                  "#{base}.webp"
                elsif mime == 'image/jpeg'
                  "#{base}.jpg"
                else
                  errors << "fila #{index + 3}: formato no admitido #{mime.inspect} para #{sku}"
                  next
                end
  destination = File.join(images_dir, output_file)

  if mime == 'image/png'
    ok = system('cwebp', '-quiet', '-q', '85', source_file, '-o', destination)
    errors << "fila #{index + 3}: no se pudo convertir PNG para #{sku}" unless ok
  else
    FileUtils.cp(source_file, destination)
  end
  next unless File.file?(destination)

  match_status = if utf8(row[11]).downcase.include?('coincidencia exacta')
                   'coincidencia_exacta_revisada'
                 elsif utf8(row[11]).downcase.include?('reemplazar') || utf8(row[11]).downcase.include?('verificar')
                   'revisar_variante_antes_de_publicar'
                 else
                   'revisar_antes_de_publicar'
                 end

  batch_rows << {
    'sku' => sku,
    'image_file' => output_file,
    'image_title' => "#{title} - Surtilec",
    'alt_text' => "#{title}, imagen de producto - Surtilec",
    'source_url' => source[1],
    'rights_status' => 'autorizada',
    'rights_reference' => 'confirmed-by-surtilec-2026-08-03',
    'image_match_status' => match_status,
    'sha256' => Digest::SHA256.file(destination).hexdigest,
  }
end

abort errors.join("\n") unless errors.empty?

header = batch_rows.first.keys
CSV.open(File.join(output_dir, 'manifest.csv'), 'w', encoding: 'UTF-8', force_quotes: true) do |csv|
  csv << header
  batch_rows.each { |row| csv << header.map { |field| row[field] } }
end

puts "products=#{batch_rows.length}"
puts "images=#{batch_rows.map { |row| row['image_file'] }.uniq.length}"
puts "output=#{output_dir}"
