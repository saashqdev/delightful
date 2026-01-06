<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Delightful\BeDelightful\Infrastructure\Utils\Middleware\RequestContextMiddlewareV2;
use Delightful\BeDelightful\Interfaces\SuperAgent\Facade\AccountApi;
use Delightful\BeDelightful\Interfaces\SuperAgent\Facade\FileApi;
use Delightful\BeDelightful\Interfaces\SuperAgent\Facade\FileEditingApi;
use Delightful\BeDelightful\Interfaces\SuperAgent\Facade\FileKeyCleanupApi;
use Delightful\BeDelightful\Interfaces\SuperAgent\Facade\MessageApi;
use Delightful\BeDelightful\Interfaces\SuperAgent\Facade\ProjectApi;
use Delightful\BeDelightful\Interfaces\SuperAgent\Facade\ProjectInvitationLinkApi;
use Delightful\BeDelightful\Interfaces\SuperAgent\Facade\ProjectMemberApi;
use Delightful\BeDelightful\Interfaces\SuperAgent\Facade\SandboxApi;
use Delightful\BeDelightful\Interfaces\SuperAgent\Facade\SuperAgentMemoryApi;
use Delightful\BeDelightful\Interfaces\SuperAgent\Facade\TaskApi;
use Delightful\BeDelightful\Interfaces\SuperAgent\Facade\TopicApi;
use Delightful\BeDelightful\Interfaces\SuperAgent\Facade\WorkspaceApi;
use Hyperf\HttpServer\Router\Router;

