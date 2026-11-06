# Working on the MCP server and the Public API

This is about changing them. For using them, see [Public API](../docs/public_api.md), [MCP reference](../docs/AI-mcp-reference.md) and [Advanced AI setup](../docs/AI-advanced.md).

## Where things are

| Path | What |
|---|---|
| `mcp/index.php` | Entry point, at `/mcp` |
| `mcp/mcp.php` | The `FormulizeMCP` class: authentication, requests, the embedded assistant's startup |
| `mcp/tools.php`, `resources.php`, `prompts.php` | Traits with the tools, resources and prompts |
| `mcp/mcpException.php` | `FormulizeMCPException`, with its error types and HTTP codes |
| `mcp/test.html` | A page for trying the server in a browser |
| `modules/formulize/public_api/` | The Public API, at `/formulize-public-api/`. `index.php` routes, and `v1/` has the endpoints. |
| `modules/formulize/include/readentries.php` | Reading entries, shared by both |
| `modules/formulize/include/aiadminconfig.php` | Which tools the embedded assistant may use |

The local server that MCP clients run is the `formulize-mcp` npm package, in its own repository. It has its own `test_connection`, which takes precedence over the one here.

## Who a request is

- **A session,** for the embedded assistant. It needs the AI Assistant preference. A session POST must be `Content-Type: application/json`, which is what protects it from forged requests. That protection depends on the CORS headers in `sendResponse()`, so never allow credentials there.
- **An API key,** for external clients. It needs the MCP Server preference. The key can come in `X-Formulize-API-Key`, which leaves `Authorization` free for sites behind HTTP Basic Auth.

Either way the code runs as that user, with their groups.

## Adding a tool

1. Add an entry to `$this->tools` in `registerTools()`, in `mcp/tools.php`: `name`, `description` and `inputSchema`.
2. Add a private method with exactly the tool's name, taking `$arguments`. `handleToolCall()` calls it by name.
3. Add the name to the right set in `formulizeAI_toolSets()`, in `aiadminconfig.php`: `formMgmt`, `formInspect` or `entryWrite`. That's how a site that limits the assistant to Read, Write or Manage gets it.
4. Add it to the expected list in `tests/e2e/formulize-core/03-validate-sequential/040-mcp-working.spec.js`. `02-validate/043-mcp-subform-connections` is a good example of testing a tool's behaviour.

The tool reference on formulize.org is generated from `registerTools()` by `mcp/dump_tools_for_docs.php`, so there's no page to write.

**The description is the tool's interface.** A model reads it to decide when and how to call the tool, so say what it does, when to use it, what to call first, and what happens to anything it changes.

## Rules for tools

- **Check permissions in the method.** Registering a tool doesn't restrict it. Tools that change the structure of the system start with `isUserAWebmaster()`. Tools that read data go through code that applies the user's own permissions.
- **Use the same code as the application,** so a tool can't do something differently from the admin UI. Forms, elements and screens go through `formulizeHandler::upsertFormSchemaAndResources()`, `upsertElementSchemaAndResources()`, `upsertMultiPageScreen()` and `upsertListScreen()`. Reading entries goes through `formulize_readEntries()`, which applies permissions and runs `gatherDataset()`.
- **System forms aren't available.** Users and groups have their own tools. Call `assertNotSystemForm()` before working with a form's entries, and `assertFormIsEditableByTools()` before changing a form, which also refuses locked forms and forms on existing tables. Reading code that lists forms has to exclude them too.
- **Update tools change only what they're given.** Anything left out stays as it is. Where absent and empty mean different things, as they do for `display_conditions`, keep them apart and say so in the schema.
- **Refuse what you can't do.** Throw `FormulizeMCPException` for an unknown value, and don't ignore it. Some error types return HTTP 200 on purpose, so a model sees a mistake as a result it can correct, not a failed connection.
- **State counts plainly.** `get_entries_from_form` reports how many entries came back and how many match, under names that can't be confused, before the entries themselves. A model reports whatever number it finds.

## Elements

The element tools are built when the server starts. `formulizeHandler::discoverElementTypes()` looks through the element classes, so a new type doesn't need a tool of its own. A type takes part by providing:

- `mcpElementPropertiesDescriptionAndExamples($update)`, a static method that describes its properties
- `validateEleValuePublicAPIProperties()`, which turns those properties into `ele_value`

See [Custom form elements](../docs/custom_form_elements.md).

The first element with data in a form becomes its principal identifier, unless one is set. So a test of a form with no principal identifier has to clear it, with `update_form` and `principal_identifier: 0`. `upsertElementSchemaAndResources()` does this, so it's true of the admin UI as well.

## The Public API

`public_api/v1/form.php` handles `/formulize-public-api/v1/form/{form}/read`. It authenticates in `public_api/authenticate.php`, then does its work through `formulize_readEntries()` and `formulize_renderEntriesAsRows()`. MCP calls only the first of those, because its callers expect the raw dataset and have `prepare_database_values_for_human_readability`. Errors are `FormulizeApiException`, which each side turns into its own error shape, so new shared code should throw that. The shared code lives in the module, so the module doesn't depend on `/mcp`.
