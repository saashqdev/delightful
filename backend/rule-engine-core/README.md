# rule engine core

## Implemented Features

1. JSR-94 standard implementation
2. PHP script type rule service

## Examples

### Register rule service

```php
use BeDelightful\RuleEngineCore\PhpScript\RuleServiceProvider;
use BeDelightful\RuleEngineCore\Standards\RuleServiceProviderManager;

$uri = RuleServiceProvider::RULE_SERVICE_PROVIDER; 
$container = ApplicationContext::getContainer();
RuleServiceProviderManager::registerRuleServiceProvider($uri, RuleServiceProvider::class, $container);
```

By default, the PHP script rule repository is effective at the process (function repository) and coroutine (rule group) levels. If you need to customize the repository (such as using cache or database storage), you can replace it as follows.

```php
use BeDelightful\RuleEngineCore\PhpScript\RuleServiceProvider;
use BeDelightful\RuleEngineCore\Standards\RuleServiceProviderManager;

$provider = new RuleServiceProvider();
$provider
    ->setExecutionSetRepository(new CustomExecutionSetRepository())  // Use custom rule group repository
    ->setFunctionRepository(new CustomFunctionRepository());  // Use custom function repository
$container = ApplicationContext::getContainer();
RuleServiceProviderManager::registerRuleServiceProvider(RuleServiceProvider::RULE_SERVICE_PROVIDER, $provider, $container);
```

Function and rule group repositories need to implement `\Delightful\RuleEngineCore\PhpScript\Repository\ExpressionFunctionRepositoryInterface` and `\Delightful\RuleEngineCore\PhpScript\Repository\RuleExecutionSetRepositoryInterface`.

Additionally, it is recommended to register rule services when the framework starts. The following example completes rule service registration by listening to framework events.

```php
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Utils\ApplicationContext;
use BeDelightful\RuleEngineCore\PhpScript\RuleServiceProvider;
use BeDelightful\RuleEngineCore\Standards\RuleServiceProviderManager;
use Hyperf\Event\Annotation\Listener;

#[Listener]
class AutoRegister implements ListenerInterface
{
    public function listen(): array
    {
        return [
            \Hyperf\Framework\Event\BootApplication::class,
        ];
    }

    public function process(object $event): void
    {
        $uri = RuleServiceProvider::RULE_SERVICE_PROVIDER;
$container = ApplicationContext::getContainer();
RuleServiceProviderManager::registerRuleServiceProvider($uri, RuleServiceProvider::class, $container);
    }
}
```

### Register functions

By default, any function execution is prohibited in scripts and expressions. Users can register functions as follows.

```php
$uri = RuleServiceProvider::RULE_SERVICE_PROVIDER;
$ruleProvider = RuleServiceProviderManager::getRuleServiceProvider($uri);
$admin = $ruleProvider->getRuleAdministrator();
$executableCode = new ExecutableFunction('add', function ($arg1, $arg2) {
    return $arg1 + $arg2;
});
$admin->registerExecutableCode($executableCode);
```

Shortcut registration method based on native PHP functions:

```php
$uri = RuleServiceProvider::RULE_SERVICE_PROVIDER;
$ruleProvider = RuleServiceProviderManager::getRuleServiceProvider($uri);
$admin = $ruleProvider->getRuleAdministrator();
$executableCode = ExecutableFunction::fromPhp('is_array', 'is_array2'); //In scripts, use is_array2 to call
$admin->registerExecutableCode($executableCode);
```

Note: Do not write code in functions that may cause coroutine switching.


### Register rule execution group

```php
use BeDelightful\RuleEngineCore\PhpScript\RuleServiceProvider;
use BeDelightful\RuleEngineCore\Standards\RuleServiceProviderManager;
use BeDelightful\RuleEngineCore\Standards\Admin\InputType;
use BeDelightful\RuleEngineCore\PhpScript\RuleType;

$uri = RuleServiceProvider::RULE_SERVICE_PROVIDER;
$ruleProvider = RuleServiceProviderManager::getRuleServiceProvider($uri);
$admin = $ruleProvider->getRuleAdministrator();
$ruleExecutionSetProvider = $admin->getRuleExecutionSetProvider(InputType::from(InputType::String));
$input = ['$a + $b'];  // Script or expression content
$properties = new RuleExecutionSetProperties();
$properties->setName('add-rule');
$properties->setRuleType(RuleType::Expression); // Rule type, supports script or expression types. Defaults to script type when not defined.
$set = $ruleExecutionSetProvider->createRuleExecutionSet($input, $properties);
$admin->registerRuleExecutionSet('mysample', $set, $properties);
```