Router::addGroup(
    '/api/v1/super-agent',
    static function () {
        // Workspace management
        Router::addGroup('/workspaces', static function () {
            // Get workspace list
            Router::get('/queries', [WorkspaceApi::class, 'getWorkspaceList']);
            // Get workspace details
            Router::get('/{id}', [WorkspaceApi::class, 'getWorkspaceDetail']);
            // Get topic list under workspace (to be implemented during optimization)
            Router::post('/{id}/topics', [WorkspaceApi::class, 'getWorkspaceTopics']);
            // Create workspace
            Router::post('', [WorkspaceApi::class, 'createWorkspace']);
            // Update workspace
            Router::put('/{id}', [WorkspaceApi::class, 'updateWorkspace']);
            // Delete workspace (soft delete)
            Router::delete('/{id}', [WorkspaceApi::class, 'deleteWorkspace']);
            // Set workspace archive status
            Router::post('/set-archived', [WorkspaceApi::class, 'setArchived']);
        });

        // Project management
        Router::addGroup('/projects', static function () {
            // Get project list
            Router::get('/queries', [ProjectApi::class, 'index']);
            // Get list of projects user participates in (supports collaborative project filtering)
            Router::post('/participated', [ProjectApi::class, 'getParticipatedProjects']);
            // Get project details
            Router::get('/{id}', [ProjectApi::class, 'show']);
            // Create project
            Router::post('', [ProjectApi::class, 'store']);
            // Update project
            Router::put('/{id}', [ProjectApi::class, 'update']);
            // Delete project
            Router::delete('/{id}', [ProjectApi::class, 'destroy']);
            // Pin project
            Router::put('/{id}/pin', [ProjectApi::class, 'pin']);
            // Get topic list under project
            Router::get('/{id}/topics', [ProjectApi::class, 'getTopics']);
            // Check if project file list needs update
            Router::get('/{id}/last-file-updated-time', [ProjectApi::class, 'checkFileListUpdate']);
            // Get attachment list
            Router::get('/{id}/cloud-files', [ProjectApi::class, 'getCloudFiles']);
            // Copy project
            Router::post('/fork', [ProjectApi::class, 'fork']);
            // Query copy status
            Router::get('/{id}/fork-status', [ProjectApi::class, 'forkStatus']);
            // Move project to another workspace
            Router::post('/move', [ProjectApi::class, 'moveProject']);

            // Project member resource management
            Router::addGroup('/{projectId}/members', static function () {
                // Get project collaborators
                Router::get('', [ProjectMemberApi::class, 'getMembers']);
                // Update project collaborators (new version doesn't need this interface)
                //                Router::put('', [ProjectMemberApi::class, 'updateMembers']);
                // Add project members
                Router::post('', [ProjectMemberApi::class, 'createProjectMembers']);
                // Batch delete members
                Router::delete('', [ProjectMemberApi::class, 'deleteProjectMembers']);
                // Batch update member permissions
                Router::put('/roles', [ProjectMemberApi::class, 'updateProjectMemberRoles']);
            });

            // Project invitation link management
            Router::addGroup('/{projectId}/invitation-links', static function () {
                // Get project invitation link information
                Router::get('', [ProjectInvitationLinkApi::class, 'getInvitationLink']);
                // Enable/disable invitation link
                Router::put('/toggle', [ProjectInvitationLinkApi::class, 'toggleInvitationLink']);
                // Reset invitation link
                Router::post('/reset', [ProjectInvitationLinkApi::class, 'resetInvitationLink']);
                // Set password protection
                Router::post('/password', [ProjectInvitationLinkApi::class, 'setPassword']);
                // Reset password
                Router::post('/reset-password', [ProjectInvitationLinkApi::class, 'resetPassword']);
                // Change invitation link password
                Router::put('/change-password', [ProjectInvitationLinkApi::class, 'changePassword']);
                // Modify permission level
                Router::put('/permission', [ProjectInvitationLinkApi::class, 'updateDefaultJoinPermission']);
            });
        });
        // Collaboration project related routing group
        Router::addGroup('/collaboration-projects', static function () {
            // Get collaboration project list
            Router::get('', [ProjectMemberApi::class, 'getCollaborationProjects']);
            // Get collaboration project creator list
            Router::get('/creators', [ProjectMemberApi::class, 'getCollaborationProjectCreators']);
            // Update project pin status
            Router::put('/{project_id}/pin', [ProjectMemberApi::class, 'updateProjectPin']);
            // Update project shortcut status
            Router::put('/{project_id}/shortcut', [ProjectMemberApi::class, 'updateProjectShortcut']);
        });

        // Topic related
        Router::addGroup('/topics', static function () {
            // Get topic details
            Router::get('/{id}', [TopicApi::class, 'getTopic']);
            // Get message list by topic ID
            Router::post('/{id}/messages', [TopicApi::class, 'getMessagesByTopicId']);
            // Create topic
            Router::post('', [TopicApi::class, 'createTopic']);
            // Update topic
            Router::put('/{id}', [TopicApi::class, 'updateTopic']);
            // Delete topic
            Router::post('/delete', [TopicApi::class, 'deleteTopic']);
            // Smart rename topic
            Router::post('/rename', [TopicApi::class, 'renameTopic']);
            // Checkpoint rollback management
            Router::addGroup('/{id}/checkpoints', static function () {
                // Directly rollback checkpoint
                Router::post('/rollback', [TopicApi::class, 'rollbackCheckpoint']);

                Router::addGroup('/rollback', static function () {
                    // Check checkpoint rollback feasibility
                    Router::post('/check', [TopicApi::class, 'rollbackCheckpointCheck']);
                    // Start checkpoint rollback (mark status instead of deletion)
                    Router::post('/start', [TopicApi::class, 'rollbackCheckpointStart']);
                    // Commit checkpoint rollback (physically delete messages in withdrawn status)
                    Router::post('/commit', [TopicApi::class, 'rollbackCheckpointCommit']);
                    // Undo checkpoint rollback (restore withdrawn messages to normal status)
                    Router::post('/undo', [TopicApi::class, 'rollbackCheckpointUndo']);
                });
            });
            // Copy topic messages
            Router::addGroup('/{id}/duplicate-chat', static function () {
                // Copy topic messages (synchronous)
                Router::post('', [TopicApi::class, 'duplicateChat']);
                // Copy topic messages (asynchronous)
                Router::post('/create-job', [TopicApi::class, 'duplicateChatAsync']);
                // Check if topic message copy is successful
                Router::post('/check', [TopicApi::class, 'duplicateChatCheck']);
            });
        });

        // Task related
        Router::addGroup('/tasks', static function () {
            // Get attachment list under task
            Router::get('/{id}/attachments', [TaskApi::class, 'getTaskAttachments']);
        });

        // Account related
        Router::addGroup('/accounts', static function () {
            // Initialize Be Delightful account
            Router::post('/init', [AccountApi::class, 'initAccount']);
        });

        // Message queue management
        Router::addGroup('/message-queue', static function () {
            // Create message queue
            Router::post('', [MessageApi::class, 'createMessageQueue']);
            // Modify message queue
            Router::put('/{id}', [MessageApi::class, 'updateMessageQueue']);
            // Delete message queue
            Router::delete('/{id}', [MessageApi::class, 'deleteMessageQueue']);
            // Query message queues
            Router::post('/queries', [MessageApi::class, 'queryMessageQueues']);
            // Consume message
            Router::post('/{id}/consume', [MessageApi::class, 'consumeMessageQueue']);
        });

        // Message scheduled task
        Router::addGroup('/message-schedule', static function () {
            // Create scheduled task
            Router::post('', [MessageApi::class, 'createMessageSchedule']);
            // Modify scheduled task
            Router::put('/{id}', [MessageApi::class, 'updateMessageSchedule']);
            // Delete scheduled task
            Router::delete('/{id}', [MessageApi::class, 'deleteMessageSchedule']);
            // Query scheduled tasks
            Router::post('/queries', [MessageApi::class, 'queryMessageSchedules']);
            // Query scheduled task details
            Router::get('/{id}', [MessageApi::class, 'getMessageScheduleDetail']);
            // Query scheduled task execution logs
            Router::post('/{id}/logs', [MessageApi::class, 'getMessageScheduleLogs']);
            // Manually execute scheduled task (for testing)
            Router::post('/{id}/execute', [MessageApi::class, 'executeMessageScheduleForTest']);
        });

        Router::addGroup('/file', static function () {
            // Get project file upload STS Token
            Router::get('/project-upload-token', [FileApi::class, 'getProjectUploadToken']);
            // Get topic file upload STS Token
            Router::get('/topic-upload-token', [FileApi::class, 'getTopicUploadToken']);
            // Create file and folder
            Router::post('', [FileApi::class, 'createFile']);
            // Save attachment relationship
            Router::post('/project/save', [FileApi::class, 'saveProjectFile']);
            // Batch save attachment relationships
            Router::post('/project/batch-save', [FileApi::class, 'batchSaveProjectFiles']);
            // Save file content
            Router::post('/save', [FileApi::class, 'saveFileContent']);
            // Delete attachment
            Router::delete('/{id}', [FileApi::class, 'deleteFile']);
            // Delete directory and all files under it
            Router::post('/directory/delete', [FileApi::class, 'deleteDirectory']);
            // Rename file
            Router::post('/{id}/rename', [FileApi::class, 'renameFile']);
            // Move file
            Router::post('/{id}/move', [FileApi::class, 'moveFile']);
            // Copy file
            Router::post('/{id}/copy', [FileApi::class, 'copyFile']);
            // Get file version list
            Router::get('/{id}/versions', [FileApi::class, 'getFileVersions']);
            // Rollback file to specified version
            Router::post('/{id}/rollback', [FileApi::class, 'rollbackFileToVersion']);
            // Replace file
            Router::post('/{id}/replace', [FileApi::class, 'replaceFile']);
            // Batch move files
            Router::post('/batch-move', [FileApi::class, 'batchMoveFile']);
            // Batch copy files
            Router::post('/batch-copy', [FileApi::class, 'batchCopyFile']);
            // Batch delete files
            Router::post('/batch-delete', [FileApi::class, 'batchDeleteFiles']);

            // Batch download related
            Router::addGroup('/batch-download', static function () {
                // Create batch download task
                Router::post('/create', [FileApi::class, 'createBatchDownload']);
                // Check batch download status
                Router::get('/check', [FileApi::class, 'checkBatchDownload']);
            });

            // Batch operation status query
            Router::addGroup('/batch-operation', static function () {
                // Check batch operation status
                Router::get('/check', [FileApi::class, 'checkBatchOperationStatus']);
            });

            // File editing status management
            // Join editing
            Router::post('/{fileId}/join-editing', [FileEditingApi::class, 'joinEditing']);
            // Leave editing
            Router::post('/{fileId}/leave-editing', [FileEditingApi::class, 'leaveEditing']);
            // Get number of editing users
            Router::get('/{fileId}/editing-users', [FileEditingApi::class, 'getEditingUsers']);
        });

        Router::addGroup('/sandbox', static function () {
            // Initialize sandbox
            Router::post('/init', [SandboxApi::class, 'initSandboxByAuthorization']);
            // Get sandbox status
            Router::get('/status', [SandboxApi::class, 'getSandboxStatus']);
            // Upgrade sandbox image
            Router::put('/upgrade', [SandboxApi::class, 'upgradeSandbox']);
        });

        // File key cleanup management
        Router::addGroup('/file-keys/cleanup', static function () {
            // Get cleanup statistics
            Router::get('/statistics', [FileKeyCleanupApi::class, 'getStatistics']);
            // Execute cleanup
            Router::post('', [FileKeyCleanupApi::class, 'cleanup']);
            // Preview cleanup (dry-run mode)
            Router::post('/preview', [FileKeyCleanupApi::class, 'preview']);
        });

        // Invitation link access (requires authentication, for external users)
        Router::addGroup('/invitation', static function () {
            // Access invitation link by token (external user preview)
            Router::get('/links/{token}', [ProjectInvitationLinkApi::class, 'getInvitationByToken']);

            // Join project (external user operation)
            Router::post('/join', [ProjectInvitationLinkApi::class, 'joinProject']);
        });
    },
    ['middleware' => [RequestContextMiddlewareV2::class]]
);

