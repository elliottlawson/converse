<?php

namespace ElliottLawson\Converse\Enums;

enum MessageRole: string
{
    case User = 'user';
    case Assistant = 'assistant';
    case System = 'system';
    case ToolCall = 'tool_call';
    case ToolResult = 'tool_result';
}
