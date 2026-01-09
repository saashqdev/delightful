<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Search\Structure;

enum LeftType: string
{
    // person员
    case Username = 'username';
    case WorkNumber = 'work_number';
    case Position = 'position';
    case Phone = 'phone';
    case DepartmentName = 'department_name';
    case GroupName = 'group_name';

    // toquantitydatalibrary
    case VectorDatabaseId = 'vector_database_id';
    case VectorDatabaseName = 'vector_database_name';
}
