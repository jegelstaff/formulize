Jekyll::Hooks.register :site, :after_init do |site|
  require 'fileutils'

  puts "=== DEBUG: Plugin running ==="
  puts "Plugin file location: #{__FILE__}"

  source_file = File.expand_path('../../../writableFilesAndFolders.json', __FILE__)
  dest_file = File.join(site.source, '_data', 'writableFilesAndFolders.json')

  puts "Source file path: #{source_file}"
  puts "Dest file path: #{dest_file}"
  puts "Source exists? #{File.exist?(source_file)}"
  puts "Site source: #{site.source}"

  if File.exist?(source_file)
    FileUtils.mkdir_p(File.dirname(dest_file))
    FileUtils.cp(source_file, dest_file)
    puts "✓ Copied #{source_file} to #{dest_file}"
  else
    puts "✗ Warning: #{source_file} not found"
  end
end
