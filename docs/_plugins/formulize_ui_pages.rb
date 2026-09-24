# _plugins/formulize_ui_pages.rb
#
# Generates the Formulize UI reference: /documentation/formulize_ui/ and one
# page per section of the catalog under it.
#
# The catalog, modules/formulize/include/ui_catalog.php, is the one place the
# classes and tokens are documented; the in-app style guide renders the same
# catalog. So nothing here is written by hand: this runs the catalog with --json
# on every build and turns what it prints into pages. Nothing it produces is
# committed.
#
# It needs PHP. It uses php if it is installed, as it is on GitHub's runners,
# and otherwise the local development environment's web container (see
# docs/deploying_locally). Like the site's other generated feeds, it degrades
# rather than failing the deploy: without PHP the reference pages are left out
# of that build, with an error in the log (and a warning on the workflow run).
#
# The catalog is outside docs/, so `jekyll serve` doesn't rebuild when it
# changes. Restart it, or save any file in docs/, to see a change.

require "json"
require "open3"
require "cgi"
require "rouge"

module Jekyll
  module FormulizeUi
    CATALOG = File.join("modules", "formulize", "include", "ui_catalog.php").freeze
    CONTAINER = "formulize-web-1".freeze
    PAGES_DIR = File.join("documentation", "formulize_ui").freeze

    # The catalog as a hash, or nil when PHP can't be found or the catalog fails.
    def self.load_catalog
      repo_root = File.expand_path("../..", __dir__)
      commands = []
      commands << ["php", "-d", "xdebug.mode=off", File.join(repo_root, CATALOG), "--json"] if command_exists?("php")
      commands << ["docker", "exec", CONTAINER, "php", "-d", "xdebug.mode=off", File.join("/var/www/html", CATALOG), "--json"] if container_running?

      commands.each do |command|
        output, errors, status = Open3.capture3(*command)
        if status.success?
          return JSON.parse(output)
        end
        Jekyll.logger.warn "Formulize UI", "#{command.first} could not read the catalog: #{errors.strip}"
      end
      nil
    rescue JSON::ParserError => e
      Jekyll.logger.warn "Formulize UI", "The catalog did not print valid JSON: #{e.message}"
      nil
    end

    def self.command_exists?(name)
      ENV["PATH"].to_s.split(File::PATH_SEPARATOR).any? { |dir| File.executable?(File.join(dir, name)) }
    end

    def self.container_running?
      return false unless command_exists?("docker")
      output, _errors, status = Open3.capture3("docker", "ps", "--filter", "name=^#{CONTAINER}$", "--format", "{{.Names}}")
      status.success? && output.strip == CONTAINER
    end

    # Catalog text marks code the way Markdown does, in backticks. A short
    # name is kept from breaking at its hyphens; anything longer, which might
    # not fit on a phone, is left to wrap.
    def self.text(value)
      CGI.escapeHTML(value.to_s).gsub(/`([^`]+)`/) do
        code = Regexp.last_match(1)
        code.include?(" ") || code.length > 24 ? "<code>#{code}</code>" : %(<code class="formulize-ui-nobreak">#{code}</code>)
      end
    end

    # A class name, token or pattern, in code. It never breaks at a single
    # hyphen, but a long one can break after a comma in a pattern, or after the
    # -- or __ that starts a variation or a part, so the tables fit on a phone.
    def self.name(value)
      parts = CGI.escapeHTML(value.to_s).split(/(?<=,)|(?<=[a-z0-9}]--)|(?<=__)/).map { |part| "<span>#{part}</span>" }
      "<code class=\"formulize-ui-name\">#{parts.join('<wbr>')}</code>"
    end

    # A highlighted code block, marked up the way kramdown marks up a fenced
    # block, so the site's code styles apply to it.
    def self.code_block(code)
      code = code.to_s.rstrip
      language = if code.start_with?("<?php")
        "php"
      elsif code.lstrip.start_with?("<")
        "html"
      else
        "css"
      end
      lexer = ::Rouge::Lexer.find(language)
      formatted = ::Rouge::Formatters::HTML.new.format(lexer.lex(code))
      %(<div class="language-#{language} highlighter-rouge"><div class="highlight"><pre class="highlight"><code>#{formatted}</code></pre></div></div>)
    end

    # A section with its text turned into HTML, for the layout to arrange.
    def self.section_for_page(section)
      {
        "id" => section["id"],
        "title" => section["title"],
        "intro_html" => text(section["intro"]),
        "entries" => section["entries"].map do |entry|
          {
            "id" => entry["id"],
            "name" => entry["name"],
            "summary_html" => text(entry["summary"]),
            "tables" => [["classes", "Class", "What it does"], ["tokens", "Token", "Value"]].filter_map do |kind, heading, second|
              next if entry[kind].nil? || entry[kind].empty?
              {
                "heading" => heading,
                "second" => second,
                "rows" => entry[kind].map { |key, description| { "name_html" => name(key), "description_html" => text(description) } }
              }
            end,
            "notes_html" => (entry["notes"] || []).map { |note| text(note) },
            # a recipe's example is its result, which the code blocks produce;
            # there, the example's own markup isn't shown
            "example_html" => (entry["example"] && !entry["code"] ? code_block(entry["example"]) : nil),
            "code" => (entry["code"] || []).map { |block| { "label" => block["label"], "html" => code_block(block["code"]) } }
          }
        end
      }
    end
  end

  class FormulizeUiPage < PageWithoutAFile
    def initialize(site, dir, data)
      super(site, site.source, dir, "index.html")
      self.data.merge!(data)
      self.data["layout"] = "formulize-ui"
      self.content = ""
    end
  end

  class FormulizeUiPages < Generator
    safe true
    priority :normal

    def generate(site)
      catalog = FormulizeUi.load_catalog
      if catalog.nil?
        message = "Couldn't run modules/formulize/include/ui_catalog.php, so the Formulize UI reference pages were not built. Install PHP, or start the local development environment."
        Jekyll.logger.error "Formulize UI", message
        puts "::warning::#{message}" if ENV["GITHUB_ACTIONS"]
        return
      end

      overview, *sections = catalog["sections"].map { |section| FormulizeUi.section_for_page(section) }
      contents = sections.map do |section|
        { "title" => section["title"], "intro_html" => section["intro_html"], "url" => "/#{FormulizeUi::PAGES_DIR}/#{section['id']}/" }
      end
      common = { "formulize_ui_version" => catalog["version"], "formulize_ui_contents" => contents }

      site.pages << FormulizeUiPage.new(site, FormulizeUi::PAGES_DIR, common.merge(
        "title" => catalog["title"],
        "description" => "#{catalog['title']}: the classes and tokens for building markup in Formulize screens, templates and themes.",
        "section" => overview,
        "is_overview" => true
      ))

      sections.each_with_index do |section, index|
        site.pages << FormulizeUiPage.new(site, File.join(FormulizeUi::PAGES_DIR, section["id"]), common.merge(
          "title" => section["title"],
          "description" => "#{catalog['title']}: #{section['title'].downcase}.",
          "section" => section,
          "previous_section" => index > 0 ? contents[index - 1] : nil,
          "next_section" => contents[index + 1]
        ))
      end

      Jekyll.logger.info "Formulize UI", "Generated #{sections.size + 1} reference pages from the catalog."
    end
  end
end
