export interface ChatMessage {
  id: string;
  sender: 'user' | 'assistant';
  text: string;
  timestamp: string;
}

export interface SendMessageResponse {
  success: boolean;
  reply: string;
  timestamp: string;
  sessionId: string;
}

export interface ChatHistoryResponse {
  success: boolean;
  messages: ChatMessage[];
}

export interface QuickPromptCategory {
  category: string;
  icon: string;
  prompts: string[];
}

export interface QuickPromptsResponse {
  categories: QuickPromptCategory[];
}

