# Phase 05: Task AI Chat — Full Conversation UI

This phase delivers the complete Task AI Chat interface inside the Filament admin panel. The developer selects a project, pastes customer feedback (text, image, or voice), and enters a back-and-forth conversation with the AI until they're satisfied with the interpretation. One click on "Accept Tasks" creates all confirmed tasks in the database, linked to the project, with the full conversation history preserved. This is the moment Command Center becomes genuinely useful as a daily tool.

## Tasks

- [ ] Create the `TaskAiChat` custom Filament page scaffolding:
  - Run `php artisan make:filament-page TaskAiChat`
  - Set the page's navigation label to "Task AI Chat", icon `heroicon-o-chat-bubble-left-right`, navigation group `Projects`, sort order 10
  - The page's Blade view should be the primary UI — use `resources/views/filament/pages/task-ai-chat.blade.php`
  - The page class should extend `Filament\Pages\Page` and use the `HasForms` and `InteractsWithForms` traits
  - Add a `$selectedProjectId` public property (nullable int) and a `$conversationId` public property (nullable int)
  - Add a `getTitle()` method that returns "Task AI Chat" when no project is selected, or "Task AI Chat — {project name}" when a project is selected

- [ ] Build the project selector and conversation starter form in the Filament page class:
  - Implement `form(Form $form)` with two logical sections:
    - **Section 1 — Select Project** (shown when `$selectedProjectId` is null):
      - `Select::make('selectedProjectId')` — searchable, options from active projects (name + customer name), label "Which project is this feedback for?", required
      - Submit button label: "Start Session"
    - **Section 2 — Chat Input** (shown when `$selectedProjectId` is set):
      - `Textarea::make('userMessage')` — label "Paste feedback, describe the issue, or ask a question", rows 4, placeholder "e.g. Customer says the checkout button doesn't work on iPhone..."
      - `FileUpload::make('attachments')` — label "Attach screenshots or images (optional)", multiple, acceptedFileTypes `['image/*']`, stored in `temp/ai-chat/` disk
      - A "Record voice note" hint text pointing to a future voice feature (placeholder for Phase 2 voice polish)
  - Add a `startSession()` Livewire action that sets `$selectedProjectId` and creates a new `Conversation` record (status: discussing, messages: [], type: text)
  - Add a `sendMessage()` Livewire action that:
    - Validates `userMessage` is not empty
    - Appends `{ role: 'user', content: $userMessage, timestamp: now() }` to `$conversation->messages`
    - Saves the conversation
    - Dispatches `ProcessAiChatMessage` job with `$conversationId` and `$userMessage`
    - Clears the `userMessage` field
    - Sets a `$isWaiting` flag to true (shows loading state in the UI)

- [ ] Build the conversation history display and AI response rendering in the Blade view:
  - The view should use Livewire's `wire:poll.2000ms` or a Livewire event listener to detect when `AiResponseReady` fires and refresh the message list
  - Render each message in the `$conversation->messages` array:
    - User messages: right-aligned bubble, grey background
    - AI messages: left-aligned bubble, blue/indigo background, with the agent icon
    - AI messages should render the `interpretation` field as the main message text
    - If `proposed_tasks` array is non-empty, render a card below the AI message showing each proposed task: title (bold), description (muted text), type badge, priority badge
    - If `clarifying_question` is non-null, render it highlighted below the interpretation
  - Show a pulsing "AI is thinking..." indicator when `$isWaiting` is true
  - Implement a Livewire listener `$listeners = ['echo:ai-responses,AiResponseReady' => 'handleAiResponse']` — or use polling as a fallback if Reverb is not yet configured (it is not, until Phase 5)
  - The `handleAiResponse(array $data)` method should: set `$isWaiting = false`, refresh the conversation from DB

- [ ] Build the task confirmation flow — the "Accept Tasks" action:
  - When the latest AI message has `ready_to_confirm = false`, show a disabled "Accept Tasks" button with tooltip "Tell the AI you're happy with the interpretation first"
  - When the latest AI message has `ready_to_confirm = true`, show an active green "Accept Tasks" button
  - The `acceptTasks()` Livewire action should:
    - Load the latest `TaskExtractionResult` from the conversation's last AI message
    - Create a `Task` record for each entry in `proposed_tasks`:
      - `project_id` = `$selectedProjectId`
      - `title`, `description`, `type`, `priority` from the proposed task
      - `status` = `backlog`
      - `source` = `ai-chat`
      - `original_input` = the first user message in the conversation (the raw customer feedback)
    - Update the `Conversation` record: set `status = confirmed`, `final_tasks` = the accepted tasks array
    - Show a Filament success notification: "X tasks created for {project name}"
    - Redirect to the TaskResource list filtered by the current project, or show a "Start new session" button to reset the page state
  - Add a "Start Over" link/button that resets `$selectedProjectId`, `$conversationId`, and all form state without deleting the conversation history

- [ ] Add voice input support via `laravel/ai` audio transcription:
  - Add a `FileUpload::make('voiceNote')` field to the chat input form: label "Or upload a voice note", acceptedFileTypes `['audio/*']`, stored in `temp/ai-chat/audio/`
  - In `sendMessage()`, if a voice file is attached: call `AI::transcribe($voiceFilePath)` using `laravel/ai`'s transcription capability (Whisper via OpenAI), prepend the transcript to `$userMessage` with a label: "Voice note transcript: {transcript}\n\n{any additional text}"
  - Store the original voice file path in the conversation message entry alongside the transcript
  - Show the transcript preview inline above the message bubble in the conversation view so the developer can confirm it was transcribed correctly before it's sent to the AI
