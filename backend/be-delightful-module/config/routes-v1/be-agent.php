<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Delightful\BeDelightful\Infrastructure\Utils\Middleware\RequestContextMiddlewareV2;
use Delightful\BeDelightful\Interfaces\BeAgent\Facade\AccountApi;
use Delightful\BeDelightful\Interfaces\BeAgent\Facade\FileApi;
use Delightful\BeDelightful\Interfaces\BeAgent\Facade\FileEditingApi;
use Delightful\BeDelightful\Interfaces\BeAgent\Facade\FileKeyCleanupApi;
use Delightful\BeDelightful\Interfaces\BeAgent\Facade\MessageApi;
use Delightful\BeDelightful\Interfaces\BeAgent\Facade\ProjectApi;
use Delightful\BeDelightful\Interfaces\BeAgent\Facade\ProjectInvitationLinkApi;
use Delightful\BeDelightful\Interfaces\BeAgent\Facade\ProjectMemberApi;
use Delightful\BeDelightful\Interfaces\BeAgent\Facade\SandboxApi;
use Delightful\BeDelightful\Interfaces\BeAgent\Facade\BeAgentMemoryApi;
use Delightful\BeDelightful\Interfaces\BeAgent\Facade\TaskApi;
use Delightful\BeDelightful\Interfaces\BeAgent\Facade\TopicApi;
use Delightful\BeDelightful\Interfaces\BeAgent\Facade\WorkspaceApi;
use Hyperf\HttpServer\Router\Router;

