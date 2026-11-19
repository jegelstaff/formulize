# _plugins/latest_release_check.rb
#
# The download page and the download strip on every page link to the latest
# release through site.github.latest_release, from jekyll-github-metadata. When
# GitHub can't be reached or its rate limit is hit, that gem only logs a warning
# and leaves the value as false, so the links would go out without a version.
# This makes that an incomplete site, like any other (see incomplete_site.rb).
#
# The gem puts site.github into the render payload in a pre_render hook, not into
# site.config, so a generator can't see it. This is a pre_render hook too, at a
# lower priority so it runs after the gem's.

Jekyll::Hooks.register :site, :pre_render, priority: :low do |_site, payload|
  github = payload.site["github"]
  release = github.respond_to?(:[]) ? github["latest_release"] : nil
  tag = release.is_a?(Hash) ? release["tag_name"].to_s : ""

  if tag.empty?
    Jekyll::IncompleteSite.report "Latest release",
      "Couldn't read the latest release from GitHub, so the download links would have no version in them. Setting JEKYLL_GITHUB_TOKEN avoids GitHub's unauthenticated rate limit."
  else
    Jekyll.logger.info "Latest release", tag
  end
end
