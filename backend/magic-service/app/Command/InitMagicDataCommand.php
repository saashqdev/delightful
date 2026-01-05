<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Command;

use App\Domain\ModelGateway\Entity\AccessTokenEntity;
use App\Domain\ModelGateway\Entity\ApplicationEntity;
use App\Domain\ModelGateway\Entity\ModelConfigEntity;
use App\Domain\ModelGateway\Entity\ValueObject\AccessTokenType;
use App\Domain\ModelGateway\Entity\ValueObject\LLMDataIsolation;
use App\Domain\ModelGateway\Repository\Facade\AccessTokenRepositoryInterface;
use App\Domain\ModelGateway\Repository\Facade\ApplicationRepositoryInterface;
use App\Domain\ModelGateway\Repository\Facade\ModelConfigRepositoryInterface;
use DateTime;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

#[Command]
class InitMagicDataCommand extends HyperfCommand
{
    public function __construct(
        protected ContainerInterface $container,
        protected StdoutLoggerInterface $logger,
        protected AccessTokenRepositoryInterface $accessTokenRepository,
        protected ApplicationRepositoryInterface $applicationRepository,
        protected ModelConfigRepositoryInterface $modelConfigRepository,
    ) {
        // Normal mode (no model-gateway initialization)
        // php bin/hyperf.php init-magic:data
        // Unit-test mode (initializes model-gateway data)
        // php bin/hyperf.php init-magic:data --type=all --unit-test
        parent::__construct('init-magic:data');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Initialize required system data');

        // Option to continue execution when errors occur; default stops on error
        $this->addOption('continue-on-error', 'c', InputOption::VALUE_NONE, 'Continue when errors occur; by default execution stops on error');
        // Option to control model-gateway initialization when type=all
        $this->addOption('unit-test', 'u', InputOption::VALUE_NONE, 'Unit-test mode; when type=all decide whether to initialize model-gateway');
    }

    public function handle()
    {
        try {
            $this->initAllData();
        } catch (Throwable $e) {
            $this->logger->error(sprintf('Failed to initialize data: %s', $e->getMessage()));
            return 1; // Return non-zero status to indicate failure
        }

        return 0; // Return 0 to indicate success
    }

    /**
     * Initialize all data types.
     */
    protected function initAllData(): void
    {
        $this->logger->info('Initializing all data');
        $continueOnError = $this->input->getOption('continue-on-error');
        $isUnitTest = $this->input->getOption('unit-test');

        // Check whether magic_contact_users already has user data
        try {
            $userCount = Db::table('magic_contact_users')->count();
            if ($userCount > 0) {
                $this->logger->info("magic_contact_users already has {$userCount} user records; initialization complete");
                return;
            }
        } catch (Throwable $e) {
            $this->logger->error('Failed to check magic_contact_users user data: ' . $e->getMessage());
            // If the check fails, continue initialization
        }

        try {
            // Invoke each initialization routine
            $this->initUserData();
        } catch (Throwable $e) {
            $this->logger->error('Failed to initialize user data: ' . $e->getMessage());
            if (! $continueOnError) {
                throw $e;
            }
        }

        // Initialize model gateway data only in unit-test mode
        if ($isUnitTest) {
            try {
                $this->initModelGatewayData();
            } catch (Throwable $e) {
                $this->logger->error('Failed to initialize model gateway data: ' . $e->getMessage());
                if (! $continueOnError) {
                    throw $e;
                }
            }
        } else {
            $this->logger->info('Skipping model gateway initialization (non-unit-test mode)');
        }

        try {
            $this->runAllDbSeeders();
        } catch (Throwable $e) {
            $this->logger->error('Failed to run database seeders: ' . $e->getMessage());
            if (! $continueOnError) {
                throw $e;
            }
        }

        $this->logger->info('All data initialization completed');
    }

