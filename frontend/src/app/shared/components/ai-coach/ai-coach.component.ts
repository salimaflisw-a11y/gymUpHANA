import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';
import { ChatApiService } from '../../../core/services/chat-api.service';
import { ChatMessage, QuickPromptCategory } from '../../../core/models/chat.model';

@Component({
  selector: 'app-ai-coach',
  templateUrl: './ai-coach.component.html',
  styleUrls: ['./ai-coach.component.scss']
})
export class AiCoachComponent implements OnInit {
  @ViewChild('messagesContainer') private messagesContainer?: ElementRef<HTMLDivElement>;

  isOpen = false;
  isLoading = false;
  userInput = '';
  sessionId: string = '';

  messages: ChatMessage[] = [];
  categories: QuickPromptCategory[] = [];

  constructor(private chatApi: ChatApiService) {}

  ngOnInit(): void {
    // Generate or retrieve session identifier for guest / member
    this.sessionId = localStorage.getItem('gymup_chat_session') || this.generateSessionId();
    localStorage.setItem('gymup_chat_session', this.sessionId);

    this.loadHistory();
    this.loadQuickPrompts();
  }

  toggleChat(): void {
    this.isOpen = !this.isOpen;
    if (this.isOpen) {
      setTimeout(() => this.scrollToBottom(), 100);
    }
  }

  loadHistory(): void {
    this.chatApi.getHistory(this.sessionId).subscribe({
      next: (res) => {
        if (res.success && res.messages) {
          this.messages = res.messages;
          setTimeout(() => this.scrollToBottom(), 100);
        }
      },
      error: () => {
        // Fallback silently if offline or initial load
      }
    });
  }

  loadQuickPrompts(): void {
    this.chatApi.getQuickPrompts().subscribe({
      next: (res) => {
        if (res.categories) {
          this.categories = res.categories;
        }
      },
      error: () => {
        // Fallback default quick prompts
        this.categories = [
          {
            category: 'Nutrition & Diet',
            icon: '🥗',
            prompts: [
              'Suggest a high-protein post-workout meal for muscle recovery.',
              'How do I calculate my daily protein and caloric needs for fat loss?',
              'Give me 3 easy high-protein breakfast recipes.'
            ]
          },
          {
            category: 'Workouts & Training',
            icon: '🏋️',
            prompts: [
              'Design a 4-day Upper / Lower workout split for hypertrophy.',
              'What is progressive overload and how do I apply it?',
              'Give me a 20-minute bodyweight HIIT workout.'
            ]
          }
        ];
      }
    });
  }

  sendMessage(presetPrompt?: string): void {
    const textToSend = (presetPrompt || this.userInput).trim();
    if (!textToSend || this.isLoading) {
      return;
    }

    // Add user message optimistically to UI
    const tempUserMsg: ChatMessage = {
      id: 'local_' + Date.now(),
      sender: 'user',
      text: textToSend,
      timestamp: new Date().toISOString()
    };
    this.messages.push(tempUserMsg);
    this.userInput = '';
    this.isLoading = true;

    setTimeout(() => this.scrollToBottom(), 50);

    this.chatApi.sendMessage(textToSend, this.sessionId).subscribe({
      next: (res) => {
        this.isLoading = false;
        if (res.sessionId) {
          this.sessionId = res.sessionId;
          localStorage.setItem('gymup_chat_session', this.sessionId);
        }

        const assistantMsg: ChatMessage = {
          id: 'ai_' + Date.now(),
          sender: 'assistant',
          text: res.reply,
          timestamp: res.timestamp || new Date().toISOString()
        };
        this.messages.push(assistantMsg);
        setTimeout(() => this.scrollToBottom(), 50);
      },
      error: (err) => {
        this.isLoading = false;
        const errorMsg: ChatMessage = {
          id: 'err_' + Date.now(),
          sender: 'assistant',
          text: '⚠️ I encountered a temporary connection issue. Please make sure the backend is active and try again.',
          timestamp: new Date().toISOString()
        };
        this.messages.push(errorMsg);
        setTimeout(() => this.scrollToBottom(), 50);
      }
    });
  }

  clearChat(): void {
    if (!confirm('Clear all conversation history?')) {
      return;
    }

    this.chatApi.clearHistory(this.sessionId).subscribe({
      next: () => {
        this.messages = [];
      },
      error: () => {
        this.messages = [];
      }
    });
  }

  /**
   * Helper to format markdown text safely with bold and list items
   */
  formatMessage(text: string): string {
    if (!text) return '';

    // Escape HTML entities to prevent XSS
    const escaped = text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      // Bold **text**
      .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

    const lines = escaped.split('\n');
    const result: string[] = [];
    let inUl = false;
    let inOl = false;

    for (const line of lines) {
      const trimmed = line.trim();
      const bulletMatch = trimmed.match(/^[\*\-]\s+(.*)$/);
      const numberMatch = trimmed.match(/^(\d+)\.\s+(.*)$/);

      if (bulletMatch) {
        if (inOl) {
          result.push('</ol>');
          inOl = false;
        }
        if (!inUl) {
          result.push('<ul class="chat-ul">');
          inUl = true;
        }
        result.push(`<li class="chat-li">${bulletMatch[1]}</li>`);
      } else if (numberMatch) {
        if (inUl) {
          result.push('</ul>');
          inUl = false;
        }
        if (!inOl) {
          result.push('<ol class="chat-ol">');
          inOl = true;
        }
        result.push(`<li class="chat-num-li">${numberMatch[2]}</li>`);
      } else {
        if (inUl) {
          result.push('</ul>');
          inUl = false;
        }
        if (inOl) {
          result.push('</ol>');
          inOl = false;
        }
        if (trimmed === '') {
          result.push('<br>');
        } else {
          result.push(`<span>${trimmed}</span><br>`);
        }
      }
    }

    if (inUl) result.push('</ul>');
    if (inOl) result.push('</ol>');

    return result.join('');
  }

  private scrollToBottom(): void {
    if (this.messagesContainer) {
      try {
        const el = this.messagesContainer.nativeElement;
        el.scrollTop = el.scrollHeight;
      } catch {}
    }
  }

  private generateSessionId(): string {
    return 'sess_' + Math.random().toString(36).substring(2, 12) + Date.now().toString(36);
  }
}

