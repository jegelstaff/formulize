# _plugins/mcp_tool_pages.rb
#
# Generates one page per MCP tool (/ai/mcp-reference/tools/<name>/) plus the
# standard/admin grouping used by the tools list on docs/AI-mcp-reference.md.
#
# Unlike resources/prompts (still scraped from PHP source text by
# mcp_item_extractor.rb), tool schemas can't be reliably reconstructed from
# text - most are assembled at runtime from PHP variables/function calls, and
# the create/update element tools are built entirely at runtime by
# buildFormElementTools(). So this reads docs/_data/mcp_tools.json instead: a
# real dump of registerTools()'s output, produced by
# mcp/dump_tools_for_docs.php and auto-loaded by Jekyll into
# site.data['mcp_tools'].

module Jekyll
  # A generated tool page. All rendering happens in the mcp-tool layout via
  # page.tool - this class just places the page at the right URL.
  class McpToolPage < PageWithoutAFile
    def initialize(site, base, tool)
      super(site, base, File.join(McpToolPages::TOOL_PAGES_DIR, tool["name"]), "index.html")

      self.data["layout"] = "mcp-tool"
      self.data["title"] = tool["name"]
      self.data["tool"] = tool
      self.content = ""
    end
  end

  class McpToolPages < Generator
    safe true
    priority :low # run after McpItemExtractor and after Jekyll loads _data

    # Nested under /ai/mcp-reference/ (rather than directly under /ai/) so
    # that page is a genuine URL ancestor of every tool page. breadcrumbs.rb
    # builds its trail purely by walking url path segments and matching them
    # against real pages, so this is what gets "MCP Items Reference" to show
    # up as an intermediate breadcrumb instead of jumping straight from "AI"
    # to the tool name.
    TOOL_PAGES_DIR = File.join("ai", "mcp-reference", "tools").freeze
    TOOL_PAGES_URL_PREFIX = "/ai/mcp-reference/tools".freeze

    # A docs-presentation decision (which tools to keep secret from the
    # reference site), so it lives here rather than in the PHP dump - the
    # dump script's job is just to report the ground truth.
    SKIP_TOOLS = %w[
      locate_captain_picard
      open_the_pod_bay_doors_hal
      lets_play_global_thermonuclear_war
    ].freeze

    # Local MCP-proxy tools. These aren't part of tools.php - they belong to
    # the local server that fronts Formulize for MCP clients - so they can
    # never appear in the PHP dump and are declared here by hand instead.
    CACHE_TOOLS = [
      {
        "name" => "cache_stats",
        "description" => "The local MCP server caches information to reduce network traffic and the load on the Formulize system. Use this tool to see the status of the cache.",
        "visibility" => "standard",
        "inputSchema" => { "type" => "object", "properties" => {} }
      },
      {
        "name" => "cache_refresh",
        "description" => "The local MCP server caches information to reduce network traffic and the load on the Formulize system. Use this tool to clear the cache.",
        "visibility" => "standard",
        "inputSchema" => { "type" => "object", "properties" => {} }
      }
    ].freeze

    def generate(site)
      raw_tools = site.data["mcp_tools"]

      unless raw_tools.is_a?(Array) && !raw_tools.empty?
        Jekyll.logger.error "MCP tool pages", "site.data['mcp_tools'] is missing or empty - no tool pages generated. Run mcp/dump_tools_for_docs.php to (re)populate docs/_data/mcp_tools.json."
        return
      end

      markdown_converter = site.find_converter_instance(Jekyll::Converters::Markdown)

      # Order follows tools.php's actual declaration order (the raw dump is
      # already in that order, since it's array_values() of PHP's own
      # insertion-ordered $this->tools) rather than alphabetical - the one
      # exception is the create/update element tools, which have no
      # "declared" position of their own (they're built in a loop at runtime)
      # and so keep whatever order buildFormElementTools() produces them in.
      tools = raw_tools.reject { |tool| SKIP_TOOLS.include?(tool["name"]) } + CACHE_TOOLS

      enriched = tools.map { |tool| enrich(tool, markdown_converter) }

      enriched.each do |tool|
        site.pages << McpToolPage.new(site, site.source, tool)
      end

      site.data["mcp_tools_list"] = {
        "standard" => enriched.select { |tool| tool["visibility"] == "standard" },
        "admin" => enriched.select { |tool| tool["visibility"] == "admin_only" },
        "all" => enriched
      }

      Jekyll.logger.info "MCP tool pages", "Generated #{enriched.length} tool pages (#{enriched.count { |t| t["visibility"] == "standard" }} standard, #{enriched.count { |t| t["visibility"] == "admin_only" }} admin-only)"
    end

    private

    def enrich(tool, markdown_converter)
      tool = tool.dup
      tool["url"] = "#{TOOL_PAGES_URL_PREFIX}/#{tool["name"]}/"
      tool["description_html"] = format_description(tool["description"], markdown_converter)

      schema = tool["inputSchema"] || {}
      rows = flatten_schema(schema["properties"] || {}, schema["required"] || [])
      rows.each { |row| row["description_html"] = format_description(row["description"], markdown_converter) }
      tool["properties_table"] = rows

      tool
    end

    # Turns a raw, possibly multi-paragraph PHP description into HTML that
    # actually reads like multiple paragraphs/lines, rather than the run-on
    # blob the old extractor produced. Two things need fixing up before
    # handing the text to the site's markdown converter (kramdown):
    #
    # 1. These descriptions use "**Label:**"-style pseudo-headings (Overview,
    #    Important notes, Properties, Examples, Element, Description...), but
    #    the source text often has only a single newline before/after them,
    #    not a blank line. Markdown treats a line straight after a list item
    #    as a continuation of that item's own paragraph when there's no blank
    #    line separating them, so without forcing one, a heading right after
    #    a bulleted list gets silently folded into the last bullet instead of
    #    standing on its own.
    # 2. Outside of lists, a single newline in the source is a deliberate
    #    line break (eg plain example lines with no bullet marker) that
    #    Markdown would otherwise collapse to a plain space, running
    #    everything together - so those get turned into hard breaks. List
    #    lines already form their own block structure and are left alone.
    def format_description(text, markdown_converter)
      return "" if text.nil? || text.strip.empty?

      text = bold_property_names(text)
      text = isolate_heading_lines(text)
      paragraphs = text.split(/\n{2,}/).map { |paragraph| add_soft_break_hints(paragraph) }

      render_with_element_sections(paragraphs, markdown_converter)
    end

    # A bullet whose line is just "propertyName (...)" - eg "delimiter
    # (optional, a string ...)" - is documenting a property of the element
    # type, and the name reads much better bolded. There's no dedicated
    # markup for this in the source text; the only thing that reliably marks
    # it as a property name rather than an ordinary prose bullet (eg "all the
    # common properties for List elements, plus:") is that it's a single
    # bare identifier sitting immediately before an opening parenthesis. That
    # pattern is consistent across every element type, so this isn't scoped
    # to element tools specifically - it just never matches anything in
    # other tools' descriptions.
    PROPERTY_NAME_LINE = /\A(\s*-\s+)([A-Za-z_][A-Za-z0-9_]*)(\s*\()/.freeze

    def bold_property_names(text)
      text.split("\n").map do |line|
        line.sub(PROPERTY_NAME_LINE, '\1**\2**\3')
      end.join("\n")
    end

    # Only the create/update element tools have this shape: a shared
    # preamble (Overview, common properties, etc) followed by one
    # "**Element:** X" block per element type, each with its own
    # Description/Properties/Examples. Each "**Element:**" paragraph is
    # rendered on its own at the left margin, like a heading, and everything
    # under it - up to (not including) the next "**Element:**" paragraph, or
    # the end of the text - is indented as its body. Tools without any
    # "Element:" marker (nearly all of them) render exactly as before: a
    # single, unindented block.
    def render_with_element_sections(paragraphs, markdown_converter)
      pieces = []
      body = []
      indent_body = false

      flush_body = lambda do
        next if body.empty?

        html = markdown_converter.convert(body.join("\n\n"))
        html = %(<div style="border-left: 2px solid #e5e5e5; margin: 0 0 15px; padding: 1px 0 1px 1em;">#{html}</div>) if indent_body
        pieces << html
        body = []
      end

      paragraphs.each do |paragraph|
        if paragraph.strip.start_with?("**Element:**")
          flush_body.call
          pieces << markdown_converter.convert(paragraph)
          indent_body = true
        else
          body << paragraph
        end
      end
      flush_body.call

      pieces.join("\n")
    end

    def isolate_heading_lines(text)
      lines = text.split("\n")
      result = []

      lines.each_with_index do |line, index|
        heading = line.strip.start_with?("**")
        result << "" if heading && !(result.empty? || result.last.empty?)
        result << line
        following = lines[index + 1]
        result << "" if heading && following && !following.strip.empty?
      end

      result.join("\n")
    end

    def add_soft_break_hints(paragraph)
      lines = paragraph.strip.split("\n")

      lines.each_with_index.map do |line, index|
        is_list_line = line.lstrip.start_with?("- ")
        next_is_list_line = lines[index + 1]&.lstrip&.start_with?("- ")
        needs_break = index < lines.length - 1 && !is_list_line && !next_is_list_line
        needs_break ? "#{line}  " : line
      end.join("\n")
    end

    # Flattens inputSchema.properties into an ordered list of printable rows,
    # recursing into nested object properties (eg display_conditions) so the
    # layout can render a flat table indented by depth instead of needing
    # recursive Liquid includes.
    def flatten_schema(properties, required, depth = 0)
      rows = []

      properties.each do |name, schema|
        schema ||= {}
        rows << {
          "name" => name,
          "depth" => depth,
          "type_label" => type_label(schema),
          "required" => required.include?(name),
          "description" => schema["description"] || ""
        }

        nested_properties = schema["properties"]
        if schema["type"] == "object" && nested_properties.is_a?(Hash)
          rows.concat(flatten_schema(nested_properties, schema["required"] || [], depth + 1))
        end
      end

      rows
    end

    def type_label(schema)
      return schema["oneOf"].map { |variant| type_label(variant) }.uniq.join(" or ") if schema["oneOf"].is_a?(Array)

      base = schema["type"] || "any"
      base = "array of #{type_label(schema["items"])}" if base == "array" && schema["items"].is_a?(Hash)
      base = "#{base} (one of: #{schema["enum"].join(", ")})" if schema["enum"].is_a?(Array)
      base
    end
  end
end