    /**
     * Initialize user data.
     */
    protected function initUserData(): void
    {
        $this->logger->info('Initializing user data');
        $continueOnError = $this->input->getOption('continue-on-error');

        try {
            // Place user initialization logic here
            // ...

            $this->logger->info('User data initialization complete');
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Failed to initialize user data: %s, file:%s line:%s trace:%s',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            ));

            if (! $continueOnError) {
                throw $e;
            }
        }
    }

    /**
     * Initialize API access tokens.
     */
    protected function initModelGatewayData(): void
    {
        $this->logger->info('Initializing ModelGateway data');
        $continueOnError = $this->input->getOption('continue-on-error');

        $dataIsolation = new LLMDataIsolation('system', 'default');

        try {
            // Initialize tokens
            $this->initAccessTokens($dataIsolation);
        } catch (Throwable $e) {
            $this->logger->error('Failed to initialize access tokens: ' . $e->getMessage());
            if (! $continueOnError) {
                throw $e;
            }
        }

        try {
            // Initialize model configuration data
            $this->initModelConfigs();
        } catch (Throwable $e) {
            $this->logger->error('Failed to initialize model configuration: ' . $e->getMessage());
            if (! $continueOnError) {
                throw $e;
            }
        }

        $this->logger->info('ModelGateway data initialization completed');
    }

    /**
     * Initialize access token configuration.
     */
    protected function initAccessTokens(LLMDataIsolation $dataIsolation): void
    {
        $this->logger->info('Starting access token initialization');
        $continueOnError = $this->input->getOption('continue-on-error');

        // Token configuration definitions
        $tokenConfigs = [
            // User-wide token
            [
                'type' => AccessTokenType::User->value,
                'name' => 'User General Token',
                'description' => 'API token for users to access all models',
                'models' => 'all',
                'tokenValue' => env('UNIT_TEST_USER_TOKEN', ''),
                'user_id' => 'default_user',
                'total_amount' => 9999999,
            ],
            // Application-wide token
            [
                'type' => AccessTokenType::Application->value,
                'name' => 'Application General Token',
                'description' => 'API token for applications to access all models',
                'models' => 'all',
                'tokenValue' => env('MAGIC_ACCESS_TOKEN', ''),
                'app_code' => 'default_app',
                'app_name' => 'Default Application',
                'app_description' => 'Application created by default',
                'total_amount' => 9999999,
                'user_id' => 'default_user',
            ],
        ];

        foreach ($tokenConfigs as $config) {
            try {
                $this->createOrUpdateToken($dataIsolation, $config);
            } catch (Throwable $e) {
                $this->logger->error(sprintf(
                    'Failed to initialize access token: %s, name: %s file:%s line:%s trace:%s',
                    $e->getMessage(),
                    $config['name'],
                    $e->getFile(),
                    $e->getLine(),
                    $e->getTraceAsString()
                ));

                // If not continuing on error, abort execution
                if (! $continueOnError) {
                    $this->logger->error('Stopping due to error; use --continue-on-error to ignore errors');
                    throw $e;
                }
            }
        }

        $this->logger->info('Access token initialization complete');
    }

