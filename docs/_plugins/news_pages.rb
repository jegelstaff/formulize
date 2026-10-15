# _plugins/news_pages.rb
#
# Generates the whole /news/ section at build time from the News form on
# formulize.net, read through the Public API.
#
# Why build time rather than in the browser: the previous version of these
# pages fetched the API from JavaScript on every visit, which meant search
# engines indexed an empty page, there were no per-story URLs to link to or
# share (only /news/entry/?id=N), and there was no feed. Generating the pages
# here gives real URLs, real titles and descriptions, and an RSS feed, at the
# cost of news going live on a rebuild rather than on save. The News form's
# on_after_save hook pokes GitHub to run that rebuild, so in practice the
# delay is one workflow run.
#
# The shape of this plugin deliberately follows mcp_tool_pages.rb: fetch and
# stage the data, hand it to PageWithoutAFile subclasses, let layouts render.

require "net/http"
require "uri"
require "json"
require "date"
require "nokogiri"

module Jekyll
  # One generated page. Everything is rendered by the layout from page.entry /
  # page.entries - this class only decides where the page lives.
  class NewsGeneratedPage < PageWithoutAFile
    def initialize(site, base, dir, filename, layout, data)
      super(site, base, dir, filename)
      self.data["layout"] = layout
      data.each { |key, value| self.data[key] = value }
      self.content = ""
    end
  end

  class NewsPages < Generator
    safe true
    priority :low # after Jekyll has loaded _data

    API_BASE = "https://formulize.net/services/formulize-public-api/v1/form".freeze
    API_URL = "#{API_BASE}/news/read".freeze
    IMAGES_API_URL = "#{API_BASE}/news_images/read".freeze

    # news_slug, news_type and news_pin were added to the form for this
    # plugin's benefit; all are optional in the data and all have fallbacks
    # below, so entries that predate them still generate correctly.
    FIELDS = %w[
      news_headline news_slug news_type news_teaser news_body
      news_published_date news_link_text news_link_url news_image news_pin
    ].freeze

    # The News Images form holds the images uploaded to a story through the
    # Images subform on the News form. news_images_story is a linked element,
    # asked for raw so that it arrives as the story's entry id rather than its
    # headline.
    IMAGE_FIELDS = %w[
      news_images_story news_images_main news_images_description news_images_image
    ].freeze

    # Truthy spellings for a yn (Yes/No) element - news_pin, news_images_main -
    # as the Public API might return it: the readable "Yes", or the raw stored
    # value (Formulize's yn element type stores Yes as 1, not as a plain
    # boolean).
    YN_TRUE_VALUES = %w[1 yes true].freeze

    RELEASES_DIR = File.join("news", "releases").freeze
    FETCH_ATTEMPTS = 3
    RELEASES_ON_INDEX = 10 # how many releases the main news page lists inline
    FEED_LENGTH = 30

    # The widest a story page shows any of its images: the right-hand column
    # that .news-entry-image and .news-float cap at 320px in formulize.css. An
    # image file wider than this has been scaled down, so it is worth offering
    # to enlarge. Keep the two in step.
    IMAGE_COLUMN_WIDTH = 320

    def generate(site)
      @site = site
      @image_widths = {} # src => pixel width, so each image is measured once per build

      raw_entries = load_entries(site)
      return if raw_entries.nil? || raw_entries.empty?

      images_by_story = group_images(load_images(site))

      converter = site.find_converter_instance(Jekyll::Converters::Markdown)
      entries = raw_entries.map { |raw| build_entry(raw, converter, images_by_story.fetch(raw["entry_id"].to_i, [])) }
      assign_slugs(entries)
      entries.sort_by! { |entry| [entry["date"].to_s, entry["id"]] }
      entries.reverse!

      stories  = entries.reject { |entry| entry["type"] == "Release" }
      releases = entries.select { |entry| entry["type"] == "Release" }

      # A pinned story (news_pin in the News form, kept to at most one by the
      # form's on_after_save hook) always leads the list, regardless of its
      # publish date - everything else stays in normal newest-first order.
      # Only stories are affected; the release feed and the combined RSS feed
      # (built from `entries`, not `stories`) are unaffected by pinning.
      pinned_stories, stories = stories.partition { |entry| entry["pinned"] }
      stories = pinned_stories + stories

      entries.each do |entry|
        site.pages << story_page(site, entry)
        site.pages << id_redirect_page(site, entry)
      end

      site.pages << index_page(site, stories, releases)
      site.pages << releases_page(site, releases)
      site.pages << feed_page(site, entries)

      # Used by the homepage band and anywhere else that wants a few stories
      # without going through a generated page.
      site.data["news_all"]      = entries
      site.data["news_stories"]  = stories
      site.data["news_releases"] = releases

      Jekyll.logger.info "News pages",
        "Generated #{entries.length} stories (#{stories.length} announcements/articles, #{releases.length} releases), an index, a releases index and a feed."
    end

    private

    # ---- fetching -------------------------------------------------------

    # Writes every successful fetch to _data/news.json and falls back to it
    # when the API cannot be reached, so a wobble at formulize.net degrades the
    # docs deploy to "slightly stale" rather than "broken" - the same tradeoff
    # the MCP tool dump makes in .github/workflows/jekyll.yml.
    #
    # _data is gitignored in full, so in CI the cache is always cold and the
    # live fetch is what matters; the retries below are there for that case.
    def load_entries(site)
      cache_path = File.join(site.source, "_data", "news.json")

      if ENV["FORMULIZE_NEWS_OFFLINE"] == "1"
        cached = read_cache(cache_path)
        if cached
          Jekyll.logger.info "News pages",
            "FORMULIZE_NEWS_OFFLINE=1 - built #{cached.length} stories from the cached _data/news.json, without contacting formulize.net."
        else
          # Silence here would mean a site with no news section and no
          # explanation, which is the one outcome this plugin must never
          # produce quietly.
          Jekyll.logger.error "News pages",
            "FORMULIZE_NEWS_OFFLINE=1 but there is no cached _data/news.json to build from. No news pages will be generated. Run a build without that variable set to populate the cache."
        end
        return cached
      end

      last_error = nil
      FETCH_ATTEMPTS.times do |attempt|
        begin
          entries = fetch_rows(API_URL,
            "fields"    => FIELDS,
            "sortField" => "news_published_date",
            "sortOrder" => "DESC",
            "limitSize" => nil, # every entry; the form is small and fully public
            # The stored date (2026-10-15) rather than the readable one, which
            # follows the site's date format setting. See iso_date.
            "raw"       => ["news_published_date"]
          )
          write_cache(cache_path, entries)
          return entries
        rescue StandardError => error
          last_error = error
          Jekyll.logger.warn "News pages",
            "Attempt #{attempt + 1}/#{FETCH_ATTEMPTS} to read the News form failed: #{error.message}"
        end
      end

      cached = read_cache(cache_path)
      if cached
        Jekyll.logger.warn "News pages", "Falling back to the cached _data/news.json - news may be out of date."
        return cached
      end

      Jekyll.logger.error "News pages",
        "Could not read the News form (#{last_error && last_error.message}) and there is no cached _data/news.json. No news pages will be generated for this build."
      nil
    end

    # Images are fetched separately from the stories, and a failure here is only
    # a warning: a story is still worth publishing without its uploaded images,
    # where a missing story is not. The same caching applies, in its own file.
    #
    # Asking for a field the form does not have is refused outright by the API,
    # so if an element in IMAGE_FIELDS is ever renamed or removed, this is where
    # it shows up - as the warning below, with the API's own message in it.
    def load_images(site)
      cache_path = File.join(site.source, "_data", "news_images.json")
      return read_cache(cache_path) || [] if ENV["FORMULIZE_NEWS_OFFLINE"] == "1"

      last_error = nil
      FETCH_ATTEMPTS.times do
        begin
          rows = fetch_rows(IMAGES_API_URL,
            "fields"    => IMAGE_FIELDS,
            "raw"       => ["news_images_story"],
            "limitSize" => nil
          )
          write_cache(cache_path, rows)
          return rows
        rescue StandardError => error
          last_error = error
        end
      end

      cached = read_cache(cache_path)
      Jekyll.logger.warn "News pages",
        "Could not read the News Images form (#{last_error && last_error.message}). " +
        (cached ? "Using the cached _data/news_images.json - images may be out of date." : "Stories will be built without their uploaded images.")
      cached || []
    end

    def fetch_rows(url, parameters)
      uri = URI(url)
      request = Net::HTTP::Post.new(uri, "Content-Type" => "application/json")
      request.body = JSON.dump(parameters)

      response = Net::HTTP.start(uri.hostname, uri.port, use_ssl: true, open_timeout: 10, read_timeout: 30) do |http|
        http.request(request)
      end

      raise "the API responded with HTTP #{response.code}#{api_error_message(response)}" unless response.is_a?(Net::HTTPSuccess)

      payload = JSON.parse(response.body)
      rows = payload["data"]
      raise "the API response had no data array" unless rows.is_a?(Array)

      rows
    end

    # The API explains a refusal in its error envelope - "Unknown or
    # unavailable field: ...", for one - which says far more in a build log
    # than the status code alone.
    def api_error_message(response)
      message = JSON.parse(response.body.to_s).dig("error", "message")
      message ? " (#{message})" : ""
    rescue StandardError
      ""
    end

    def read_cache(path)
      return nil unless File.exist?(path)

      entries = JSON.parse(File.read(path))
      entries.is_a?(Array) && !entries.empty? ? entries : nil
    rescue StandardError => error
      Jekyll.logger.warn "News pages", "Could not read the cached #{path}: #{error.message}"
      nil
    end

    def write_cache(path, entries)
      FileUtils.mkdir_p(File.dirname(path))
      File.write(path, JSON.pretty_generate(entries))
    rescue StandardError => error
      # A read-only checkout should not fail the build over a cache write.
      Jekyll.logger.warn "News pages", "Could not write the news cache to #{path}: #{error.message}"
    end

    # ---- shaping --------------------------------------------------------

    # The uploaded images, keyed by the entry id of the story each belongs to,
    # and each carrying the file name an author refers to it by in the body.
    # Oldest first, so if two of a story's images are ever both flagged as the
    # main image (the News Images form's hook stops that, but nothing here
    # relies on it), the choice is at least a stable one.
    def group_images(rows)
      grouped = {}
      unnamed = false

      rows.sort_by { |row| row["entry_id"].to_i }.each do |row|
        url = row["news_images_image"].to_s.strip
        story_id = row["news_images_story"].to_s[/\d+/].to_i
        # A row with no file yet holds an empty value, or a message in place of
        # the address; neither is anything to show.
        next unless url.match?(%r{\Ahttps?://}) && story_id.positive?

        file_name = file_name_from_url(url)
        unnamed = true if file_name.nil?

        (grouped[story_id] ||= []) << {
          "url"         => url,
          "file_name"   => file_name,
          "description" => presence(row["news_images_description"]),
          "main"        => YN_TRUE_VALUES.include?(row["news_images_main"].to_s.strip.downcase)
        }
      end

      if unnamed
        Jekyll.logger.warn "News pages",
          "Some uploaded news images are served through download.php, so they can be a story's main image but cannot be shown in a story body by file name. Turn on \"Link directly to files\" on the Image element of the News Images form."
      end

      grouped
    end

    # The name the file was uploaded with. Only recoverable when the upload
    # element links to its files directly (/uploads/.../name.png); the other
    # mode serves every file from download.php, and such an image can still be
    # a story's main image but cannot be referred to by name in the body.
    #
    # A file uploaded while direct linking was off is stored with twenty random
    # digits and +---+ in front of its name, and keeps them if linking is
    # turned on later. Formulize shows the name without them, so that is the
    # name an author sees and types.
    def file_name_from_url(url)
      name = File.basename(URI(url).path.to_s)
      return nil if name.empty? || name == "download.php"

      URI::DEFAULT_PARSER.unescape(name).sub(/\A\d+\+---\+/, "")
    rescue StandardError
      nil
    end

    def build_entry(raw, converter, images)
      date = iso_date(raw["news_published_date"])
      teaser_text = teaser_for(raw)
      # The main image comes from the story's uploaded images when one is
      # flagged, and otherwise from the News form's own Image field, which is
      # where every story before the News Images form kept it.
      main_image = images.find { |image| image["main"] }
      image_url = main_image ? main_image["url"] : presence(raw["news_image"])
      image_alt = main_image ? main_image["description"].to_s : ""

      {
        "id"           => raw["entry_id"].to_i,
        "headline"     => raw["news_headline"].to_s,
        "type"         => normalize_type(raw["news_type"]),
        "teaser"       => teaser_text,
        "teaser_html"  => teaser_html_for(teaser_text, converter),
        "date"         => date,
        "date_display" => display_date(date),
        "date_rfc822"  => rfc822_date(date),
        "body_html"    => render_body(raw["news_body"], converter, images, raw["news_headline"].to_s),
        "image"        => image_url,
        "image_html"   => main_image_html(image_url, image_alt),
        "link_text"    => presence(raw["news_link_text"]),
        "link_url"     => presence(raw["news_link_url"]),
        "raw_slug"     => presence(raw["news_slug"]),
        "pinned"       => YN_TRUE_VALUES.include?(raw["news_pin"].to_s.strip.downcase)
      }
    end

    # Anything that is not explicitly a Release is treated as a story, so a
    # blank type (or a new option added to the form later) shows up on the main
    # news page rather than silently disappearing into the release feed.
    def normalize_type(value)
      return "Release" if value.to_s.strip == "Release"

      presence(value) || "Announcement"
    end

    # No length cap here: news_teaser is written by an author specifically to
    # be used in full wherever a teaser appears, not as a snippet to be cut
    # short. Cards that need equal height regardless of how long a given
    # teaser runs handle that in CSS (grid stretch + a bottom-anchored "Read
    # it" link), not by truncating the text.
    def teaser_for(raw)
      text = presence(raw["news_teaser"]) || presence(raw["news_body"]) || ""
      text.gsub(/\s+/, " ").strip
    end

    # The teaser is Markdown too, so it gets the same converter - but inline
    # only: the wrapping <p> is dropped so the teaser can sit inside whatever
    # element a template puts it in.
    #
    # A teaser's own links survive. They could not while a card was one big <a>
    # around everything (an <a> inside an <a> is invalid - browsers split it in
    # two), so the cards and rows that show a teaser now put the link on the
    # headline and stretch it over the box instead. See .stretched-link.
    def teaser_html_for(text, converter)
      return "" if text.empty?

      fragment = Nokogiri::HTML.fragment(converter.convert(text).strip)
      fragment.css("p").each { |para| para.replace(para.children) }
      fragment.to_html
    end

    # news_body is Markdown, rendered through the site's own kramdown converter
    # so news inherits .prose typography and Rouge syntax highlighting.
    #
    # Unlike the browser-side renderer this replaces (which set markdown-it's
    # html:false), raw HTML in a body is passed through. That is acceptable here
    # and only here: the News form is writable by Webmasters alone, so an author
    # who could put markup in a news body is already someone who can edit these
    # templates directly - the same trust boundary - and nothing a visitor
    # supplies ever reaches this code.
    def render_body(text, converter, images, headline)
      return "" if text.nil? || text.to_s.strip.empty?

      html = converter.convert(text.to_s)
      html.include?("<img") ? mark_up_body_images(html, images, headline) : html
    end

    # Images in a body are shown the way the story's own image is: floated
    # right beside the text, at the same size and gutter (see .news-float in
    # formulize.css), and enlargeable when the file is bigger than that (see
    # make_enlargeable).
    #
    # An image on a line of its own - the usual way to write one in Markdown -
    # becomes a <figure>, and its Markdown title, ![alt](src "Like this"),
    # becomes the caption, whether or not there is a blank line around it. An
    # image in the middle of a sentence stays where it is and floats from there. An image the author made into a link keeps its
    # link and is not made enlargeable.
    #
    # Bodies with no images are returned exactly as kramdown wrote them, so this
    # re-parse can never change a story that does not use the feature.
    def mark_up_body_images(html, images, headline)
      fragment = Nokogiri::HTML.fragment(html)
      lightboxes = []

      fragment.css("img").each_with_index do |img, index|
        resolve_uploaded_image(img, images, headline)
        img["class"] = [img["class"], "news-inline-image"].compact.join(" ")

        # Where the image is in its paragraph decides whether it becomes a
        # figure. On its own, or at the very start or end - which is how an
        # image on its own line reads when the author did not leave a blank
        # line before or after it, since Markdown then runs it into the next
        # line's paragraph - it does. Only an image in the middle of a sentence
        # stays where it is, with no caption.
        para = img.parent
        content = para.name == "p" ? para.children.reject { |node| node.text? && node.text.strip.empty? } : []
        position =
          if content == [img] then :alone
          elsif content.first == img then :first
          elsif content.last == img then :last
          end
        caption = img.remove_attribute("title") if position

        # The main image is news-image-0; body images count up from 1.
        outer = img
        unless img.ancestors("a").any?
          outer, lightbox = make_enlargeable(img, "news-image-#{index + 1}")
          lightboxes << lightbox if lightbox
        end

        if position
          figure = Nokogiri::XML::Node.new("figure", fragment.document)
          figure["class"] = "news-figure news-float"
          figure.add_child(outer)
          if caption && !caption.value.strip.empty?
            figcaption = Nokogiri::XML::Node.new("figcaption", fragment.document)
            figcaption.content = caption.value.strip
            figure.add_child(figcaption)
            add_class(figure, "news-figure--captioned")
          end
          # A figure cannot be inside a paragraph, so one taken from the start
          # or end of a paragraph goes just before or after it instead - where
          # it floats beside the same text - and the line break it leaves is
          # trimmed off the text.
          case position
          when :alone
            para.replace(figure)
          when :first
            para.add_previous_sibling(figure)
            first = para.children.first
            first.content = first.content.lstrip if first&.text?
          when :last
            para.add_next_sibling(figure)
            last = para.children.last
            last.content = last.content.rstrip if last&.text?
          end
        else
          add_class(outer, "news-float")
        end
      end

      # The enlarged copies go after the body rather than beside their images,
      # because an image in the middle of a sentence sits inside a <p>, and a
      # <div> is not allowed there - a browser would split the paragraph to
      # make room for it.
      lightboxes.each { |lightbox| fragment.add_child(lightbox) }
      fragment.to_html
    end

    # The story's own image at the top of its page. Built here rather than in
    # the layout so that it is made enlargeable by the same code as the images
    # in the body. The cards on the home page and news index use entry.image
    # directly and are not affected.
    def main_image_html(url, alt)
      return "" if url.nil?

      fragment = Nokogiri::HTML.fragment("")
      img = Nokogiri::XML::Node.new("img", fragment.document)
      img["src"] = url
      img["alt"] = alt
      fragment.add_child(img)

      outer, lightbox = make_enlargeable(img, "news-image-0")
      # With a Description (only an image from the News Images form has one),
      # the main image is a captioned card like the images in the body, and the
      # card takes the main image's place and size in the layout.
      if alt.to_s.strip.empty?
        add_class(outer, "news-entry-image")
      else
        figure = Nokogiri::XML::Node.new("figure", fragment.document)
        figure["class"] = "news-entry-image news-figure--captioned"
        outer.add_previous_sibling(figure)
        figure.add_child(outer)
        figcaption = Nokogiri::XML::Node.new("figcaption", fragment.document)
        figcaption.content = alt.strip
        figure.add_child(figcaption)
      end
      fragment.add_child(lightbox) if lightbox
      fragment.to_html
    end

    # Click to enlarge, with no JavaScript: when the image file is wider than
    # the column it is shown in, the image is wrapped in a button that opens a
    # popover holding a full-size copy. The browser does the rest - opening it,
    # closing it on Escape or a click outside, and dimming the page behind it
    # (.image-lightbox in formulize.css).
    #
    # Returns the element now on the outside - the button, or the image itself
    # when it is not wrapped - so the caller can put its layout class on that,
    # and the popover for the caller to place, or nil.
    def make_enlargeable(img, id)
      return [img, nil] unless worth_enlarging?(img["src"].to_s)

      document = img.document
      alt = img["alt"].to_s.strip

      button = Nokogiri::XML::Node.new("button", document)
      button["type"] = "button"
      button["class"] = "news-image-zoom"
      button["popovertarget"] = id
      button["aria-label"] = alt.empty? ? "Enlarge image" : "Enlarge image: #{alt}"
      img.add_previous_sibling(button)
      button.add_child(img)

      lightbox = Nokogiri::XML::Node.new("div", document)
      lightbox["id"] = id
      lightbox["popover"] = ""
      lightbox["class"] = "image-lightbox"

      close = Nokogiri::XML::Node.new("button", document)
      close["type"] = "button"
      close["class"] = "image-lightbox-close"
      close["popovertarget"] = id
      close["popovertargetaction"] = "hide"
      close["aria-label"] = "Close"
      # Drawn rather than typed: a "×" character sits wherever the font puts
      # it, which in the site's font is off centre in the round button.
      close.inner_html = '<svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M3.5 3.5l9 9M12.5 3.5l-9 9"/></svg>'
      lightbox.add_child(close)

      large = Nokogiri::XML::Node.new("img", document)
      large["src"] = img["src"]
      large["alt"] = img["alt"].to_s
      lightbox.add_child(large)

      [button, lightbox]
    end

    def add_class(node, name)
      node["class"] = [node["class"], name].compact.join(" ")
    end

    # An image whose width cannot be found out - an SVG, a file that would not
    # download, any build run with FORMULIZE_NEWS_OFFLINE=1 for an image held
    # elsewhere - is offered for enlarging anyway. Showing a picture at the
    # same size is a smaller fault than refusing to show a big one.
    #
    # On a phone the column is the full screen width rather than 320px, so an
    # image only a little wider than 320px can be offered there and open at
    # much the same size. Knowing that would mean measuring in the browser,
    # which is the one thing this is built not to need.
    def worth_enlarging?(src)
      width = image_width(src)
      width.nil? || width > IMAGE_COLUMN_WIDTH
    end

    # ---- image sizes ----------------------------------------------------

    def image_width(src)
      return @image_widths[src] if @image_widths.key?(src)

      @image_widths[src] = pixel_width(read_image(src))
    end

    # Images in the repository ("/images/x.png", "/assets/img/news/x.png") are
    # read from the source tree, and uploaded ones from wherever they are
    # served. Anything else - a relative path, an unreachable address - is
    # nil, which worth_enlarging? treats as "offer it".
    def read_image(src)
      if src.match?(%r{\A/[^/]})
        path = File.join(@site.source, URI::DEFAULT_PARSER.unescape(src.split(/[?#]/).first))
        File.exist?(path) ? File.binread(path) : nil
      elsif src.match?(%r{\Ahttps?://}) && ENV["FORMULIZE_NEWS_OFFLINE"] != "1"
        download(src)
      end
    rescue StandardError => error
      Jekyll.logger.warn "News pages", "Could not read #{src} to find its size: #{error.message}"
      nil
    end

    def download(url, redirects = 3)
      uri = URI(url)
      response = Net::HTTP.start(uri.hostname, uri.port, use_ssl: uri.scheme == "https", open_timeout: 10, read_timeout: 30) do |http|
        http.request(Net::HTTP::Get.new(uri))
      end
      return download(URI.join(url, response["location"]).to_s, redirects - 1) if response.is_a?(Net::HTTPRedirection) && redirects.positive?
      raise "HTTP #{response.code}" unless response.is_a?(Net::HTTPSuccess)

      response.body
    end

    # The pixel width written in the image file's own header, for the formats
    # an article image is likely to be in: PNG, GIF, JPEG and WebP. Read
    # directly rather than through an image library, so the site needs no new
    # dependency for one number. nil for anything else, including SVG.
    def pixel_width(bytes)
      return nil if bytes.nil?

      bytes = bytes.b
      if bytes.start_with?("\x89PNG\r\n\x1A\n".b)
        bytes[16, 4].unpack1("N")
      elsif bytes.start_with?("GIF8")
        bytes[6, 2].unpack1("v")
      elsif bytes.start_with?("\xFF\xD8".b)
        jpeg_width(bytes)
      elsif bytes[0, 4] == "RIFF" && bytes[8, 4] == "WEBP"
        webp_width(bytes)
      end
    rescue StandardError
      nil
    end

    # A JPEG is a run of segments, each starting 0xFF <marker>. The width is in
    # the start-of-frame segment (markers C0-CF, except C4, C8 and CC, which
    # are other things), five bytes after its two-byte length.
    def jpeg_width(bytes)
      offset = 2
      while offset + 9 <= bytes.bytesize
        return nil unless bytes.getbyte(offset) == 0xFF

        marker = bytes.getbyte(offset + 1)
        if marker == 0xFF # fill byte before a marker
          offset += 1
        elsif marker == 0x01 || (0xD0..0xD8).cover?(marker) # markers with no length
          offset += 2
        elsif (0xC0..0xCF).cover?(marker) && ![0xC4, 0xC8, 0xCC].include?(marker)
          return bytes[offset + 7, 2].unpack1("n")
        else
          offset += 2 + bytes[offset + 2, 2].unpack1("n")
        end
      end
      nil
    end

    # WebP comes in three layouts, named by the chunk at byte 12.
    def webp_width(bytes)
      case bytes[12, 4]
      when "VP8 " then bytes[26, 2].unpack1("v") & 0x3FFF
      when "VP8L" then (bytes[21, 4].unpack1("V") & 0x3FFF) + 1
      when "VP8X" then 1 + bytes[24, 3].bytes.each_with_index.sum { |byte, i| byte << (8 * i) }
      end
    end

    # ![Alt](screenshot.png) - a bare file name, with no path and no address -
    # means one of the images uploaded to this story through the News Images
    # form. Names are matched ignoring case, since an author typing the name of
    # Screenshot.PNG has no reason to remember how it was capitalised.
    #
    # The upload's Description is both the alt text and the caption, so
    # ![](screenshot.png) is all a body needs. Alt text or a caption written in
    # the Markdown, ![Alt](screenshot.png "Caption"), is used instead of it.
    # (The caption is the image's title attribute until mark_up_body_images
    # turns it into a <figcaption>.)
    #
    # A name that matches nothing is left as it is, which shows as a broken
    # image, and is reported in the build log by story so it can be found.
    def resolve_uploaded_image(img, images, headline)
      src = img["src"].to_s
      return if src.empty? || src.include?("/") || src.include?(":")

      name = URI::DEFAULT_PARSER.unescape(src)
      image = images.find { |candidate| candidate["file_name"] && candidate["file_name"].casecmp?(name) }
      unless image
        Jekyll.logger.warn "News pages",
          "The story \"#{headline}\" shows an image called #{name}, but no image with that file name has been uploaded to it."
        return
      end

      img["src"] = image["url"]
      return unless image["description"]

      img["alt"] = image["description"] if img["alt"].to_s.strip.empty?
      img["title"] = image["description"] if img["title"].to_s.strip.empty?
    end

    # Slugs come from the form's news_slug when an author set one. Otherwise
    # they are derived from the headline, which keeps pre-existing entries
    # working without anyone editing them by hand. Collisions (two stories with
    # the same headline, or two authors typing the same slug) fall back to the
    # entry id, which is unique by construction.
    def assign_slugs(entries)
      seen = {}

      entries.each do |entry|
        base = slugify(entry["raw_slug"] || entry["headline"])
        base = "story-#{entry["id"]}" if base.empty?
        slug = seen.key?(base) ? "#{base}-#{entry["id"]}" : base
        seen[base] = true
        seen[slug] = true
        entry["slug"] = slug
        entry["url"]  = "/news/#{slug}/"
      end
    end

    def slugify(value)
      value.to_s.downcase.gsub(/[^a-z0-9]+/, "-").gsub(/\A-+|-+\z/, "")
    end

    # Every story's date as YYYY-MM-DD, because the stories are sorted on it as
    # text and it goes into <time datetime>, which needs that form. The API is
    # asked for the stored date, which already is, but a readable date such as
    # "Oct 15, 2026" - in a cache written before that, or if the request ever
    # changes - is converted rather than trusted. Sorting readable dates as text
    # put every September story above October's, and ignored the year.
    def iso_date(value)
      text = value.to_s.strip
      return nil if text.empty?

      Date.parse(text).strftime("%Y-%m-%d")
    rescue StandardError
      Jekyll.logger.warn "News pages", "Could not read the published date #{text.inspect}; the story is sorted as undated."
      nil
    end

    def display_date(date)
      Date.parse(date.to_s).strftime("%-d %B %Y")
    rescue StandardError
      date
    end

    def rfc822_date(date)
      DateTime.parse("#{date} 12:00:00 +0000").rfc822
    rescue StandardError
      nil
    end

    def presence(value)
      text = value.to_s.strip
      text.empty? ? nil : text
    end

    # ---- pages ----------------------------------------------------------

    def story_page(site, entry)
      NewsGeneratedPage.new(
        site, site.source, File.join("news", entry["slug"]), "index.html", "news-entry",
        "title"       => entry["headline"],
        "description" => Nokogiri::HTML.fragment(entry["teaser_html"]).text,
        "entry"       => entry
      )
    end

    # A permanent, headline-proof address for every story: /news/entry/<id>/
    # redirects to the current slug. Editing a headline (or a slug) therefore
    # never strands a link shared earlier, and the old query-string form
    # /news/entry/?id=N has somewhere reliable to send people.
    def id_redirect_page(site, entry)
      NewsGeneratedPage.new(
        site, site.source, File.join("news", "entry", entry["id"].to_s), "index.html", "news-redirect",
        "title"       => entry["headline"],
        "redirect_to" => entry["url"],
        "sitemap"     => false
      )
    end

    def index_page(site, stories, releases)
      NewsGeneratedPage.new(
        site, site.source, "news", "index.html", "news-index",
        "title"           => "News",
        "description"     => "Announcements, articles and release notes from the Formulize project.",
        "entries"         => stories,
        "recent_releases" => releases.first(RELEASES_ON_INDEX),
        "releases_total"  => releases.length
      )
    end

    def releases_page(site, releases)
      NewsGeneratedPage.new(
        site, site.source, RELEASES_DIR, "index.html", "news-releases",
        "title"       => "Releases",
        "description" => "Every Formulize release, with the changes in each one.",
        "entries"     => releases
      )
    end

    def feed_page(site, entries)
      NewsGeneratedPage.new(
        site, site.source, "news", "feed.xml", "news-feed",
        "title"   => "Formulize news",
        "entries" => entries.first(FEED_LENGTH),
        "sitemap" => false
      )
    end
  end
end
