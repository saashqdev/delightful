<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\Share\DTO\Request;

use App\Infrastructure\Core\AbstractDTO;
use Hyperf\HttpServer\Contract\RequestInterface;
/** * ShareRequestDTO. */

class AccessShareRequestDTO extends AbstractDTO 
{
 /** * Share code */ 
    public string $shareCode = ''; /** * Password */ public ?string $password = null; /** * Source. */ 
    public int $accessSource = 0; /** * FromRequestin CreateDTO. */ 
    public 
    static function fromRequest(RequestInterface $request): self 
{
 $dto = new self(); $dto->shareCode = (string) $request->input('share_code', ''); $dto->password = $request->has('password') ? (string) $request->input('password') : null; $dto->accessSource = (int) $request->input('access_source', 0); return $dto; 
}
 /** * GetShare code */ 
    public function getShareCode(): string 
{
 return $this->shareCode; 
}
 /** * GetPassword */ 
    public function getPassword(): ?string 
{
 return $this->password; 
}
 /** * GetSource. */ 
    public function getAccessSource(): int 
{
 return $this->accessSource; 
}
 /** * BuildValidate Rule. */ 
    public function rules(): array 
{
 return [ 'share_code' => 'required|string|max:16', 'password' => 'nullable|string|max:32', 'access_source' => 'nullable|integer|min:0', ]; 
}
 /** * GetValidate errorMessage. */ 
    public function messages(): array 
{
 return [ 'share_code.required' => 'Share codeCannot be empty', ]; 
}
 /** * PropertyName. */ 
    public function attributes(): array 
{
 return [ 'share_code' => 'Share code', 'password' => 'Password', 'access_source' => 'Source', ]; 
}
 
}
 
