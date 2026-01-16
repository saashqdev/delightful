<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

/** * workspace CreateParameterValueObject * FollowDDDin ValueObjectrelated ParameterEnsure. */ readonly

class WorkspaceCreationParams 
{
 /** * @param string $chatConversationId SessionID * @param string $workspaceName workspace Name * @param string $chatConversationTopicId Sessiontopic ID * @param string $topicName topic Name */ 
    public function __construct( 
    private string $chatConversationId, 
    private string $workspaceName, 
    private string $chatConversationTopicId, 
    private string $topicName ) 
{
 
}
 /** * GetSessionID. */ 
    public function getChatConversationId(): string 
{
 return $this->chatConversationId; 
}
 /** * Getworkspace Name. */ 
    public function getWorkspaceName(): string 
{
 return $this->workspaceName; 
}
 /** * Gettopic ID. */ 
    public function getChatConversationTopicId(): string 
{
 return $this->chatConversationTopicId; 
}
 /** * Gettopic Name. */ 
    public function getTopicName(): string 
{
 return $this->topicName; 
}
 /** * CreateNewInstanceModifyspecified Property * ValueObjectCreateNewInstanceIs notModifyHaveInstance. * * @param array $params ModifyPropertyValue * @return self NewInstance */ 
    public function with(array $params): self 
{
 return new self( $params['chatConversationId'] ?? $this->chatConversationId, $params['workspaceName'] ?? $this->workspaceName, $params['chatConversationTopicId'] ?? $this->chatConversationTopicId, $params['topicName'] ?? $this->topicName ); 
}
 
}
 