// Interface types supporting both logged-in and non-logged-in users (compatible with frontend components)
Router::addGroup('/api/v1/super-agent', static function () {
    // Get topic attachment list
    Router::addGroup('/topics', static function () {
        Router::post('/{id}/attachments', [TopicApi::class, 'getTopicAttachments']);
    });

    // Get project attachment list
    Router::addGroup('/projects', static function () {
        Router::post('/{id}/attachments', [ProjectApi::class, 'getProjectAttachments']);
    });

    // Get task attachments (need to replace this name)
    Router::post('/tasks/get-file-url', [FileApi::class, 'getFileUrls']);
    // Deliver message
    Router::post('/tasks/deliver-message', [TaskApi::class, 'deliverMessage']);

    // File conversion related
    Router::addGroup('/file-convert', static function () {
        // Create file conversion task
        Router::post('/create', [TaskApi::class, 'convertFiles']);
        // Check file conversion status
        Router::get('/check', [TaskApi::class, 'checkFileConvertStatus']);
    });

    // Long-term memory management (sandbox token validation moved to API layer)
    Router::addGroup('/memories', static function () {
        Router::post('', [SuperAgentMemoryApi::class, 'createMemory']);
        Router::put('/{id}', [SuperAgentMemoryApi::class, 'agentUpdateMemory']);
        Router::delete('/{id}', [SuperAgentMemoryApi::class, 'deleteMemory']);
    });
    // File related
    Router::addGroup('/file', static function () {
        // Sandbox file change notification
        Router::post('/sandbox/notifications', [FileApi::class, 'handleSandboxNotification']);
        // Refresh STS Token (for be-delightful use, exchange metadata for directory information)
        Router::post('/refresh-sts-token', [FileApi::class, 'refreshStsToken']);
        // Batch process attachments
        Router::post('/process-attachments', [FileApi::class, 'processAttachments']);
        // Add topic attachment list (git management)
        Router::post('/workspace-attachments', [FileApi::class, 'workspaceAttachments']);

        // Get basic file information by file ID
        Router::get('/{id}', [FileApi::class, 'getFileInfo']);
        // Get file name by file ID
        Router::get('/{id}/file-name', [FileApi::class, 'getFileByName']);
        // Batch get download links
        // Router::post('/batch-urls', [FileApi::class, 'getFileUrls']);
    });
});

// V2 API Routes
Router::addGroup(
    '/api/v2/super-agent',
    static function () {
        // Get project attachment list V2 (does not return tree structure)
        Router::addGroup('/projects', static function () {
            Router::post('/{id}/attachments', [ProjectApi::class, 'getProjectAttachmentsV2']);
        });
    },
    ['middleware' => [RequestContextMiddlewareV2::class]]
);
