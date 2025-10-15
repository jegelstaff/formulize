---
layout: default
permalink: ai/setup
title: AI Setup Instructions
---

# AI Setup Instructions

1. Formulize works with AI assistants that are capable of __local__ MCP connections. You need to have one installed locally on your computer.

	We have had good results with <a href='https://claude.ai/download' target='_blank'>Claude Desktop</a>, and Claude helped create the Formulize MCP server. We can recommend it as a top tier AI assistant.

	However, to get the best results from Claude, a paid subscription seems to be necessary. The cost may be good value for money, if Claude can save you a lot of time, which seems likely given what AI and Formulize is capable of.

	Regardless, any AI assistant capable of using _local MCP connections_ should be compatible with Formulize. Please <a href='mailto:info@formulize.org'>let us know about your experience</a>.

2. Create an API key in Formulize for the user(s) who are going to work with AI. You create API keys on the __Manage API Keys__ page, accessible from the main Formulize admin page:\
![Click Manage API Keys on the Formulize admin page](../../images/Manage-API-keys.png)

3. Enable the AI Features in Formulize. You do this through the __Formulize Preferences__ page, , accessible from the main Formulize admin page:\
![Click Manage API Keys on the Formulize admin page](../../images/Formulize-preferences.png)\
\
On that page, scroll down to the __System Configuration__ section, and click _Yes_ for the _Enable AI Integration_ option:\
\
![Enable AI Integration in Preferences](../../images/enable-ai.png)\
\
__If the preference does not remain on__, and instead reverts to _no_, then you need to add this code to the ```.htaccess``` file at the root of your website. Make sure to put it after any other rewrite rules.\
\
```apacheconf
# Necessary for HTTP Authorization header to be passed through to the MCP server
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```

4. Write some introductory notes for the AI. When AI is enabled in Formulize, there is a preference called _System Specific Instructions for the AI Assistant_. This is a very useful and powerful feature! Everything you type in this preference, will be communicated to the AI every time it connects to your Formulize system. This is your chance to give it any unique background info it might need in order to understand your system, what it's for, and how it works. Include details. The AI loves details.

4. Configure your AI assistant. For <a href='https://claude.ai/download' target='_blank'>Claude Desktop</a>, you can simply <a href='https://github.com/jegelstaff/formulize-mcp/releases/download/v1.3.2/formulize-mcp.dxt' download='formulize-mcp.dxt'>download the Formulize DXT extention</a>, and install it in Claude. Unfortunately, the exact steps to install are changing regularly, and depend on which version of Claude you're using.

	Other AI assistants might be compatible with DXT extensions as well, now or in the future.

5. If your AI assistant is does not support DXT extensions, you need to update the configuration of your AI assistant manually. Exactly how to do this varies from assistant to assistant:

	- For Copilot in VSCode, make a file called ```mcp.json``` in the ```.vscode``` folder of your project. It should look like this

	```json
	{
		"servers": {
			"My Formulize MCP Server": {
				"command": "npx",
				"args": [
					"-y",
					"formulize-mcp"
				],
				"env": {
					"FORMULIZE_URL": "https://<your.formulize.site.url>",
					"FORMULIZE_API_KEY": "<your api key from your formulize site>",
					"FORMULIZE_SERVER_NAME": "My Formulize MCP Server"
				}
			}
		}
	}
	```

	- Also, in VSCode you will want to go into the preferences, and under __Chat > MCP__, make sure _discovery_ is enabled.

	- For Claude Desktop, if you're not using the DXT file, modify the file ```claude_desktop_config.json```. Where is it?\
	Windows: ```%APPDATA%\Claude\claude_desktop_config.json```\
	macOS: ```~/Library/Application Support/Claude/claude_desktop_config.json```\
	\
	The file should look like this:

	```json
	{
		"mcpServers": {
			"My Formulize MCP Server": {
				"command": "npx",
				"args": [
					"-y",
					"formulize-mcp"
				],
				"env": {
					"FORMULIZE_URL": "https://<your.formulize.site.url>",
					"FORMULIZE_API_KEY": "<your api key from your formulize site>",
					"FORMULIZE_SERVER_NAME": "My Formulize MCP Server"
				}
			}
		}
	}
	```

	- The configuration for other AI assistants should be similar. You need to use ```npx``` with ```formulize-mcp```, and set the environment variables.

## Options

There are five options you can configure in your AI assistant, for working with Formulize.

The DXT extension will give you a user interface to fill in with these options.

If you are manually configuring through a .json file, you need to include the options as the environment variables (env).

- FORMULIZE_URL - Required - The URL for your Formulize system, ie: https://myformulize.com
- FORMULIZE_API_KEY - Required - Your API key for your Formulize system.
- FORMULIZE_SERVER_NAME - Recommended - The name of your server. Although the name may be stated already higher up in the .json file, including the name as an environment variable will help the AI understand your system.
- FORMULIZE_DEBUG - Optional - Either _true_ or _false_. Defaults to _false_.
- FORMULIZE_TIMEOUT - Optional - Timeout in milliseconds. Defaults to _30000_.

## Advanced Configuration

You can connect your AI assistant to multiple Formulize instances. You can also connect with the credentials of different users. [Read more about these advanced configurations](../ai/advanced-setup).


