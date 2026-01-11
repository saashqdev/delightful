# Async Event

- Events will be placed into a coroutine and then executed in sequence  
- Core code is `\Delightful\AsyncEvent\AsyncEventDispatcher::dispatch`

## Installation
- Install
```
composer require delightful/async-event
```
- Publish configuration
```
php bin/hyperf.php vendor:publish delightful/async-event
```
- Run database migration
```
php bin/hyperf.php migrate
```

## Usage

- To avoid affecting existing logic, use the new dispatcher

demo
```php
<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
 
namespace App\Controller;

use App\Event\DemoEvent;
use Hyperf\Di\Annotation\Inject;
use Delightful\AsyncEvent\AsyncEventDispatcher;

class IndexController extends AbstractController
{
    /**
     * @Inject()
     */
    protected AsyncEventDispatcher $asyncEventDispatcher;

    public function index()
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        $this->asyncEventDispatcher->dispatch(new DemoEvent([123,222], 9));

        return [
            'method' => $method,
            'message' => "Hello {$user}.",
        ];
    }
}

```

- When the maximum execution count is reached, you can send message notifications, but you need to add configuration yourself. This project only provides the maximum retry event


## Notes

- Try to avoid using coroutine context to pass data in events, as events are asynchronous and may cause data inconsistency