Router::addGroup(
    '/api/v1/be-agent',
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
            // Set workspace archived status
            Router::post('/set-archived', [WorkspaceApi::class, 'setArchived']);
        });

        // Project management
        Router::addGroup('/projects', static function () {
            // Get project list
            Router::get('/queries', [ProjectApi::class, 'index']);
            // Get user participated project list (supports collaboration project filtering)
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
            // Check if project file list needs to be updated
            Router::get('/{id}/last-file-updated-time', [ProjectApi::class, 'checkFileListUpdate']);
            // Get attachment list
            Router::get('/{id}/cloud-files', [ProjectApi::class, 'getCloudFiles']);
            // Fork project
            Router::post('/fork', [ProjectApi::class, 'fork']);
            // Query fork status
            Router::get('/{id}/fork-status', [ProjectApi::class, 'forkStatus']);
            // Move project to another workspace
            Router::post('/move', [ProjectApi::class, 'moveProject']);

            // Project member resource management
            Router::addGroup('/{projectId}/members', static function () {
                // Get project collaboration members
                Router::get('', [ProjectMemberApi::class, 'getMembers']);
                // Update project collaboration members (not needed in new version)
                //                Router::put('', [ProjectMemberApi::class, 'updateMembers']);
                // Add project members
                Router::post('', [ProjectMemberApi::class, 'createProjectMembers']);
                // Batch delete members
                Router::delete('', [ProjectMemberApi::class, 'deleteProjectMembers']);
                // Batch update member roles
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
                // Update permission level
                Router::put('/permission', [ProjectInvitationLinkApi::class, 'updateDefaultJoinPermission']);
            });
        });
        // Collaboration project related route group
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
                // Rollback checkpoint directly
                Router::post('/rollback', [TopicApi::class, 'rollbackCheckpoint']);

                Router::addGroup('/rollback', static function () {
                    // Check checkpoint rollback feasibility
                    Router::post('/check', [TopicApi::class, 'rollbackCheckpointCheck']);
                    // Start checkpoint rollback (mark state instead of delete)
                    Router::post('/start', [TopicApi::class, 'rollbackCheckpointStart']);
                    // Commit checkpoint rollback (physically delete revoked messages)
                    Router::post('/commit', [TopicApi::class, 'rollbackCheckpointCommit']);
                    // Undo checkpoint rollback (restore revoked messages to normal state)
                    Router::post('/undo', [TopicApi::class, 'rollbackCheckpointUndo']);
                });
            });
            // Duplicate topic messages
            Router::addGroup('/{id}/duplicate-chat', static function () {
                // Duplicate topic messages (sync)
                Router::post('', [TopicApi::class, 'duplicateChat']);
                // Duplicate topic messages (async)
                Router::post('/create-job', [TopicApi::class, 'duplicateChatAsync']);
                // Check if topic message duplication succeeded
                Router::post('/check', [TopicApi::class, 'duplicateChatCheck']);
            });
        });

        // Task related
        Router::addGroup('/tasks', static function () {
            // Get task attachment list
            Router::get('/{id}/attachments', [TaskApi::class, 'getTaskAttachments']);
        });

        // Account related
        Router::addGroup('/accounts', static function () {
            // Initialize Super Magic account
            Router::post('/init', [AccountApi::class, 'initAccount']);
        });

        // Message queue management
        Router::addGroup('/message-queue', static function () {
            // Create message queue
            Router::post('', [MessageApi::class, 'createMessageQueue']);
            // Update message queue
            Router::put('/{id}', [MessageApi::class, 'updateMessageQueue']);
            // Delete message queue
            Router::delete('/{id}', [MessageApi::class, 'deleteMessageQueue']);
            // Query message queues
            Router::post('/queries', [MessageApi::class, 'queryMessageQueues']);
            // Consume message
            Router::post('/{id}/consume', [MessageApi::class, 'consumeMessageQueue']);
        });

        // Message scheduled tasks
        Router::addGroup('/message-schedule', static function () {
            // Create scheduled task
            Router::post('', [MessageApi::class, 'createMessageSchedule']);
            // Update scheduled task
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
            // Get project file upload STS token
            Router::get('/project-upload-token', [FileApi::class, 'getProjectUploadToken']);
            // Get topic file upload STS token
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
            // Get editing user count
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

// Supports both authenticated and non-authenticated interface types (compatible with frontend components)
Router::addGroup('/api/v1/be-agent', static function () {
    // Get topic attachment list
    Router::addGroup('/topics', static function () {
        Router::post('/{id}/attachments', [TopicApi::class, 'getTopicAttachments']);
    });

    // Get project attachment list
    Router::addGroup('/projects', static function () {
        Router::post('/{id}/attachments', [ProjectApi::class, 'getProjectAttachments']);
    });

    // Get task attachments (this name needs to be replaced)
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

    // Long-term memory management (sandbox token verification moved to API layer)
    Router::addGroup('/memories', static function () {
        Router::post('', [BeAgentMemoryApi::class, 'createMemory']);
        Router::put('/{id}', [BeAgentMemoryApi::class, 'agentUpdateMemory']);
        Router::delete('/{id}', [BeAgentMemoryApi::class, 'deleteMemory']);
    });
    // File related
    Router::addGroup('/file', static function () {
        // Sandbox file change notification
        Router::post('/sandbox/notifications', [FileApi::class, 'handleSandboxNotification']);
        // Refresh STS Token (for super-magic use, exchange metadata for directory information)
        Router::post('/refresh-sts-token', [FileApi::class, 'refreshStsToken']);
        // Batch process attachments
        Router::post('/process-attachments', [FileApi::class, 'processAttachments']);
        // Add topic attachment list (git managed)
        Router::post('/workspace-attachments', [FileApi::class, 'workspaceAttachments']);

        // Get file basic information by file ID
        Router::get('/{id}', [FileApi::class, 'getFileInfo']);
        // Get file name by file ID
        Router::get('/{id}/file-name', [FileApi::class, 'getFileByName']);
        // Batch get download links
        // Router::post('/batch-urls', [FileApi::class, 'getFileUrls']);
    });
});

// V2 API Routes
Router::addGroup(
    '/api/v2/be-agent',
    static function () {
        // Get project attachment list V2 (does not return tree structure)
        Router::addGroup('/projects', static function () {
            Router::post('/{id}/attachments', [ProjectApi::class, 'getProjectAttachmentsV2']);
        });
    },
    ['middleware' => [RequestContextMiddlewareV2::class]]
);
