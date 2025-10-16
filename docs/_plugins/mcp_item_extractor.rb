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
        # Extract all MCP items
        all_items = {
          'tools' => extract_from_file(File.join(mcp_dir, 'tools.php'), 'tools'),
          'resources' => extract_from_file(File.join(mcp_dir, 'resources.php'), 'resources'),
          'prompts' => extract_from_file(File.join(mcp_dir, 'prompts.php'), 'prompts')
        }

        # Calculate totals
        tools_data = all_items['tools']
        resources_data = all_items['resources']
        prompts_data = all_items['prompts']

        # Combine all data
        combined_data = {
          'tools' => tools_data['items'] || [],
          'resources' => resources_data['items'] || [],
          'prompts' => prompts_data['items'] || [],
          'tools_standard' => tools_data['standard_items'] || [],
          'tools_admin' => tools_data['admin_items'] || [],
          'resources_standard' => resources_data['standard_items'] || [],
          'resources_admin' => resources_data['admin_items'] || [],
          'prompts_standard' => prompts_data['standard_items'] || [],
          'prompts_admin' => prompts_data['admin_items'] || [],
          'total_count' => (tools_data['total_count'] || 0) + (resources_data['total_count'] || 0) + (prompts_data['total_count'] || 0),
          'tools_count' => tools_data['total_count'] || 0,
          'resources_count' => resources_data['total_count'] || 0,
          'prompts_count' => prompts_data['total_count'] || 0,
          'extracted_at' => Time.now.strftime('%Y-%m-%d %H:%M:%S %Z'),
          'source_dir' => mcp_dir,
          'debug_info' => {
            'jekyll_source' => site.source,
            'mcp_directory' => mcp_dir,
            'tools_file_size' => File.exist?(File.join(mcp_dir, 'tools.php')) ? File.size(File.join(mcp_dir, 'tools.php')) : 0,
            'resources_file_size' => File.exist?(File.join(mcp_dir, 'resources.php')) ? File.size(File.join(mcp_dir, 'resources.php')) : 0,
            'prompts_file_size' => File.exist?(File.join(mcp_dir, 'prompts.php')) ? File.size(File.join(mcp_dir, 'prompts.php')) : 0
          }
        }

        # Add any errors
        errors = []
        errors << tools_data['error'] if tools_data['error']
        errors << resources_data['error'] if resources_data['error']
        errors << prompts_data['error'] if prompts_data['error']
        combined_data['errors'] = errors unless errors.empty?

        # Store in Jekyll's data for use in templates
        site.data['mcp_items'] = combined_data

        Jekyll.logger.info "MCP extractor", "Extracted #{combined_data['total_count']} MCP items (#{combined_data['tools_count']} tools, #{combined_data['resources_count']} resources, #{combined_data['prompts_count']} prompts)"
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
      when 'tools'
        extract_tools_from_php(content)
      when 'resources'
        extract_resources_from_php(content)
      when 'prompts'
        extract_prompts_from_php(content)
      else
        { 'items' => [], 'error' => "Unknown item type: #{item_type}" }
      end
    end

    def extract_tools_from_php(content)
      tools = []
      admin_only_tools = []

      # Find the registerTools method
      register_match = content.match(/private\s+function\s+registerTools\(\)\s*\{(.*?)\n\s*\}/m)

      unless register_match
        return { 'items' => [], 'error' => 'registerTools method not found' }
      end

      method_content = register_match[1]

      # Extract the main tools array assignment
      array_match = method_content.match(/\$this->tools\s*=\s*\[(.*?)\];/m)

      unless array_match
        return { 'items' => [], 'error' => 'tools array assignment not found' }
      end

      array_content = array_match[1]
			skip_tools = ['dynamic_server_name', 'locate_captain_picard', 'open_the_pod_bay_doors_hal', 'lets_play_global_thermonuclear_war']
			# Replace the tool_pattern scanning with bracket-counting approach
			current_pos = 0
			while current_pos < array_content.length
				# Find next tool definition
				tool_match = array_content.match(/(?:'([^']+)'|\$this->mcpRequest\['localServerName'\])\s*=>\s*\[/, current_pos)
				break unless tool_match

				tool_name = tool_match[1] || "dynamic_server_name"
				config_start = tool_match.end(0) - 1  # Position of opening [

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

				# Extract the complete tool configuration
				tool_config = array_content[config_start + 1...config_end]
				description = extract_description(tool_config)

				if !skip_tools.include?(tool_name)
					tools << {
						'name' => tool_name,
						'description' => description,
						'type' => 'standard',
						'category' => 'tool'
					}
				end

				current_pos = config_end + 1
			end

			tools << {
          'name' => 'cache_stats',
          'description' => 'The local MCP server caches information to reduce network traffic and the load on the Formulize system. Use this tool to see the status of the cache.',
          'type' => 'standard',
          'category' => 'tool'
        }
			tools << {
          'name' => 'cache_refresh',
          'description' => 'The local MCP server caches information to reduce network traffic and the load on the Formulize system. Use this tool to clear the cache.',
          'type' => 'standard',
          'category' => 'tool'
        }


      # Extract admin-only tools
      main_array_end = array_match.end(0)
      remaining_content = method_content[main_array_end..-1]

      admin_tool_matches = remaining_content.scan(/\$this->tools\['([^']+)'\]\s*=\s*\[(.*?)\];/m)

      admin_tool_matches.each do |tool_name, tool_config|
        description = extract_description(tool_config)
        description = description.gsub(/\{\$dbVersionData\['version'\]\}/, 'MariaDB/MySQL')

        tool_type = tool_name == 'read_system_activity_log' ? 'admin_conditional' : 'admin_only'

        admin_only_tools << {
          'name' => tool_name,
          'description' => description,
          'type' => tool_type,
          'category' => 'tool'
        }
      end

			admin_only_tools << {
				'name' => 'create_text_box_element',
				'description' => 'Create a new text box element (field) in a specified form.',
				'type' => 'admin_only',
				'category' => 'tool'
			}
    	admin_only_tools << {
				'name' => 'create_list_element',
      	'description' => 'Create a new list element (field) in a specified form.',
				'type' => 'admin_only',
				'category' => 'tool'
			}
    	admin_only_tools << {
				'name' => 'create_linked_list_element',
				'description' => 'Create a new list element (field) in a specified form, with options linked to entries in another form.',
				'type' => 'admin_only',
				'category' => 'tool'
			}
    	admin_only_tools << {
				'name' => 'create_user_list_element',
				'description' => 'Create a new list element (field) in a specified form, with options based on user accounts in the system.',
				'type' => 'admin_only',
				'category' => 'tool'
			}
    	admin_only_tools << {
				'name' => 'create_selector_element',
      	'description' => 'Create a new selector element (field) in a specified form, such as date selector, time selector, etc.',
				'type' => 'admin_only',
				'category' => 'tool'
			}
    	admin_only_tools << {
				'name' => 'create_subform_interface',
				'description' => 'Create a new subform interface in a specified form, for displaying entries in a connected form.',
				'type' => 'admin_only',
				'category' => 'tool'
			}
    	admin_only_tools << {
				'name' => 'update_text_box_element',
				'description' => 'Update an existing text box element (field) in a specified form.',
				'type' => 'admin_only',
				'category' => 'tool'
			}
    	admin_only_tools << {
				'name' => 'update_list_element',
				'description' => 'Update an existing list element (field) in a specified form.',
				'type' => 'admin_only',
				'category' => 'tool'
			}
    	admin_only_tools << {
				'name' => 'update_linked_list_element',
				'description' => 'Update an existing linked list element (field) in a specified form, with options linked to entries in another form.',
				'type' => 'admin_only',
				'category' => 'tool'
			}
    	admin_only_tools << {
				'name' => 'update_user_list_element',
				'description' => 'Update an existing user list element (field) in a specified form, with options based on user accounts in the system.',
				'type' => 'admin_only',
				'category' => 'tool'
			}
    	admin_only_tools << {
				'name' => 'update_selector_element',
				'description' => 'Update an existing selector element (field) in a specified form, such as date selector, time selector, etc.',
				'type' => 'admin_only',
				'category' => 'tool'
			}
    	admin_only_tools << {
				'name' => 'update_subform_interface',
      	'description' => 'Update an existing subform interface in a specified form, for displaying entries in a connected form.',
				'type' => 'admin_only',
				'category' => 'tool'
			}

      all_tools = (tools + admin_only_tools)

      {
        'items' => all_tools,
        'standard_items' => tools,
        'admin_items' => admin_only_tools,
        'total_count' => all_tools.length,
        'standard_count' => tools.length,
        'admin_count' => admin_only_tools.length
      }
    end

    def extract_resources_from_php(content)
      resources = []

      # Find the registerResources method
      register_match = content.match(/private\s+function\s+registerResources\(\)\s*\{(.*?)\n\s*\}/m)

      unless register_match
        return { 'items' => [], 'error' => 'registerResources method not found' }
      end

      method_content = register_match[1]

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

			# Find the registerPrompts method
			register_match = content.match(/private\s+function\s+registerPrompts\(\)\s*\{(.*?)\n\s*\}/m)

			unless register_match
				return { 'items' => [], 'error' => 'registerPrompts method not found' }
			end

			method_content = register_match[1]

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

		def extract_description(config)
			# Try standard format first
			description_match = config.match(/'description'\s*=>\s*'([\s\S]*?)',/m)

			# If that doesn't work, try the newline format
			if !description_match
				description_match = config.match(/'description'\s*=>\s*\n'([\s\S]*?)',/m)
			end

			# If still no match, try quoted format
			if !description_match
				description_match = config.match(/'description'\s*=>\s*"([\s\S]*?)",/m)
			end

			description = description_match ? description_match[1] : ''
			description = description.gsub(/\\'/, "'").gsub(/\\"/, '"')

			# Split on blank lines and take only the first part BEFORE cleaning whitespace
			if description.include?("\n\n")
				description_parts = description.split("\n\n")
				description = description_parts[0] || description
			end

			# Clean up whitespace AFTER splitting
			description = description.gsub(/\n\s*/, ' ').strip

			return description
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
