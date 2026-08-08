#!/usr/bin/env ruby
# Keep only downloaded images whose file hash belongs to one exact product
# title. Shared hashes across different variants stay blocked.
#
# Usage:
#   ruby scripts/build-verified-exact-image-manifest.rb \
#     [candidates.csv] [downloaded-manifest.csv] [verified-manifest.csv] [review.csv] [current-manifest.csv]

require 'csv'
require 'fileutils'

repo_root = File.expand_path('..', __dir__)
candidates_path = ARGV[0] || File.join(repo_root, 'data/product-exact-image-candidates.csv')
downloaded_path = ARGV[1] || '/private/tmp/surtilec-exact-image-batch-20260804/manifest.csv'
verified_path = ARGV[2] || '/private/tmp/surtilec-exact-image-batch-20260804/verified-upload/manifest.csv'
review_path = ARGV[3] || File.join(repo_root, 'data/product-exact-image-verification.csv')
current_manifest_path = ARGV[4] || '/private/tmp/surtilec-authorized-image-batch-20260803/manifest.csv'

abort "No existe la cola: #{candidates_path}" unless File.file?(candidates_path)
abort "No existe el manifiesto descargado: #{downloaded_path}" unless File.file?(downloaded_path)
abort "No existe el manifiesto anterior: #{current_manifest_path}" unless File.file?(current_manifest_path)

candidates = CSV.read(candidates_path, headers: true, encoding: 'UTF-8').each_with_object({}) do |row, index|
  index[row['sku']] = row
end
downloaded = CSV.read(downloaded_path, headers: true, encoding: 'UTF-8')
current_exact = CSV.read(current_manifest_path, headers: true, encoding: 'UTF-8').select do |row|
  row['image_match_status'] == 'coincidencia_exacta_revisada'
end
by_hash = downloaded.group_by { |row| row['sha256'] }
legacy_names_by_hash = current_exact.each_with_object(Hash.new { |hash, key| hash[key] = [] }) do |row, index|
  title = row['image_title'].to_s.sub(/\s+-\s+Surtilec\z/, '').strip
  index[row['sha256']] << title unless title.empty?
end

verified = []
review = []

downloaded.each do |row|
  candidate = candidates[row['sku']]
  title = candidate ? candidate['nombre_surtilec'].to_s.strip : ''
  names = by_hash[row['sha256']].map { |item| candidates[item['sku']]&.[]('nombre_surtilec').to_s.strip }
  names.concat(legacy_names_by_hash[row['sha256']])
  names = names.reject(&:empty?).uniq
  safe = names == [title] && !title.empty?
  state = safe ? 'coincidencia_exacta_hash_unico' : 'fuente_visual_reutilizada_entre_variantes'
  action = safe ? 'asignar_imagen' : 'mantener_sin_miniatura'

  review << [
    row['sku'],
    title,
    row['image_file'],
    row['sha256'],
    names.join(' | '),
    state,
    action,
  ]
  verified << row if safe
end

FileUtils.mkdir_p(File.dirname(verified_path))
verified_images_dir = File.join(File.dirname(verified_path), 'images')
downloaded_images_dir = File.join(File.dirname(downloaded_path), 'images')
FileUtils.rm_rf(verified_images_dir)
FileUtils.mkdir_p(verified_images_dir)
verified.each do |row|
  source = File.join(downloaded_images_dir, row['image_file'])
  destination = File.join(verified_images_dir, row['image_file'])
  abort "No existe la imagen descargada: #{source}" unless File.file?(source)
  FileUtils.cp(source, destination)
end

header = downloaded.first.headers
CSV.open(verified_path, 'w', encoding: 'UTF-8', force_quotes: true) do |csv|
  csv << header
  verified.each { |row| csv << header.map { |field| row[field] } }
end

FileUtils.mkdir_p(File.dirname(review_path))
CSV.open(review_path, 'w', encoding: 'UTF-8', force_quotes: true) do |csv|
  csv << %w[sku nombre_surtilec image_file sha256 variantes_que_comparten_hash estado_verificacion accion]
  review.each { |row| csv << row }
end

puts "descargadas=#{downloaded.length}"
puts "verificadas=#{verified.length}"
puts "bloqueadas=#{downloaded.length - verified.length}"
puts "manifest=#{verified_path}"
puts "review=#{review_path}"
