<?php

use App\Http\Controllers\Api\DaemonController;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\AgentTaskController;
use App\Http\Controllers\Api\AuthCodeController;
use App\Http\Controllers\Api\CustomerApiController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\PersonalAccessTokenController;
use App\Http\Controllers\Api\ProjectApiController;
use App\Http\Controllers\Api\ProjectResourceController;
use App\Http\Controllers\Api\RuntimeController;
use App\Http\Controllers\Api\AgentTaskSnapshotController;
use App\Http\Controllers\Api\SquadController;
use App\Http\Controllers\Api\IssueListController;
use App\Http\Controllers\Api\InboxController;
use App\Http\Controllers\Api\PinController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\WorkspaceMemberController;
use App\Http\Controllers\Api\WorkspaceApiController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Middleware\ApiSessionOrPatAuth;
use App\Http\Middleware\DaemonTokenMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/health', function() {
    return response()->json(['status' => 'ok']);
});

Route::middleware('api.secret')->prefix('v1')->group(function () {
    Route::get('customers/{identifier}/invoices', [CustomerApiController::class, 'invoices']);
    Route::post('customers/invoices', [CustomerApiController::class, 'invoices']);
});

Route::post('/auth/send-code', [AuthCodeController::class, 'sendCode']);
Route::post('/auth/verify-code', [AuthCodeController::class, 'verifyCode']);

Route::get('/config', function() {
    return response()->json([
        'cdn_domain'             => null,
        'cdn_signed'             => false,
        'allow_signup'           => false,
        'google_client_id'       => null,
        'posthog_key'            => null,
        'posthog_host'           => null,
        'analytics_environment'  => 'production',
    ]);
});