    /**
     * Create or update an access token.
     */
    protected function createOrUpdateToken(LLMDataIsolation $dataIsolation, array $config): void
    {
        // Check whether a token with the same name exists
        $existingToken = $this->accessTokenRepository->getByName($dataIsolation, $config['name']);

        if ($existingToken !== null) {
            $this->logger->info(sprintf(
                'ModelGateway token name %s already exists; skipping initialization',
                $config['name']
            ));
            return;
        }

        // Resolve token value
        $tokenValue = $config['tokenValue'] ?? '10086';
        if (empty($tokenValue) && $config['type'] === AccessTokenType::Application->value) {
            $tokenValue = Uuid::uuid4()->toString();
        }

        // Create access token
        $accessToken = new AccessTokenEntity();
        $accessToken->setAccessToken($tokenValue);
        $accessToken->setType(AccessTokenType::from($config['type']));
        $accessToken->setName($config['name']);
        $accessToken->setDescription($config['description']);
        $accessToken->setModels([$config['models']]);
        $accessToken->setIpLimit([]);
        $accessToken->setTotalAmount($config['total_amount']);
        $accessToken->setUseAmount(0);
        $accessToken->setRpm(0);
        $accessToken->setOrganizationCode('default');
        $accessToken->setCreator('system');
        $accessToken->setModifier('system');
        $accessToken->setCreatedAt(new DateTime());
        $accessToken->setUpdatedAt(new DateTime());

        // Handle relation ID
        if ($config['type'] === AccessTokenType::Application->value) {
            $applicationEntity = $this->getOrCreateApplication($dataIsolation, $config);
            $accessToken->setRelationId($applicationEntity->getCode());
        } else {
            $accessToken->setRelationId('system');
        }

        // Persist to database
        $savedToken = $this->accessTokenRepository->save($dataIsolation, $accessToken);

        $this->logger->info(sprintf(
            'Initialized ModelGateway token. Type: %s, Name: %s, Token: %s',
            $config['type'],
            $config['name'],
            $savedToken->getAccessToken()
        ));
    }

    /**
     * Get or create an application.
     */
    protected function getOrCreateApplication(LLMDataIsolation $dataIsolation, array $config): ApplicationEntity
    {
        $continueOnError = $this->input->getOption('continue-on-error');

        try {
            // Check whether the application already exists
            $existingApp = $this->applicationRepository->getByCode($dataIsolation, $config['app_code']);

            if ($existingApp !== null) {
                $this->logger->info(sprintf(
                    'Application code %s already exists; skipping initialization',
                    $config['app_code']
                ));
                return $existingApp;
            }

            // Create a new application
            $application = new ApplicationEntity();
            $application->setCode($config['app_code']);
            $application->setName($config['app_name']);
            $application->setDescription($config['app_description']);
            $application->setOrganizationCode('default');
            $application->setCreator('system');
            $application->setModifier('system');
            $application->setCreatedAt(new DateTime());
            $application->setUpdatedAt(new DateTime());

            // Persist application
            $savedApp = $this->applicationRepository->save($dataIsolation, $application);

            $this->logger->info(sprintf(
                'Initialized application. Code: %s, Name: %s',
                $savedApp->getCode(),
                $savedApp->getName()
            ));

            return $savedApp;
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Failed to initialize application: %s, app_code: %s file:%s line:%s trace:%s',
                $e->getMessage(),
                $config['app_code'],
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            ));

            // If not continuing on error, abort execution
            if (! $continueOnError) {
                $this->logger->error('Stopping due to error; use --continue-on-error to ignore errors');
                throw $e;
            }

