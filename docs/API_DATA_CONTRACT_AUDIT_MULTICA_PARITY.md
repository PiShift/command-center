# Command Center API/Data Contract Audit for Multica Parity

Generated: 2026-06-20
Scope: Current Command Center Laravel repository runtime and code contract.
Mode: Read-only analysis (no runtime behavior changes).

## 1. Executive summary

- Runtime API inventory currently exposes 212 routes under api/* (from route:list runtime dump), including desktop/session PAT routes and daemon token routes.
- Focus endpoint families are implemented, but many extras remain placeholder contracts returning empty arrays/objects or status ok.
- Issue contracts recently drifted from previous shape: assignee fields now include typed IDs like member-{id} or agent-{uuid}, and issue status mapping differs between list/detail vs action endpoints.
- Route ordering for major dynamic collisions is currently safe for issues and projects; static paths are declared before wildcard id routes.
- Auth model is mixed session plus PAT plus task token and differs by route group; this is functional but a parity risk if desktop expects one canonical auth flow.
- New agent_task_usage is present in schema and wired through daemon usage write and issue usage read.

Key source anchors:
- API route declarations: [routes/api.php](routes/api.php#L1-L307)
- Session or PAT auth middleware: [app/Http/Middleware/ApiSessionOrPatAuth.php](app/Http/Middleware/ApiSessionOrPatAuth.php#L17-L94)
- Daemon token middleware: [app/Http/Middleware/DaemonTokenMiddleware.php](app/Http/Middleware/DaemonTokenMiddleware.php#L17-L89)

## 2. Full route inventory

Source: runtime export via php artisan route:list --json.
Notes:
- Order shown below is runtime order.
- Potential collision checks performed for api/* with same method and same segment count found no active shadows.
- Guarded order examples:
  - issues/search and issues/grouped before issues/{id}: [routes/api.php](routes/api.php#L82-L83), [routes/api.php](routes/api.php#L167-L172)
  - projects/search before projects/{id}: [routes/api.php](routes/api.php#L135-L140)

| # | Method | URI | Action | Middleware | Name | Notes |
|---:|---|---|---|---|---|---|
| 1 | GET, HEAD | api/agent-activity-30d | Closure | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 2 | GET, HEAD | api/agent-run-counts | Closure | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 3 | GET, HEAD | api/agent-task-snapshot | App\Http\Controllers\Api\AgentTaskSnapshotController@index | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 4 | GET, HEAD | api/agent-tasks/{queueId}/messages | App\Http\Controllers\Api\AgentTaskController@messages | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 5 | GET, HEAD | api/agent-templates | App\Http\Controllers\Api\AgentExtrasController@templates | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 6 | GET, HEAD | api/agent-templates/{slug} | App\Http\Controllers\Api\AgentExtrasController@template | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 7 | POST | api/agents | App\Http\Controllers\Api\AgentController@store | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 8 | GET, HEAD | api/agents | App\Http\Controllers\Api\AgentController@index | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 9 | POST | api/agents/from-template | App\Http\Controllers\Api\AgentExtrasController@fromTemplate | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 10 | GET, HEAD | api/agents/{id} | App\Http\Controllers\Api\AgentController@show | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 11 | PUT | api/agents/{id} | App\Http\Controllers\Api\AgentController@update | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 12 | DELETE | api/agents/{id} | App\Http\Controllers\Api\AgentController@destroy | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 13 | POST | api/agents/{id}/archive | App\Http\Controllers\Api\AgentExtrasController@archive | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 14 | POST | api/agents/{id}/cancel-tasks | App\Http\Controllers\Api\AgentExtrasController@cancelTasks | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 15 | GET, HEAD | api/agents/{id}/env | App\Http\Controllers\Api\AgentExtrasController@env | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 16 | PUT | api/agents/{id}/env | App\Http\Controllers\Api\AgentExtrasController@updateEnv | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 17 | POST | api/agents/{id}/restore | App\Http\Controllers\Api\AgentExtrasController@restore | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 18 | GET, HEAD | api/agents/{id}/skills | App\Http\Controllers\Api\AgentExtrasController@skills | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 19 | PUT | api/agents/{id}/skills | App\Http\Controllers\Api\AgentExtrasController@setSkills | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 20 | POST | api/agents/{id}/skills/add | App\Http\Controllers\Api\AgentExtrasController@addSkills | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 21 | GET, HEAD | api/agents/{id}/tasks | App\Http\Controllers\Api\AgentExtrasController@tasks | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 22 | GET, HEAD | api/assignee-frequency | App\Http\Controllers\Api\IssueActionController@assigneeFrequency | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 23 | GET, HEAD | api/attachments/{id} | App\Http\Controllers\Api\UploadController@show | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 24 | DELETE | api/attachments/{id} | App\Http\Controllers\Api\UploadController@destroy | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 25 | GET, HEAD | api/attachments/{id}/content | App\Http\Controllers\Api\UploadController@content | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 26 | POST | api/auth/logout | Closure | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 27 | POST | api/auth/send-code | App\Http\Controllers\Api\AuthCodeController@sendCode | api |  |  |
| 28 | POST | api/auth/verify-code | App\Http\Controllers\Api\AuthCodeController@verifyCode | api |  |  |
| 29 | GET, HEAD | api/autopilots | App\Http\Controllers\Api\AutopilotController@index | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 30 | POST | api/autopilots | App\Http\Controllers\Api\AutopilotController@store | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 31 | GET, HEAD | api/autopilots/{autopilotId}/deliveries | App\Http\Controllers\Api\AutopilotController@deliveries | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 32 | GET, HEAD | api/autopilots/{autopilotId}/deliveries/{deliveryId} | App\Http\Controllers\Api\AutopilotController@delivery | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 33 | POST | api/autopilots/{autopilotId}/deliveries/{deliveryId}/replay | App\Http\Controllers\Api\AutopilotController@replayDelivery | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 34 | GET, HEAD | api/autopilots/{autopilotId}/runs/{runId} | App\Http\Controllers\Api\AutopilotController@run | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 35 | POST | api/autopilots/{autopilotId}/triggers | App\Http\Controllers\Api\AutopilotController@createTrigger | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 36 | PATCH | api/autopilots/{autopilotId}/triggers/{triggerId} | App\Http\Controllers\Api\AutopilotController@updateTrigger | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 37 | DELETE | api/autopilots/{autopilotId}/triggers/{triggerId} | App\Http\Controllers\Api\AutopilotController@deleteTrigger | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 38 | POST | api/autopilots/{autopilotId}/triggers/{triggerId}/rotate-webhook-token | App\Http\Controllers\Api\AutopilotController@rotateWebhook | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 39 | GET, HEAD | api/autopilots/{id} | App\Http\Controllers\Api\AutopilotController@show | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 40 | PATCH | api/autopilots/{id} | App\Http\Controllers\Api\AutopilotController@update | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 41 | DELETE | api/autopilots/{id} | App\Http\Controllers\Api\AutopilotController@destroy | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 42 | GET, HEAD | api/autopilots/{id}/runs | App\Http\Controllers\Api\AutopilotController@runs | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 43 | POST | api/autopilots/{id}/trigger | App\Http\Controllers\Api\AutopilotController@trigger | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 44 | GET, HEAD | api/chat/pending-tasks | App\Http\Controllers\Api\ChatController@pendingTasks | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 45 | GET, HEAD | api/chat/sessions | App\Http\Controllers\Api\ChatController@sessions | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 46 | POST | api/chat/sessions | App\Http\Controllers\Api\ChatExtrasController@createSession | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 47 | GET, HEAD | api/chat/sessions/{id} | App\Http\Controllers\Api\ChatExtrasController@showSession | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 48 | PATCH | api/chat/sessions/{id} | App\Http\Controllers\Api\ChatExtrasController@updateSession | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 49 | DELETE | api/chat/sessions/{id} | App\Http\Controllers\Api\ChatExtrasController@deleteSession | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 50 | GET, HEAD | api/chat/sessions/{sessionId}/messages | App\Http\Controllers\Api\ChatExtrasController@messages | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 51 | POST | api/chat/sessions/{sessionId}/messages | App\Http\Controllers\Api\ChatExtrasController@sendMessage | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 52 | GET, HEAD | api/chat/sessions/{sessionId}/messages/page | App\Http\Controllers\Api\ChatExtrasController@messagesPage | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 53 | GET, HEAD | api/chat/sessions/{sessionId}/pending-task | App\Http\Controllers\Api\ChatExtrasController@pendingTask | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 54 | POST | api/chat/sessions/{sessionId}/read | App\Http\Controllers\Api\ChatExtrasController@readSession | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 55 | POST | api/cli-token | App\Http\Controllers\Api\MeController@cliToken | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 56 | PUT | api/comments/{commentId} | App\Http\Controllers\Api\CommentController@update | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 57 | DELETE | api/comments/{commentId} | App\Http\Controllers\Api\CommentController@destroy | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 58 | POST | api/comments/{commentId}/reactions | App\Http\Controllers\Api\CommentController@addReaction | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 59 | DELETE | api/comments/{commentId}/reactions | App\Http\Controllers\Api\CommentController@removeReaction | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 60 | POST | api/comments/{commentId}/resolve | App\Http\Controllers\Api\CommentController@resolve | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 61 | DELETE | api/comments/{commentId}/resolve | App\Http\Controllers\Api\CommentController@unresolve | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 62 | GET, HEAD | api/config | Closure | api |  |  |
| 63 | POST | api/daemon/deregister | App\Http\Controllers\Api\DaemonController@deregister | api, App\Http\Middleware\DaemonTokenMiddleware |  |  |
| 64 | POST | api/daemon/heartbeat | App\Http\Controllers\Api\DaemonController@heartbeat | api, App\Http\Middleware\DaemonTokenMiddleware |  |  |
| 65 | POST | api/daemon/register | App\Http\Controllers\Api\DaemonController@register | api, App\Http\Middleware\DaemonTokenMiddleware |  |  |
| 66 | POST | api/daemon/runtimes/{runtimeId}/tasks/claim | App\Http\Controllers\Api\DaemonController@claimTask | api, App\Http\Middleware\DaemonTokenMiddleware |  |  |
| 67 | POST | api/daemon/tasks/{taskId}/cancel | App\Http\Controllers\Api\DaemonController@cancelTask | api, App\Http\Middleware\DaemonTokenMiddleware |  |  |
| 68 | POST | api/daemon/tasks/{taskId}/complete | App\Http\Controllers\Api\DaemonController@completeTask | api, App\Http\Middleware\DaemonTokenMiddleware |  |  |
| 69 | POST | api/daemon/tasks/{taskId}/fail | App\Http\Controllers\Api\DaemonController@failTask | api, App\Http\Middleware\DaemonTokenMiddleware |  |  |
| 70 | POST | api/daemon/tasks/{taskId}/messages | App\Http\Controllers\Api\DaemonController@messages | api, App\Http\Middleware\DaemonTokenMiddleware |  |  |
| 71 | POST | api/daemon/tasks/{taskId}/output | App\Http\Controllers\Api\DaemonController@outputTask | api, App\Http\Middleware\DaemonTokenMiddleware |  |  |
| 72 | POST | api/daemon/tasks/{taskId}/session | Closure | api, App\Http\Middleware\DaemonTokenMiddleware |  |  |
| 73 | POST | api/daemon/tasks/{taskId}/start | App\Http\Controllers\Api\DaemonController@startTask | api, App\Http\Middleware\DaemonTokenMiddleware |  |  |
| 74 | POST | api/daemon/tasks/{taskId}/usage | App\Http\Controllers\Api\DaemonController@usage | api, App\Http\Middleware\DaemonTokenMiddleware |  |  |
| 75 | GET, HEAD | api/daemon/workspaces | App\Http\Controllers\Api\WorkspaceController@index | api, App\Http\Middleware\DaemonTokenMiddleware |  |  |
| 76 | GET, HEAD | api/daemon/workspaces/{workspaceId} | App\Http\Controllers\Api\WorkspaceController@show | api, App\Http\Middleware\DaemonTokenMiddleware |  |  |
| 77 | GET, HEAD | api/daemon/workspaces/{workspaceId}/repos | App\Http\Controllers\Api\DaemonController@workspaceRepos | api, App\Http\Middleware\DaemonTokenMiddleware |  |  |
| 78 | GET, HEAD | api/daemon/workspaces/{workspaceId}/runtime-profiles | Closure | api, App\Http\Middleware\DaemonTokenMiddleware |  |  |
| 79 | GET, HEAD | api/dashboard/agent-runtime | App\Http\Controllers\Api\DashboardApiController@agentRuntime | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 80 | GET, HEAD | api/dashboard/runtime/daily | App\Http\Controllers\Api\DashboardApiController@runtimeDaily | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 81 | GET, HEAD | api/dashboard/usage/by-agent | App\Http\Controllers\Api\DashboardApiController@usageByAgent | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 82 | GET, HEAD | api/dashboard/usage/daily | App\Http\Controllers\Api\DashboardApiController@usageDaily | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 83 | POST | api/feedback | App\Http\Controllers\Api\FeedbackController@store | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 84 | GET, HEAD | api/health | Closure | api |  |  |
| 85 | GET, HEAD | api/inbox | App\Http\Controllers\Api\InboxController@index | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 86 | POST | api/inbox/archive-all | App\Http\Controllers\Api\InboxExtrasController@archiveAll | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 87 | POST | api/inbox/archive-all-read | App\Http\Controllers\Api\InboxExtrasController@archiveAllRead | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 88 | POST | api/inbox/archive-completed | App\Http\Controllers\Api\InboxExtrasController@archiveCompleted | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 89 | POST | api/inbox/mark-all-read | App\Http\Controllers\Api\InboxExtrasController@markAllRead | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 90 | GET, HEAD | api/inbox/unread-count | App\Http\Controllers\Api\InboxExtrasController@unreadCount | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 91 | POST | api/inbox/{id}/archive | App\Http\Controllers\Api\InboxExtrasController@archive | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 92 | POST | api/inbox/{id}/read | App\Http\Controllers\Api\InboxExtrasController@read | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 93 | GET, HEAD | api/invitations | Closure | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 94 | GET, HEAD | api/invitations/{invitationId} | App\Http\Controllers\Api\InvitationController@show | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 95 | POST | api/invitations/{invitationId}/accept | App\Http\Controllers\Api\InvitationController@accept | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 96 | POST | api/invitations/{invitationId}/decline | App\Http\Controllers\Api\InvitationController@decline | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 97 | GET, HEAD | api/issues | App\Http\Controllers\Api\IssueListController@index | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 98 | POST | api/issues | App\Http\Controllers\Api\IssueActionController@create | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 99 | POST | api/issues/batch-delete | App\Http\Controllers\Api\IssueActionController@batchDelete | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 100 | POST | api/issues/batch-update | App\Http\Controllers\Api\IssueActionController@batchUpdate | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 101 | GET, HEAD | api/issues/child-progress | App\Http\Controllers\Api\IssueListController@childProgress | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 102 | GET, HEAD | api/issues/children | App\Http\Controllers\Api\IssueActionController@childrenBulk | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 103 | GET, HEAD | api/issues/grouped | App\Http\Controllers\Api\IssueActionController@grouped | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 104 | POST | api/issues/quick-create | App\Http\Controllers\Api\IssueActionController@quickCreate | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 105 | GET, HEAD | api/issues/search | App\Http\Controllers\Api\IssueActionController@search | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 106 | GET, HEAD | api/issues/{id} | App\Http\Controllers\Api\IssueController@show | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 107 | PUT | api/issues/{id} | App\Http\Controllers\Api\IssueController@update | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 108 | PATCH | api/issues/{id} | App\Http\Controllers\Api\IssueController@update | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 109 | DELETE | api/issues/{id} | App\Http\Controllers\Api\IssueController@destroy | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 110 | GET, HEAD | api/issues/{id}/active-task | App\Http\Controllers\Api\IssueSubResourceController@activeTask | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 111 | GET, HEAD | api/issues/{id}/attachments | App\Http\Controllers\Api\IssueSubResourceController@attachments | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 112 | GET, HEAD | api/issues/{id}/children | App\Http\Controllers\Api\IssueSubResourceController@children | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 113 | GET, HEAD | api/issues/{id}/comments | App\Http\Controllers\Api\IssueController@comments | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 114 | POST | api/issues/{id}/comments | App\Http\Controllers\Api\IssueController@storeComment | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 115 | GET, HEAD | api/issues/{id}/labels | App\Http\Controllers\Api\IssueSubResourceController@labels | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 116 | POST | api/issues/{id}/labels | App\Http\Controllers\Api\LabelController@addToIssue | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 117 | DELETE | api/issues/{id}/labels/{labelId} | App\Http\Controllers\Api\LabelController@removeFromIssue | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 118 | GET, HEAD | api/issues/{id}/pull-requests | App\Http\Controllers\Api\IssueSubResourceController@pullRequests | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 119 | POST | api/issues/{id}/reactions | App\Http\Controllers\Api\CommentController@addIssueReaction | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 120 | DELETE | api/issues/{id}/reactions | App\Http\Controllers\Api\CommentController@removeIssueReaction | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 121 | POST | api/issues/{id}/rerun | App\Http\Controllers\Api\IssueSubResourceController@rerun | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 122 | POST | api/issues/{id}/subscribe | App\Http\Controllers\Api\IssueActionController@subscribe | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 123 | GET, HEAD | api/issues/{id}/subscribers | App\Http\Controllers\Api\IssueSubResourceController@subscribers | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 124 | GET, HEAD | api/issues/{id}/task-runs | App\Http\Controllers\Api\IssueSubResourceController@taskRuns | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 125 | POST | api/issues/{id}/tasks/{taskId}/cancel | App\Http\Controllers\Api\IssueSubResourceController@cancelTask | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 126 | GET, HEAD | api/issues/{id}/timeline | App\Http\Controllers\Api\IssueSubResourceController@timeline | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 127 | POST | api/issues/{id}/unsubscribe | App\Http\Controllers\Api\IssueActionController@unsubscribe | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 128 | GET, HEAD | api/issues/{id}/usage | App\Http\Controllers\Api\IssueSubResourceController@usage | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 129 | POST | api/issues/{issueId}/comments/trigger-preview | App\Http\Controllers\Api\CommentController@triggerPreview | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 130 | GET, HEAD | api/labels | App\Http\Controllers\Api\LabelController@index | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 131 | POST | api/labels | App\Http\Controllers\Api\LabelController@store | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 132 | GET, HEAD | api/labels/{id} | App\Http\Controllers\Api\LabelController@show | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 133 | PUT | api/labels/{id} | App\Http\Controllers\Api\LabelController@update | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 134 | DELETE | api/labels/{id} | App\Http\Controllers\Api\LabelController@destroy | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 135 | GET, HEAD | api/me | App\Http\Controllers\Api\MeController@show | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 136 | PATCH | api/me | App\Http\Controllers\Api\MeController@update | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 137 | PATCH | api/me/onboarding | App\Http\Controllers\Api\MeController@updateOnboarding | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 138 | POST | api/me/onboarding/cloud-waitlist | App\Http\Controllers\Api\MeController@cloudWaitlist | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 139 | POST | api/me/onboarding/complete | Closure | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 140 | GET, HEAD | api/notification-preferences | App\Http\Controllers\Api\NotificationPreferenceController@index | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 141 | PUT | api/notification-preferences | App\Http\Controllers\Api\NotificationPreferenceController@update | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 142 | GET, HEAD | api/pins | App\Http\Controllers\Api\PinController@index | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 143 | POST | api/pins | App\Http\Controllers\Api\PinController@store | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 144 | PUT | api/pins/reorder | App\Http\Controllers\Api\PinController@reorder | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 145 | DELETE | api/pins/{itemType}/{itemId} | App\Http\Controllers\Api\PinController@destroy | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 146 | GET, HEAD | api/projects | App\Http\Controllers\Api\ProjectApiController@index | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 147 | POST | api/projects | App\Http\Controllers\Api\ProjectApiController@store | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 148 | GET, HEAD | api/projects/search | App\Http\Controllers\Api\ProjectSearchController@search | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 149 | GET, HEAD | api/projects/{id} | App\Http\Controllers\Api\ProjectApiController@show | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 150 | PUT | api/projects/{id} | App\Http\Controllers\Api\ProjectApiController@update | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 151 | DELETE | api/projects/{id} | App\Http\Controllers\Api\ProjectApiController@destroy | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 152 | GET, HEAD | api/projects/{id}/resources | App\Http\Controllers\Api\ProjectResourceController@index | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 153 | POST | api/projects/{id}/resources | App\Http\Controllers\Api\ProjectResourceController@store | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 154 | PUT | api/projects/{id}/resources/{resourceId} | App\Http\Controllers\Api\ProjectResourceController@update | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 155 | DELETE | api/projects/{id}/resources/{resourceId} | App\Http\Controllers\Api\ProjectResourceController@destroy | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 156 | GET, HEAD | api/runtimes | App\Http\Controllers\Api\RuntimeController@index | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 157 | DELETE | api/runtimes/{runtimeId} | App\Http\Controllers\Api\RuntimeExtrasController@destroy | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 158 | PATCH | api/runtimes/{runtimeId} | App\Http\Controllers\Api\RuntimeExtrasController@update | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 159 | GET, HEAD | api/runtimes/{runtimeId}/activity | App\Http\Controllers\Api\RuntimeExtrasController@activity | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 160 | POST | api/runtimes/{runtimeId}/archive-agents-and-delete | App\Http\Controllers\Api\RuntimeExtrasController@archiveAndDelete | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 161 | POST | api/runtimes/{runtimeId}/local-skills | App\Http\Controllers\Api\RuntimeExtrasController@requestLocalSkills | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 162 | POST | api/runtimes/{runtimeId}/local-skills/import | App\Http\Controllers\Api\RuntimeExtrasController@importLocalSkill | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 163 | GET, HEAD | api/runtimes/{runtimeId}/local-skills/import/{requestId} | App\Http\Controllers\Api\RuntimeExtrasController@getLocalSkillImport | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 164 | GET, HEAD | api/runtimes/{runtimeId}/local-skills/{requestId} | App\Http\Controllers\Api\RuntimeExtrasController@getLocalSkills | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 165 | POST | api/runtimes/{runtimeId}/models | App\Http\Controllers\Api\RuntimeExtrasController@requestModels | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 166 | GET, HEAD | api/runtimes/{runtimeId}/models/{requestId} | App\Http\Controllers\Api\RuntimeExtrasController@getModels | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 167 | POST | api/runtimes/{runtimeId}/update | App\Http\Controllers\Api\RuntimeExtrasController@requestUpdate | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 168 | GET, HEAD | api/runtimes/{runtimeId}/update/{updateId} | App\Http\Controllers\Api\RuntimeExtrasController@getUpdate | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 169 | GET, HEAD | api/runtimes/{runtimeId}/usage | Closure | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 170 | GET, HEAD | api/runtimes/{runtimeId}/usage/by-agent | App\Http\Controllers\Api\RuntimeExtrasController@usageByAgent | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 171 | GET, HEAD | api/runtimes/{runtimeId}/usage/by-hour | App\Http\Controllers\Api\RuntimeExtrasController@usageByHour | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 172 | GET, HEAD | api/skills | App\Http\Controllers\Api\SkillController@index | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 173 | POST | api/skills | App\Http\Controllers\Api\SkillController@store | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 174 | POST | api/skills/import | App\Http\Controllers\Api\SkillController@import | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 175 | GET, HEAD | api/skills/{id} | App\Http\Controllers\Api\SkillController@show | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 176 | PUT | api/skills/{id} | App\Http\Controllers\Api\SkillController@update | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 177 | DELETE | api/skills/{id} | App\Http\Controllers\Api\SkillController@destroy | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 178 | GET, HEAD | api/squads | App\Http\Controllers\Api\SquadController@index | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 179 | POST | api/squads | App\Http\Controllers\Api\SquadExtrasController@store | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 180 | GET, HEAD | api/squads/{id} | App\Http\Controllers\Api\SquadExtrasController@show | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 181 | PUT | api/squads/{id} | App\Http\Controllers\Api\SquadExtrasController@update | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 182 | DELETE | api/squads/{id} | App\Http\Controllers\Api\SquadExtrasController@destroy | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 183 | GET, HEAD | api/squads/{squadId}/members | App\Http\Controllers\Api\SquadExtrasController@members | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 184 | POST | api/squads/{squadId}/members | App\Http\Controllers\Api\SquadExtrasController@addMember | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 185 | DELETE | api/squads/{squadId}/members | App\Http\Controllers\Api\SquadExtrasController@removeMember | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 186 | PATCH | api/squads/{squadId}/members/role | App\Http\Controllers\Api\SquadExtrasController@updateMemberRole | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 187 | GET, HEAD | api/squads/{squadId}/members/status | App\Http\Controllers\Api\SquadExtrasController@memberStatus | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 188 | POST | api/tasks/{taskId}/cancel | App\Http\Controllers\Api\ChatExtrasController@cancelTask | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 189 | GET, HEAD | api/tasks/{taskId}/messages | App\Http\Controllers\Api\ChatExtrasController@taskMessages | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 190 | POST | api/tokens | App\Http\Controllers\Api\PersonalAccessTokenController@store | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 191 | GET, HEAD | api/tokens | App\Http\Controllers\Api\PersonalAccessTokenController@index | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 192 | DELETE | api/tokens/{id} | App\Http\Controllers\Api\PersonalAccessTokenController@destroy | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 193 | POST | api/upload-file | App\Http\Controllers\Api\UploadController@upload | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 194 | POST | api/v1/customers/invoices | App\Http\Controllers\Api\CustomerApiController@invoices | api, App\Http\Middleware\ApiSecretKey |  |  |
| 195 | GET, HEAD | api/v1/customers/{identifier}/invoices | App\Http\Controllers\Api\CustomerApiController@invoices | api, App\Http\Middleware\ApiSecretKey |  |  |
| 196 | GET, HEAD | api/workspaces | App\Http\Controllers\Api\WorkspaceApiController@index | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 197 | PATCH | api/workspaces/{id} | App\Http\Controllers\Api\WorkspaceExtrasController@update | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 198 | DELETE | api/workspaces/{id} | App\Http\Controllers\Api\WorkspaceExtrasController@destroy | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 199 | GET, HEAD | api/workspaces/{id}/github/connect | App\Http\Controllers\Api\WorkspaceExtrasController@githubConnect | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 200 | GET, HEAD | api/workspaces/{id}/github/installations | App\Http\Controllers\Api\WorkspaceExtrasController@githubInstallations | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 201 | DELETE | api/workspaces/{id}/github/installations/{instId} | App\Http\Controllers\Api\WorkspaceExtrasController@deleteGithubInstallation | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 202 | GET, HEAD | api/workspaces/{id}/invitations | App\Http\Controllers\Api\WorkspaceExtrasController@invitations | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 203 | DELETE | api/workspaces/{id}/invitations/{invId} | App\Http\Controllers\Api\WorkspaceExtrasController@deleteInvitation | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 204 | POST | api/workspaces/{id}/leave | App\Http\Controllers\Api\WorkspaceExtrasController@leave | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 205 | POST | api/workspaces/{id}/members | App\Http\Controllers\Api\WorkspaceExtrasController@addMember | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 206 | PATCH | api/workspaces/{id}/members/{memberId} | App\Http\Controllers\Api\WorkspaceExtrasController@updateMember | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 207 | DELETE | api/workspaces/{id}/members/{memberId} | App\Http\Controllers\Api\WorkspaceExtrasController@removeMember | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 208 | GET, HEAD | api/workspaces/{id}/runtime-profiles | App\Http\Controllers\Api\WorkspaceExtrasController@runtimeProfiles | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 209 | POST | api/workspaces/{id}/runtime-profiles | App\Http\Controllers\Api\WorkspaceExtrasController@createRuntimeProfile | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 210 | PATCH | api/workspaces/{id}/runtime-profiles/{profileId} | App\Http\Controllers\Api\WorkspaceExtrasController@updateRuntimeProfile | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 211 | DELETE | api/workspaces/{id}/runtime-profiles/{profileId} | App\Http\Controllers\Api\WorkspaceExtrasController@deleteRuntimeProfile | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |
| 212 | GET, HEAD | api/workspaces/{workspaceId}/members | App\Http\Controllers\Api\WorkspaceMemberController@index | api, Illuminate\Session\Middleware\StartSession, App\Http\Middleware\ApiSessionOrPatAuth |  |  |

## 3. Endpoint contract catalog

### GET api/config
- Controller: Closure in [routes/api.php](routes/api.php#L38-L47)
- Validation: none
- Response shape:
  - cdn_domain: null (hardcoded)
  - cdn_signed: boolean false (hardcoded)
  - allow_signup: boolean false (hardcoded)
  - google_client_id: null (hardcoded)
  - posthog_key: null (hardcoded)
  - posthog_host: null (hardcoded)
  - analytics_environment: string production (hardcoded)
- Notes: static config stub.

### GET api/me
- Controller: Api\\MeController@show at [app/Http/Controllers/Api/MeController.php](app/Http/Controllers/Api/MeController.php#L14-L23)
- Validation: none
- Response shape:
  - id: integer (from users.id)
  - name: string (from users.name)
  - email: string (from users.email)

### GET api/workspaces
- Controller: Api\\WorkspaceApiController@index at [app/Http/Controllers/Api/WorkspaceApiController.php](app/Http/Controllers/Api/WorkspaceApiController.php#L11-L30)
- Validation: none
- Response shape: array of
  - id: string (teams.id cast)
  - name: string (teams.name)
  - slug: string (computed via Str::slug(name))
  - type: string local (hardcoded)

### GET api/workspaces/{workspaceId}/members
- Controller: Api\\WorkspaceMemberController@index at [app/Http/Controllers/Api/WorkspaceMemberController.php](app/Http/Controllers/Api/WorkspaceMemberController.php#L12-L45)
- Validation: none explicit
- Access behavior:
  - returns [] if team not found
  - returns [] if caller lacks projects.view_all and is not a team member
- Response shape: array of
  - id: string (users.id cast)
  - workspace_id: string (route param)
  - user_id: string with member- prefix (computed)
  - role: owner or member (computed from teams.lead_user_id)
  - created_at: nullable ISO8601 from pivot timestamp
  - name: string
  - email: string
  - avatar_url: null

### GET api/projects
- Controller: Api\\ProjectApiController@index at [app/Http/Controllers/Api/ProjectApiController.php](app/Http/Controllers/Api/ProjectApiController.php#L15-L31)
- Validation: none
- Auth gate: projects.view required, otherwise 403 forbidden
- Response shape:
  - projects: array of project summary payloads
  - total: integer
- Project summary fields from [app/Http/Controllers/Api/ProjectApiController.php](app/Http/Controllers/Api/ProjectApiController.php#L191-L210)
  - id, workspace_id, title, description, icon(null), status(mapped), priority(hardcoded medium), lead_type(null), lead_id(null), created_at, updated_at, issue_count(computed), done_count(computed), resource_count(computed)

### GET api/projects/{id}
- Controller: Api\\ProjectApiController@show at [app/Http/Controllers/Api/ProjectApiController.php](app/Http/Controllers/Api/ProjectApiController.php#L94-L110)
- Validation: none
- Response shape: project summary plus resources[] from [app/Http/Controllers/Api/ProjectApiController.php](app/Http/Controllers/Api/ProjectApiController.php#L213-L234)

### GET api/projects/search
- Controller: Api\\ProjectSearchController@search at [app/Http/Controllers/Api/ProjectSearchController.php](app/Http/Controllers/Api/ProjectSearchController.php#L11-L80)
- Query params:
  - q: string, required logically (empty returns empty result)
  - limit: integer min 1 max 100 default 20
  - offset: integer min 0 default 0
  - include_closed: boolean default false
- Response shape:
  - projects: project summary array (same shape as /projects)
  - total: integer

### Project resources CRUD
- Source: [app/Http/Controllers/Api/ProjectResourceController.php](app/Http/Controllers/Api/ProjectResourceController.php#L14-L252)

#### GET api/projects/{id}/resources
- Validation: none
- Response:
  - resources: array of resource payloads
  - total: integer

#### POST api/projects/{id}/resources
- Validation:
  - resource_type required in github_repo|local_directory
  - resource_ref required array
  - resource_ref.url nullable string max 2048
  - resource_ref.local_path nullable string
  - resource_ref.daemon_id nullable uuid
  - label nullable string
  - position nullable integer
- Response: resource payload, status 201

#### PUT api/projects/{id}/resources/{resourceId}
- Validation:
  - label/position/resource_ref optional
  - daemon_id immutable for local_directory
- Response: updated resource payload

#### DELETE api/projects/{id}/resources/{resourceId}
- Response: {status: ok}

### GET api/issues (list)
- Controller: Api\\IssueListController@index at [app/Http/Controllers/Api/IssueListController.php](app/Http/Controllers/Api/IssueListController.php#L13-L50)
- Validation:
  - limit nullable int 1..200
  - status nullable string
  - sort nullable string
- Response shape:
  - issues: array
  - total: integer
- Issue payload source: [app/Http/Controllers/Api/IssueListController.php](app/Http/Controllers/Api/IssueListController.php#L58-L93)
  - assignee_type: agent|member|null
  - assignee_id: agent-{uuid}|member-{id}|null
  - creator_id: hardcoded user-1
  - description: tasks.description string (not buildPrompt)
  - status mapping: open->todo, in-progress->in_progress, in-review->in_review

### GET api/issues/{id}
- Controller: Api\\IssueController@show at [app/Http/Controllers/Api/IssueController.php](app/Http/Controllers/Api/IssueController.php#L16-L25)
- Resolution formats: uuid-like string, task-{id}, numeric id via [app/Http/Controllers/Api/IssueController.php](app/Http/Controllers/Api/IssueController.php#L132-L152)
- Response payload source: [app/Http/Controllers/Api/IssueController.php](app/Http/Controllers/Api/IssueController.php#L179-L217)
  - Same core keys as list, but status mapping differs: open->backlog (not todo)

### GET and POST api/issues/{id}/comments
- Controller methods:
  - comments: [app/Http/Controllers/Api/IssueController.php](app/Http/Controllers/Api/IssueController.php#L81-L98)
  - storeComment: [app/Http/Controllers/Api/IssueController.php](app/Http/Controllers/Api/IssueController.php#L100-L119)
- POST validation:
  - content required string
- Response shape comment payload from [app/Http/Controllers/Api/IssueController.php](app/Http/Controllers/Api/IssueController.php#L154-L176)
  - id, issue_id, author_type(user), author_id, content, type(comment), parent_id(null), created_at, updated_at, resolved_at(null), resolved_by_type(null), resolved_by_id(null), reactions[], attachments[]

### GET api/issues/{id}/timeline
- Controller: Api\\IssueSubResourceController@timeline at [app/Http/Controllers/Api/IssueSubResourceController.php](app/Http/Controllers/Api/IssueSubResourceController.php#L12-L68)
- Response: merged sorted array of
  - agent_message events from agent_task_messages via queueEntries.messages
  - comment events from task_comments

### GET api/issues/{id}/usage
- Controller: Api\\IssueSubResourceController@usage at [app/Http/Controllers/Api/IssueSubResourceController.php](app/Http/Controllers/Api/IssueSubResourceController.php#L89-L127)
- Response:
  - total_cost float
  - total_tokens integer
  - runs[] with task_id(queue id), cost, input_tokens, output_tokens, model, created_at

### GET api/issues/{id}/attachments
- Controller: Api\\IssueSubResourceController@attachments at [app/Http/Controllers/Api/IssueSubResourceController.php](app/Http/Controllers/Api/IssueSubResourceController.php#L129-L152)
- Source:
  - media in collections attachments and images on Task
- Response array item:
  - id(uuid), filename, url, download_url, content_type, size_bytes, created_at

### GET api/issues/{id}/task-runs
- Controller: Api\\IssueSubResourceController@taskRuns at [app/Http/Controllers/Api/IssueSubResourceController.php](app/Http/Controllers/Api/IssueSubResourceController.php#L164-L196)
- Response: queue entries ordered desc with fields
  - id, agent_id, runtime_id, issue_id, workspace_id, status, priority(0 hardcoded), started_at, completed_at, created_at, kind(direct hardcoded), failure_reason(from error_message), attempt(1 hardcoded), max_attempts(1 hardcoded)

### GET api/issues/{id}/active-task
- Controller: Api\\IssueSubResourceController@activeTask at [app/Http/Controllers/Api/IssueSubResourceController.php](app/Http/Controllers/Api/IssueSubResourceController.php#L203-L228)
- Response:
  - tasks: array of queue entries where status in queued|dispatched|running

### GET api/issues/search
- Controller: Api\\IssueActionController@search at [app/Http/Controllers/Api/IssueActionController.php](app/Http/Controllers/Api/IssueActionController.php#L16-L59)
- Query params:
  - q required logically
  - limit default 20 max 200
  - offset default 0
  - include_closed boolean
- Response:
  - issues[] and total
  - issue payload uses AgentTaskQueue::buildPrompt for description (different from IssueController/IssueList)

### GET api/issues/grouped
- Controller: Api\\IssueActionController@grouped at [app/Http/Controllers/Api/IssueActionController.php](app/Http/Controllers/Api/IssueActionController.php#L61-L114)
- Query params:
  - group_by in status|priority|project_id|assignee_id default status
- Response:
  - groups[] each with key, issues[], total
  - total overall count

### GET api/assignee-frequency
- Controller: Api\\IssueActionController@assigneeFrequency at [app/Http/Controllers/Api/IssueActionController.php](app/Http/Controllers/Api/IssueActionController.php#L116-L145)
- Response array items:
  - assignee_type user
  - assignee_id string
  - count integer

### Agents endpoints (api/agents*)
- Controller primary CRUD: [app/Http/Controllers/Api/AgentController.php](app/Http/Controllers/Api/AgentController.php#L14-L176)
- Store validation:
  - name, runtime_id(uuid), team_id(integer exists), optional visibility, model, custom_env, custom_args, max_concurrent_tasks
- Index response:
  - returns raw Agent model collection with loaded runtime relation
- Detail/update/delete:
  - ownership checks, archived behavior

### Runtimes endpoints
- GET api/runtimes controller: [app/Http/Controllers/Api/RuntimeController.php](app/Http/Controllers/Api/RuntimeController.php#L12-L56)
- Validation:
  - workspace_id nullable string numeric filter
- Response item keys:
  - id, workspace_id, daemon_id, name, runtime_mode(local hardcoded), provider, launch_header(provider), status, device_info, metadata object, owner_id, visibility(private hardcoded), profile_id null, last_seen_at, created_at, updated_at

### GET api/agent-task-snapshot
- Controller: [app/Http/Controllers/Api/AgentTaskSnapshotController.php](app/Http/Controllers/Api/AgentTaskSnapshotController.php#L12-L54)
- Response: active queue entries with normalized fields including issue_id task-{id}

### Chat endpoints
- Base chat controller:
  - pendingTasks returns {tasks: []} at [app/Http/Controllers/Api/ChatController.php](app/Http/Controllers/Api/ChatController.php#L10-L13)
  - sessions returns [] at [app/Http/Controllers/Api/ChatController.php](app/Http/Controllers/Api/ChatController.php#L15-L18)
- Extras:
  - many stubs status ok or [] at [app/Http/Controllers/Api/ChatExtrasController.php](app/Http/Controllers/Api/ChatExtrasController.php#L10-L19)
  - taskMessages real DB read from agent_task_messages at [app/Http/Controllers/Api/ChatExtrasController.php](app/Http/Controllers/Api/ChatExtrasController.php#L21-L30)

### Daemon endpoints (all api/daemon/*)
- Route group and middleware: [routes/api.php](routes/api.php#L292-L307), DaemonTokenMiddleware in [app/Http/Middleware/DaemonTokenMiddleware.php](app/Http/Middleware/DaemonTokenMiddleware.php#L17-L89)
- Implemented controller methods in [app/Http/Controllers/Api/DaemonController.php](app/Http/Controllers/Api/DaemonController.php#L22-L571)
  - register, deregister, heartbeat, workspaceRepos, claimTask, startTask, outputTask, messages, usage, completeTask, failTask, cancelTask
- Placeholder closures still in daemon group:
  - workspaces/{workspaceId}/runtime-profiles
  - tasks/{taskId}/session

### Auth, OTP, token routes
- OTP:
  - POST api/auth/send-code and verify-code at [routes/api.php](routes/api.php#L35-L36)
  - logic in [app/Http/Controllers/Api/AuthCodeController.php](app/Http/Controllers/Api/AuthCodeController.php#L16-L112)
- PAT:
  - POST/GET/DELETE api/tokens at [routes/api.php](routes/api.php#L60-L62)
  - logic in [app/Http/Controllers/Api/PersonalAccessTokenController.php](app/Http/Controllers/Api/PersonalAccessTokenController.php#L14-L77)
- API auth semantics:
  - session OR Bearer mul_ PAT OR mat_ task token for authenticated api group via [app/Http/Middleware/ApiSessionOrPatAuth.php](app/Http/Middleware/ApiSessionOrPatAuth.php#L17-L94)

## 4. DB schema catalog

Source of truth: live database via artisan db:table on each relevant table.

| Table | Column | Type | Nullable | Default | Index/FK | Notes |
|---|---|---|---|---|---|---|
| users | id | int8, autoincrement | no | nextval('users_id_seq'::regclass) | users_pkey |  |
| users | name | varchar | no | character |  |  |
| users | email | varchar | no | character | users_email_unique |  |
| users | email_verified_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| users | password | varchar | no | character |  |  |
| users | remember_token | varchar, nullable | yes | character |  |  |
| users | created_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| users | updated_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| users | role | varchar, nullable | yes | character |  |  |
| users | color | varchar | no | '#D97757'::character varying character |  |  |
| users | initials | varchar, nullable | yes | character |  |  |
| users | role_id | int8, nullable | yes |  | users_role_id_foreign->id on roles .. no action / set null |  |
| users | notification_preferences | json | no | '{"email_enabled":true}'::json |  |  |
| teams | id | int8, autoincrement | no | nextval('teams_id_seq'::regclass) | teams_pkey |  |
| teams | name | varchar | no | character |  |  |
| teams | description | text, nullable | yes |  |  |  |
| teams | lead_user_id | int8, nullable | yes |  | teams_lead_user_id_foreign->id on users  no action / set null |  |
| teams | created_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| teams | updated_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| teams | deleted_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| team_members | id | int8, autoincrement | no | nextval('team_members_id_seq'::regclass) | team_members_pkey |  |
| team_members | team_id | int8 | no |  | team_members_team_id_user_id_unique; team_members_team_id_foreign->id on teams  no action / cascade |  |
| team_members | user_id | int8 | no |  | team_members_team_id_user_id_unique; team_members_user_id_foreign->id on users  no action / cascade |  |
| team_members | created_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| team_members | updated_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| projects | id | int8, autoincrement | no | nextval('projects_id_seq'::regclass) | projects_pkey |  |
| projects | customer_id | int8, nullable | yes |  | projects_customer_id_foreign->id on customers  no action / set null |  |
| projects | name | varchar | no | character |  |  |
| projects | description | text, nullable | yes |  |  |  |
| projects | github_repo | varchar, nullable | yes | character |  |  |
| projects | stack | varchar, nullable | yes | character |  |  |
| projects | status | varchar | no | 'active'::character varying character |  |  |
| projects | created_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| projects | updated_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| projects | start_date | date, nullable | yes |  |  |  |
| projects | deadline | date, nullable | yes |  |  |  |
| projects | budget | numeric, nullable | yes |  |  |  |
| projects | health | varchar | no | 'on-track'::character varying character |  |  |
| projects | color | varchar | no | '#4a90d9'::character varying character |  |  |
| projects | guide | text, nullable | yes |  |  |  |
| projects | slack_channel | varchar, nullable | yes | character |  |  |
| projects | website | varchar, nullable | yes | character |  |  |
| projects | repos | json, nullable | yes |  |  |  |
| tasks | id | int8, autoincrement | no | nextval('tasks_id_seq'::regclass) | tasks_pkey |  |
| tasks | project_id | int8 | no |  | tasks_project_id_foreign->id on projects  no action / cascade |  |
| tasks | title | varchar | no | character |  |  |
| tasks | description | text, nullable | yes |  |  |  |
| tasks | type | varchar | no | 'feature'::character varying character |  |  |
| tasks | priority | varchar | no | 'medium'::character varying character |  |  |
| tasks | status | varchar | no | 'backlog'::character varying character |  |  |
| tasks | source | varchar | no | 'manual'::character varying character |  |  |
| tasks | original_input | text, nullable | yes |  |  |  |
| tasks | created_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| tasks | updated_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| tasks | assigned_to | int8, nullable | yes |  | tasks_assigned_to_foreign->id on users  no action / set null |  |
| tasks | due_date | date, nullable | yes |  |  |  |
| tasks | estimated_hours | int2, nullable | yes |  |  |  |
| tasks | labels | json, nullable | yes |  |  |  |
| tasks | completed_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| tasks | sprint_id | int8, nullable | yes |  | tasks_milestone_id_foreign->id on sprints  no action / set null |  |
| tasks | weight | int2, nullable | yes |  |  |  |
| tasks | guide | text, nullable | yes |  |  |  |
| tasks | overdue_notified_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| tasks | agent_id | uuid, nullable | yes |  | tasks_agent_id_foreign->id on agents  no action / set null |  |
| task_comments | id | int8, autoincrement | no | nextval('task_comments_id_seq'::regclass) | task_comments_pkey |  |
| task_comments | task_id | int8 | no |  | task_comments_task_id_foreign->id on tasks  no action / cascade |  |
| task_comments | user_id | int8 | no |  | task_comments_user_id_foreign->id on users  no action / cascade |  |
| task_comments | body | text | no |  |  |  |
| task_comments | created_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| task_comments | updated_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| task_checklists | id | int8, autoincrement | no | nextval('task_checklists_id_seq'::regclass) | task_checklists_pkey |  |
| task_checklists | task_id | int8 | no |  | task_checklists_task_id_foreign->id on tasks  no action / cascade |  |
| task_checklists | label | varchar | no | character |  |  |
| task_checklists | is_checked | bool | no | false |  |  |
| task_checklists | sort_order | int2 | no | '0'::smallint |  |  |
| task_checklists | created_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| task_checklists | updated_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| media | id | int8, autoincrement | no | nextval('media_id_seq'::regclass) | media_pkey |  |
| media | model_type | varchar | no | character | media_model_type_model_id_index |  |
| media | model_id | int8 | no |  | media_model_type_model_id_index |  |
| media | uuid | uuid, nullable | yes |  | media_uuid_unique |  |
| media | collection_name | varchar | no | character |  |  |
| media | name | varchar | no | character |  |  |
| media | file_name | varchar | no | character |  |  |
| media | mime_type | varchar, nullable | yes | character |  |  |
| media | disk | varchar | no | character |  |  |
| media | conversions_disk | varchar, nullable | yes | character |  |  |
| media | size | int8 | no |  |  |  |
| media | manipulations | json | no |  |  |  |
| media | custom_properties | json | no |  |  |  |
| media | generated_conversions | json | no |  |  |  |
| media | responsive_images | json | no |  |  |  |
| media | order_column | int4, nullable | yes |  | media_order_column_index |  |
| media | created_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| media | updated_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| personal_access_tokens | id | uuid | no |  | personal_access_tokens_pkey |  |
| personal_access_tokens | user_id | int8 | no |  | personal_access_tokens_user_id_revoked_index; personal_access_tokens_user_id_foreign->id on users  no action / cascade |  |
| personal_access_tokens | name | varchar | no | character |  |  |
| personal_access_tokens | token_hash | varchar | no | character | personal_access_tokens_token_hash_unique |  |
| personal_access_tokens | token_prefix | varchar | no | character |  |  |
| personal_access_tokens | expires_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| personal_access_tokens | last_used_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| personal_access_tokens | revoked | bool | no | false | personal_access_tokens_user_id_revoked_index |  |
| personal_access_tokens | created_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| personal_access_tokens | updated_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| task_tokens | id | int8, autoincrement | no | nextval('task_tokens_id_seq'::regclass) | task_tokens_pkey |  |
| task_tokens | token_hash | varchar | no | character | task_tokens_token_hash_unique |  |
| task_tokens | task_id | uuid | no |  | task_tokens_task_id_foreign->id on agent_task_queue  no action / cascade |  |
| task_tokens | agent_id | uuid | no |  | task_tokens_agent_id_foreign->id on agents  no action / cascade |  |
| task_tokens | team_id | int8, nullable | yes |  | task_tokens_team_id_foreign->id on teams  no action / set null |  |
| task_tokens | user_id | int8, nullable | yes |  | task_tokens_user_id_foreign->id on users  no action / set null |  |
| task_tokens | expires_at | timestamp | no | timestamp(0) without time | task_tokens_expires_at_index |  |
| task_tokens | created_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| task_tokens | updated_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| agent_runtimes | id | uuid | no |  | agent_runtimes_pkey |  |
| agent_runtimes | user_id | int8, nullable | yes |  | agent_runtimes_user_id_foreign->id on users  no action / set null |  |
| agent_runtimes | daemon_id | varchar | no | character | agent_runtimes_team_id_daemon_id_provider_unique |  |
| agent_runtimes | name | varchar | no | character |  |  |
| agent_runtimes | provider | varchar | no | character | agent_runtimes_team_id_daemon_id_provider_unique |  |
| agent_runtimes | status | varchar | no | 'offline'::character varying character |  |  |
| agent_runtimes | device_info | varchar, nullable | yes | character |  |  |
| agent_runtimes | cli_version | varchar, nullable | yes | character |  |  |
| agent_runtimes | launched_by | varchar, nullable | yes | character |  |  |
| agent_runtimes | last_seen_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| agent_runtimes | metadata | json, nullable | yes |  |  |  |
| agent_runtimes | created_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| agent_runtimes | updated_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| agent_runtimes | team_id | int8, nullable | yes |  | agent_runtimes_team_id_daemon_id_provider_unique; agent_runtimes_team_id_foreign->id on teams  no action / set null |  |
| agents | id | uuid | no |  | agents_pkey |  |
| agents | team_id | int8 | no |  | agents_team_id_owner_id_index; agents_team_id_visibility_index; agents_team_id_foreign->id on teams .. no action / cascade |  |
| agents | runtime_id | uuid, nullable | yes |  | agents_runtime_id_foreign->id on agent_runtimes  no action / set null |  |
| agents | owner_id | int8 | no |  | agents_team_id_owner_id_index; agents_owner_id_foreign->id on users  no action / cascade |  |
| agents | name | varchar | no | character |  |  |
| agents | description | text, nullable | yes |  |  |  |
| agents | instructions | text, nullable | yes |  |  |  |
| agents | visibility | varchar | no | 'private'::character varying character | agents_team_id_visibility_index |  |
| agents | status | varchar | no | 'idle'::character varying character |  |  |
| agents | max_concurrent_tasks | int4 | no | 6 |  |  |
| agents | model | varchar, nullable | yes | character |  |  |
| agents | custom_env | json, nullable | yes |  |  |  |
| agents | custom_args | json, nullable | yes |  |  |  |
| agents | archived_at | timestamp, nullable | yes | timestamp(0) without time | agents_archived_at_index |  |
| agents | created_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| agents | updated_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| agent_task_queue | id | uuid | no |  | agent_task_queue_pkey |  |
| agent_task_queue | task_id | int8 | no |  | agent_task_queue_task_id_foreign->id on tasks  no action / cascade |  |
| agent_task_queue | runtime_id | uuid, nullable | yes |  | agent_task_queue_runtime_id_foreign->id on agent_runtimes  no action / set null |  |
| agent_task_queue | status | varchar | no | 'queued'::character varying character |  |  |
| agent_task_queue | prompt | text, nullable | yes |  |  |  |
| agent_task_queue | output | text, nullable | yes |  |  |  |
| agent_task_queue | error_message | text, nullable | yes |  |  |  |
| agent_task_queue | pr_url | varchar, nullable | yes | character |  |  |
| agent_task_queue | claimed_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| agent_task_queue | started_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| agent_task_queue | completed_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| agent_task_queue | created_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| agent_task_queue | updated_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| agent_task_queue | team_id | int8, nullable | yes |  | agent_task_queue_team_id_foreign->id on teams  no action / set null |  |
| agent_task_queue | agent_id | uuid, nullable | yes |  | agent_task_queue_agent_id_foreign->id on agents  no action / set null |  |
| agent_task_messages | id | int8, autoincrement  nextval('agent_task_messages_id_seq'::regclass) bigint | no | autoincrement | agent_task_messages_pkey |  |
| agent_task_messages | task_queue_id | uuid | no |  | agent_task_messages_task_queue_id_seq_index; agent_task_messages_task_queue_id_foreign->id on agent_task_queue  no action / cascade |  |
| agent_task_messages | seq | int4 | no |  | agent_task_messages_task_queue_id_seq_index |  |
| agent_task_messages | type | varchar | no | character |  |  |
| agent_task_messages | tool | varchar, nullable | yes | character |  |  |
| agent_task_messages | content | text, nullable | yes |  |  |  |
| agent_task_messages | input | json, nullable | yes |  |  |  |
| agent_task_messages | output | text, nullable | yes |  |  |  |
| agent_task_messages | created_at | timestamp | no | CURRENT_TIMESTAMP timestamp(0) without time |  |  |
| agent_task_usage | id | int8, autoincrement . nextval('agent_task_usage_id_seq'::regclass) bigint | no | autoincrement | agent_task_usage_pkey |  |
| agent_task_usage | task_queue_id | uuid | no |  | agent_task_usage_task_queue_id_index; agent_task_usage_task_queue_id_foreign->id on agent_task_queue  no action / cascade |  |
| agent_task_usage | input_tokens | int4 | no | 0 |  |  |
| agent_task_usage | output_tokens | int4 | no | 0 |  |  |
| agent_task_usage | cost | numeric | no | '0'::numeric |  |  |
| agent_task_usage | model | varchar, nullable | yes | character |  |  |
| agent_task_usage | created_at | timestamp | no | timestamp(0) without time |  |  |
| project_resources | id | uuid | no |  | project_resources_pkey |  |
| project_resources | project_id | int8 | no |  | project_resources_project_id_resource_type_index; project_resources_project_id_foreign->id on projects  no action / cascade |  |
| project_resources | resource_type | varchar | no | character | project_resources_project_id_resource_type_index |  |
| project_resources | resource_ref | json | no |  |  |  |
| project_resources | label | varchar, nullable | yes | character |  |  |
| project_resources | position | int4 | no | 0 |  |  |
| project_resources | created_by | int8, nullable | yes |  | project_resources_created_by_foreign->id on users  no action / set null |  |
| project_resources | created_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| project_resources | updated_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| verification_codes | id | int8, autoincrement  nextval('verification_codes_id_seq'::regclass) bigint | no | autoincrement | verification_codes_pkey |  |
| verification_codes | email | varchar | no | character | verification_codes_email_used_expires_at_index |  |
| verification_codes | code | varchar | no | character |  |  |
| verification_codes | expires_at | timestamp | no | timestamp(0) without time | verification_codes_email_used_expires_at_index |  |
| verification_codes | used | bool | no | false | verification_codes_email_used_expires_at_index |  |
| verification_codes | attempts | int4 | no | 0 |  |  |
| verification_codes | created_at | timestamp, nullable | yes | timestamp(0) without time |  |  |
| verification_codes | updated_at | timestamp, nullable | yes | timestamp(0) without time |  |  |

## 5. Model relationship map

### User
- File: [app/Models/User.php](app/Models/User.php)
- Fillable: name, email, password, role_id, color, initials, notification_preferences
- Hidden: password, remember_token
- Casts: email_verified_at datetime, password hashed, notification_preferences array
- Relationships: tasks, roleModel, teams, twoFactor, employeeProfile, devices, personalAccessTokens, agents, loginHistory
- API-relevant accessors/logic: hasPermission, requiresTwoFactor, wantsEmailNotification

### Team
- File: [app/Models/Team.php](app/Models/Team.php)
- Fillable: name, description, lead_user_id
- SoftDeletes enabled
- Relationships: lead, members (pivot team_members with timestamps), projects

### Project
- File: [app/Models/Project.php](app/Models/Project.php)
- Fillable includes repos legacy json and status/health fields
- Casts: start_date date, deadline date, repos array
- Relationships: customer, tasks, conversations, teams, sprints, backlogItems, projectDocuments, resources

### Task
- File: [app/Models/Task.php](app/Models/Task.php)
- Fillable includes assigned_to and agent_id plus task metadata fields
- Casts: due_date, completed_at, overdue_notified_at, labels array, weight int
- Relationships: project, sprint, assignee, agent, queueEntries, latestQueue, comments, checklists
- Media collections: attachments and images

### TaskComment
- File: [app/Models/TaskComment.php](app/Models/TaskComment.php)
- Fillable: task_id, user_id, body
- Relationships: task, author
- Media collection: attachment (singleFile)

### AgentRuntime
- File: [app/Models/AgentRuntime.php](app/Models/AgentRuntime.php)
- Fillable: id, user_id, team_id, daemon_id, name, provider, status, device_info, cli_version, launched_by, last_seen_at, metadata
- Casts: last_seen_at datetime, metadata array
- Relationships: user, team, agents

### Agent
- File: [app/Models/Agent.php](app/Models/Agent.php)
- Fillable includes runtime_id, owner_id, visibility, status, model, custom_env/custom_args
- Casts: custom_env array, custom_args array, archived_at datetime
- Relationships: team, runtime, owner, tasks

### AgentTaskQueue
- File: [app/Models/AgentTaskQueue.php](app/Models/AgentTaskQueue.php)
- Fillable includes task_id, team_id, runtime_id, agent_id, status, prompt, output, error_message, pr_url timestamps
- Casts: claimed_at, started_at, completed_at datetimes
- Relationships: task, runtime, agent, team, messages, usage
- API helper: buildPrompt(Task)

### AgentTaskMessage
- File: [app/Models/AgentTaskMessage.php](app/Models/AgentTaskMessage.php)
- Timestamps: false
- Fillable: task_queue_id, seq, type, tool, content, input, output, created_at
- Casts: seq int, input array, created_at datetime
- Relationship: taskQueue

### AgentTaskUsage
- File: [app/Models/AgentTaskUsage.php](app/Models/AgentTaskUsage.php)
- Timestamps: false
- Fillable: task_queue_id, input_tokens, output_tokens, cost, model, created_at
- Casts: input_tokens int, output_tokens int, cost decimal:6, created_at datetime
- Relationship: queue

### ProjectResource
- File: [app/Models/ProjectResource.php](app/Models/ProjectResource.php)
- Fillable: id, project_id, resource_type, resource_ref, label, position, created_by
- Casts: resource_ref array, position int
- Relationships: project, creator
- Accessors/helpers: isGithubRepo, isLocalDirectory, getLocalPath, getDaemonId, getUrl

### PersonalAccessToken
- File: [app/Models/PersonalAccessToken.php](app/Models/PersonalAccessToken.php)
- Fillable: id, user_id, name, token_hash, token_prefix, expires_at, last_used_at, revoked
- Hidden: token_hash
- Casts: expires_at, last_used_at datetimes, revoked bool
- Relationship: user
- Scope: active

### TaskToken
- File: [app/Models/TaskToken.php](app/Models/TaskToken.php)
- Fillable: token_hash, task_id, agent_id, team_id, user_id, expires_at
- Casts: expires_at datetime
- Relationships: task, agent, user

## 6. Stub endpoint list

Representative stubs detected from controllers and route closures:

| Endpoint | Current Stub Response | Recommended real source |
|---|---|---|
| GET api/chat/pending-tasks | {tasks: []} | agent_task_queue filtered by session/workspace and active states |
| GET api/chat/sessions | [] | chat sessions table or agent_conversations |
| POST api/chat/sessions | {status: ok} | create row in session/conversation table |
| GET api/chat/sessions/{id} | {status: ok} | session row with participants/metadata |
| PATCH api/chat/sessions/{id} | {status: ok} | update session fields |
| DELETE api/chat/sessions/{id} | {status: ok} | soft-delete session |
| GET api/chat/sessions/{sessionId}/messages | [] | message table scoped to session |
| GET api/issues/{id}/labels | {labels: []} | labels + pivot table |
| GET api/issues/{id}/pull-requests | {pull_requests: []} | PR linkage table/integration cache |
| GET api/issues/{id}/children | {issues: []} | task parent-child relation table |
| POST api/issues/{id}/tasks/{taskId}/cancel | {status: ok} | agent_task_queue status transition + audit comment |
| POST api/issues/{id}/rerun | {status: ok} | enqueue new queue entry in agent_task_queue |
| POST api/issues/quick-create | {task_id: ''} | tasks insert returning identifier |
| POST api/issues/batch-update | {updated: 0} | tasks bulk update |
| POST api/issues/batch-delete | {deleted: 0} | tasks bulk delete/soft delete |
| GET api/issues/children | {issues: []} | bulk parent-child lookup on tasks |
| POST api/issues/{id}/subscribe | {status: ok} | issue_subscribers pivot table |
| POST api/issues/{id}/unsubscribe | {status: ok} | issue_subscribers pivot table delete |
| GET api/invitations | [] | invitations table |
| GET api/agent-activity-30d | [] | derived from agent_task_queue/usage by day |
| GET api/agent-run-counts | [] | grouped counts from agent_task_queue |
| GET api/runtimes/{runtimeId}/usage | {days:[], total:0} | aggregate from agent_task_usage |
| GET api/daemon/workspaces/{workspaceId}/runtime-profiles | {profiles: []} | runtime profiles table |
| POST api/daemon/tasks/{taskId}/session | {status: ok} | queue session metadata table |

Primary source files:
- [app/Http/Controllers/Api/ChatController.php](app/Http/Controllers/Api/ChatController.php#L10-L18)
- [app/Http/Controllers/Api/ChatExtrasController.php](app/Http/Controllers/Api/ChatExtrasController.php#L10-L19)
- [app/Http/Controllers/Api/IssueSubResourceController.php](app/Http/Controllers/Api/IssueSubResourceController.php#L154-L236)
- [app/Http/Controllers/Api/IssueActionController.php](app/Http/Controllers/Api/IssueActionController.php#L194-L219)
- [routes/api.php](routes/api.php#L269-L286)
- [routes/api.php](routes/api.php#L296-L300)

## 7. Contract normalization risks

### P0
- Issue status contract mismatch across endpoints.
  - Issue list maps open -> todo, while issue detail maps open -> backlog.
  - Sources: [app/Http/Controllers/Api/IssueListController.php](app/Http/Controllers/Api/IssueListController.php#L100-L106), [app/Http/Controllers/Api/IssueController.php](app/Http/Controllers/Api/IssueController.php#L249-L255)
- Assignee ID format mismatch between endpoints and update expectations.
  - List/detail emit member-{id}/agent-{id}; update accepts numeric assignee_id for member/user.
  - Sources: [app/Http/Controllers/Api/IssueController.php](app/Http/Controllers/Api/IssueController.php#L58-L67), [app/Http/Controllers/Api/IssueController.php](app/Http/Controllers/Api/IssueController.php#L184-L201)

### P1
- Description field source mismatch for issue payloads.
  - IssueAction uses buildPrompt; IssueList/IssueController use raw tasks.description.
  - Sources: [app/Http/Controllers/Api/IssueActionController.php](app/Http/Controllers/Api/IssueActionController.php#L235), [app/Http/Controllers/Api/IssueListController.php](app/Http/Controllers/Api/IssueListController.php#L72), [app/Http/Controllers/Api/IssueController.php](app/Http/Controllers/Api/IssueController.php#L193)
- creator_id inconsistent and hardcoded in list/detail (user-1) but derived/null in action payload.
  - Sources: [app/Http/Controllers/Api/IssueListController.php](app/Http/Controllers/Api/IssueListController.php#L79-L80), [app/Http/Controllers/Api/IssueController.php](app/Http/Controllers/Api/IssueController.php#L200-L201), [app/Http/Controllers/Api/IssueActionController.php](app/Http/Controllers/Api/IssueActionController.php#L240-L241)
- Daemon ownership check couples queue mutation to task.assigned_to user.
  - MAT token accepted globally by middleware, but ownedQueue still requires assigned_to equals authenticated user.
  - Source: [app/Http/Controllers/Api/DaemonController.php](app/Http/Controllers/Api/DaemonController.php#L523-L532)
- Workspace members endpoint returns user_id as member-{id} while id is plain string id.
  - Source: [app/Http/Controllers/Api/WorkspaceMemberController.php](app/Http/Controllers/Api/WorkspaceMemberController.php#L32-L37)

### P2
- Large number of placeholder endpoints may satisfy shape discovery but not behavior parity.
- Some responses are raw Eloquent serialization (agents) while other endpoints use normalized explicit payloads.
  - Source: [app/Http/Controllers/Api/AgentController.php](app/Http/Controllers/Api/AgentController.php#L63-L73)

Route ordering status:
- No active runtime shadows detected for api/* static-vs-dynamic on same method and segment count.
- Guarded critical orders are currently correct in [routes/api.php](routes/api.php#L82-L83), [routes/api.php](routes/api.php#L135-L140), [routes/api.php](routes/api.php#L167-L172).

Auth and CSRF notes:
- Authenticated api group uses StartSession plus custom Bearer auth middleware (PAT/task token), not Sanctum.
- Daemon routes use dedicated DaemonTokenMiddleware and accept mul_ and mat_ tokens.
- API routes are in api middleware group, so web CSRF middleware is not applied by default.

## 8. Sample payload pack

Source-derived examples from current controller code contracts.

### GET api/me
{
  "id": 1,
  "name": "Alice",
  "email": "alice@example.com"
}

### GET api/workspaces
[
  {
    "id": "1",
    "name": "Core Team",
    "slug": "core-team",
    "type": "local"
  }
]

### GET api/projects
{
  "projects": [
    {
      "id": "3",
      "workspace_id": "1",
      "title": "Command Center",
      "description": "...",
      "icon": null,
      "status": "in_progress",
      "priority": "medium",
      "lead_type": null,
      "lead_id": null,
      "created_at": "2026-06-20T10:00:00+00:00",
      "updated_at": "2026-06-20T10:30:00+00:00",
      "issue_count": 12,
      "done_count": 3,
      "resource_count": 2
    }
  ],
  "total": 1
}

### GET api/issues?status=todo&limit=50
{
  "issues": [
    {
      "id": "38",
      "workspace_id": "1",
      "number": 38,
      "identifier": "task-38",
      "title": "Fix status mapping",
      "description": "Raw task description",
      "status": "todo",
      "priority": "medium",
      "assignee_type": "member",
      "assignee_id": "member-1",
      "creator_type": "user",
      "creator_id": "user-1",
      "parent_issue_id": null,
      "project_id": "3",
      "position": 0,
      "start_date": null,
      "due_date": null,
      "created_at": "2026-06-20T09:00:00+00:00",
      "updated_at": "2026-06-20T09:30:00+00:00",
      "metadata": {},
      "labels": []
    }
  ],
  "total": 1
}

### GET api/issues/{id}
{
  "id": "38",
  "workspace_id": "1",
  "number": 38,
  "identifier": "task-38",
  "title": "Fix status mapping",
  "description": "Raw task description",
  "status": "backlog",
  "priority": "medium",
  "assignee_type": "member",
  "assignee_id": "member-1",
  "creator_type": "user",
  "creator_id": "user-1",
  "parent_issue_id": null,
  "project_id": "3",
  "position": 0,
  "start_date": null,
  "due_date": null,
  "created_at": "2026-06-20T09:00:00+00:00",
  "updated_at": "2026-06-20T09:30:00+00:00",
  "metadata": {},
  "reactions": [],
  "attachments": [],
  "labels": []
}

### GET api/issues/{id}/comments
[
  {
    "id": "15",
    "issue_id": "38",
    "author_type": "user",
    "author_id": "1",
    "content": "Looks good",
    "type": "comment",
    "parent_id": null,
    "created_at": "2026-06-20T09:10:00+00:00",
    "updated_at": "2026-06-20T09:10:00+00:00",
    "resolved_at": null,
    "resolved_by_type": null,
    "resolved_by_id": null,
    "reactions": [],
    "attachments": []
  }
]

### GET api/runtimes?workspace_id=1
[
  {
    "id": "runtime-uuid",
    "workspace_id": "1",
    "daemon_id": "daemon-abc",
    "name": "Claude Local",
    "runtime_mode": "local",
    "provider": "claude",
    "launch_header": "claude",
    "status": "online",
    "device_info": "MacBook",
    "metadata": {},
    "owner_id": "1",
    "visibility": "private",
    "profile_id": null,
    "last_seen_at": "2026-06-20T09:30:00+00:00",
    "created_at": "2026-06-20T08:00:00+00:00",
    "updated_at": "2026-06-20T09:30:00+00:00"
  }
]

### GET api/agents
[
  {
    "id": "agent-uuid",
    "team_id": 1,
    "runtime_id": "runtime-uuid",
    "owner_id": 1,
    "name": "Agent A",
    "description": null,
    "instructions": null,
    "visibility": "workspace",
    "status": "idle",
    "max_concurrent_tasks": 6,
    "model": null,
    "custom_env": null,
    "custom_args": null,
    "archived_at": null,
    "created_at": "2026-06-20T08:00:00+00:00",
    "updated_at": "2026-06-20T08:00:00+00:00",
    "runtime": {
      "id": "runtime-uuid",
      "name": "Claude Local",
      "provider": "claude",
      "status": "online"
    }
  }
]

### GET api/agent-task-snapshot
[
  {
    "id": "queue-uuid",
    "agent_id": "agent-uuid",
    "runtime_id": "runtime-uuid",
    "issue_id": "task-38",
    "workspace_id": "1",
    "status": "running",
    "priority": 0,
    "dispatched_at": "2026-06-20T09:05:00+00:00",
    "started_at": "2026-06-20T09:06:00+00:00",
    "completed_at": null,
    "result": null,
    "error": null,
    "failure_reason": "",
    "attempt": 1,
    "max_attempts": 1,
    "parent_task_id": null,
    "created_at": "2026-06-20T09:04:00+00:00",
    "kind": "direct"
  }
]

Variant notes:
- assignee fields vary by endpoint and may be null.
- issue status string mapping differs between list, detail, and grouped/action contracts.
- some endpoints return wrapper objects, others bare arrays.

## 9. Diff-ready keys list

Use these normalized keys for direct compare against Multica audit:

- Common identity: id, workspace_id, project_id, issue_id, task_id, runtime_id, agent_id, user_id
- Issue core: number, identifier, title, description, status, priority, assignee_type, assignee_id, creator_type, creator_id, parent_issue_id, due_date, created_at, updated_at
- Project core: title, description, status, priority, issue_count, done_count, resource_count
- Resource core: resource_type, resource_ref, label, position
- Queue/run core: status, dispatched_at, started_at, completed_at, failure_reason, attempt, max_attempts, kind
- Usage core: input_tokens, output_tokens, total_tokens, cost, total_cost, model
- Comment core: content, author_type, author_id, reactions, attachments
- Attachment core: filename, url, download_url, content_type, size_bytes
- Workspace/member core: name, email, role, avatar_url
- Auth/token core: token, token_prefix, expires_at, last_used_at

