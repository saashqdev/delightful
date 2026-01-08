<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
return [
    'error' => [
        'common' => 'Common error',
        'common_validate_failed' => 'Common parameter validation failed',
        'common_business_exception' => 'Common business exception',
        'flow_node_validate_failed' => 'Node parameter validation failed',
        'message_error' => 'Common message error',
        'execute_failed' => 'Common execution failed',
        'execute_validate_failed' => 'Common execution validation failed',
        'knowledge_validate_failed' => 'Knowledge base validation failed',
        'access_denied' => 'Access denied',
    ],
    'system' => [
        'uid_not_found' => 'User uid missing',
        'unknown_authorization_type' => 'Unknown authorization type',
        'unknown_node_params_config' => ':label unknown node configuration',
    ],
    'common' => [
        'not_found' => ':label not found',
        'empty' => ':label cannot be empty',
        'repeat' => ':label is duplicated',
        'exist' => ':label already exists',
        'invalid' => ':label is invalid',
    ],
    'organization_code' => [
        'empty' => 'Organization code cannot be empty',
    ],
    'knowledge_code' => [
        'empty' => 'Knowledge code cannot be empty',
    ],
    'content' => [
        'empty' => 'Content cannot be empty',
    ],
    'flow_code' => [
        'empty' => 'Flow code cannot be empty',
    ],
    'flow_entity' => [
        'empty' => 'Flow entity cannot be empty',
    ],
    'name' => [
        'empty' => 'Name cannot be empty',
    ],
    'branches' => [
        'empty' => 'Branches cannot be empty',
    ],
    'conversation_id' => [
        'empty' => 'Conversation ID cannot be empty',
    ],
    'creator' => [
        'empty' => 'Creator cannot be empty',
    ],
    'model' => [
        'empty' => 'Model cannot be empty',
        'not_found' => '[:model_name] model not found',
        'disabled' => 'Model [:model_name] is disabled',
        'not_support_embedding' => '[:model_name] does not support embedding',
        'error_config_missing' => 'Configuration item :name is missing, please check related settings or contact administrator',
        'invalid_implementation_interface' => 'Implementation must be an instance of :interface',
        'embedding_failed' => '[:model_name] embedding failed, error message: [:error_message], please check embedding configuration',
        'vector_size_not_match' => '[:model_name] vector size mismatch, expected size: [:expected_size], actual size: [:actual_size], please check vector size',
    ],
    'knowledge_base' => [
        're_vectorized_not_support' => 'Re-vectorization not supported',
    ],
    'max_record' => [
        'positive_integer' => 'Max records can only be a positive integer between :min and :max',
    ],
    'nodes' => [
        'empty' => 'No nodes',
    ],
    'node_id' => [
        'empty' => 'Node ID cannot be empty',
    ],
    'node_type' => [
        'empty' => 'Node type cannot be empty',
        'unsupported' => 'Unsupported node type',
    ],
    'node_input' => [
        'empty' => 'Node[:label] input cannot be empty',
    ],
    'node_output' => [
        'empty' => 'Node[:label] output cannot be empty',
    ],
    'tool_set' => [
        'not_edit_default_tool_set' => 'Cannot edit default tool set',
    ],
    'node' => [
        'empty' => 'Node cannot be empty',
        'execute_num_limit' => 'Node[:name] execution count exceeds maximum limit',
        'duplication_node_id' => 'Node ID[:node_id] is duplicated',
        'single_debug_not_support' => 'Single point debugging not supported',
        'cache_key' => [
            'empty' => 'Cache key cannot be empty',
            'string_only' => 'Cache key must be a string',
        ],
        'cache_value' => [
            'empty' => 'Cache value cannot be empty',
            'string_only' => 'Cache value must be a string',
        ],
        'cache_ttl' => [
            'empty' => 'Cache TTL cannot be empty',
            'int_only' => 'Cache TTL must be a positive integer',
        ],
        'cannot_enable_empty_nodes' => 'Current flow has no nodes configured, cannot be enabled',
        'validation_failed' => 'Node[:node_id][:node_type] validation failed: :error',
        'code' => [
            'empty' => 'Code cannot be empty',
            'empty_language' => 'Code language cannot be empty',
            'unsupported_code_language' => '[:language] unsupported code language',
            'execute_failed' => 'Code execution failed | :error',
            'execution_error' => 'Code execution error: :error',
        ],
        'http' => [
            'api_request_fail' => 'API request failed | :error',
            'output_error' => 'Output non-compliant | :error',
        ],
        'intent' => [
            'empty' => 'Intent cannot be empty',
            'title_empty' => 'Title cannot be empty',
            'desc_empty' => 'Description cannot be empty',
        ],
        'knowledge' => [
            'knowledge_code_empty' => 'Knowledge code cannot be empty',
        ],
        'knowledge_fragment_store' => [
            'knowledge_code_empty' => 'Knowledge code cannot be empty',
            'content_empty' => 'Text fragment cannot be empty',
        ],
        'knowledge_similarity' => [
            'knowledge_codes_empty' => 'Knowledge codes cannot be empty',
            'query_empty' => 'Search content cannot be empty',
            'limit_valid' => 'Quantity can only be a positive integer between :min and :max',
            'score_valid' => 'Similarity can only be a decimal between 0-1',
        ],
        'llm' => [
            'tools_execute_failed' => 'Tool execution failed | :error',
        ],
        'loop' => [
            'relation_id_empty' => 'Associated loop body ID cannot be empty',
            'origin_flow_not_found' => '[:label] flow not found',
            'count_format_error' => 'Count loop must be a positive integer',
            'array_format_error' => 'Loop array must be an array',
            'max_loop_count_format_error' => 'Max iteration count can only be a positive integer between :min and :max',
            'loop_flow_execute_failed' => 'Loop body execution failed :error',
        ],
        'start' => [
            'only_one' => 'Start node can only have one',
            'must_exist' => 'Start node must exist',
            'unsupported_trigger_type' => '[:trigger_type] unsupported trigger type',
            'unsupported_unit' => '[:unit] unsupported time unit',
            'content_empty' => 'Message cannot be empty',
            'interval_valid' => 'Interval time must be a positive integer',
            'unsupported_routine_type' => 'Unsupported routine type',
            'input_key_conflict' => 'Field name [:key] conflicts with system reserved field, please use another name',
            'json_schema_validation_failed' => 'JSON Schema format error: :error',
        ],
        'sub' => [
            'flow_not_found' => 'Sub-flow [:flow_code] not found',
            'start_node_not_found' => 'Sub-flow[:flow_code] start node not found',
            'end_node_not_found' => 'Sub-flow[:flow_code] end node not found',
            'execute_failed' => 'Sub-flow[:flow_name] execution failed :error',
            'flow_id_empty' => 'Sub-flow ID cannot be empty',
        ],
        'text_embedding' => [
            'text_empty' => 'Text cannot be empty',
        ],
        'text_splitter' => [
            'text_empty' => 'Text cannot be empty',
        ],
        'variable' => [
            'name_empty' => 'Variable name cannot be empty',
            'name_invalid' => 'Variable name can only contain letters, numbers, underscores, and cannot start with a number',
            'value_empty' => 'Variable value cannot be empty',
            'value_format_error' => 'Variable value format error',
            'variable_not_exist' => 'Variable [:var_name] does not exist',
            'variable_not_array' => 'Variable [:var_name] is not an array',
            'element_list_empty' => 'Element list cannot be empty',
        ],
        'message' => [
            'type_error' => 'Message type error',
            'unsupported_message_type' => 'Unsupported message type',
            'content_error' => 'Message content error',
        ],
        'knowledge_fragment_remove' => [
            'metadata_business_id_empty' => 'Metadata or business ID cannot be empty',
        ],
        'tool' => [
            'tool_id_empty' => 'Tool ID cannot be empty',
            'flow_not_found' => 'Tool [:flow_code] not found',
            'start_node_not_found' => 'Tool[:flow_code] start node not found',
            'end_node_not_found' => 'Tool[:flow_code] end node not found',
            'execute_failed' => 'Tool[:flow_name] execution failed :error',
            'name' => [
                'invalid_format' => 'Tool name can only contain letters, numbers, and underscores',
            ],
        ],
    ],
    'executor' => [
        'unsupported_node_type' => '[:node_type] unsupported node type',
        'has_circular_dependencies' => '[:label] has circular dependencies',
        'unsupported_trigger_type' => 'Unsupported trigger type',
        'unsupported_flow_type' => 'Unsupported flow type',
        'node_execute_count_reached' => 'Reached maximum global node execution count (:max_count)',
    ],
    'component' => [
        'format_error' => '[:label] format error',
    ],
    'export' => [
        'not_main_flow' => 'Export flow failed: [:label] is not main flow',
        'circular_dependency_detected' => 'Export flow failed: circular dependency detected',
    ],
    'import' => [
        'missing_main_flow' => 'Import flow failed: missing main flow data',
        'missing_import_data' => 'Import flow failed: missing import data',
        'main_flow_failed' => 'Import main flow failed: :error',
        'failed' => 'Import flow [:label] failed',
        'tool_set_failed' => 'Import tool set [:name] failed: :error',
        'tool_flow_failed' => 'Import tool flow [:name] failed: :error',
        'sub_flow_failed' => 'Import sub-flow [:name] failed: :error',
        'associate_agent_failed' => 'Associate agent failed: :error',
        'missing_data' => 'Import flow failed: missing agent or flow data',
    ],
    'fields' => [
        'flow_name' => 'Flow name',
        'flow_type' => 'Flow type',
        'organization_code' => 'Organization code',
        'creator' => 'Creator',
        'creator_uid' => 'Creator UID',
        'tool_name' => 'Tool name',
        'tool_description' => 'Tool description',
        'nodes' => 'Node list',
        'node' => 'Node',
        'api_key' => 'API key',
        'api_key_name' => 'API key name',
        'test_case_name' => 'Test case name',
        'flow_code' => 'Flow code',
        'created_at' => 'Creation time',
        'case_config' => 'Test configuration',
        'nickname' => 'Nickname',
        'chat_time' => 'Chat time',
        'message_type' => 'Message type',
        'content' => 'Content',
        'open_time' => 'Open time',
        'trigger_type' => 'Trigger type',
        'message_id' => 'Message ID',
        'type' => 'Type',
        'analysis_result' => 'Analysis result',
        'model_name' => 'Model name',
        'implementation' => 'Implementation',
        'vector_size' => 'Vector size',
        'conversation_id' => 'Conversation ID',
        'modifier' => 'Modifier',
    ],
    'cache' => [
        'validation_failed' => 'Cache validation failed',
        'not_found' => 'Cache does not exist',
        'expired' => 'Cache expired',
        'operation_failed' => 'Cache operation failed',
        'cache_prefix' => [
            'empty' => 'Cache prefix cannot be empty',
            'too_long' => 'Cache prefix cannot exceed {max} characters',
        ],
        'cache_key' => [
            'empty' => 'Cache key cannot be empty',
            'too_long' => 'Cache key cannot exceed {max} characters',
        ],
        'cache_key_hash' => [
            'invalid_length' => 'Cache key hash length must be {expected} characters',
        ],
        'scope_tag' => [
            'empty' => 'Scope tag cannot be empty',
            'too_long' => 'Scope tag cannot exceed {max} characters',
        ],
        'organization_code' => [
            'empty' => 'Organization code cannot be empty',
            'too_long' => 'Organization code cannot exceed {max} characters',
        ],
        'ttl' => [
            'invalid_range' => 'TTL seconds must be between {min} and {max} (-1/0/null represents permanent cache)',
        ],
        'creator' => [
            'too_long' => 'Creator cannot exceed {max} characters',
        ],
        'modifier' => [
            'too_long' => 'Modifier cannot exceed {max} characters',
        ],
        'extend' => [
            'negative_seconds' => 'Extension time cannot be negative',
            'exceeds_maximum' => 'Extended TTL cannot exceed maximum value {max}',
        ],
        'id' => [
            'invalid' => 'ID must be a positive integer, current value: {id}',
        ],
        'prefix' => [
            'invalid_format' => 'Cache prefix format invalid',
        ],
    ],
];