            throw $e; // Still bubble up; caller needs the application entity
        }
    }

    /**
     * Initialize model configuration data.
     */
    protected function initModelConfigs(): void
    {
        $this->logger->info('Starting model configuration initialization');

        $dataIsolation = new LLMDataIsolation('system', 'default');
        $continueOnError = $this->input->getOption('continue-on-error');

        // Base model configuration
        $modelBaseConfigs = [
            'deepseek-v3' => [
                'model' => 'ep-20250222192351-h5g65',
                'type' => 'deepseek-v3',
                'name' => 'Volcengine deepseek-v3',
                'implementation' => '\Hyperf\Odin\Model\DoubaoModel',
                'implementation_config' => [
                    'base_url' => 'ModelGateWayHost|http://127.0.0.1:9503',
                    'api_key' => 'SKYLARK_PRO_API_KEY|unit_test',
                    'model' => 'deepseek-v3',
                ],
            ],
            'local-gemma2-2b' => [
                'model' => 'local-gemma2-2b',
                'type' => 'local-gemma2-2b',
                'name' => 'local-gemma2-2b',
                'id' => 47,
                'rpm' => 1000,
                'implementation' => '\Hyperf\Odin\Model\DoubaoModel',
                'implementation_config' => [
                    'base_url' => 'ModelGateWayHost|http://127.0.0.1:9503',
                    'api_key' => 'SKYLARK_PRO_API_KEY|unit_test',
                    'model' => 'local-gemma2-2b',
                ],
            ],
            'gpt-4o' => [
                'model' => 'gpt-4o-global',
                'type' => 'gpt-4o',
                'name' => 'gpt-4o',
                'id' => 728192245266141184,
                'implementation' => '\Hyperf\Odin\Model\AzureOpenAIModel',
                'implementation_config' => [
                    'api_key' => 'AZURE_OPENAI_4O_API_KEY|unit_test',
                    'api_base' => 'ModelGateWayHost|http://127.0.0.1:9503',
                    'api_version' => 'AZURE_OPENAI_4O_API_VERSION|2024-10-21',
                    'deployment_name' => 'AZURE_OPENAI_4O_DEPLOYMENT_NAME|unit_test',
                ],
            ],
            'gpt-4o-mini' => [
                'model' => 'gpt-4o-mini-global',
                'type' => 'gpt-4o-mini',
                'name' => 'gpt-4o-mini',
                'id' => 728301272608460800,
                'implementation' => '\Hyperf\Odin\Model\AzureOpenAIModel',
                'implementation_config' => [
                    'api_key' => 'AZURE_OPENAI_4O_API_KEY|unit_test',
                    'api_base' => 'ModelGateWayHost|http://127.0.0.1:9503',
                    'api_version' => 'AZURE_OPENAI_4O_API_VERSION|2024-10-21',
                    'deployment_name' => 'AZURE_OPENAI_4O_MINI_DEPLOYMENT_NAME|unit_test',
                ],
            ],
            'deepseek-r1' => [
                'model' => 'ep-20250205161348-4nxnn',
                'type' => 'deepseek-r1',
                'name' => 'Volcengine deepseek-r1',
                'id' => 745679428835708928,
                'implementation' => '\Hyperf\Odin\Model\DoubaoModel',
                'implementation_config' => [
                    'base_url' => 'ModelGateWayHost|http://127.0.0.1:9503',
                    'api_key' => 'SKYLARK_PRO_API_KEY|unit_test',
                    'model' => 'deepseek-r1',
                ],
            ],
            'text-embedding-3-small' => [
                'model' => 'text-embedding-3-small',
                'type' => 'text-embedding-3-small',
                'name' => 'Microsoft text-embedding-3-small',
                'id' => 756574747410190336,
                'rpm' => 1000,
                'implementation' => '\Hyperf\Odin\Model\AzureOpenAIModel',
                'implementation_config' => [
                    'api_key' => 'AZURE_OPENAI_4O_API_KEY|unit_test',
                    'api_base' => 'ModelGateWayHost|http://127.0.0.1:9503',
                    'api_version' => 'AZURE_OPENAI_4O_API_VERSION|2024-10-21',
                    'deployment_name' => 'AZURE_OPENAI_TEXT_EMBEDDING_DEPLOYMENT_NAME|unit_test',
                ],
            ],
        ];

        // Common defaults
        $defaultConfig = [
            'enabled' => true,
            'total_amount' => 5000000.000000,
            'use_amount' => 0.0,
            'rpm' => 0,
            'exchange_rate' => 7.40,
            'input_cost_per_1000' => 0.001500,
            'output_cost_per_1000' => 0.002000,
            'created_at' => new DateTime(),
            'updated_at' => new DateTime(),
        ];

        // Create models
        $count = 0;
        foreach ($modelBaseConfigs as $baseConfig) {
            try {
                // Check whether the model already exists
                $exists = Db::table('magic_api_model_configs')
                    ->where('model', $baseConfig['model'])
                    ->exists();

                if ($exists) {
                    $this->logger->info(sprintf('Model already exists; skipping initialization: %s', $baseConfig['name']));
                    continue;
                }

                // Merge base config with defaults
                $configData = array_merge($defaultConfig, $baseConfig);

                // Create ModelConfigEntity
                $modelConfigEntity = new ModelConfigEntity();

                if (isset($configData['id'])) {
                    $modelConfigEntity->setId((int) $configData['id']);
                }

                $modelConfigEntity->setModel($configData['model']);
                $modelConfigEntity->setType($configData['type']);
                $modelConfigEntity->setName($configData['name']);
                $modelConfigEntity->setEnabled((bool) $configData['enabled']);
                $modelConfigEntity->setTotalAmount((float) $configData['total_amount']);
                $modelConfigEntity->setUseAmount((float) $configData['use_amount']);
                $modelConfigEntity->setRpm((int) $configData['rpm']);
                $modelConfigEntity->setExchangeRate((float) $configData['exchange_rate']);
                $modelConfigEntity->setInputCostPer1000((float) $configData['input_cost_per_1000']);
                $modelConfigEntity->setOutputCostPer1000((float) $configData['output_cost_per_1000']);
                $modelConfigEntity->setImplementation($configData['implementation']);
                $modelConfigEntity->setImplementationConfig($configData['implementation_config']);

                if ($configData['created_at'] instanceof DateTime) {
                    $modelConfigEntity->setCreatedAt($configData['created_at']);
                }
                if ($configData['updated_at'] instanceof DateTime) {
                    $modelConfigEntity->setUpdatedAt($configData['updated_at']);
                }

                // Persist model configuration via repository
                $this->modelConfigRepository->save($dataIsolation, $modelConfigEntity);

                ++$count;

                $this->logger->info(sprintf('Initialized model: %s', $configData['name']));
            } catch (Throwable $e) {
                $this->logger->error(sprintf(
                    'Failed to initialize model config: %s, model: %s  file:%s line:%s  trace:%s',
                    $e->getMessage(),
                    $baseConfig['model'],
                    $e->getFile(),
                    $e->getLine(),
                    $e->getTraceAsString()
                ));

                // If not continuing on error, abort execution
                if (! $continueOnError) {
                    $this->logger->error('Stopping due to error; use --continue-on-error to ignore errors');
                    throw $e;
                }
            }
        }

        $this->logger->info(sprintf('Initialized %d model configurations', $count));
    }

    /**
     * Run all database seeders.
     */
    protected function runAllDbSeeders(): void
    {
        $this->logger->info('Starting all database seeders');
        $continueOnError = $this->input->getOption('continue-on-error');

        try {
            // Run db:seed via command executor
            $command = 'php bin/hyperf.php db:seed --force';
            $process = proc_open($command, [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ], $pipes);

            if (is_resource($process)) {
                $output = stream_get_contents($pipes[1]);
                $error = stream_get_contents($pipes[2]);
                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $exitCode = proc_close($process);

                if ($exitCode === 0) {
                    $this->logger->info('All database seeders completed');
                    if (! empty($output)) {
                        $this->logger->info('Output: ' . $output);
                    }
                } else {
                    $errorMessage = 'Database seeder execution failed';
                    if (! empty($error)) {
                        $errorMessage .= "\nError output: " . $error;
                        $this->logger->error($error);
                    }
                    if (! empty($output)) {
                        $errorMessage .= "\nStdout: " . $output;
                        $this->logger->error($output);
                    }

                    // If not continuing on error, abort execution
                    if (! $continueOnError) {
                        throw new RuntimeException($errorMessage);
                    }
                }
            } else {
                $errorMessage = 'Failed to start process to run database seeders';
                $this->logger->error($errorMessage);

                // If not continuing on error, abort execution
                if (! $continueOnError) {
                    throw new RuntimeException($errorMessage);
                }
            }
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Failed to run database seeders: %s, file:%s line:%s trace:%s',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            ));
            throw $e;
        }
    }
}
