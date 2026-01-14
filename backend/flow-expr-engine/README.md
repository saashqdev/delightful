<h1 align="center"> FlowExprEngine </h1>

<p align="center"> A powerful flow expression engine for creating and managing structured components.</p>


## Installation

```shell
$ composer require bedelightful/flow-expr-engine -vvv
```

## Usage

```php
use BeDelightful\FlowExprEngine\ComponentFactory;
use BeDelightful\FlowExprEngine\Structure\StructureType;

// Create form component example
$formComponent = ComponentFactory::generateTemplate(StructureType::Form);

// More usage examples...
``