---
layout: default
permalink: ai/advanced-setup
title: Advanced AI Setup
---

# Advanced AI Setup

You can have multiple connections between AI and your Formulize system, or systems. To do this, you need to do a manual configuration, such as with a ```.json``` file.

## Connecting to multiple systems

This kind of setup allows you to have the AI work with a variety of Formulize systems at the same time.

__However__, you need to be careful when using this kind of configuration, to be specific with your AI assistant about which Formulize connection you want to use. The AI will understand that the systems are different, once it has connected to each of them, and it will probably understand which system you are meaning to use, from the context.

But not necessarily all the time, and __proactive guidance goes a long way with AI__.

The ```.json``` file for this kind of configuration would have multiple servers specified, with different names and URLs and API keys:

```json
{
  "mcpServers": {
    "ACME Widgets System": {
      "command": "npx",
      "args": [
        "-y",
        "formulize-mcp"
      ],
      "env": {
        "FORMULIZE_URL": "https://timesheets.formulize.net",
        "FORMULIZE_API_KEY": "<api key for this formulize site>",
        "FORMULIZE_SERVER_NAME": "Timesheet System"
      }
    }, "Inventory System": {
      "command": "npx",
      "args": [
        "-y",
        "formulize-mcp"
      ],
      "env": {
        "FORMULIZE_URL": "https://inventory.formulize.net",
        "FORMULIZE_API_KEY": "<api key for this formulize site>",
        "FORMULIZE_SERVER_NAME": "Inventory System"
      }
    }
  }
}
```

## Connecting as multiple users

This kind of setup allows you to have the AI work with your Formulize system, under the auspices of different users.

__However__, you need to be __very careful__ when using this kind of configuration, to be specific with your AI assistant about _which Formulize connection you want to use_. It will know there are two MCP server connections available, but it may not always understand which one to use without your clear guidance, since the two connections are to the same Formulize system and their configuration will be largely the same.

The information available in each one will differ based on the different permissions of the different users, but with respect to that forms and data that both users have access to, the servers will appear identical.

__Proactive guidance goes a long way with AI__.

The ```.json``` file for this kind of configuration would have multiple connections to the same Formulize system, just using different API keys. You also need to name the different connections appropriately:

```json
{
  "mcpServers": {
    "Formulize Admin": {
      "command": "npx",
      "args": [
        "-y",
        "formulize-mcp"
      ],
      "env": {
        "FORMULIZE_URL": "https://<your.formulize.site.url>",
        "FORMULIZE_API_KEY": "<api key for a ADMIN user in your formulize site>",
        "FORMULIZE_SERVER_NAME": "Formulize Admin"
      }
    }, "Formulize Regular User": {
      "command": "npx",
      "args": [
        "-y",
        "formulize-mcp"
      ],
      "env": {
        "FORMULIZE_URL": "https://<your.formulize.site.url>",
        "FORMULIZE_API_KEY": "<api key for a REGULAR user in your formulize site>",
        "FORMULIZE_SERVER_NAME": "Formulize Regular User"
      }
    }
  }
}
```
