---
layout: default
permalink: ai/
title: AI and Formulize
---

# AI and Formulize

- [Overview](#overview)
- [Setup](#setup)
- [Working with Formulize and AI](#working)
- [Advanced Configuration](#advanced)
- [Testing that it's working](#testing)

## <a name='overview'></a>Overview

You can use AI assistants, like Claude, Copilot, etc, to help you work with Formulize. They can understand the way it's configured, and they can help you create data, update data, analyze data, maintain, validate and correct data...

The AI can even create forms and elements based on your prompts, or based on documents you upload, or whatever other information you can provide. The AI can connect multiple forms into useful applications, and it can update the configuration of the elements too.

With AI, instead of having to do all the clicking and organizing yourself, you can just tell the AI what you want, and it will help you create it or find it or update it, and so on. Think of it like having a super fast and overeager intern, who knows everything about your Formulize system.

### Formulize is ideal for AI

Formulize is the perfect system to use with AI, because Formulize is 100% configuration-based. The configuration of the forms in Formulize, their permissions, their connections to other forms, etc, all of that together completely explains what your system does _and how it does it_. With most other software, the only way to understand how it works is by reading the source code, which the AI does not have access to.

So, when the AI connects to Formulize, it truly gets the big picture. And when you add a new form to Formulize, or you change the permissions on another form, or add a connection, or do anything at all, the AI will instantly be able to see and understand how you've modified your application, and it will probably understand _why_ you've modified it that way as well.

### But can you trust it?

As with any overeager intern, AI might do the wrong thing sometimes. However, because the _Formulize <—> AI_ connection includes the entire configuration of your Formulize system, the AI does not have to guess about anything.

The so-called _hallucinations_ that AI sometimes has, are usually when it's missing some information and it's just trying to come up with something that would be consistent with everything else it knows. With Formulize, the AI has the _complete picture_ of how your system works, so it will generally do the right thing.

Also, when the AI asks Formulize for something, Formulize validates what the AI is asking for, and if something is wrong, Formulize helps the AI self-correct from any mistakes. For example, if the AI is asking about a form that doesn't exist, Formulize will suggest to the AI that it check the list of existing forms first.

---

## <a name='setup'></a>Setup

You need to [follow a few steps](../ai/setup) to get Formulize working with AI. Setup is a one time thing, once you've completed the setup you don't have to do it again.

- [Setup Instructions](../ai/setup)

---

## <a name='working'></a>Working with Formulize and AI

### The Basics

AI Assistants can discover all the configuration information about your Formulize system, they can read the data that has been entered into forms, and they can create and update entries in forms.

To work with Formulize and AI, you just need to send a prompt the AI assistant, and your prompt can be anything at all. Some examples:

> What are the forms in the Formulize system?

> What can you tell me about the Inventory form in the Formulize system?

> Please record this blood pressure reading in Formulize: 120/80

> What are the ten entries in the Activity Log form that have the highest attendance?

> The data in the Provinces form is incomplete and incorrect. Can you validate the population numbers and update them as required, and add any missing provinces? Thanks.

> I'm uploading information about a new client. Please read it and extract the information necessary to fill in the Client Profile form, and make a new entry in the form for this client. Thanks.

> Please check the activity logs for recent interactions with the Expenses form.

### Creating forms

AI Assistants can create forms and elements in Formulize, including connecting forms together into useful applications. The AI can also update the configuration of existing forms and elements.

The AI cannot do everything that you can do through the administration interface in Formulize. But it can do the most common actions. The AI is a great way to create a working prototype of an application, and then you can refine it from there with more advanced configuration options if necessary.

Some examples:

> Please create a Geography database in the Formulize system. It should record countries, states/provinces, and cities. Make sure it has space to record population numbers.

> I'm uploading PDFs of our intake forms. Please recreate these in Formulize.

> I'm uploading .csv files of our contacts lists. Look at the data in the files, and create a single contact form for us that will have space for all the different contact info we have been collecting. Then copy all the entries from the .csv files into the new contact form. Make sure to include a yes/no question in the form to flag possible duplicate entries, since we know we have multiple copies of some people's contact information. We will review the duplicates after you've created all the entries in the new form.

### Pro tips

Even though the AI isn't a person, it's often better to communicate with it the same way you would with a person, because AI has been trained on human language, so a more fluid conversation like you would have with a person, often yields better results.

The more information you can give the AI, the better it does. So if you can reference forms by their ID numbers or actual names, that's good. If you can be precise about entries you're interested in, by date, or by the value of certain elements, that's good. The more information the AI has about what you want it to do, the better it does.

The AI is also usually eager to please, so if you __don't__ want it to do something, you should be specific, such as:

> I'm uploading information about a new client. Please read it and extract the information necessary to fill in the Client Profile form. Tell me what information you've found, but DO NOT make an entry in the form. I want to see the information first before you create any new entries.

Every action the AI assistant performs, happens under the auspices of the user associated with the API key that you issued. So whenever the AI does anything, it will be recorded in Formulize as if that user were logged in and performing the action. Entries the AI creates will belong to that user and their group(s). Data that the AI retrieves, will be limited to the scope of data that the user has access to.

If the AI is using an API key for a webmaster user, there will be some additional capabilities:

- The AI can check the system activity logs (if logging is turned on in your Formulize system)
- The AI can execute direct SQL statements against the database. These are limited to SELECT statements, but can be very complex. AI is good at writing complicated SQL for generating statistical analysis, and other advanced ways of working with your data

### Tools, Resources, and Prompts

The AI has access to several items which it can use to interact with your Formulize system:

- **Tools**: Functions the AI assistant can call to perform actions (read data, create entries, etc.\
You generally don't even need to know what these are, because the AI assistant will use them as it sees fit. However, it can be useful to suggest certain tools to the AI if you are doing something particularly complicated.

- **Resources**: Read-only information sources about your system configuration\
Not all AI assistants have the capability to use the resources. Some will give you access to them, either for your own reference, or for including with prompts you write.

- **Prompts**: Pre-defined templates for common tasks\
Prompts are activated by you, the user. Not all AI assistants support prompts, and how you access them varies from assistant to assistant.

[Complete Formulize MCP Reference](../ai/mcp-reference/) - Descriptions of all {{ site.data.mcp_items.total_count }} tools, resources, and prompts available in Formulize.

#### More about prompts

The prompts are pre-defined templates, with placeholders for critical information that you can provide. For example, there's a pre-defined prompt called _generate_a_report_about_a_form_ and when you activate the prompt, you can provide three specific pieces of information:

- The form you want a report about
- The type of report, ie: summary, statistical, detailed...
- Any elements in the form that you want the AI to focus on

The information you provide is slotted into the pre-defined prompt template, to provide the AI assistant with instructions that will generate the report you've asked for.

Pre-defined prompts may make reference to particular tools that the AI assistant should use, and to particular procedures it should follow when carrying out the task. This makes the pre-defined prompts a useful way to perform certain actions in a standardized way, without you having to point out all the nuances to the AI every time.

---

## <a name='advanced'></a>Advanced Configuration

You can connect your AI assistant to multiple Formulize instances. You can also connect with the credentials of different users.

- [Read more about advanced configurations](../ai/advanced-setup).

---

## <a name='testing'></a>Testing that it's working

### Connection testing

AI assistants connect to Formulize through a _local MCP server_ which passes requests to your Formulize system, and listens for the responses. There is a built in _test_connection_ tool in the local MCP server. You can ask your AI assistant to test the connection to Formulize and it should run the _test_connection_ tool and give you the results. This can reveal if there's a network issue, or if AI is not enabled in your Formulize system, or if your API key is wrong, and so on.

### Testing the tools, resources and prompts

You can also test the behaviour and performance of the tools, resources and prompts themselves, through the ```mcp/test.html``` page of your Formulize system, ie: ```https://yoursite.com/mcp/test.html```

The test page lets you provide an API key, and try out all the different requests that an AI assistant can send to Formulize. It also lets you inspect the results that Formulize would send to the AI, so you can see exactly what the AI would be seeing.
