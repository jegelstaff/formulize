---
layout: default
permalink: roadmap/
title: Roadmap
---

# Formulize Roadmap

*The master branch of Formulize is always stable and ready to install.* Features completed for an upcoming release are available immediately — you don't have to wait for a formal release to get new capabilities.

---

{% for release in site.data.roadmap_issues %}

## Version {{ release.version }}

{% if release.milestone %}
{% if release.milestone.due_on %}
**Projected release:** {{ release.milestone.due_on | date: "%B %-d, %Y" }}
{% endif %}
{% if release.milestone.description and release.milestone.description != "" %}
{{ release.milestone.description }}
{% endif %}
{% endif %}

{% if release.done and release.done.size > 0 %}
### Features available now on the master branch

{% for issue in release.done %}
- **[{{ issue.title }}]({{ issue.html_url }})** — {% if issue.body %}{{ issue.body | strip_html | truncatewords: 30 }}{% endif %}
{% endfor %}

### Features still under development
{% endif %}

{% if release.open and release.open.size > 0 %}

{% for issue in release.open %}
- **[{{ issue.title }}]({{ issue.html_url }})** — {% if issue.body %}{{ issue.body | strip_html | truncatewords: 30 }}{% endif %}
{% endfor %}

{% else %}
{% unless release.done and release.done.size > 0 %}
*Feature planning for this release is in progress. Check the [GitHub milestone](https://github.com/jegelstaff/formulize/milestones) for the latest.*
{% endunless %}
{% endif %}

---

{% endfor %}

*This roadmap is generated from the [GitHub issues](https://github.com/jegelstaff/formulize/issues) for Formulize. [View all milestones on GitHub](https://github.com/jegelstaff/formulize/milestones) for the full picture, or [join the discussion](https://github.com/jegelstaff/formulize/discussions) to share feedback on priorities.*
