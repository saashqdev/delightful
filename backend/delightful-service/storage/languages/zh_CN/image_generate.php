<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
return [
    'general_error' => 'Image generation service error',
    'response_format_error' => 'Image generation response format error',
    'missing_image_data' => 'Generated image data error',
    'no_valid_image_generated' => 'No valid image generated',
    'input_image_audit_failed' => 'The image you sent does not comply with relevant regulations and requirements, unable to generate corresponding image',
    'output_image_audit_failed' => 'Output image post-audit failed',
    'input_text_audit_failed' => 'The text you sent does not comply with relevant regulations and requirements, unable to generate corresponding image',
    'output_text_audit_failed' => 'Output text post-audit failed',
    'text_blocked' => 'Input text contains sensitive content and has been blocked',
    'invalid_prompt' => 'Prompt content is invalid',
    'prompt_check_failed' => 'Prompt validation failed',
    'polling_failed' => 'Failed to poll task result',
    'task_timeout' => 'Task execution timeout',
    'invalid_request_type' => 'Invalid request type',
    'missing_job_id' => 'Missing job ID',
    'task_failed' => 'Task execution failed',
    'polling_response_format_error' => 'Polling response format error',
    'missing_image_url' => 'Failed to retrieve image URL',
    'prompt_check_response_error' => 'Prompt validation response format error',
    'api_request_failed' => 'Failed to call image generation API',
    'image_to_image_missing_source' => 'Image-to-image missing source: image or base64',
    'output_image_audit_failed_with_reason' => 'Unable to generate image, please try changing the prompt',
    'task_timeout_with_reason' => 'Image generation task not found or has expired',
    'not_found_error_code' => 'Unknown error code',
    'unsupported_image_format' => 'Only JPG, JPEG, BMP, PNG format images are supported',
    'invalid_aspect_ratio' => 'Image-to-image aspect ratio difference is too large, can only differ by 3 times',
    'image_url_is_empty' => 'Image is empty',
    'unsupported_image_size' => 'Unsupported image size :size, supported sizes are: :supported_sizes',
    'unsupported_image_size_range' => 'Image size :size exceeds supported range, width and height must be between :min_size-:max_size pixels',

    // Azure OpenAI related error messages
    'api_key_update_failed' => 'Failed to update API key',
    'prompt_required' => 'Image generation prompt cannot be empty',
    'reference_images_required' => 'Image editing requires reference images',
    'invalid_image_count' => 'Image generation count must be between 1-10',
    'invalid_image_url' => 'Invalid reference image URL format',
    'invalid_mask_url' => 'Invalid mask image URL format',
    'no_image_generated' => 'No images generated',
    'invalid_image_data' => 'All image data invalid',
    'no_valid_image_data' => 'No valid image data',
    'response_build_failed' => 'Failed to build response',
    'api_call_failed' => 'API call failed',
    'request_conversion_failed' => 'Request format conversion failed',
    'invalid_size_format' => 'Invalid size format, should be WIDTHxHEIGHT format',
    'invalid_quality_parameter' => 'Invalid quality parameter, valid options are: standard, hd',

    'model_not_support_edit' => 'Model does not support image editing',
    # Watermark related
    'image_watermark' => 'MagiAI Generated',
];
