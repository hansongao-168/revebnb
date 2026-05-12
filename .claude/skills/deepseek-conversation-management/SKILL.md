---
name: deepseek-conversation-management
description: |
  Build a DeepSeek conversation history management feature in Laravel Filament admin. Use this skill whenever the user mentions:
  - "DeepSeek conversation history", "deepseek chat management", "AI chat history", "deekseep conversation"
  - "import/export chat history", "download conversation as MD", "export chat as JSON"
  - "AI对话历史管理", "DeepSeek对话管理", "聊天记录导入导出"
  - "sync conversations from API", "DeepSeek API sync"
  - Building any feature that manages conversation/message history from AI platforms

  This skill creates a complete, production-ready Filament feature with: conversations table, Conversation model, ConversationService with import/export/search/API sync, ConversationResource with full CRUD table UI, and a custom chat-style ViewConversation page.

  Trigger whenever the user wants to manage, search, import, export, or display AI conversation history in a Laravel Filament admin panel.
---

# DeepSeek Conversation History Management

This skill builds a complete DeepSeek conversation history management module for Laravel Filament admin panels. It creates: a database table, Eloquent model, service layer, Filament Resource with table/list view, and a custom chat-style detail page.

## When to Use This Skill

Use this skill when the user wants to:
- Manage DeepSeek (or similar AI platform) conversation history
- Import/export conversations as JSON or Markdown
- Search through message content
- Sync conversations from DeepSeek API
- Display chat history in a chat-bubble UI

## Output File Structure

```
database/migrations/xxxx_create_conversations_table.php
app/Models/Conversation.php
app/Services/ConversationService.php
app/Filament/Resources/ConversationResource.php
app/Filament/Resources/ConversationResource/Pages/ListConversations.php
app/Filament/Resources/ConversationResource/Pages/ViewConversation.php
resources/views/filament/pages/view-conversation.blade.php
```

## Database Design

### Conversations Table

```php
Schema::create('conversations', function (Blueprint $table) {
    $table->id();
    $table->string('deepseek_id', 64)->nullable()->unique(); // API sync dedup
    $table->string('title', 500);                            // Auto-generated or from import
    $table->string('model', 100)->nullable();                // deepseek-chat, deepseek-reasoner
    $table->json('messages');                                // Full message array
    $table->unsignedInteger('message_count')->default(0);  // Denormalized for sorting
    $table->string('source', 20)->default('manual');        // 'manual' or 'api'
    $table->timestamps();

    $table->index('source');
    $table->index('created_at');
    $table->fullText('title');
});
```

**Key decisions:**
- `deepseek_id` is nullable and unique — allows both manual imports (no ID) and API syncs (with dedup)
- `messages` stored as JSON — DeepSeek messages have complex nested content (text blocks, reasoning_content, tool_calls) that doesn't fit a relational structure
- `message_count` is denormalized — avoids `JSON_LENGTH()` calls in every list query
- `source` distinguishes manual imports from API syncs for filtering

## Conversation Model

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'deepseek_id', 'title', 'model', 'messages', 'message_count', 'source',
    ];

    protected function casts(): array
    {
        return ['messages' => 'array'];
    }
}
```

Keep it minimal — all logic goes in the Service.

## ConversationService

The service handles all business logic. Follow this method organization:

### Return Format Convention
- **Operation methods** (mutating): return `['success' => bool, 'message' => string, ...data]`
- **Data methods**: return structured arrays directly
- **Download methods**: return `['success' => bool, 'content' => string, 'filename' => string, 'mime' => string]`

### Required Methods

```php
class ConversationService
{
    // --- Import ---
    public function importFile(UploadedFile|string|array $file): array
    // Handles FileUpload path strings (Filament 5 stores to disk, returns path)
    // Accepts: UploadedFile, path string, or array from FileUpload
    // Returns: ['success', 'message', 'imported_count']

    public function importJson(string $json): array
    // Parse JSON, extract title/messages, handle multiple format variants
    // Check deepseek_id for dedup, store to DB
    // Returns: ['success', 'message', 'imported_count']

    public function importMarkdown(string $content): array
    // Parse **User**: /** **Assistant**: /** format
    // Extract title from first # heading
    // Returns: ['success', 'message', 'imported_count']

    // --- Export ---
    public function exportConversation(int $id, string $format): array
    // $format is 'md' or 'json'
    // Returns: ['success', 'content', 'filename', 'mime']

