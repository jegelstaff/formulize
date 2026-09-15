# Copies the canonical writableFilesAndFolders.json from the repository root
# into _data, so the installation docs can list the folders that need to be
# writable without that list being maintained in two places.
#
# Reports through Jekyll's own logger rather than puts, so it sits alongside
# the other plugins' output at the right verbosity instead of printing a debug
# block on every build.
Jekyll::Hooks.register :site, :after_init do |site|
  require "fileutils"

  source_file = File.expand_path("../../../writableFilesAndFolders.json", __FILE__)
  dest_file = File.join(site.source, "_data", "writableFilesAndFolders.json")

  if File.exist?(source_file)
    FileUtils.mkdir_p(File.dirname(dest_file))
    FileUtils.cp(source_file, dest_file)
    Jekyll.logger.debug "Writable folders", "Copied #{source_file} into _data."
  else
    Jekyll.logger.warn "Writable folders",
      "#{source_file} not found - the writable folders list in the install docs will be empty."
  end
end
