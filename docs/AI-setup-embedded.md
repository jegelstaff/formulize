---
layout: default
permalink: ai/setup-embedded
title: Embedded AI Assistant Setup
---

# Embedded AI Assistant Setup

To use the embedded AI assistant with a commercial AI provider, users will need _an API key from the AI provider_.

The embedded AI assistant can also be used with local language models, via <a href="https://ollama.com" target="_blank">Ollama</a>. In this case, you do not need any API keys.

1. **Enable the embedded AI assistant.** In the __Preferences__ tab of the main Formulize admin page, scroll down to the __AI__ section, and click _Yes_ for the _Enable the embedded AI assistant_ option.

2. **Select the group(s) that should have access.** Once the embedded AI assistant is enabled, you can select which groups have access to the assistant. Select them in the preference called _Groups that can use the embedded AI assistant_.

3. **Write some introductory notes for the AI.** When AI is enabled in Formulize, there is an additional preference called _System Specific Instructions for the AI Assistant_. This is a very useful and powerful feature! Everything you type in this preference, will be communicated to the AI every time it connects to your Formulize system. This is your chance to give it any unique background info it might need in order to understand your system, what it's for, and how it works. Include details. The AI loves details.

4. **Open the assistant and save your settings.** Go to the front page of your Formulize site and open the menu. Click _Use AI_ at the bottom of the menu. When the assistant opens, you will need to specify these things:

    * _The AI provider:_ Anthropic, Google, OpenAI, or <a href="https://ollama.com" target="_blank">Ollama</a> (for local language models)
    * _The language model:_ available models are shown, based on your provider and API key
    * _The API key:_ for commercial providers you will need to supply a _valid API key from that provider_ (no key necessary for <a href="https://ollama.com" target="_blank">Ollama</a>)
    * _The history limit:_ the number of characters in the chat history that will be sent to the AI with each message. Sensible defaults are set for each provider, so modify these at your own risk, but you may wish to adjust this to control the token usage of the model, especially with <a href="https://ollama.com" target="_blank">Ollama</a> since the performance of local models varies greatly based on your hardware.
    * _The available tools:_ you can choose which Formulize tools are exposed to the AI, which helps control the context window and token usage, since the schema for all enabled tools is sent to the AI with each message. The default settings are to use only the tools for reading information in Formulize.

    You can open the settings and adjust them, including the available tools, at any time. Just click the chip in upper right where the model name is displayed.

5. **Keep the assistant open while using Formulize.** You can keep a browser tab open with the assistant, while you use Formulize in other tabs/windows. _The assistant will have access to all your actions in Formulize_ (searching for entries, creating and updating entries, and saving forms, elements, and screens). These actions from the last 30 minutes will be passed to the assistant from all the other Formulize tabs/windows that you are using.

6. **Start a new conversation to clear the context window.** If you're done dealing with a certain issue or goal, you can click the _Start a new conversation_ button in the header bar. This is useful because the chat history is sent to the AI with every message, up to the character limit saved in your settings. Starting a new conversation means the history is completely cleared.

---

- [Setup an external AI assistant via MCP](../ai/setup-mcp)
- [Read more about AI and Formulize](../ai/)
