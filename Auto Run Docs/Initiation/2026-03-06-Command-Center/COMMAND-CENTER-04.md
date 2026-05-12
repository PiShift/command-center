# Phase 04: Task AI Chat — Foundation

This phase installs and configures `laravel/ai`, creates the Conversation model and persistence layer, sets up Laravel Horizon for queue processing, and builds the core `TaskAiChatAgent` class. By the end, the agent can receive a text message, hold a multi-turn conversation with memory across requests, and return a structured JSON response containing proposed tasks — all running through the queue. The UI wiring comes in the next phase.

## Tasks

- [ ] Install `laravel/ai` and configure the AI provider:
  - Run `composer require laravel/ai`
  - Run `php artisan ai:install` (publishes config and migrations)
  - Run `php artisan migrate` (creates `agent_conversations` table used by `RemembersConversations`)
  - Add to `.env`: `AI_PROVIDER=openai`, `OPENAI_API_KEY=your-key-here` (leave placeholder — the developer will fill in their real key)
  - Add to `.env.example`: `AI_PROVIDER=openai` and `OPENAI_API_KEY=` (no value)
  - In `config/ai.php`, confirm the default provider is set to `openai` and the default model is `gpt-4o`
  - Add `OPENAI_API_KEY` to the `.env` key list in the README instructions comment at top of `.env`

- [ ] Create the `Conversation` model migration and update the model (check if already created in Phase 02 — if so, verify columns match and skip re-creation):
  - Verify the `conversations` table exists with: `id`, `project_id`, `user_id`, `type`, `messages` (json), `final_tasks` (json, nullable), `status`, `timestamps`
  - If the migration from Phase 02 was not run, create and run it now
  - Ensure `app/Models/Conversation.php` has `$casts = ['messages' => 'array', 'final_tasks' => 'array']`
  - Add a scope to `Conversation`: `scopeDiscussing($query)` → `where('status', 'discussing')` and `scopeConfirmed($query)` → `where('status', 'confirmed')`

- [ ] Create a `TaskExtraction` structured output DTO class used by the agent to return proposed tasks:
  - Create `app/AI/Data/ProposedTask.php` using `spatie/laravel-data`:
    ```php
    class ProposedTask extends Data {
        public function __construct(
            public string $title,
            public string $description,
            public string $type,       // bug | feature | change
            public string $priority,   // low | medium | high
        ) {}
    }
    ```
  - Create `app/AI/Data/TaskExtractionResult.php` using `spatie/laravel-data`:
    ```php
    class TaskExtractionResult extends Data {
        public function __construct(
            public string $interpretation,        // AI's explanation of what it understood
            public bool $ready_to_confirm,        // true only if founder has explicitly accepted
            /** @var ProposedTask[] */
            public DataCollection $proposed_tasks,
            public ?string $clarifying_question,  // null if no question needed
        ) {}
    }
    ```

- [ ] Build the `TaskAiChatAgent` class in `app/AI/Agents/TaskAiChatAgent.php`:
  - Extend `Laravel\AI\Agent` and use the `RemembersConversations` trait
  - Set the agent system prompt as a class constant or method — the prompt must:
    - Identify the agent as a task extraction assistant for PiShift Command Center
    - Explain that its job is to interpret customer feedback and propose structured tasks
    - Enforce the core rule: NEVER confirm tasks as ready unless the founder explicitly says "accept", "confirm", "yes that's right", or similar
    - Instruct the agent to always present its interpretation first and ask if it's correct
    - Instruct the agent to ask one clarifying question at a time when input is ambiguous
    - Format: use the `TaskExtractionResult` structured output schema for every response
  - Add a method `interpretInput(string $userMessage, int $projectId, int $userId): TaskExtractionResult` that:
    - Sets the conversation context (project name loaded from DB)
    - Sends the message through the agent with `RemembersConversations` using `$projectId` + `$userId` as the conversation identifier
    - Returns a typed `TaskExtractionResult` via structured output
  - Register the agent in a service provider or keep it instantiable via `new TaskAiChatAgent()` — no container binding needed yet

- [ ] Configure Laravel Horizon and set up queue workers for AI jobs:
  - In `config/horizon.php`, add a `default` environment supervisor watching the `default` queue with `maxProcesses: 3`
  - Add an `ai` queue supervisor watching the `ai` queue with `maxProcesses: 2` and `timeout: 120` (AI calls can be slow)
  - Create `app/Jobs/ProcessAiChatMessage.php` — a queued job that:
    - Accepts: `int $conversationId`, `string $userMessage`
    - In `handle()`: loads the conversation + project, calls `TaskAiChatAgent::interpretInput()`, updates the `conversations.messages` array with the AI response, and broadcasts an event (placeholder `AiResponseReady` event — can be a simple event class with the conversation ID)
  - Create `app/Events/AiResponseReady.php` — a simple event with `$conversationId` and `$result` (the `TaskExtractionResult` array)
  - Add `QUEUE_CONNECTION=redis` to `.env` if not already set from Phase 01
