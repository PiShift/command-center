<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\AgentExtrasController;
use App\Http\Controllers\Api\AgentTaskController;
use App\Http\Controllers\Api\AgentTaskSnapshotController;
use App\Http\Controllers\Api\AuthCodeController;
use App\Http\Controllers\Api\AutopilotController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ChatExtrasController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\CustomerApiController;
use App\Http\Controllers\Api\DaemonController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\InboxController;
use App\Http\Controllers\Api\InboxExtrasController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\IssueActionController;
use App\Http\Controllers\Api\IssueController;
use App\Http\Controllers\Api\IssueListController;
use App\Http\Controllers\Api\IssueSubResourceController;
use App\Http\Controllers\Api\LabelController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\NotificationPreferenceController;
use App\Http\Controllers\Api\PersonalAccessTokenController;
use App\Http\Controllers\Api\PinController;
use App\Http\Controllers\Api\ProjectApiController;
use App\Http\Controllers\Api\ProjectResourceController;
use App\Http\Controllers\Api\ProjectSearchController;
use App\Http\Controllers\Api\RuntimeController;
use App\Http\Controllers\Api\RuntimeExtrasController;
use App\Http\Controllers\Api\SkillController;
use App\Http\Controllers\Api\SquadController;
use App\Http\Controllers\Api\SquadExtrasController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\WorkspaceApiController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\Api\WorkspaceExtrasController;
use App\Http\Controllers\Api\WorkspaceMemberController;
use App\Http\Middleware\ApiSessionOrPatAuth;
use App\Http\Middleware\DaemonTokenMiddleware;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::middleware('api.secret')->prefix('v1')->group(function () {
    Route::get('customers/{identifier}/invoices', [CustomerApiController::class, 'invoices']);
    Route::post('customers/invoices', [CustomerApiController::class, 'invoices']);
});

Route::post('/auth/send-code', [AuthCodeController::class, 'sendCode']);
Route::post('/auth/verify-code', [AuthCodeController::class, 'verifyCode']);

Route::get('/config', function () {
    return response()->json([
        'cdn_domain' => null,
        'cdn_signed' => false,
        'allow_signup' => false,
        'google_client_id' => null,
        'posthog_key' => null,
        'posthog_host' => null,
        'analytics_environment' => 'production',
    ]);
});

