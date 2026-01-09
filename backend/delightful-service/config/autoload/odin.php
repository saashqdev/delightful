<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Infrastructure\Core\Hyperf\Odin\Model\MiscEmbeddingModel;
use Hyperf\Odin\Model\AwsBedrockModel;
use Hyperf\Odin\Model\AzureOpenAIModel;
use Hyperf\Odin\Model\DoubaoModel;
use Hyperf\Odin\Model\OpenAIModel;

use function Hyperf\Support\env;

// 递归handleconfigurationvalue中的环境variable
function processConfigValue(&$value): void
{
    if (is_string($value)) {
        // stringtype：parse环境variable
        $parts = explode('|', $value);
        if (count($parts) > 1) {
            $value = env($parts[0], $parts[1]);
        } else {
            $value = env($parts[0], $parts[0]);
        }
    } elseif (is_array($value)) {
        // arraytype：递归handle每个元素，保留array结构
        foreach ($value as &$item) {
            processConfigValue($item);
        }
    }
    // 其他type（如 int, bool 等）：保留原value，不进行parse
}

// handleconfiguration中的环境variable
function processModelConfig(&$modelItem, string $modelName): void
{
    // handlemodelvalue
    if (isset($modelItem['model'])) {
        $modelItemModel = explode('|', $modelItem['model']);
        if (count($modelItemModel) > 1) {
            $modelItem['model'] = env($modelItemModel[0], $modelItemModel[1]);
        } else {
            $modelItem['model'] = env($modelItemModel[0], $modelItemModel[0]);
        }
    } else {
        $modelItem['model'] = $modelName;
    }

    // handleconfigurationvalue
    if (isset($modelItem['config']) && is_array($modelItem['config'])) {
        foreach ($modelItem['config'] as &$item) {
            processConfigValue($item);
        }
    }

    // handle API optionvalue
    if (isset($modelItem['api_options']) && is_array($modelItem['api_options'])) {
        foreach ($modelItem['api_options'] as &$item) {
            processConfigValue($item);
        }
    }

    // 优雅的打印loadsuccess的model
    echo "\033[32m✓\033[0m modelloadsuccess: \033[1m" . $modelName . ' (' . $modelItem['model'] . ")\033[0m" . PHP_EOL;
}

$envModelConfigs = [];
// AzureOpenAI gpt-4o
if (env('AZURE_OPENAI_GPT4O_ENABLED', false)) {
    $envModelConfigs['gpt-4o-global'] = [
        'model' => 'AZURE_OPENAI_4O_GLOBAL_MODEL|gpt-4o-global',
        'implementation' => AzureOpenAIModel::class,
        'config' => [
            'api_key' => 'AZURE_OPENAI_4O_GLOBAL_API_KEY',
            'base_url' => 'AZURE_OPENAI_4O_GLOBAL_BASE_URL',
            'api_version' => 'AZURE_OPENAI_4O_GLOBAL_API_VERSION',
            'deployment_name' => 'AZURE_OPENAI_4O_GLOBAL_DEPLOYMENT_NAME',
        ],
        'model_options' => [
            'chat' => true,
            'function_call' => true,
            'embedding' => false,
            'multi_modal' => true,
            'vector_size' => 0,
        ],
    ];
}

// 豆packagePro 32k
if (env('DOUBAO_PRO_32K_ENABLED', false)) {
    $envModelConfigs['doubao-pro-32k'] = [
        'model' => 'DOUBAO_PRO_32K_ENDPOINT|doubao-1.5-pro-32k',
        'implementation' => DoubaoModel::class,
        'config' => [
            'api_key' => 'DOUBAO_PRO_32K_API_KEY',
            'base_url' => 'DOUBAO_PRO_32K_BASE_URL|https://ark.cn-beijing.volces.com',
        ],
        'model_options' => [
            'chat' => true,
            'function_call' => true,
            'embedding' => false,
            'multi_modal' => false,
            'vector_size' => 0,
        ],
    ];
}

