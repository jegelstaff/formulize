# _plugins/roadmap_data.rb
#
# Builds site.data["roadmap_issues"] - the data behind /documentation/roadmap/ -
# from the GitHub milestones and issues for jegelstaff/formulize.
#
# This used to be a curl/jq step in .github/workflows/jekyll.yml, which meant a
# local `jekyll serve` never had it and the roadmap rendered empty. Doing it
# here makes local and CI builds identical, the same way jekyll-github-metadata
# already makes site.github.latest_release work in both.
#
# Everything read here is public, so no token is required. Unauthenticated
# requests are limited to 60 an hour per IP, though, so a token is used when
# one is set: JEKYLL_GITHUB_TOKEN (the variable jekyll-github-metadata also
# reads, so one token covers both), GITHUB_TOKEN or GH_TOKEN.
#
# Results are fetched once per Jekyll process, not once per regeneration, so a
# `jekyll serve --watch` session costs a handful of API calls in total rather
# than a handful per saved file. Restart serve to pick up milestone changes.

require "net/http"
require "uri"
require "json"

module Jekyll
  class RoadmapData < Generator
    safe true
    priority :high # before anything renders

    REPO = "jegelstaff/formulize".freeze
    API_BASE = "https://api.github.com/repos/#{REPO}".freeze
    LABEL = "Marquee Feature".freeze

    # Only OPEN milestones whose title is a plain version number (8.3, 9.0,
    # 10.0 ...) become roadmap sections, so closing a milestone on GitHub drops
    # it from the roadmap on the next build and opening one adds it. Backlog,
    # Deep Backlog and the like are ignored.
    VERSION_TITLE = /\A\d+(\.\d+)+\z/.freeze

    # A PR is left out when its body closes one of the issues already listed,
    # so a feature doesn't appear twice (once as its issue, once as its PR).
    CLOSING_REF = /(?:closes?|fixes?|resolves?)\s+#(\d+)/i.freeze

    @memo = nil
    class << self
      attr_accessor :memo
    end

    def generate(site)
      self.class.memo ||= load_releases(site)
      site.data["roadmap_issues"] = self.class.memo
    end

    private

    # ---- fetching -------------------------------------------------------

    # Every successful fetch is written to _data/roadmap_issues.json and used
    # as a fallback when GitHub can't be reached (or the rate limit is hit), so
    # a local build that has worked once keeps a populated roadmap offline.
    # _data is gitignored in full, so in CI the cache is always cold.
    def load_releases(site)
      cache_path = File.join(site.source, "_data", "roadmap_issues.json")

      begin
        releases = fetch_releases
        write_cache(cache_path, releases)
        Jekyll.logger.info "Roadmap",
          "Read #{releases.length} release(s) from GitHub: " +
          releases.map { |r| "#{r["version"]} (#{r["done"].length} done, #{r["open"].length} open)" }.join(", ")
        return releases
      rescue StandardError => error
        Jekyll.logger.warn "Roadmap", "Could not read milestones/issues from GitHub: #{error.message}"
      end

      cached = read_cache(cache_path)
      if cached
        Jekyll.logger.warn "Roadmap", "Falling back to the cached _data/roadmap_issues.json - the roadmap may be out of date."
        return cached
      end

      Jekyll.logger.error "Roadmap",
        "No cached _data/roadmap_issues.json to fall back on. The roadmap page will have no release sections." +
        (token ? "" : " Setting JEKYLL_GITHUB_TOKEN avoids GitHub's unauthenticated rate limit.")
      []
    end

    def fetch_releases
      milestones = api_get("/milestones?state=open&per_page=100")
        .select { |ms| ms["title"].to_s =~ VERSION_TITLE }
        .sort_by { |ms| ms["title"].split(".").map(&:to_i) }

      Jekyll.logger.warn "Roadmap", "No open version-numbered milestones found." if milestones.empty?

      milestones.map do |ms|
        query = "labels=#{URI.encode_www_form_component(LABEL)}&milestone=#{ms["number"]}&per_page=100"
        raw_open = api_get("/issues?#{query}&state=open")
        raw_done = api_get("/issues?#{query}&state=closed")

        issue_numbers = (raw_open + raw_done).reject { |item| item.key?("pull_request") }.map { |item| item["number"] }

        {
          "key"       => "v" + ms["title"].delete("."), # "8.3" -> "v83"
          "version"   => ms["title"],
          "milestone" => ms,
          "open"      => shape(raw_open, issue_numbers),
          "done"      => shape(raw_done, issue_numbers),
        }
      end
    end

    # Keep issues, and PRs that don't close one of the listed issues.
    def shape(items, issue_numbers)
      items.select do |item|
        next true unless item.key?("pull_request")
        item["body"].to_s.scan(CLOSING_REF).flatten.map(&:to_i).none? { |n| issue_numbers.include?(n) }
      end.map do |item|
        item.slice("number", "title", "body", "html_url")
      end
    end

    def api_get(path)
      uri = URI(API_BASE + path)
      request = Net::HTTP::Get.new(uri)
      request["Accept"] = "application/vnd.github+json"
      request["User-Agent"] = "formulize-docs-jekyll"
      request["Authorization"] = "Bearer #{token}" if token

      response = Net::HTTP.start(uri.hostname, uri.port, use_ssl: true, open_timeout: 10, read_timeout: 30) do |http|
        http.request(request)
      end

      unless response.is_a?(Net::HTTPSuccess)
        detail = response["x-ratelimit-remaining"] == "0" ? " (rate limit exhausted)" : ""
        raise "GET #{path} responded with HTTP #{response.code}#{detail}"
      end

      JSON.parse(response.body)
    end

    def token
      %w[JEKYLL_GITHUB_TOKEN GITHUB_TOKEN GH_TOKEN].map { |name| ENV[name] }.find { |value| value && !value.empty? }
    end

    def read_cache(path)
      return nil unless File.exist?(path)

      releases = JSON.parse(File.read(path))
      releases.is_a?(Array) ? releases : nil
    rescue StandardError => error
      Jekyll.logger.warn "Roadmap", "Could not read the cached #{path}: #{error.message}"
      nil
    end

    def write_cache(path, releases)
      FileUtils.mkdir_p(File.dirname(path))
      File.write(path, JSON.pretty_generate(releases))
    rescue StandardError => error
      Jekyll.logger.warn "Roadmap", "Could not write the roadmap cache to #{path}: #{error.message}"
    end
  end
end