Route::middleware([
    StartSession::class,
    ApiSessionOrPatAuth::class,
])->group(function () {
    Route::post('/agents', [AgentController::class, 'store']);
    Route::get('/agents', [AgentController::class, 'index']);
    Route::get('/agents/{id}', [AgentController::class, 'show']);
    Route::put('/agents/{id}', [AgentController::class, 'update']);
    Route::delete('/agents/{id}', [AgentController::class, 'destroy']);

    Route::post('/tokens', [PersonalAccessTokenController::class, 'store']);
    Route::get('/tokens', [PersonalAccessTokenController::class, 'index']);
    Route::delete('/tokens/{id}', [PersonalAccessTokenController::class, 'destroy']);

    Route::post('/auth/logout', function () {
        return response()->json(['status' => 'ok']);
    });

    Route::get('/me', [MeController::class, 'show']);
    Route::patch('/me', [MeController::class, 'update']);
    Route::patch('/me/onboarding', [MeController::class, 'updateOnboarding']);
    Route::post('/me/onboarding/cloud-waitlist', [MeController::class, 'cloudWaitlist']);
    Route::post('/cli-token', [MeController::class, 'cliToken']);

    Route::post('/feedback', [FeedbackController::class, 'store']);

    Route::get('/runtimes', [RuntimeController::class, 'index']);
    Route::get('/agent-task-snapshot', [AgentTaskSnapshotController::class, 'index']);
    Route::get('/squads', [SquadController::class, 'index']);
    Route::get('/issues/child-progress', [IssueListController::class, 'childProgress']);
    Route::get('/issues', [IssueListController::class, 'index']);
    Route::get('/issues/search', [IssueActionController::class, 'search']);
    Route::get('/issues/grouped', [IssueActionController::class, 'grouped']);
    Route::get('/issues/children', [IssueActionController::class, 'childrenBulk']);
    Route::post('/issues', [IssueActionController::class, 'create']);
    Route::post('/issues/quick-create', [IssueActionController::class, 'quickCreate']);
    Route::post('/issues/batch-update', [IssueActionController::class, 'batchUpdate']);
    Route::post('/issues/batch-delete', [IssueActionController::class, 'batchDelete']);

    Route::get('/inbox', [InboxController::class, 'index']);
    Route::get('/inbox/unread-count', [InboxExtrasController::class, 'unreadCount']);
    Route::post('/inbox/mark-all-read', [InboxExtrasController::class, 'markAllRead']);
    Route::post('/inbox/archive-all', [InboxExtrasController::class, 'archiveAll']);
    Route::post('/inbox/archive-all-read', [InboxExtrasController::class, 'archiveAllRead']);
    Route::post('/inbox/archive-completed', [InboxExtrasController::class, 'archiveCompleted']);
    Route::post('/inbox/{id}/read', [InboxExtrasController::class, 'read']);
    Route::post('/inbox/{id}/archive', [InboxExtrasController::class, 'archive']);

    Route::get('/pins', [PinController::class, 'index']);
    Route::post('/pins', [PinController::class, 'store']);
    Route::delete('/pins/{itemType}/{itemId}', [PinController::class, 'destroy']);
    Route::put('/pins/reorder', [PinController::class, 'reorder']);

    Route::get('/chat/pending-tasks', [ChatController::class, 'pendingTasks']);
    Route::get('/chat/sessions', [ChatController::class, 'sessions']);
    Route::post('/chat/sessions', [ChatExtrasController::class, 'createSession']);
    Route::get('/chat/sessions/{id}', [ChatExtrasController::class, 'showSession']);
    Route::patch('/chat/sessions/{id}', [ChatExtrasController::class, 'updateSession']);
    Route::delete('/chat/sessions/{id}', [ChatExtrasController::class, 'deleteSession']);
    Route::get('/chat/sessions/{sessionId}/messages', [ChatExtrasController::class, 'messages']);
    Route::get('/chat/sessions/{sessionId}/messages/page', [ChatExtrasController::class, 'messagesPage']);
    Route::post('/chat/sessions/{sessionId}/messages', [ChatExtrasController::class, 'sendMessage']);
    Route::get('/chat/sessions/{sessionId}/pending-task', [ChatExtrasController::class, 'pendingTask']);
    Route::post('/chat/sessions/{sessionId}/read', [ChatExtrasController::class, 'readSession']);
    Route::post('/tasks/{taskId}/cancel', [ChatExtrasController::class, 'cancelTask']);
    Route::get('/tasks/{taskId}/messages', [ChatExtrasController::class, 'taskMessages']);

    Route::get('/workspaces/{workspaceId}/members', [WorkspaceMemberController::class, 'index']);
    Route::patch('/workspaces/{id}', [WorkspaceExtrasController::class, 'update']);
    Route::delete('/workspaces/{id}', [WorkspaceExtrasController::class, 'destroy']);
    Route::post('/workspaces/{id}/leave', [WorkspaceExtrasController::class, 'leave']);
    Route::post('/workspaces/{id}/members', [WorkspaceExtrasController::class, 'addMember']);
    Route::patch('/workspaces/{id}/members/{memberId}', [WorkspaceExtrasController::class, 'updateMember']);
    Route::delete('/workspaces/{id}/members/{memberId}', [WorkspaceExtrasController::class, 'removeMember']);
    Route::get('/workspaces/{id}/invitations', [WorkspaceExtrasController::class, 'invitations']);
    Route::delete('/workspaces/{id}/invitations/{invId}', [WorkspaceExtrasController::class, 'deleteInvitation']);
    Route::get('/workspaces/{id}/runtime-profiles', [WorkspaceExtrasController::class, 'runtimeProfiles']);
    Route::post('/workspaces/{id}/runtime-profiles', [WorkspaceExtrasController::class, 'createRuntimeProfile']);
    Route::patch('/workspaces/{id}/runtime-profiles/{profileId}', [WorkspaceExtrasController::class, 'updateRuntimeProfile']);
    Route::delete('/workspaces/{id}/runtime-profiles/{profileId}', [WorkspaceExtrasController::class, 'deleteRuntimeProfile']);
    Route::get('/workspaces/{id}/github/connect', [WorkspaceExtrasController::class, 'githubConnect']);
    Route::get('/workspaces/{id}/github/installations', [WorkspaceExtrasController::class, 'githubInstallations']);
    Route::delete('/workspaces/{id}/github/installations/{instId}', [WorkspaceExtrasController::class, 'deleteGithubInstallation']);
    Route::get('/workspaces', [WorkspaceApiController::class, 'index']);
    Route::get('/workspaces/{id}', [WorkspaceApiController::class, 'show']);

    Route::get('/projects/search', [ProjectSearchController::class, 'search']);

    Route::get('/projects', [ProjectApiController::class, 'index']);
    Route::post('/projects', [ProjectApiController::class, 'store']);
    Route::get('/projects/{id}', [ProjectApiController::class, 'show']);
    Route::put('/projects/{id}', [ProjectApiController::class, 'update']);
    Route::delete('/projects/{id}', [ProjectApiController::class, 'destroy']);

    Route::get('/projects/{id}/resources', [ProjectResourceController::class, 'index']);
    Route::post('/projects/{id}/resources', [ProjectResourceController::class, 'store']);
    Route::put('/projects/{id}/resources/{resourceId}', [ProjectResourceController::class, 'update']);
    Route::delete('/projects/{id}/resources/{resourceId}', [ProjectResourceController::class, 'destroy']);

    Route::get('/issues/{id}/timeline', [IssueSubResourceController::class, 'timeline']);
    Route::get('/issues/{id}/subscribers', [IssueSubResourceController::class, 'subscribers']);
    Route::post('/issues/{id}/subscribe', [IssueActionController::class, 'subscribe']);
    Route::post('/issues/{id}/unsubscribe', [IssueActionController::class, 'unsubscribe']);
    Route::get('/issues/{id}/usage', [IssueSubResourceController::class, 'usage']);
    Route::get('/issues/{id}/active-task', [IssueSubResourceController::class, 'activeTask']);
    Route::get('/issues/{id}/task-runs', [IssueSubResourceController::class, 'taskRuns']);
    Route::post('/issues/{id}/tasks/{taskId}/cancel', [IssueSubResourceController::class, 'cancelTask']);
    Route::post('/issues/{id}/rerun', [IssueSubResourceController::class, 'rerun']);
    Route::get('/issues/{id}/attachments', [IssueSubResourceController::class, 'attachments']);
    Route::get('/issues/{id}/pull-requests', [IssueSubResourceController::class, 'pullRequests']);
    Route::get('/issues/{id}/children', [IssueSubResourceController::class, 'children']);
    Route::get('/issues/{id}/labels', [IssueSubResourceController::class, 'labels']);
    Route::post('/issues/{id}/labels', [LabelController::class, 'addToIssue']);
    Route::delete('/issues/{id}/labels/{labelId}', [LabelController::class, 'removeFromIssue']);
    Route::post('/issues/{issueId}/comments/trigger-preview', [CommentController::class, 'triggerPreview']);
    Route::post('/issues/{id}/reactions', [CommentController::class, 'addIssueReaction']);
    Route::delete('/issues/{id}/reactions', [CommentController::class, 'removeIssueReaction']);

    Route::get('/issues/{id}', [IssueController::class, 'show']);
    Route::put('/issues/{id}', [IssueController::class, 'update']);
    Route::patch('/issues/{id}', [IssueController::class, 'update']);
    Route::delete('/issues/{id}', [IssueController::class, 'destroy']);
    Route::get('/issues/{id}/comments', [IssueController::class, 'comments']);
    Route::post('/issues/{id}/comments', [IssueController::class, 'storeComment']);

    Route::put('/comments/{commentId}', [CommentController::class, 'update']);
    Route::delete('/comments/{commentId}', [CommentController::class, 'destroy']);
    Route::post('/comments/{commentId}/resolve', [CommentController::class, 'resolve']);
    Route::delete('/comments/{commentId}/resolve', [CommentController::class, 'unresolve']);
    Route::post('/comments/{commentId}/reactions', [CommentController::class, 'addReaction']);
    Route::delete('/comments/{commentId}/reactions', [CommentController::class, 'removeReaction']);

    Route::get('/labels', [LabelController::class, 'index']);
    Route::post('/labels', [LabelController::class, 'store']);
    Route::get('/labels/{id}', [LabelController::class, 'show']);
    Route::put('/labels/{id}', [LabelController::class, 'update']);
    Route::delete('/labels/{id}', [LabelController::class, 'destroy']);

    Route::get('/assignee-frequency', [IssueActionController::class, 'assigneeFrequency']);

    Route::get('/squads/{id}', [SquadExtrasController::class, 'show']);
    Route::post('/squads', [SquadExtrasController::class, 'store']);
    Route::put('/squads/{id}', [SquadExtrasController::class, 'update']);
    Route::delete('/squads/{id}', [SquadExtrasController::class, 'destroy']);
    Route::get('/squads/{squadId}/members', [SquadExtrasController::class, 'members']);
    Route::post('/squads/{squadId}/members', [SquadExtrasController::class, 'addMember']);
    Route::delete('/squads/{squadId}/members', [SquadExtrasController::class, 'removeMember']);
    Route::patch('/squads/{squadId}/members/role', [SquadExtrasController::class, 'updateMemberRole']);
    Route::get('/squads/{squadId}/members/status', [SquadExtrasController::class, 'memberStatus']);

    Route::post('/agents/{id}/archive', [AgentExtrasController::class, 'archive']);
    Route::post('/agents/{id}/restore', [AgentExtrasController::class, 'restore']);
    Route::post('/agents/{id}/cancel-tasks', [AgentExtrasController::class, 'cancelTasks']);
    Route::get('/agents/{id}/tasks', [AgentExtrasController::class, 'tasks']);
    Route::get('/agents/{id}/env', [AgentExtrasController::class, 'env']);
    Route::put('/agents/{id}/env', [AgentExtrasController::class, 'updateEnv']);
    Route::get('/agents/{id}/skills', [AgentExtrasController::class, 'skills']);
    Route::put('/agents/{id}/skills', [AgentExtrasController::class, 'setSkills']);
    Route::post('/agents/{id}/skills/add', [AgentExtrasController::class, 'addSkills']);
    Route::get('/agent-templates', [AgentExtrasController::class, 'templates']);
    Route::get('/agent-templates/{slug}', [AgentExtrasController::class, 'template']);
    Route::post('/agents/from-template', [AgentExtrasController::class, 'fromTemplate']);

    Route::delete('/runtimes/{runtimeId}', [RuntimeExtrasController::class, 'destroy']);
    Route::patch('/runtimes/{runtimeId}', [RuntimeExtrasController::class, 'update']);
    Route::get('/runtimes/{runtimeId}/activity', [RuntimeExtrasController::class, 'activity']);
    Route::get('/runtimes/{runtimeId}/usage', [RuntimeExtrasController::class, 'usage']);
    Route::get('/runtimes/{runtimeId}/usage/by-agent', [RuntimeExtrasController::class, 'usageByAgent']);
    Route::get('/runtimes/{runtimeId}/usage/by-hour', [RuntimeExtrasController::class, 'usageByHour']);
    Route::post('/runtimes/{runtimeId}/archive-agents-and-delete', [RuntimeExtrasController::class, 'archiveAndDelete']);
    Route::post('/runtimes/{runtimeId}/update', [RuntimeExtrasController::class, 'requestUpdate']);
    Route::get('/runtimes/{runtimeId}/update/{updateId}', [RuntimeExtrasController::class, 'getUpdate']);
    Route::post('/runtimes/{runtimeId}/models', [RuntimeExtrasController::class, 'requestModels']);
    Route::get('/runtimes/{runtimeId}/models/{requestId}', [RuntimeExtrasController::class, 'getModels']);
    Route::post('/runtimes/{runtimeId}/local-skills', [RuntimeExtrasController::class, 'requestLocalSkills']);
    Route::get('/runtimes/{runtimeId}/local-skills/{requestId}', [RuntimeExtrasController::class, 'getLocalSkills']);
    Route::post('/runtimes/{runtimeId}/local-skills/import', [RuntimeExtrasController::class, 'importLocalSkill']);
    Route::get('/runtimes/{runtimeId}/local-skills/import/{requestId}', [RuntimeExtrasController::class, 'getLocalSkillImport']);

    Route::get('/skills', [SkillController::class, 'index']);
    Route::post('/skills', [SkillController::class, 'store']);
    Route::post('/skills/import', [SkillController::class, 'import']);
    Route::get('/skills/{id}', [SkillController::class, 'show']);
    Route::put('/skills/{id}', [SkillController::class, 'update']);
    Route::delete('/skills/{id}', [SkillController::class, 'destroy']);

    Route::get('/notification-preferences', [NotificationPreferenceController::class, 'index']);
    Route::put('/notification-preferences', [NotificationPreferenceController::class, 'update']);

    Route::post('/upload-file', [UploadController::class, 'upload']);
    Route::get('/attachments/{id}', [UploadController::class, 'show']);
    Route::delete('/attachments/{id}', [UploadController::class, 'destroy']);
    Route::get('/attachments/{id}/content', [UploadController::class, 'content']);

    Route::get('/autopilots', [AutopilotController::class, 'index']);
    Route::post('/autopilots', [AutopilotController::class, 'store']);
    Route::get('/autopilots/{id}', [AutopilotController::class, 'show']);
    Route::patch('/autopilots/{id}', [AutopilotController::class, 'update']);
    Route::delete('/autopilots/{id}', [AutopilotController::class, 'destroy']);
    Route::post('/autopilots/{id}/trigger', [AutopilotController::class, 'trigger']);
    Route::get('/autopilots/{id}/runs', [AutopilotController::class, 'runs']);
    Route::get('/autopilots/{autopilotId}/runs/{runId}', [AutopilotController::class, 'run']);
    Route::post('/autopilots/{autopilotId}/triggers', [AutopilotController::class, 'createTrigger']);
    Route::patch('/autopilots/{autopilotId}/triggers/{triggerId}', [AutopilotController::class, 'updateTrigger']);
    Route::delete('/autopilots/{autopilotId}/triggers/{triggerId}', [AutopilotController::class, 'deleteTrigger']);
    Route::post('/autopilots/{autopilotId}/triggers/{triggerId}/rotate-webhook-token', [AutopilotController::class, 'rotateWebhook']);
    Route::get('/autopilots/{autopilotId}/deliveries', [AutopilotController::class, 'deliveries']);
    Route::get('/autopilots/{autopilotId}/deliveries/{deliveryId}', [AutopilotController::class, 'delivery']);
    Route::post('/autopilots/{autopilotId}/deliveries/{deliveryId}/replay', [AutopilotController::class, 'replayDelivery']);

    Route::get('/dashboard/usage/daily', [DashboardApiController::class, 'usageDaily']);
    Route::get('/dashboard/usage/by-agent', [DashboardApiController::class, 'usageByAgent']);
    Route::get('/dashboard/agent-runtime', [DashboardApiController::class, 'agentRuntime']);
    Route::get('/dashboard/runtime/daily', [DashboardApiController::class, 'runtimeDaily']);

    Route::get('/invitations/{invitationId}', [InvitationController::class, 'show']);
    Route::post('/invitations/{invitationId}/accept', [InvitationController::class, 'accept']);
    Route::post('/invitations/{invitationId}/decline', [InvitationController::class, 'decline']);

    Route::get('/agent-tasks/{queueId}/messages', [AgentTaskController::class, 'messages']);
    Route::post('/me/onboarding/complete', [MeController::class, 'completeOnboarding']);

    // Placeholder for future invitation implementation
    Route::get('/invitations', function () {
        return response()->json([]);
    });
    Route::get('/agent-activity-30d', function () {
        return response()->json([]);
    });
    Route::get('/agent-run-counts', function () {
        return response()->json([]);
    });
});

