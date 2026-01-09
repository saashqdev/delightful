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

// recursionhandleconfigurationvaluemiddleenvironmentvariable
function processConfigValue(&$value): void
{
    if (is_string($value)) {
        // stringtype:parseenvironmentvariable
        $parts = explode('|', $value);
        if (count($parts) > 1) {
            $value = env($parts[0], $parts[1]);
        } else {
            $value = env($parts[0], $parts[0]);
        }
    } elseif (is_array($value)) {
        // arraytype:recursionhandleeachyuan素,retainarraystructure
        foreach ($value as &$item) {
            processConfigValue($item);
        }
    }
    // othertype(如 int, bool etc):retain原value,notconductparse
}

// handleconfigurationmiddleenvironmentvariable
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

    // 优雅printloadsuccessmodel
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

// loaddefaultmodelconfiguration(prioritylevelmostlow)
$models = [];

// loaddefaultmodelconfiguration
foreach ($envModelConfigs as $modelKey => $config) {
    processModelConfig($config, $modelKey);
    $models[$modelKey] = $config;
}

// load odin_models.json configuration(prioritylevelmorehigh,willoverridedefaultconfiguration)
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
                'connection' => 5.0,  // connecttimeout(second)
                'write' => 10.0,      // writetimeout(second)
                'read' => 300.0,      // readtimeout(second)
                'total' => 350.0,     // 总bodytimeout(second)
                'thinking' => 120.0,  // thinktimeout(second)
                'stream_chunk' => 30.0, // streampiecebetweentimeout(second)
                'stream_first' => 60.0, // 首streampiecetimeout(second)
            ],
            'custom_error_mapping_rules' => [],
            'logging' => [
                // logfield白名singleconfiguration
                // iffornullarrayornotconfiguration,thenprint所havefield
                // ifconfigurationfieldcolumntable,thenonlyprintfinger定field
                // support嵌setfield,usepoint语法如 'args.messages'
                // notice:messages and tools fieldnotin白名singlemiddle,notwillbeprint
                'whitelist_fields' => [
                    // basicrequestinfo
                    'request_id',                  // requestID
                    'model_id',                    // modelID
                    'model',                       // modelname
                    'duration_ms',                 // request耗o clock
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

                    // usequantitystatistics
                    'usage',                       // completeusageobject
                    'usage.input_tokens',          // inputtokenquantity
                    'usage.output_tokens',         // outputtokenquantity
                    'usage.total_tokens',          // 总tokenquantity

                    // requestparameter(rowexcept敏感content)
                    'args.temperature',            // 温degreeparameter
                    'args.max_tokens',             // mostbigtokenlimit
                    'args.max_completion_tokens',             // mostbigtokenlimit
                    'args.top_p',                  // Top-pparameter
                    'args.top_k',                  // Top-kparameter
                    'args.frequency_penalty',      // frequency惩罚
                    'args.presence_penalty',       // 存in惩罚
                    'args.stream',                 // streamresponse标志
                    'args.stop',                   // stop词
                    'args.seed',                   // randomtype子

                    // Token预估info
                    'token_estimate',              // Token估算detail
                    'token_estimate.input_tokens', // 估算inputtokens
                    'token_estimate.output_tokens', // 估算outputtokens

                    // responsecontent(rowexceptspecificcontent)
                    'choices.0.finish_reason',     // completereason
                    'choices.0.index',             // chooseindex

                    // errorinfo
                    'error',                       // errordetail
                    'error.type',                  // errortype
                    'error.message',               // errormessage(notcontainspecificcontent)

                    // otheryuandata
                    'created',                     // createtime戳
                    'id',                         // requestID
                    'object',                     // objecttype
                    'system_fingerprint',         // systemfinger纹
                    'performance_flag',            // performancemark(slowrequestidentifier)

                    // notice:bydownfieldberowexcept,notwillprint
                    // - args.messages (usermessagecontent)
                    // - args.tools (tooldefinition)
                    // - choices.0.message (responsemessagecontent)
                    // - choices.0.delta (streamresponse增quantitycontent)
                    // - content (responsecontent)
                ],
                // whetherenablefield白名singlefilter,defaulttrue(enablefilter)
                'enable_whitelist' => env('ODIN_LOG_WHITELIST_ENABLED', true),
                // mostbigstringlengthlimit,超passthislengthstringwillbereplacefor [Long Text],settingfor 0 indicatenotlimit
                'max_text_length' => env('ODIN_LOG_MAX_TEXT_LENGTH', 0),
            ],
            'network_retry_count' => 1,
        ],
        'models' => $models,
        // all局model options,canbemodelitself options override
        'model_options' => [
            'error_mapping_rules' => [
                // example:customizeerrormapping
                // 'customizeerrorkeyword' => \Hyperf\Odin\Exception\LLMException\LLMTimeoutError::class,
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