### Execute rule group

```php
use BeDelightful\RuleEngineCore\Standards\RuleSessionType;

$runtime = $ruleProvider->getRuleRuntime();
$properties = new RuleExecutionSetProperties();
$ruleSession = $runtime->createRuleSession('mysample', $properties, RuleSessionType::from(RuleSessionType::Stateless));
$inputs = [];
$inputs['a'] = 1;
$inputs['b'] = 2;
$res = $ruleSession->executeRules($inputs);
$ruleSession->release();
```



### AST syntax tree

When there are no placeholders in the rule, syntax parsing will be performed when creating the rule group, at which time the AST syntax tree can be obtained.

```php
use BeDelightful\RuleEngineCore\PhpScript\RuleServiceProvider;
use BeDelightful\RuleEngineCore\Standards\RuleServiceProviderManager;
use BeDelightful\RuleEngineCore\Standards\Admin\InputType;
use BeDelightful\RuleEngineCore\PhpScript\RuleType;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

$uri = RuleServiceProvider::RULE_SERVICE_PROVIDER;
$ruleProvider = RuleServiceProviderManager::getRuleServiceProvider($uri);
$admin = $ruleProvider->getRuleAdministrator();
$ruleExecutionSetProvider = $admin->getRuleExecutionSetProvider(InputType::from(InputType::String));
$input = ['$a + $b'];  // Does not contain placeholders
$properties = new RuleExecutionSetProperties();
$properties->setName('add-rule');
$properties->setRuleType(RuleType::Expression); // Rule type, supports script or expression types. Defaults to script type when not defined.
$set = $ruleExecutionSetProvider->createRuleExecutionSet($input, $properties);
// Perform custom parsing and validation actions
$ast = $set->getAsts();
$traverser = new NodeTraverser();
$visitor = new class() extends NodeVisitorAbstract {
	public function leaveNode(Node $node)
	{
		var_dump($node);
	}
};
$traverser->addVisitor($visitor);
foreach ($ast as $stmts) {
	$traverser->traverse($stmts);
}

```

If the rule contains placeholders, the AST syntax tree can only be obtained during the rule execution phase.

```php
use BeDelightful\RuleEngineCore\PhpScript\RuleServiceProvider;
use BeDelightful\RuleEngineCore\Standards\RuleServiceProviderManager;
use BeDelightful\RuleEngineCore\Standards\Admin\InputType;
use BeDelightful\RuleEngineCore\PhpScript\RuleType;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

$uri = RuleServiceProvider::RULE_SERVICE_PROVIDER;
$ruleProvider = RuleServiceProviderManager::getRuleServiceProvider($uri);
$admin = $ruleProvider->getRuleAdministrator();
$ruleExecutionSetProvider = $admin->getRuleExecutionSetProvider(InputType::from(InputType::String));
$input = ['if( {{ruleEnableCondition}} ) return $so;'];  // Contains placeholders
$properties = new RuleExecutionSetProperties();
$properties->setName('testPlaceholder-rule');
$properties->setRuleType(RuleType::Script); // Rule type, supports script or expression types. Defaults to script type when not defined.
$properties->setResolvePlaceholders(true);
$set = $ruleExecutionSetProvider->createRuleExecutionSet($input, $properties);
$admin->registerRuleExecutionSet('mysample', $set, $properties);
//After registration, pass in placeholder information and facts to prepare for rule execution
$runtime = $ruleProvider->getRuleRuntime();
$properties = new RuleExecutionSetProperties();
$properties->setPlaceholders(['ruleEnableCondition' => '1 == 1']);
$ruleSession = $runtime->createRuleSession('mysample', $properties, RuleSessionType::from(RuleSessionType::Stateless));
$inputs = [];
$inputs['so'] = 'aaaa111122';
$res = $ruleSession->getAsts();
$traverser = new NodeTraverser();
$visitor = new class() extends NodeVisitorAbstract {
	public function leaveNode(Node $node)
	{
		var_dump($node);
	}
};
$traverser->addVisitor($visitor);
foreach ($res as $stmts) {
	$traverser->traverse($stmts);
}

```