    public function exportAll(string $format): array
    // Single conversation → direct download
    // Multiple → ZIP archive
    // Returns: ['success', 'path'|'content', 'filename', 'mime']

    public function toMarkdown(array $conversation): string
    // Output format:
    // # Title
    // **User**: message
    // **Assistant**: response
    // <details><summary>思考过程</summary>reasoning_content</details>

    public function toJsonFormatted(array $conversation): string
    // Include: title, model, messages, exported_at, deepseek_id (if present)
    // Pretty print with JSON_UNESCAPED_UNICODE

    // --- Search ---
    public function searchMessages(string $keyword): array
    // Search message content via MySQL JSON_SEARCH or PHP iteration
    // Return matching snippets with conversation_id, role, message_index
    // Highlight keyword in snippet with <mark> tags

    // --- API Sync ---
    public function syncFromApi(string $apiKey, ?string $lastId = null): array
    // GET https://api.deepseek.com/v1/chat/conversations
    // Skip already-existing (deepseek_id dedup)
    // Fetch each conversation's messages separately
    // Store with source='api'
    // Return: ['success', 'message', 'new_count', 'next_cursor']
}
```

### JSON Import Compatibility

Handle multiple DeepSeek export formats:

```php
private function extractMessages(array $data): array
{
    return $data['messages']
        ?? $data['conversation']['messages']
        ?? $data['chat']['messages']
        ?? [];
}

private function extractTitle(array $data, array $messages): string
{
    // Priority: explicit title field > first user message (truncated) > '未命名对话'
    $title = $data['title'] ?? $data['name'] ?? '';
    if ($title) return $title;

    foreach ($messages as $msg) {
        if ($msg['role'] === 'user') {
            $content = is_array($msg['content'])
                ? ($msg['content'][0]['text'] ?? '')
                : $msg['content'];
            return mb_substr($content, 0, 100);
        }
    }
    return '未命名对话';
}
```

### Message Content Format

DeepSeek messages use nested content structures. Always handle both:

```php
private function extractTextContent(array|string $content): string
{
    if (is_string($content)) {
        return $content;
    }
    // Content blocks: [{type: "text", text: "..."}]
    if (is_array($content)) {
        $parts = [];
        foreach ($content as $block) {
            if (($block['type'] ?? '') === 'text') {
                $parts[] = $block['text'] ?? '';
            }
        }
        return implode("\n", $parts);
    }
    return '';
}
```

## ConversationResource

### Navigation Setup

```php
protected static string $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;
protected static ?string $navigationLabel = '对话历史';
protected static ?string $modelLabel = 'DeepSeek 对话';
protected static ?string $pluralModelLabel = '对话历史列表';
protected static ?int $navigationSort = 102;

