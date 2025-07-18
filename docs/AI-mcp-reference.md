---
layout: default
permalink: ai/mcp-reference/
title: MCP Items Reference
---

# MCP Items Reference

This page provides a comprehensive reference for all available MCP (Model Context Protocol) items in the Formulize system. The items listed below are automatically extracted from the codebase and updated whenever new items are added.

{% if site.data.mcp_items.errors %}
<div class="error-message">
  <strong>Errors:</strong>
  <ul>
    {% for error in site.data.mcp_items.errors %}
    <li>{{ error }}</li>
    {% endfor %}
  </ul>
</div>
{% elsif site.data.mcp_items.error %}
<div class="error-message">
  <strong>Error:</strong> {{ site.data.mcp_items.error }}

  {% if site.data.mcp_items.jekyll_source %}
  <details>
    <summary>Debug Information</summary>
    <ul>
      <li><strong>Jekyll Source:</strong> {{ site.data.mcp_items.jekyll_source }}</li>
      {% if site.data.mcp_items.attempted_paths %}
      <li><strong>Attempted Paths:</strong>
        <ul>
          {% for path in site.data.mcp_items.attempted_paths %}
          <li><code>{{ path }}</code></li>
          {% endfor %}
        </ul>
      </li>
      {% endif %}
    </ul>
  </details>
  {% endif %}
</div>
{% else %}

## Summary

- **Total MCP Items:** {{ site.data.mcp_items.total_count }}
- **Tools:** {{ site.data.mcp_items.tools_count }}
- **Resources:** {{ site.data.mcp_items.resources_count }}
- **Prompts:** {{ site.data.mcp_items.prompts_count }}

---

## Available Tools

Tools are functions that AI assistants can call to perform actions in Formulize.

### Standard Tools ({{ site.data.mcp_items.tools_standard.size }})

These tools are available to all authenticated users:

{% for tool in site.data.mcp_items.tools_standard %}
#### {{ tool.name }}
{{ tool.description }}
{% endfor %}

### Admin-Only Tools ({{ site.data.mcp_items.tools_admin.size }})

These tools are only available to webmaster users:

{% for tool in site.data.mcp_items.tools_admin %}
#### {{ tool.name }}
{{ tool.description }}
{% endfor %}

---

## Available Resources ({{ site.data.mcp_items.resources_count }})

Resources provide read-only access to system information and data.

{% for resource in site.data.mcp_items.resources %}
#### {{ resource.name }}
`{{ resource.uri }}`\
{{ resource.description }}
{% endfor %}

---

## Available Prompts

Prompts are pre-defined templates that can be used with AI assistants for common tasks.

### Standard Prompts ({{ site.data.mcp_items.prompts_standard.size }})

{% for prompt in site.data.mcp_items.prompts_standard %}
#### {{ prompt.name }}
{{ prompt.description }}
{% if prompt.arguments.size > 0 %}
_Arguments:_
{% for arg in prompt.arguments %}- {{ arg.name }}{% if arg.required %} (required){% endif %}: {{ arg.description }}
{% endfor %}{% endif %}
{% endfor %}

### Admin-Only Prompts ({{ site.data.mcp_items.prompts_admin.size }})

{% for prompt in site.data.mcp_items.prompts_admin %}
#### {{ prompt.name }}
{{ prompt.description }}
{% if prompt.arguments.size > 0 %}
_Arguments:_
{% for arg in prompt.arguments %}- {{ arg.name }}{% if arg.required %} (required){% endif %}: {{ arg.description }}
{% endfor %}{% endif %}
{% endfor %}

{% endif %}
