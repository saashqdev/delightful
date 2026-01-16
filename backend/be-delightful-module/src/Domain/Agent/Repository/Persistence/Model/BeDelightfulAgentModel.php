<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\Agent\Repository\Persistence\Model;

use App\Infrastructure\Core\AbstractModel;
use DateTime;
use Hyperf\Database\Model\Softdelete s;
use Hyperf\Snowflake\Concern\Snowflake;
/** * @property int $id ID * @property string $organization_code organization code * @property string $code Encode * @property string $name AgentName * @property string $description AgentDescription * @property array $icon AgentIcon * @property int $icon_type IconType * @property array $prompt SystemNotice * @property array $tools tool list * @property int $type Type * @property bool $enabled whether Enabled * @property string $creator creator * @property DateTime $created_at Creation time * @property string $modifier Modify * @property DateTime $updated_at Update time * @property null|DateTime $deleted_at Deletion time */

class BeDelightfulAgentModel extends AbstractModel 
{
 use Snowflake;
use Softdelete s;
protected ?string $table = 'magic_super_magic_agents'; 
    protected array $fillable = [ 'id', 'organization_code', 'code', 'name', 'description', 'icon', 'icon_type', 'prompt', 'tools', 'type', 'enabled', 'creator', 'created_at', 'modifier', 'updated_at', ]; 
    protected array $casts = [ 'id' => 'integer', 'organization_code' => 'string', 'code' => 'string', 'name' => 'string', 'description' => 'string', 'icon' => 'array', 'icon_type' => 'integer', 'prompt' => 'array', 'tools' => 'array', 'type' => 'integer', 'enabled' => 'boolean', 'creator' => 'string', 'modifier' => 'string', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'deleted_at' => 'datetime', ]; 
}
 