public static function getNavigationGroup(): \UnitEnum|string|null
{
    return '系统工具'; // Same group as cache/log pages
}
```

### Table Columns

| Column | Component | Notes |
|--------|-----------|-------|
| ID | TextColumn | sortable |
| 对话标题 | TextColumn | searchable, limit(60), tooltip |
| 模型 | TextColumn | badge, color by model type |
| 消息数 | TextColumn | sortable, alignCenter |
| 来源 | TextColumn | badge, 'api'→green, 'manual'→gray |
| 导入时间 | TextColumn | sortable, dateTime format |

### Table Actions (per row)

```php
->actions([
    Actions\Action::make('view')
        ->label('查看')
        ->url(fn ($record) => ConversationResource::getUrl('view', ['record' => $record])),
    Actions\Action::make('downloadMd')
        ->label('下载 MD')
        ->action(fn ($record) => static::download($record, 'md')),
    Actions\Action::make('downloadJson')
        ->label('下载 JSON')
        ->action(fn ($record) => static::download($record, 'json')),
    Actions\DeleteAction::make()->label('删除'),
])
```

### Header Actions

```php
->headerActions([
    Actions\Action::make('import')
        ->label('导入对话')
        ->icon(Heroicon::OutlinedArrowUpTray)
        ->form([
            FileUpload::make('file')
                ->label('选择文件')
                ->acceptedFileTypes(['application/json', 'text/markdown', 'text/plain'])
                ->disk('local')
                ->directory('temp-imports')
                ->maxFiles(1)
                ->required(),
        ])
        ->action(function (array $data) {
            $result = app(ConversationService::class)->importFile($data['file']);
            Notification::make()
                ->title($result['success'] ? '导入完成' : '导入失败')
                ->body($result['message'])
                ->color($result['success'] ? 'success' : 'danger')
                ->send();
        }),
    Actions\Action::make('apiSync')
        ->label('API 同步')
        ->icon(Heroicon::OutlinedCloudArrowDown)
        ->color('success')
        ->form([
            TextInput::make('api_key')
                ->label('DeepSeek API Key')
                ->password()
                ->required()
                ->placeholder('sk-...'),
        ])
        ->action(function (array $data) {
            $result = app(ConversationService::class)->syncFromApi($data['api_key']);
            Notification::make()
                ->title($result['success'] ? '同步完成' : '同步失败')
                ->body($result['message'])
                ->color($result['success'] ? 'success' : 'danger')
                ->send();
        }),
    Actions\Action::make('exportAllMd')->label('导出全部 MD')
        ->action(fn () => static::handleExportAll('md')),
    Actions\Action::make('exportAllJson')->label('导出全部 JSON')
        ->action(fn () => static::handleExportAll('json')),
])
```

### Toolbar Bulk Actions

```php
->toolbarActions([
    Actions\BulkActionGroup::make([
        Actions\BulkAction::make('exportMd')->label('批量导出 MD')
            ->action(fn (Collection $records) => static::bulkExport($records, 'md')),
        Actions\BulkAction::make('exportJson')->label('批量导出 JSON')
            ->action(fn (Collection $records) => static::bulkExport($records, 'json')),
        DeleteBulkAction::make()->label('批量删除'),
    ]),
])
```

### Filters

```php
->filters([
    SelectFilter::make('source')
        ->label('来源')
        ->options(['manual' => '手动导入', 'api' => 'API 同步']),
    SelectFilter::make('model')
        ->label('模型')
        ->options(fn () => Conversation::query()
            ->whereNotNull('model')
            ->distinct()
            ->pluck('model', 'model')
            ->toArray()),
])
```

### Static Download Methods

```php
public static function download($record, string $format): mixed
{
    $result = app(ConversationService::class)->exportConversation($record->id, $format);
    if (!$result['success']) {
        Notification::make()->title('导出失败')->body($result['message'])->danger()->send();
        return null;
    }
    return response()->streamDownload(
        fn () => print($result['content']),
        $result['filename'],
        ['Content-Type' => $result['mime']],
    );
}

public static function bulkExport(Collection $records, string $format): mixed
{
    if ($records->count() === 1) {
        return static::download($records->first(), $format);
    }
    // ZIP logic...
}

public static function handleExportAll(string $format): mixed
{
    $result = app(ConversationService::class)->exportAll($format);
    if (!$result['success']) {
        Notification::make()->title('导出失败')->body($result['message'])->danger()->send();
        return null;
    }
    // Single file or ZIP download...
}
```

## ViewConversation Page

### Key Pattern — Use `getView()`, Not Static Property

The parent `Filament\Pages\Page` declares `$view` as **non-static**. Declaring it as `static` in a child class causes a fatal PHP error. Always use the method:

```php
class ViewConversation extends Page
{
    protected static string $resource = ConversationResource::class;

    public function getView(): string
    {
        return 'filament.pages.view-conversation';
    }
}
```

Similarly, do NOT declare `public string $title` as a non-static property — the parent declares `$title` as static. Access `$record->title` directly in the Blade view instead.

### Page Class

```php
class ViewConversation extends Page
{
    protected static string $resource = ConversationResource::class;

    public Conversation $record;
    public array $messages = [];
    public ?string $model = null;
    public string $source = '';

