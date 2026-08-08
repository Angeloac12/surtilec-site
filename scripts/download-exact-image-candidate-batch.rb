#!/usr/bin/env ruby
# Download exact image candidates from the private source register and build a
# standard manifest accepted by import-authorized-product-images.php.
#
# Usage:
#   ruby scripts/download-exact-image-candidate-batch.rb [candidates.csv] [current-manifest.csv] [output-dir]

require 'csv'
require 'digest'
require 'fileutils'
require 'open3'
require 'thread'

repo_root = File.expand_path('..', __dir__)
candidates_path = ARGV[0] || File.join(repo_root, 'data/product-exact-image-candidates.csv')
current_manifest_path = ARGV[1] || '/private/tmp/surtilec-authorized-image-batch-20260803/manifest.csv'
output_dir = ARGV[2] || '/private/tmp/surtilec-exact-image-batch-20260804'

abort "No existe la cola de candidatos: #{candidates_path}" unless File.file?(candidates_path)
abort "No existe el manifiesto actual: #{current_manifest_path}" unless File.file?(current_manifest_path)

def utf8(value)
  value.to_s.encode('UTF-8', invalid: :replace, undef: :replace, replace: '')
end

def slugify(value)
  text = utf8(value).unicode_normalize(:nfkd).gsub(/\p{Mn}/, '')
  text.downcase.gsub(/[^a-z0-9]+/, '-').gsub(/\A-|-\z/, '')
end

def csv_rows(path)
  CSV.read(path, headers: true, encoding: 'UTF-8')
end

candidates = csv_rows(candidates_path)
current = csv_rows(current_manifest_path).each_with_object({}) { |row, index| index[row['sku']] = row }
expected_status = 'descargar_y_asignar_imagen_exacta'
rows = candidates.select { |row| row['accion_recomendada'] == expected_status }

FileUtils.rm_rf(output_dir)
images_dir = File.join(output_dir, 'images')
FileUtils.mkdir_p(images_dir)

errors = []
manifest_rows = []

jobs = Queue.new
rows.each_with_index { |row, index| jobs << [index, row] }
results = Queue.new
worker_count = [[Integer(ENV.fetch('SURTILEC_IMAGE_WORKERS', '8')), 1].max, 12].min

workers = worker_count.times.map do
  Thread.new do
    loop do
      index, row = jobs.pop(true)
      url = row['imagen_url_fuente'].to_s.strip
      unless url.match?(%r{\Ahttps://})
        results << [index, nil, "#{row['sku']}: URL de imagen exacta ausente o insegura"]
        next
      end

      base = "surtilec-#{slugify(row['nombre_surtilec'])[0, 90]}-#{slugify(row['sku'])}"
      raw_path = File.join(output_dir, "#{base}.source")
      command = [
        'curl', '-fL', '--retry', '1', '--connect-timeout', '10', '--max-time', '25',
        '-sS', '-o', raw_path, url,
      ]
      _stdout, stderr, status = Open3.capture3(*command)
      unless status.success? && File.file?(raw_path) && File.size(raw_path).positive?
        results << [index, nil, "#{row['sku']}: descarga fallida (#{stderr.to_s.strip[0, 180]})"]
        FileUtils.rm_f(raw_path)
        next
      end

      mime_output, = Open3.capture2('file', '--mime-type', '-b', raw_path)
      mime = mime_output.strip
      output_file = case mime
                    when 'image/webp' then "#{base}.webp"
                    when 'image/jpeg' then "#{base}.jpg"
                    when 'image/png' then "#{base}.webp"
                    else nil
                    end
      unless output_file
        results << [index, nil, "#{row['sku']}: tipo no admitido #{mime.inspect}"]
        FileUtils.rm_f(raw_path)
        next
      end

      destination = File.join(images_dir, output_file)
      if mime == 'image/png'
        ok = system('cwebp', '-quiet', '-q', '85', raw_path, '-o', destination)
        unless ok
          results << [index, nil, "#{row['sku']}: no se pudo convertir PNG"]
          FileUtils.rm_f(raw_path)
          next
        end
      else
        FileUtils.mv(raw_path, destination)
      end
      FileUtils.rm_f(raw_path)
      unless File.file?(destination)
        results << [index, nil, "#{row['sku']}: no se genero el archivo final"]
        next
      end

      results << [
        index,
        {
          'sku' => row['sku'],
          'image_file' => output_file,
          'image_title' => "#{row['nombre_surtilec']} - Surtilec",
          'alt_text' => "#{row['nombre_surtilec']}, imagen de producto - Surtilec",
          'source_url' => url,
          'rights_status' => 'autorizada',
          'rights_reference' => 'confirmed-by-surtilec-2026-08-03',
          'image_match_status' => 'coincidencia_exacta_revisada',
          'sha256' => Digest::SHA256.file(destination).hexdigest,
        },
        nil,
      ]
    rescue ThreadError
      break
    end
  end
end

workers.each(&:join)
until results.empty?
  _index, row, error = results.pop(true)
  manifest_rows << row if row
  errors << error if error
end
manifest_rows.compact!

unless errors.empty?
  File.write(File.join(output_dir, 'errors.txt'), errors.sort.join("\n") + "\n")
  abort "Descargas incompletas: #{errors.length}. Revisa #{File.join(output_dir, 'errors.txt')}"
end

manifest_path = File.join(output_dir, 'manifest.csv')
header = manifest_rows.first.keys
CSV.open(manifest_path, 'w', encoding: 'UTF-8', force_quotes: true) do |csv|
  csv << header
  manifest_rows.each { |row| csv << header.map { |field| row[field] } }
end

puts "products=#{manifest_rows.length}"
puts "images=#{Dir[File.join(images_dir, '*')].length}"
puts "manifest=#{manifest_path}"
