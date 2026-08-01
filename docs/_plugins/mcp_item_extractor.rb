# _plugins/mcp_item_extractor.rb
require 'pathname'

module Jekyll
  class McpItemExtractor < Generator
    safe true
    priority :high

    def generate(site)
      # Debug: Log the Jekyll source directory
      Jekyll.logger.info "MCP extractor", "Jekyll source directory: #{site.source}"

      # Try multiple possible paths to the MCP files
      base_paths = [
        File.expand_path("../../mcp", site.source),  # Original path
        File.expand_path("../mcp", site.source),     # One level up
        File.expand_path("mcp", site.source),        # Same level as docs
        File.join(File.dirname(site.source), "mcp") # Alternative approach
      ]

      # Find the first existing base path
      mcp_dir = base_paths.find { |path| Dir.exist?(path) }

      unless mcp_dir
        error_msg = "MCP directory not found in any of the attempted locations. Jekyll source: #{site.source}"
        Jekyll.logger.error "MCP extractor", error_msg
        site.data['mcp_items'] = {
          'error' => error_msg,
          'attempted_paths' => base_paths,
          'jekyll_source' => site.source
        }
        return
      end

      Jekyll.logger.info "MCP extractor", "Found MCP directory at: #{mcp_dir}"

      begin
        # Extract MCP items. Tools are NOT handled here - they're generated
        # from docs/_data/mcp_tools.json (a real dump of registerTools()'s
        # output, produced by mcp/dump_tools_for_docs.php) by
        # docs/_plugins/mcp_tool_pages.rb instead, because most tool schemas
        # are assembled at runtime from PHP variables/function calls that
        # can't be reconstructed by scraping the source text. Resources and
        # prompts don't have that problem (their schemas are static text), so
        # they stay on this scraper.
        all_items = {
          'resources' => extract_from_file(File.join(mcp_dir, 'resources.php'), 'resources'),
          'prompts' => extract_from_file(File.join(mcp_dir, 'prompts.php'), 'prompts')
        }

        # Calculate totals
        resources_data = all_items['resources']
        prompts_data = all_items['prompts']

        # Combine all data
        combined_data = {
          'resources' => resources_data['items'] || [],
          'prompts' => prompts_data['items'] || [],
          'resources_standard' => resources_data['standard_items'] || [],
          'resources_admin' => resources_data['admin_items'] || [],
          'prompts_standard' => prompts_data['standard_items'] || [],
          'prompts_admin' => prompts_data['admin_items'] || [],
          'total_count' => (resources_data['total_count'] || 0) + (prompts_data['total_count'] || 0),
          'resources_count' => resources_data['total_count'] || 0,
          'prompts_count' => prompts_data['total_count'] || 0,
          'extracted_at' => Time.now.strftime('%Y-%m-%d %H:%M:%S %Z'),
          'source_dir' => mcp_dir,
          'debug_info' => {
            'jekyll_source' => site.source,
            'mcp_directory' => mcp_dir,
            'resources_file_size' => File.exist?(File.join(mcp_dir, 'resources.php')) ? File.size(File.join(mcp_dir, 'resources.php')) : 0,
            'prompts_file_size' => File.exist?(File.join(mcp_dir, 'prompts.php')) ? File.size(File.join(mcp_dir, 'prompts.php')) : 0
          }
        }

        # Add any errors
        errors = []
        errors << resources_data['error'] if resources_data['error']
        errors << prompts_data['error'] if prompts_data['error']
        combined_data['errors'] = errors unless errors.empty?

        # Store in Jekyll's data for use in templates
        site.data['mcp_items'] = combined_data

        Jekyll.logger.info "MCP extractor", "Extracted #{combined_data['total_count']} MCP items (#{combined_data['resources_count']} resources, #{combined_data['prompts_count']} prompts)"
      rescue => e
        Jekyll.logger.error "MCP extractor", "Error processing MCP files: #{e.message}"
        site.data['mcp_items'] = { 'error' => e.message }
      end
    end

    private

    def extract_from_file(file_path, item_type)
      unless File.exist?(file_path)
        return { 'items' => [], 'error' => "#{item_type}.php file not found at #{file_path}" }
      end

      content = File.read(file_path, encoding: 'UTF-8')

      case item_type
      when 'resources'
        extract_resources_from_php(content)
      when 'prompts'
        extract_prompts_from_php(content)
      else
        { 'items' => [], 'error' => "Unknown item type: #{item_type}" }
      end
    end

    # Finds a `private function <method_name>() { ... }` body using
    # brace-counting rather than a lazy regex. A regex like
    # `\{(.*?)\n\s*\}` stops at the FIRST closing brace on its own line,
    # which is very likely to be a nested if/foreach block inside the
    # method rather than the method's own end - silently truncating
    # everything declared after it. Quotes are tracked so that braces
    # embedded in strings (eg PHP interpolation like "{$var}") don't throw
    # off the count.
    def extract_balanced_method_body(content, method_name)
      start_match = content.match(/private\s+function\s+#{Regexp.escape(method_name)}\s*\(\)\s*\{/)
      return nil unless start_match

      open_brace_pos = start_match.end(0) - 1
      close_brace_pos = find_matching_brace(content, open_brace_pos)
      return nil unless close_brace_pos

      content[(open_brace_pos + 1)...close_brace_pos]
    end

    # Returns the index of the '}' that closes the '{' at open_brace_pos.
    def find_matching_brace(content, open_brace_pos)
      depth = 0
      in_single = false
      in_double = false
      escaped = false

      (open_brace_pos...content.length).each do |i|
        char = content[i]

        if escaped
          escaped = false
          next
        end

        if char == '\\' && (in_single || in_double)
          escaped = true
          next
        end

        if char == "'" && !in_double
          in_single = !in_single
          next
        end

        if char == '"' && !in_single
          in_double = !in_double
          next
        end

        next if in_single || in_double

        if char == '{'
          depth += 1
        elsif char == '}'
          depth -= 1
          return i if depth == 0
        end
      end

      nil
    end

    def extract_resources_from_php(content)
      resources = []

      # Find the registerResources method body (brace-counted; see comment on
      # extract_balanced_method_body for why a lazy regex isn't safe here)
      method_content = extract_balanced_method_body(content, 'registerResources')

      unless method_content
        return { 'items' => [], 'error' => 'registerResources method not found' }
      end

      # Extract direct assignments to $this->resources
      resource_pattern = /\$this->resources\['([^']+)'\]\s*=\s*\[(.*?)\];/m

      method_content.scan(resource_pattern) do |resource_name, resource_config|
        name = extract_field_value(resource_config, 'name') || resource_name
        description = extract_field_value(resource_config, 'description')
        uri = extract_field_value(resource_config, 'uri')
        mime_type = extract_field_value(resource_config, 'mimeType')

        resources << {
          'name' => name,
          'key' => resource_name,
          'description' => description,
          'uri' => uri,
          'mime_type' => mime_type,
          'type' => 'standard',
          'category' => 'resource'
        }
      end

			resources << {
          'name' => 'Schema of {form_title} (x each form)',
					'description' => "Complete schema, element definitions, screens, and form connections (x each form).",
          'key' => 'form_schema',
          'uri' => 'formulize://schemas/{form_title}_(form_{form_id}).json',
          'type' => 'standard',
          'category' => 'resource'
        }

			resources << {
					'name' => "Perms for all groups on {form title} (x each form)",
					'description' => "All the permissions for all groups on a form (x each form).",
					'key' => 'form_perm_schema',
          'uri' => 'formulize://permissions/group_perms_for_{form_title}"."_(form_{form_id}).json',
          'type' => 'standard',
          'category' => 'resource'
        }

			resources << {
					'name' => "Perms for {group_name} on all forms (x each group)",
					'description' => "All the permissions for a group, on all forms (x each group).",
					'key' => 'group_perm_schema',
          'uri' => 'formulize://permissions/form_perms_for_{group_name}"."_(group_{group_id}).json',
          'type' => 'standard',
          'category' => 'resource'
        }

      {
        'items' => resources,
        'standard_items' => resources,
        'admin_items' => [],
        'total_count' => resources.length,
        'standard_count' => resources.length,
        'admin_count' => 0
      }
    end

    def extract_prompts_from_php(content)
			prompts = []
			admin_prompts = []

			# Find the registerPrompts method body (brace-counted; see comment on
			# extract_balanced_method_body for why a lazy regex isn't safe here)
			method_content = extract_balanced_method_body(content, 'registerPrompts')

			unless method_content
				return { 'items' => [], 'error' => 'registerPrompts method not found' }
			end

			# Extract the main prompts array assignment
			array_match = method_content.match(/\$this->prompts\s*=\s*\[(.*?)\];/m)

			if array_match
				array_content = array_match[1]

				# Extract standard prompts using bracket counting (like we did for tools)
				current_pos = 0
				while current_pos < array_content.length
					# Find next prompt definition
					prompt_match = array_content.match(/'([^']+)'\s*=>\s*\[/, current_pos)
					break unless prompt_match

					prompt_key = prompt_match[1]
					config_start = prompt_match.end(0) - 1  # Position of opening [

					# Count brackets to find the matching closing bracket
					bracket_count = 0
					config_end = config_start

					(config_start...array_content.length).each do |i|
						char = array_content[i]
						if char == '['
							bracket_count += 1
						elsif char == ']'
							bracket_count -= 1
							if bracket_count == 0
								config_end = i
								break
							end
						end
					end

					# Extract the complete prompt configuration
					prompt_config = array_content[config_start + 1...config_end]

					name = extract_field_value(prompt_config, 'name') || prompt_key
					description = extract_field_value(prompt_config, 'description')
					arguments = extract_arguments_from_config(prompt_config)

					prompts << {
						'name' => name,
						'key' => prompt_key,
						'description' => description,
						'arguments' => arguments,
						'type' => 'standard',
						'category' => 'prompt'
					}

					current_pos = config_end + 1
				end

				# Extract admin-only prompts (after the main array) - this part stays the same
				main_array_end = array_match.end(0)
				remaining_content = method_content[main_array_end..-1]

				admin_prompt_matches = remaining_content.scan(/\$this->prompts\['([^']+)'\]\s*=\s*\[(.*?)\];/m)

				admin_prompt_matches.each do |prompt_key, prompt_config|
					name = extract_field_value(prompt_config, 'name') || prompt_key
					description = extract_field_value(prompt_config, 'description')
					arguments = extract_arguments_from_config(prompt_config)

					admin_prompts << {
						'name' => name,
						'key' => prompt_key,
						'description' => description,
						'arguments' => arguments,
						'type' => 'admin_conditional',
						'category' => 'prompt'
					}
				end
			end

			all_prompts = (prompts + admin_prompts).sort_by { |prompt| prompt['name'] }

			{
				'items' => all_prompts,
				'standard_items' => prompts.sort_by { |prompt| prompt['name'] },
				'admin_items' => admin_prompts.sort_by { |prompt| prompt['name'] },
				'total_count' => all_prompts.length,
				'standard_count' => prompts.length,
				'admin_count' => admin_prompts.length
			}
		end

    def extract_field_value(config, field_name)
      field_match = config.match(/'#{field_name}'\s*=>\s*(?:'([^']*(?:\\.[^']*)*)'|"([^"]*(?:\\.[^"]*)*)")/m)
      value = field_match ? (field_match[1] || field_match[2]) : ''
      value.gsub(/\\'/, "'").gsub(/\\"/, '"')
    end

    def extract_arguments_from_config(config)
			arguments = []

			# Find the start of 'arguments' => [
			args_start = config.index("'arguments'")
			return arguments unless args_start

			# Find the opening bracket after 'arguments' =>
			bracket_start = config.index('[', args_start)
			return arguments unless bracket_start

			# Count brackets to find the matching closing bracket
			bracket_count = 0
			bracket_end = bracket_start

			(bracket_start...config.length).each do |i|
				char = config[i]
				if char == '['
					bracket_count += 1
				elsif char == ']'
					bracket_count -= 1
					if bracket_count == 0
						bracket_end = i
						break
					end
				end
			end

			# Extract the arguments array content
			args_content = config[bracket_start + 1...bracket_end]

			# Now extract individual argument objects using bracket counting
			current_pos = 0
			while current_pos < args_content.length
				# Find next argument array
				next_bracket = args_content.index('[', current_pos)
				break unless next_bracket

				# Count brackets to find the end of this argument
				bracket_count = 0
				arg_end = next_bracket

				(next_bracket...args_content.length).each do |i|
					char = args_content[i]
					if char == '['
						bracket_count += 1
					elsif char == ']'
						bracket_count -= 1
						if bracket_count == 0
							arg_end = i
							break
						end
					end
				end

				# Extract this argument's config
				arg_config = args_content[next_bracket + 1...arg_end]

				name = extract_field_value(arg_config, 'name')
				description = extract_field_value(arg_config, 'description')
				required = arg_config.include?("'required' => true")

				next if name.empty?

				arguments << {
					'name' => name,
					'description' => description,
					'required' => required
				}

				current_pos = arg_end + 1
			end

			arguments
		end
  end
end
