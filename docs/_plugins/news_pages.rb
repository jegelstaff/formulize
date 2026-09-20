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

    API_URL = "https://formulize.net/services/formulize-public-api/v1/form/news/read".freeze

    # news_slug, news_type and news_pin were added to the form for this
    # plugin's benefit; all are optional in the data and all have fallbacks
    # below, so entries that predate them still generate correctly.
    FIELDS = %w[
      news_headline news_slug news_type news_teaser news_body
      news_published_date news_link_text news_link_url news_image news_pin
    ].freeze

    # Truthy spellings for news_pin as the Public API might return it - the
    # readable "Yes", or the raw stored value (Formulize's yn element type
    # stores Yes as 1, not as a plain boolean).
    PIN_TRUE_VALUES = %w[1 yes true].freeze

    RELEASES_DIR = File.join("news", "releases").freeze
    FETCH_ATTEMPTS = 3
    RELEASES_ON_INDEX = 10 # how many releases the main news page lists inline
    FEED_LENGTH = 30

    def generate(site)
      raw_entries = load_entries(site)
      return if raw_entries.nil? || raw_entries.empty?

      converter = site.find_converter_instance(Jekyll::Converters::Markdown)
      entries = raw_entries.map { |raw| build_entry(raw, converter) }
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
          entries = fetch_entries
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

    def fetch_entries
      uri = URI(API_URL)
      request = Net::HTTP::Post.new(uri, "Content-Type" => "application/json")
      request.body = JSON.dump(
        "fields"    => FIELDS,
        "sortField" => "news_published_date",
        "sortOrder" => "DESC",
        "limitSize" => nil # every entry; the form is small and fully public
      )

      response = Net::HTTP.start(uri.hostname, uri.port, use_ssl: true, open_timeout: 10, read_timeout: 30) do |http|
        http.request(request)
      end

      raise "the API responded with HTTP #{response.code}" unless response.is_a?(Net::HTTPSuccess)

      payload = JSON.parse(response.body)
      entries = payload["data"]
      raise "the API response had no data array" unless entries.is_a?(Array)

      entries
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

    def build_entry(raw, converter)
      date = raw["news_published_date"].to_s.strip
      teaser_text = teaser_for(raw)

      {
        "id"           => raw["entry_id"].to_i,
        "headline"     => raw["news_headline"].to_s,
        "type"         => normalize_type(raw["news_type"]),
        "teaser"       => teaser_text,
        "teaser_html"  => teaser_html_for(teaser_text, converter),
        "date"         => date.empty? ? nil : date,
        "date_display" => display_date(date),
        "date_rfc822"  => rfc822_date(date),
        "body_html"    => render_body(raw["news_body"], converter),
        "image"        => presence(raw["news_image"]),
        "link_text"    => presence(raw["news_link_text"]),
        "link_url"     => presence(raw["news_link_url"]),
        "raw_slug"     => presence(raw["news_slug"]),
        "pinned"       => PIN_TRUE_VALUES.include?(raw["news_pin"].to_s.strip.downcase)
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
    def render_body(text, converter)
      return "" if text.nil? || text.to_s.strip.empty?

      converter.convert(text.to_s)
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

    def display_date(date)
      Date.parse(date).strftime("%-d %B %Y")
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
