<?php

class FormulizeMCPException extends Exception
{
	private string $timestamp;
	private string $type;
	private array $context;
	public $exceptionTypeToHTTPStatusCode = [
		'preflight_success' => 204,
		'authentication_error' => 401,
		'permission_denied' => 403,
		'method_not_allowed' => 405,
		'database_error' => 500,
		'missing_method' => 500,
		'method_not_found' => 404,
		'form_not_found' => 404,
		'server_disabled' => 503,
		'unknown_prompt' => 404,
		'prompt_generation_error' => 500,
		'missing_uri' => 400,
		'invalid_uri' => 400,
		'resource_read_error' => 500,
		'unknown_element' => 404,
		'unknown_tool' => 404,
		'invalid_arguments' => 400, // bad request
		'invalid_data' => 200, // good request, internal problems, ie: wrong handle, etc
		'file_error' => 500,
		'unknown_resource_type' => 404
	];

	/**
	 * Constructor for FormulizeMCPException
	 */
	public function __construct(string $message, string $type, int $code = 0, array $context = [], ?Throwable $previous = null)
	{
			parent::__construct($message, $code, $previous);
			$this->type = $type;
			$this->timestamp = date('Y-m-d H:i:s');
			$this->context = $context;
	}

	/**
	 * Get the timestamp of the exception
	 *
	 * @return string The timestamp when the exception was created
	 */
	public function getTimestamp(): string
	{
			return $this->timestamp;
	}

	/**
	 * Get the type of the exception
	 *
	 *  @return string The type of the exception
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * Get the context of the exception
	 *
	 * @return array The context of the exception
	 */
	public function getContext(): array
	{
		return $this->context;
	}

	/**
	 * Convert the exception to an array suitable for JSON response
	 *
	 * @param array $context Additional context for the error
	 * @return array An array with error details including code, message, and troubleshooting steps
	 */
	public function toErrorResponse($context = []): array
	{
		$error = [
			'code' => $this->code,
			'message' => $this->message,
			'timestamp' => $this->getTimestamp(),
			'type' => $this->getType(),
		];

		// Add helpful context for common errors
		switch ($this->type) {
			case 'authentication_error':
			case 'permission_denied':
				$error['troubleshooting'] = [
						'issue' => 'Insufficient permissions',
						'solutions' => [
								'Check if user has required permission for this form/entry',
								'Use list_forms to see accessible forms',
								'Verify user is member of correct groups'
						]
				];
				break;
			case 'form_not_found':
				$error['troubleshooting'] = [
					'issue' => 'Invalid form ID',
					'solutions' => [
						'Use list_forms tool to get valid form IDs',
						'Verify the form exists and is accessible'
					]
				];
				break;
			case 'unknown_element':
				$error['troubleshooting'] = [
					'issue' => 'Element handle does not exist in form',
					'solutions' => [
						'Use get_form_details tool to get valid element handles',
						'Check spelling of element handle',
						'Verify element is part of the specified form'
					]
				];
				break;
		}

		if (empty($context)) {
			$context = $this->getContext();
		}
		$error['context'] = $context;

		return $error;
	}

	public function toHTTPStatusCode($default = 500): int
	{
		return $this->exceptionTypeToHTTPStatusCode[$this->type] ?? $default;
	}
}
