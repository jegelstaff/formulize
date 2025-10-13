---
layout: default
permalink: developers/Public_API/queue
title: queue
---

# formulize-public-api/v1/queue/{queue_handle}/process

Requests for the queue process method with a valid _queue_handle_, will trigger processing of the specified queue.

To trigger processing of all queues, use __all__ as the queue_handle. This can be a useful endpoint to setup a cron job for, to ensure automated processing of whatever goes into the queue.

If the queue is processed asynchronously, then the API will return true. If the queue is processed synchronously as part of the http request, then the API will return a JSON string that contains an array of the filenames that were processed in the queue.

If the queue is processed asynchronously, enabling logging in the Formulize preferences can be useful to monitor what the queue is doing.

See the documentation on the [Queue Handler's Process method](../API/classes/queue_handler/process) for more details about queue processing.

If the API is not enabled, a 503 http error is returned.

## XOOPS_URL not necessarily available when queue is processed

If you have code in a queue, that relies on the XOOPS_URL constant, you should hard code the constant in your mainfile.php. Normally, XOOPS_URL is determined at runtime from the setup of the files on the server. However, the queue may be processed using a command line instance of PHP, and in that case there are not the same environment variables available and the determination of the XOOPS_URL will be incorrect.