    public function mount(string|int $record): void
    {
        $this->record = Conversation::findOrFail($record);
        $this->messages = $this->record->messages ?? [];
        $this->model = $this->record->model;
        $this->source = $this->record->source;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadMd')->label('下载 MD')
                ->action(fn () => $this->download('md')),
            Action::make('downloadJson')->label('下载 JSON')
                ->action(fn () => $this->download('json')),
            Action::make('backToList')->label('返回列表')->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(ConversationResource::getUrl('index')),
        ];
    }

    public function formatContent(array $msg): string
    {
        $content = $msg['content'] ?? '';
        if (is_string($content)) return nl2br(e($content));
        if (is_array($content)) {
            $parts = [];
            foreach ($content as $block) {
                if (($block['type'] ?? '') === 'text') {
                    $parts[] = nl2br(e($block['text'] ?? ''));
                }
            }
            return implode("\n", $parts);
        }
        return '';
    }

    public function hasReasoning(array $msg): bool
    {
        return !empty($msg['reasoning_content']);
    }

    public function getReasoningContent(array $msg): string
    {
        return nl2br(e($msg['reasoning_content'] ?? ''));
    }
}
```

## Blade View — Chat Bubble UI

```blade
<x-filament-panels::page>
    <div class="max-w-4xl mx-auto">
        {{-- Header --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h1 class="text-xl font-bold text-gray-900 mb-2">{{ $record->title }}</h1>
            <div class="flex flex-wrap gap-3 text-sm text-gray-500">
                @if($model)
                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ $model }}
                </span>
                @endif
                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $source === 'api' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                    {{ $source === 'api' ? 'API 同步' : '手动导入' }}
                </span>
                <span>{{ $record->message_count }} 条消息</span>
                <span>导入于 {{ $record->created_at->format('Y-m-d H:i') }}</span>
            </div>
        </div>

        {{-- Messages --}}
        <div class="space-y-4">
            @foreach($messages as $msg)
                @php $isUser = $msg['role'] === 'user'; @endphp

                @if($msg['role'] === 'system')
                    <div class="flex justify-center">
                        <div class="bg-gray-100 text-gray-500 text-xs px-3 py-1 rounded-full">System</div>
                    </div>
                @else
                    <div class="flex {{ $isUser ? 'justify-start' : 'justify-end' }}">
                        <div class="max-w-[80%]">
                            <div class="text-xs text-gray-400 mb-1 {{ $isUser ? 'text-left' : 'text-right' }}">
                                {{ $isUser ? 'User' : 'Assistant' }}
                            </div>
                            <div class="rounded-2xl px-4 py-3 text-sm leading-relaxed {{
                                $isUser ? 'bg-blue-50 border border-blue-100' : 'bg-green-50 border border-green-100'
                            }}">
                                {!! $this->formatContent($msg) !!}
                            </div>
                            @if($this->hasReasoning($msg))
                            <details class="mt-2">
                                <summary class="text-xs text-amber-600 cursor-pointer hover:text-amber-700">
                                    查看思考过程
                                </summary>
                                <div class="mt-2 p-3 bg-amber-50 border border-amber-100 rounded-lg text-xs text-gray-600">
                                    {!! $this->getReasoningContent($msg) !!}
                                </div>
                            </details>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
```

## Common Pitfalls (and How to Avoid Them)

### 1. `Cannot redeclare static Page::$title as non-static`

The parent `Filament\Pages\BasePage` declares `$title` as **static**. Never re-declare it as a non-static property in your page class. Access `$record->title` in the view instead.

### 2. `Cannot redeclare non-static Page::$view as static`

Same issue — `$view` is non-static in the parent. Always use `getView(): string` method instead of the static property.

### 3. FileUpload returns path string, not UploadedFile

In Filament 5, `FileUpload::make()` stores the file to disk and returns a **path string** (or array of paths). Do NOT call `$file->getClientOriginalExtension()` or `$file->getContent()`. Instead:

```php
if (is_string($file) && file_exists($file)) {
    $content = file_get_contents($file);
    $extension = pathinfo($file, PATHINFO_EXTENSION);
}
```

### 4. BulkActionGroup lives in `Filament\Actions`, not `Filament\Tables\Actions`

```php
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
```

When using both `Actions` alias and `BulkActionGroup`, reference it fully:
```php
->toolbarActions([
    Actions\BulkActionGroup::make([...]), // avoid collision with Actions alias
])
```

### 5. API Key — Never Store in DB

For API sync features, accept the API key in the action form but do not persist it to the database. Use it directly in the HTTP call and discard. If persistence is needed, store encrypted in `.env`.

## Testing Checklist

After building, verify:
- [ ] Import JSON file — conversation appears in list
- [ ] Import Markdown file — conversation appears in list
- [ ] View conversation — User messages left/blue, Assistant right/green
- [ ] Reasoning content (deepseek-reasoner R1) shows collapsible
- [ ] Download MD — valid markdown file downloads
- [ ] Download JSON — valid JSON file downloads
- [ ] Export all (multiple) — ZIP downloads
- [ ] Search by keyword — matching message snippets return
- [ ] Filter by source — list filters correctly
- [ ] Delete — conversation removed
- [ ] API sync — input key, conversations fetched and stored
- [ ] No PHP errors in `php artisan optimize:clear && php artisan serve`