// DeepSeek R1
if (env('DEEPSEEK_R1_ENABLED', false)) {
    $envModelConfigs['deepseek-r1'] = [
        'model' => 'DEEPSEEK_R1_ENDPOINT|deepseek-reasoner',
        'implementation' => OpenAIModel::class,
        'config' => [
            'api_key' => 'DEEPSEEK_R1_API_KEY',
            'base_url' => 'DEEPSEEK_R1_BASE_URL|https://api.deepseek.com',
        ],
        'model_options' => [
            'chat' => true,
            'function_call' => false,
            'embedding' => false,
            'multi_modal' => false,
            'vector_size' => 0,
        ],
    ];
}

// DeepSeek V3
if (env('DEEPSEEK_V3_ENABLED', false)) {
    $envModelConfigs['deepseek-v3'] = [
        'model' => 'DEEPSEEK_V3_ENDPOINT|deepseek-chat',
        'implementation' => OpenAIModel::class,
        'config' => [
            'api_key' => 'DEEPSEEK_V3_API_KEY',
            'base_url' => 'DEEPSEEK_V3_BASE_URL|https://api.deepseek.com',
        ],
        'model_options' => [
            'chat' => true,
            'function_call' => false,
            'embedding' => false,
            'multi_modal' => false,
            'vector_size' => 0,
        ],
    ];
}

// 豆package Embedding
if (env('DOUBAO_EMBEDDING_ENABLED', false)) {
    $envModelConfigs['doubao-embedding-text-240715'] = [
        'model' => 'DOUBAO_EMBEDDING_ENDPOINT|doubao-embedding-text-240715',
        'implementation' => DoubaoModel::class,
        'config' => [
            'api_key' => 'DOUBAO_EMBEDDING_API_KEY',
            'base_url' => 'DOUBAO_EMBEDDING_BASE_URL|https://ark.cn-beijing.volces.com',
        ],
        'model_options' => [
            'chat' => false,
            'function_call' => false,
            'multi_modal' => false,
            'embedding' => true,
            'vector_size' => env('DOUBAO_EMBEDDING_VECTOR_SIZE', 2560),
        ],
    ];
}

// dmeta-embedding
if (env('MISC_DMETA_EMBEDDING_ENABLED', false)) {
    $envModelConfigs['dmeta-embedding'] = [
        'model' => 'MISC_DMETA_EMBEDDING_ENDPOINT|dmeta-embedding',
        'implementation' => MiscEmbeddingModel::class,
        'config' => [
            'api_key' => 'MISC_DMETA_EMBEDDING_API_KEY',
            'base_url' => 'MISC_DMETA_EMBEDDING_BASE_URL',
        ],
        'model_options' => [
            'chat' => false,
            'function_call' => false,
            'multi_modal' => false,
            'embedding' => true,
            'vector_size' => env('MISC_DMETA_EMBEDDING_VECTOR_SIZE', 768),
        ],
    ];
}

// Aws claude3.7
if (env('AWS_CLAUDE_ENABLED', false)) {
    $envModelConfigs['claude-3-7'] = [
        'model' => 'AWS_CLAUDE_3_7_ENDPOINT|claude-3-7',
        'implementation' => AwsBedrockModel::class,
        'config' => [
            'access_key' => 'AWS_CLAUDE3_7_ACCESS_KEY',
            'secret_key' => 'AWS_CLAUDE3_7_SECRET_KEY',
            'region' => 'AWS_CLAUDE3_7_REGION|us-east-1',
        ],
        'model_options' => [
            'chat' => true,
            'function_call' => true,
            'multi_modal' => true,
            'embedding' => false,
            'vector_size' => 0,
        ],
        'api_options' => [
            'proxy' => env('AWS_CLAUDE3_7_PROXY', ''),
        ],
    ];
}

// loaddefaultmodelconfiguration（优先级最低）
$models = [];

// loaddefaultmodelconfiguration
foreach ($envModelConfigs as $modelKey => $config) {
    processModelConfig($config, $modelKey);
    $models[$modelKey] = $config;
}

// load odin_models.json configuration（优先级更高，willoverridedefaultconfiguration）
if (file_exists(BASE_PATH . '/odin_models.json')) {
    $customModels = json_decode(file_get_contents(BASE_PATH . '/odin_models.json'), true);
    if (is_array($customModels)) {
        foreach ($customModels as $key => $modelItem) {
            processModelConfig($modelItem, $key);
            $models[$key] = $modelItem;
        }
    }
}