Route::middleware([
    \Illuminate\Session\Middleware\StartSession::class,
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

    Route::post('/feedback', [\App\Http\Controllers\Api\FeedbackController::class, 'store']);

    Route::get('/runtimes', [\App\Http\Controllers\Api\RuntimeController::class, 'index']);
    Route::get('/agent-task-snapshot', [\App\Http\Controllers\Api\AgentTaskSnapshotController::class, 'index']);
    Route::get('/squads', [\App\Http\Controllers\Api\SquadController::class, 'index']);
    Route::get('/issues/child-progress', [\App\Http\Controllers\Api\IssueListController::class, 'childProgress']);
    Route::get('/issues', [\App\Http\Controllers\Api\IssueListController::class, 'index']);
    Route::get('/issues/search', [\App\Http\Controllers\Api\IssueActionController::class, 'search']);
    Route::get('/issues/grouped', [\App\Http\Controllers\Api\IssueActionController::class, 'grouped']);
    Route::get('/issues/children', [\App\Http\Controllers\Api\IssueActionController::class, 'childrenBulk']);
    Route::post('/issues', [\App\Http\Controllers\Api\IssueActionController::class, 'create']);
    Route::post('/issues/quick-create', [\App\Http\Controllers\Api\IssueActionController::class, 'quickCreate']);
    Route::post('/issues/batch-update', [\App\Http\Controllers\Api\IssueActionController::class, 'batchUpdate']);
    Route::post('/issues/batch-delete', [\App\Http\Controllers\Api\IssueActionController::class, 'batchDelete']);

    Route::get('/inbox', [\App\Http\Controllers\Api\InboxController::class, 'index']);
    Route::get('/inbox/unread-count', [\App\Http\Controllers\Api\InboxExtrasController::class, 'unreadCount']);
    Route::post('/inbox/mark-all-read', [\App\Http\Controllers\Api\InboxExtrasController::class, 'markAllRead']);
    Route::post('/inbox/archive-all', [\App\Http\Controllers\Api\InboxExtrasController::class, 'archiveAll']);
    Route::post('/inbox/archive-all-read', [\App\Http\Controllers\Api\InboxExtrasController::class, 'archiveAllRead']);
    Route::post('/inbox/archive-completed', [\App\Http\Controllers\Api\InboxExtrasController::class, 'archiveCompleted']);
    Route::post('/inbox/{id}/read', [\App\Http\Controllers\Api\InboxExtrasController::class, 'read']);
    Route::post('/inbox/{id}/archive', [\App\Http\Controllers\Api\InboxExtrasController::class, 'archive']);

    Route::get('/pins', [\App\Http\Controllers\Api\PinController::class, 'index']);
    Route::post('/pins', [\App\Http\Controllers\Api\PinController::class, 'store']);
    Route::delete('/pins/{itemType}/{itemId}', [\App\Http\Controllers\Api\PinController::class, 'destroy']);
    Route::put('/pins/reorder', [\App\Http\Controllers\Api\PinController::class, 'reorder']);

    Route::get('/chat/pending-tasks', [\App\Http\Controllers\Api\ChatController::class, 'pendingTasks']);
    Route::get('/chat/sessions', [\App\Http\Controllers\Api\ChatController::class, 'sessions']);
    Route::post('/chat/sessions', [\App\Http\Controllers\Api\ChatExtrasController::class, 'createSession']);
    Route::get('/chat/sessions/{id}', [\App\Http\Controllers\Api\ChatExtrasController::class, 'showSession']);
    Route::patch('/chat/sessions/{id}', [\App\Http\Controllers\Api\ChatExtrasController::class, 'updateSession']);
    Route::delete('/chat/sessions/{id}', [\App\Http\Controllers\Api\ChatExtrasController::class, 'deleteSession']);
    Route::get('/chat/sessions/{sessionId}/messages', [\App\Http\Controllers\Api\ChatExtrasController::class, 'messages']);
    Route::get('/chat/sessions/{sessionId}/messages/page', [\App\Http\Controllers\Api\ChatExtrasController::class, 'messagesPage']);
    Route::post('/chat/sessions/{sessionId}/messages', [\App\Http\Controllers\Api\ChatExtrasController::class, 'sendMessage']);
    Route::get('/chat/sessions/{sessionId}/pending-task', [\App\Http\Controllers\Api\ChatExtrasController::class, 'pendingTask']);
    Route::post('/chat/sessions/{sessionId}/read', [\App\Http\Controllers\Api\ChatExtrasController::class, 'readSession']);
    Route::post('/tasks/{taskId}/cancel', [\App\Http\Controllers\Api\ChatExtrasController::class, 'cancelTask']);
    Route::get('/tasks/{taskId}/messages', [\App\Http\Controllers\Api\ChatExtrasController::class, 'taskMessages']);

    Route::get('/workspaces/{workspaceId}/members', [\App\Http\Controllers\Api\WorkspaceMemberController::class, 'index']);
    Route::patch('/workspaces/{id}', [\App\Http\Controllers\Api\WorkspaceExtrasController::class, 'update']);
    Route::delete('/workspaces/{id}', [\App\Http\Controllers\Api\WorkspaceExtrasController::class, 'destroy']);
    Route::post('/workspaces/{id}/leave', [\App\Http\Controllers\Api\WorkspaceExtrasController::class, 'leave']);
    Route::post('/workspaces/{id}/members', [\App\Http\Controllers\Api\WorkspaceExtrasController::class, 'addMember']);
    Route::patch('/workspaces/{id}/members/{memberId}', [\App\Http\Controllers\Api\WorkspaceExtrasController::class, 'updateMember']);
    Route::delete('/workspaces/{id}/members/{memberId}', [\App\Http\Controllers\Api\WorkspaceExtrasController::class, 'removeMember']);
    Route::get('/workspaces/{id}/invitations', [\App\Http\Controllers\Api\WorkspaceExtrasController::class, 'invitations']);
    Route::delete('/workspaces/{id}/invitations/{invId}', [\App\Http\Controllers\Api\WorkspaceExtrasController::class, 'deleteInvitation']);
    Route::get('/workspaces/{id}/runtime-profiles', [\App\Http\Controllers\Api\WorkspaceExtrasController::class, 'runtimeProfiles']);
    Route::post('/workspaces/{id}/runtime-profiles', [\App\Http\Controllers\Api\WorkspaceExtrasController::class, 'createRuntimeProfile']);
    Route::patch('/workspaces/{id}/runtime-profiles/{profileId}', [\App\Http\Controllers\Api\WorkspaceExtrasController::class, 'updateRuntimeProfile']);
    Route::delete('/workspaces/{id}/runtime-profiles/{profileId}', [\App\Http\Controllers\Api\WorkspaceExtrasController::class, 'deleteRuntimeProfile']);
    Route::get('/workspaces/{id}/github/connect', [\App\Http\Controllers\Api\WorkspaceExtrasController::class, 'githubConnect']);
    Route::get('/workspaces/{id}/github/installations', [\App\Http\Controllers\Api\WorkspaceExtrasController::class, 'githubInstallations']);
    Route::delete('/workspaces/{id}/github/installations/{instId}', [\App\Http\Controllers\Api\WorkspaceExtrasController::class, 'deleteGithubInstallation']);
    Route::get('/workspaces', [WorkspaceApiController::class, 'index']);
    Route::get('/workspaces/{id}', [WorkspaceApiController::class, 'show']);
    
    Route::get('/projects/search', [\App\Http\Controllers\Api\ProjectSearchController::class, 'search']);

    Route::get('/projects', [ProjectApiController::class, 'index']);
    Route::post('/projects', [ProjectApiController::class, 'store']);
    Route::get('/projects/{id}', [ProjectApiController::class, 'show']);
    Route::put('/projects/{id}', [ProjectApiController::class, 'update']);
    Route::delete('/projects/{id}', [ProjectApiController::class, 'destroy']);

    Route::get('/projects/{id}/resources', [ProjectResourceController::class, 'index']);
    Route::post('/projects/{id}/resources', [ProjectResourceController::class, 'store']);
    Route::put('/projects/{id}/resources/{resourceId}', [ProjectResourceController::class, 'update']);
    Route::delete('/projects/{id}/resources/{resourceId}', [ProjectResourceController::class, 'destroy']);

    Route::get('/issues/{id}/timeline', [\App\Http\Controllers\Api\IssueSubResourceController::class, 'timeline']);
    Route::get('/issues/{id}/subscribers', [\App\Http\Controllers\Api\IssueSubResourceController::class, 'subscribers']);
    Route::post('/issues/{id}/subscribe', [\App\Http\Controllers\Api\IssueActionController::class, 'subscribe']);
    Route::post('/issues/{id}/unsubscribe', [\App\Http\Controllers\Api\IssueActionController::class, 'unsubscribe']);
    Route::get('/issues/{id}/usage', [\App\Http\Controllers\Api\IssueSubResourceController::class, 'usage']);
    Route::get('/issues/{id}/active-task', [\App\Http\Controllers\Api\IssueSubResourceController::class, 'activeTask']);
    Route::get('/issues/{id}/task-runs', [\App\Http\Controllers\Api\IssueSubResourceController::class, 'taskRuns']);
    Route::post('/issues/{id}/tasks/{taskId}/cancel', [\App\Http\Controllers\Api\IssueSubResourceController::class, 'cancelTask']);
    Route::post('/issues/{id}/rerun', [\App\Http\Controllers\Api\IssueSubResourceController::class, 'rerun']);
    Route::get('/issues/{id}/attachments', [\App\Http\Controllers\Api\IssueSubResourceController::class, 'attachments']);
    Route::get('/issues/{id}/pull-requests', [\App\Http\Controllers\Api\IssueSubResourceController::class, 'pullRequests']);
    Route::get('/issues/{id}/children', [\App\Http\Controllers\Api\IssueSubResourceController::class, 'children']);
    Route::get('/issues/{id}/labels', [\App\Http\Controllers\Api\IssueSubResourceController::class, 'labels']);
    Route::post('/issues/{id}/labels', [\App\Http\Controllers\Api\LabelController::class, 'addToIssue']);
    Route::delete('/issues/{id}/labels/{labelId}', [\App\Http\Controllers\Api\LabelController::class, 'removeFromIssue']);
    Route::post('/issues/{issueId}/comments/trigger-preview', [\App\Http\Controllers\Api\CommentController::class, 'triggerPreview']);
    Route::post('/issues/{id}/reactions', [\App\Http\Controllers\Api\CommentController::class, 'addIssueReaction']);
    Route::delete('/issues/{id}/reactions', [\App\Http\Controllers\Api\CommentController::class, 'removeIssueReaction']);

    Route::get('/issues/{id}', [\App\Http\Controllers\Api\IssueController::class, 'show']);
    Route::put('/issues/{id}', [\App\Http\Controllers\Api\IssueController::class, 'update']);
    Route::patch('/issues/{id}', [\App\Http\Controllers\Api\IssueController::class, 'update']);
    Route::delete('/issues/{id}', [\App\Http\Controllers\Api\IssueController::class, 'destroy']);
    Route::get('/issues/{id}/comments', [\App\Http\Controllers\Api\IssueController::class, 'comments']);
    Route::post('/issues/{id}/comments', [\App\Http\Controllers\Api\IssueController::class, 'storeComment']);

    Route::put('/comments/{commentId}', [\App\Http\Controllers\Api\CommentController::class, 'update']);
    Route::delete('/comments/{commentId}', [\App\Http\Controllers\Api\CommentController::class, 'destroy']);
    Route::post('/comments/{commentId}/resolve', [\App\Http\Controllers\Api\CommentController::class, 'resolve']);
    Route::delete('/comments/{commentId}/resolve', [\App\Http\Controllers\Api\CommentController::class, 'unresolve']);
    Route::post('/comments/{commentId}/reactions', [\App\Http\Controllers\Api\CommentController::class, 'addReaction']);
    Route::delete('/comments/{commentId}/reactions', [\App\Http\Controllers\Api\CommentController::class, 'removeReaction']);

    Route::get('/labels', [\App\Http\Controllers\Api\LabelController::class, 'index']);
    Route::post('/labels', [\App\Http\Controllers\Api\LabelController::class, 'store']);
    Route::get('/labels/{id}', [\App\Http\Controllers\Api\LabelController::class, 'show']);
    Route::put('/labels/{id}', [\App\Http\Controllers\Api\LabelController::class, 'update']);
    Route::delete('/labels/{id}', [\App\Http\Controllers\Api\LabelController::class, 'destroy']);

    Route::get('/assignee-frequency', [\App\Http\Controllers\Api\IssueActionController::class, 'assigneeFrequency']);

    Route::get('/squads/{id}', [\App\Http\Controllers\Api\SquadExtrasController::class, 'show']);
    Route::post('/squads', [\App\Http\Controllers\Api\SquadExtrasController::class, 'store']);
    Route::put('/squads/{id}', [\App\Http\Controllers\Api\SquadExtrasController::class, 'update']);
    Route::delete('/squads/{id}', [\App\Http\Controllers\Api\SquadExtrasController::class, 'destroy']);
    Route::get('/squads/{squadId}/members', [\App\Http\Controllers\Api\SquadExtrasController::class, 'members']);
    Route::post('/squads/{squadId}/members', [\App\Http\Controllers\Api\SquadExtrasController::class, 'addMember']);
    Route::delete('/squads/{squadId}/members', [\App\Http\Controllers\Api\SquadExtrasController::class, 'removeMember']);
    Route::patch('/squads/{squadId}/members/role', [\App\Http\Controllers\Api\SquadExtrasController::class, 'updateMemberRole']);
    Route::get('/squads/{squadId}/members/status', [\App\Http\Controllers\Api\SquadExtrasController::class, 'memberStatus']);

    Route::post('/agents/{id}/archive', [\App\Http\Controllers\Api\AgentExtrasController::class, 'archive']);
    Route::post('/agents/{id}/restore', [\App\Http\Controllers\Api\AgentExtrasController::class, 'restore']);
    Route::post('/agents/{id}/cancel-tasks', [\App\Http\Controllers\Api\AgentExtrasController::class, 'cancelTasks']);
    Route::get('/agents/{id}/tasks', [\App\Http\Controllers\Api\AgentExtrasController::class, 'tasks']);
    Route::get('/agents/{id}/env', [\App\Http\Controllers\Api\AgentExtrasController::class, 'env']);
    Route::put('/agents/{id}/env', [\App\Http\Controllers\Api\AgentExtrasController::class, 'updateEnv']);
    Route::get('/agents/{id}/skills', [\App\Http\Controllers\Api\AgentExtrasController::class, 'skills']);
    Route::put('/agents/{id}/skills', [\App\Http\Controllers\Api\AgentExtrasController::class, 'setSkills']);
    Route::post('/agents/{id}/skills/add', [\App\Http\Controllers\Api\AgentExtrasController::class, 'addSkills']);
    Route::get('/agent-templates', [\App\Http\Controllers\Api\AgentExtrasController::class, 'templates']);
    Route::get('/agent-templates/{slug}', [\App\Http\Controllers\Api\AgentExtrasController::class, 'template']);
    Route::post('/agents/from-template', [\App\Http\Controllers\Api\AgentExtrasController::class, 'fromTemplate']);

    Route::delete('/runtimes/{runtimeId}', [\App\Http\Controllers\Api\RuntimeExtrasController::class, 'destroy']);
    Route::patch('/runtimes/{runtimeId}', [\App\Http\Controllers\Api\RuntimeExtrasController::class, 'update']);
    Route::get('/runtimes/{runtimeId}/activity', [\App\Http\Controllers\Api\RuntimeExtrasController::class, 'activity']);
    Route::get('/runtimes/{runtimeId}/usage', [\App\Http\Controllers\Api\RuntimeExtrasController::class, 'usage']);
    Route::get('/runtimes/{runtimeId}/usage/by-agent', [\App\Http\Controllers\Api\RuntimeExtrasController::class, 'usageByAgent']);
    Route::get('/runtimes/{runtimeId}/usage/by-hour', [\App\Http\Controllers\Api\RuntimeExtrasController::class, 'usageByHour']);
    Route::post('/runtimes/{runtimeId}/archive-agents-and-delete', [\App\Http\Controllers\Api\RuntimeExtrasController::class, 'archiveAndDelete']);
    Route::post('/runtimes/{runtimeId}/update', [\App\Http\Controllers\Api\RuntimeExtrasController::class, 'requestUpdate']);
    Route::get('/runtimes/{runtimeId}/update/{updateId}', [\App\Http\Controllers\Api\RuntimeExtrasController::class, 'getUpdate']);
    Route::post('/runtimes/{runtimeId}/models', [\App\Http\Controllers\Api\RuntimeExtrasController::class, 'requestModels']);
    Route::get('/runtimes/{runtimeId}/models/{requestId}', [\App\Http\Controllers\Api\RuntimeExtrasController::class, 'getModels']);
    Route::post('/runtimes/{runtimeId}/local-skills', [\App\Http\Controllers\Api\RuntimeExtrasController::class, 'requestLocalSkills']);
    Route::get('/runtimes/{runtimeId}/local-skills/{requestId}', [\App\Http\Controllers\Api\RuntimeExtrasController::class, 'getLocalSkills']);
    Route::post('/runtimes/{runtimeId}/local-skills/import', [\App\Http\Controllers\Api\RuntimeExtrasController::class, 'importLocalSkill']);
    Route::get('/runtimes/{runtimeId}/local-skills/import/{requestId}', [\App\Http\Controllers\Api\RuntimeExtrasController::class, 'getLocalSkillImport']);

    Route::get('/skills', [\App\Http\Controllers\Api\SkillController::class, 'index']);
    Route::post('/skills', [\App\Http\Controllers\Api\SkillController::class, 'store']);
    Route::post('/skills/import', [\App\Http\Controllers\Api\SkillController::class, 'import']);
    Route::get('/skills/{id}', [\App\Http\Controllers\Api\SkillController::class, 'show']);
    Route::put('/skills/{id}', [\App\Http\Controllers\Api\SkillController::class, 'update']);
    Route::delete('/skills/{id}', [\App\Http\Controllers\Api\SkillController::class, 'destroy']);

    Route::get('/notification-preferences', [\App\Http\Controllers\Api\NotificationPreferenceController::class, 'index']);
    Route::put('/notification-preferences', [\App\Http\Controllers\Api\NotificationPreferenceController::class, 'update']);

    Route::post('/upload-file', [\App\Http\Controllers\Api\UploadController::class, 'upload']);
    Route::get('/attachments/{id}', [\App\Http\Controllers\Api\UploadController::class, 'show']);
    Route::delete('/attachments/{id}', [\App\Http\Controllers\Api\UploadController::class, 'destroy']);
    Route::get('/attachments/{id}/content', [\App\Http\Controllers\Api\UploadController::class, 'content']);

    Route::get('/autopilots', [\App\Http\Controllers\Api\AutopilotController::class, 'index']);
    Route::post('/autopilots', [\App\Http\Controllers\Api\AutopilotController::class, 'store']);
    Route::get('/autopilots/{id}', [\App\Http\Controllers\Api\AutopilotController::class, 'show']);
    Route::patch('/autopilots/{id}', [\App\Http\Controllers\Api\AutopilotController::class, 'update']);
    Route::delete('/autopilots/{id}', [\App\Http\Controllers\Api\AutopilotController::class, 'destroy']);
    Route::post('/autopilots/{id}/trigger', [\App\Http\Controllers\Api\AutopilotController::class, 'trigger']);
    Route::get('/autopilots/{id}/runs', [\App\Http\Controllers\Api\AutopilotController::class, 'runs']);
    Route::get('/autopilots/{autopilotId}/runs/{runId}', [\App\Http\Controllers\Api\AutopilotController::class, 'run']);
    Route::post('/autopilots/{autopilotId}/triggers', [\App\Http\Controllers\Api\AutopilotController::class, 'createTrigger']);
    Route::patch('/autopilots/{autopilotId}/triggers/{triggerId}', [\App\Http\Controllers\Api\AutopilotController::class, 'updateTrigger']);
    Route::delete('/autopilots/{autopilotId}/triggers/{triggerId}', [\App\Http\Controllers\Api\AutopilotController::class, 'deleteTrigger']);
    Route::post('/autopilots/{autopilotId}/triggers/{triggerId}/rotate-webhook-token', [\App\Http\Controllers\Api\AutopilotController::class, 'rotateWebhook']);
    Route::get('/autopilots/{autopilotId}/deliveries', [\App\Http\Controllers\Api\AutopilotController::class, 'deliveries']);
    Route::get('/autopilots/{autopilotId}/deliveries/{deliveryId}', [\App\Http\Controllers\Api\AutopilotController::class, 'delivery']);
    Route::post('/autopilots/{autopilotId}/deliveries/{deliveryId}/replay', [\App\Http\Controllers\Api\AutopilotController::class, 'replayDelivery']);

    Route::get('/dashboard/usage/daily', [\App\Http\Controllers\Api\DashboardApiController::class, 'usageDaily']);
    Route::get('/dashboard/usage/by-agent', [\App\Http\Controllers\Api\DashboardApiController::class, 'usageByAgent']);
    Route::get('/dashboard/agent-runtime', [\App\Http\Controllers\Api\DashboardApiController::class, 'agentRuntime']);
    Route::get('/dashboard/runtime/daily', [\App\Http\Controllers\Api\DashboardApiController::class, 'runtimeDaily']);

    Route::get('/invitations/{invitationId}', [\App\Http\Controllers\Api\InvitationController::class, 'show']);
    Route::post('/invitations/{invitationId}/accept', [\App\Http\Controllers\Api\InvitationController::class, 'accept']);
    Route::post('/invitations/{invitationId}/decline', [\App\Http\Controllers\Api\InvitationController::class, 'decline']);

    Route::get('/agent-tasks/{queueId}/messages', [AgentTaskController::class, 'messages']);
    Route::post('/me/onboarding/complete', [MeController::class, 'completeOnboarding']);

    // Placeholder for future invitation implementation
    Route::get('/invitations', function() {
        return response()->json([]);
    });
    Route::get('/agent-activity-30d', function() {
        return response()->json([]);
    });
    Route::get('/agent-run-counts', function() {
        return response()->json([]);
    });
});

Route::prefix('daemon')->middleware(DaemonTokenMiddleware::class)->group(function () {
    Route::get('workspaces', [WorkspaceController::class, 'index']);
    Route::get('workspaces/{workspaceId}', [WorkspaceController::class, 'show']);

    // Placeholder for future runtime profile implementation
    Route::get('workspaces/{workspaceId}/runtime-profiles', function() {
        return response()->json(['profiles' => []]);
    });
    Route::post('tasks/{taskId}/session', function() {
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
    Route::post('tasks/{taskId}/start', [DaemonController::class, 'startTask']);
    Route::post('tasks/{taskId}/output', [DaemonController::class, 'outputTask']);
    Route::post('tasks/{taskId}/complete', [DaemonController::class, 'completeTask']);
    Route::post('tasks/{taskId}/fail', [DaemonController::class, 'failTask']);
    Route::post('tasks/{taskId}/cancel', [DaemonController::class, 'cancelTask']);

});