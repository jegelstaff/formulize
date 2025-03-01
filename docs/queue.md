---
layout: default
permalink: developers/Public_API/queue
---

# formulize-public-api/v1/queue/{queue_handle}/process

Requests for the queue process method with a valid _queue_handle_, will trigger processing of the specified queue. 

To trigger processing of all queues, use __all__ as the queue_handle.

If the queue is processed asynchronously, then the API will return true. If the queue is processed synchronously as part of the http request, then the API will return a JSON string that contains an array of the filenames that were processed in the queue. 

If the queue is processed asynchronously, enabling logging in the Formulize preferences can be useful to monitor what the queue is doing.

See the documentation on the [Queue Handler's Process method](../API/classes/queue_handler/process) for more details about queue processing.

If the API is not enabled, a 503 http error is returned.