return [
    'llm' => [
        'default' => '',
        'general_model_options' => [
            'chat' => true,
            'function_call' => false,
            'embedding' => false,
            'multi_modal' => false,
            'vector_size' => 0,
        ],
        'general_api_options' => [
            'timeout' => [
                'connection' => 5.0,  // connecttimeout（秒）
                'write' => 10.0,      // writetimeout（秒）
                'read' => 300.0,      // readtimeout（秒）
                'total' => 350.0,     // 总体timeout（秒）
                'thinking' => 120.0,  // 思考timeout（秒）
                'stream_chunk' => 30.0, // stream块间timeout（秒）
                'stream_first' => 60.0, // 首个stream块timeout（秒）
            ],
            'custom_error_mapping_rules' => [],
            'logging' => [
                // logfield白名单configuration
                // 如果为nullarray或未configuration，则打印所有field
                // 如果configuration了field列表，则只打印指定的field
                // 支持嵌套field，use点语法如 'args.messages'
                // 注意：messages 和 tools field不在白名单中，不will被打印
                'whitelist_fields' => [
                    // 基本requestinfo
                    'request_id',                  // requestID
                    'model_id',                    // modelID
                    'model',                       // modelname
                    'duration_ms',                 // request耗时
                    'url',                         // requestURL
                    'status_code',                 // responsestatus码

                    // options info
                    'options.headers',
                    'options.json.model',
                    'options.json.temperature',
                    'options.json.max_tokens',
                    'options.json.max_completion_tokens',
                    'options.json.stop',
                    'options.json.frequency_penalty',
                    'options.json.presence_penalty',
                    'options.json.business_params',
                    'options.json.thinking',

                    // use量statistics
                    'usage',                       // 完整的usageobject
                    'usage.input_tokens',          // inputtokenquantity
                    'usage.output_tokens',         // outputtokenquantity
                    'usage.total_tokens',          // 总tokenquantity

                    // requestparameter（排除敏感content）
                    'args.temperature',            // 温度parameter
                    'args.max_tokens',             // 最大token限制
                    'args.max_completion_tokens',             // 最大token限制
                    'args.top_p',                  // Top-pparameter
                    'args.top_k',                  // Top-kparameter
                    'args.frequency_penalty',      // 频率惩罚
                    'args.presence_penalty',       // 存在惩罚
                    'args.stream',                 // streamresponse标志
                    'args.stop',                   // stop词
                    'args.seed',                   // 随机种子

                    // Token预估info
                    'token_estimate',              // Token估算detail
                    'token_estimate.input_tokens', // 估算inputtokens
                    'token_estimate.output_tokens', // 估算outputtokens

                    // responsecontent（排除具体content）
                    'choices.0.finish_reason',     // complete原因
                    'choices.0.index',             // 选择索引

                    // errorinfo
                    'error',                       // errordetail
                    'error.type',                  // errortype
                    'error.message',               // errormessage（不contain具体content）

                    // 其他元data
                    'created',                     // create时间戳
                    'id',                         // requestID
                    'object',                     // objecttype
                    'system_fingerprint',         // 系统指纹
                    'performance_flag',            // performancemark（慢request标识）

                    // 注意：以下field被排除，不will打印
                    // - args.messages (usermessagecontent)
                    // - args.tools (tool定义)
                    // - choices.0.message (responsemessagecontent)
                    // - choices.0.delta (streamresponse增量content)
                    // - content (responsecontent)
                ],
                // 是否enablefield白名单filter，defaulttrue（enablefilter）
                'enable_whitelist' => env('ODIN_LOG_WHITELIST_ENABLED', true),
                // 最大stringlength限制，超过此length的string将被替换为 [Long Text]，setting为 0 表示不限制
                'max_text_length' => env('ODIN_LOG_MAX_TEXT_LENGTH', 0),
            ],
            'network_retry_count' => 1,
        ],
        'models' => $models,
        // 全局model options，可被model本身的 options override
        'model_options' => [
            'error_mapping_rules' => [
                // example：customizeerrormapping
                // 'customizeerror关键词' => \Hyperf\Odin\Exception\LLMException\LLMTimeoutError::class,
            ],
        ],
        'model_fixed_temperature' => [
            '%gpt-5%' => 1,
        ],
    ],
    'content_copy_keys' => [
        'request-id', 'x-b3-trace-id', 'FlowEventStreamManager::EventStream',
    ],
];
