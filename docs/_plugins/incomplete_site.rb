# _plugins/incomplete_site.rb
#
# The one rule for a part of the site that can't be built: a production build,
# which is what .github/workflows/jekyll.yml publishes, fails rather than going
# out with that part missing. Nothing is deployed, so the site stays as it was.
#
# Any other build only logs the error, so a local preview still works without
# the network, PHP or the MCP tool dump.
#
# Every plugin that leaves something out reports it through here, so the rule
# lives in one place.

module Jekyll
  module IncompleteSite
    def self.report(topic, message)
      raise Jekyll::Errors::FatalException, "#{topic}: #{message}" if Jekyll.env == "production"

      Jekyll.logger.error topic, message
    end
  end
end
