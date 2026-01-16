<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request;

use App\Infrastructure\Core\AbstractDTO;
use Hyperf\HttpServer\Contract\RequestInterface;
/** * delete topic RequestDTO * for Receivedelete topic RequestParameter. */

class delete TopicRequestDTO extends AbstractDTO 
{
 /** * TaskStatusID(primary key ) * StringTypePairTaskStatustable primary key . */ 
    public string $id = ''; /** * GetValidate Rule. */ 
    public function rules(): array 
{
 return [ 'id' => 'required|string', ]; 
}
 /** * GetValidate failedCustomError message. */ 
    public function messages(): array 
{
 return [ 'id.required' => 'TaskStatusIDCannot be empty', 'id.string' => 'TaskStatusIDMust beString', ]; 
}
 /** * FromRequestin CreateDTOInstance. */ 
    public 
    static function fromRequest(RequestInterface $request): self 
{
 $data = new self(); $data->id = $request->input('id', ''); return $data; 
}
 /** * GetTaskStatusID(primary key ). */ 
    public function getId(): string 
{
 return $this->id; 
}
 
}
 