Route::prefix('daemon')->middleware(DaemonTokenMiddleware::class)->group(function () {
    Route::get('workspaces', [WorkspaceController::class, 'index']);
    Route::get('workspaces/{workspaceId}', [WorkspaceController::class, 'show']);

    // Placeholder for future runtime profile implementation
    Route::get('workspaces/{workspaceId}/runtime-profiles', function () {
        return response()->json(['profiles' => []]);
    });
    Route::post('tasks/{taskId}/session', function () {
        return response()->json(['status' => 'ok']);
    });
    Route::post('tasks/{taskId}/wait-local-directory', [DaemonController::class, 'waitLocalDirectory']);
    Route::post('tasks/{taskId}/messages', [DaemonController::class, 'messages']);
    Route::post('tasks/{taskId}/usage', [DaemonController::class, 'usage']);

    Route::post('register', [DaemonController::class, 'register']);
    Route::post('deregister', [DaemonController::class, 'deregister']);
    Route::post('heartbeat', [DaemonController::class, 'heartbeat']);
    Route::get('workspaces/{workspaceId}/repos', [DaemonController::class, 'workspaceRepos']);
    Route::post('runtimes/{runtimeId}/tasks/claim', [DaemonController::class, 'claimTask']);
    Route::post('runtimes/{runtimeId}/tasks/{taskId}/prepare-lease', [DaemonController::class, 'prepareLease']);
    Route::post('tasks/{taskId}/start', [DaemonController::class, 'startTask']);
    Route::post('tasks/{taskId}/output', [DaemonController::class, 'outputTask']);
    Route::post('tasks/{taskId}/complete', [DaemonController::class, 'completeTask']);
    Route::post('tasks/{taskId}/fail', [DaemonController::class, 'failTask']);
    Route::post('tasks/{taskId}/cancel', [DaemonController::class, 'cancelTask']);

});
