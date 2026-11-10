import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import {
  ChatHistoryResponse,
  QuickPromptsResponse,
  SendMessageResponse
} from '../models/chat.model';

@Injectable({
  providedIn: 'root'
})
export class ChatApiService {
  private readonly apiUrl = '/api/chat';

  constructor(private http: HttpClient) {}

  /**
   * Send a prompt to the AI Fitness & Nutrition Coach.
   */
  sendMessage(message: string, sessionId?: string): Observable<SendMessageResponse> {
    return this.http.post<SendMessageResponse>(
      `${this.apiUrl}/message`,
      { message, sessionId },
      { withCredentials: true }
    );
  }

  /**
   * Fetch previous conversation history.
   */
  getHistory(sessionId?: string): Observable<ChatHistoryResponse> {
    let params = new HttpParams();
    if (sessionId) {
      params = params.set('sessionId', sessionId);
    }

    return this.http.get<ChatHistoryResponse>(`${this.apiUrl}/history`, {
      params,
      withCredentials: true
    });
  }

  /**
   * Reset / clear chat history.
   */
  clearHistory(sessionId?: string): Observable<{ success: boolean; message: string }> {
    let params = new HttpParams();
    if (sessionId) {
      params = params.set('sessionId', sessionId);
    }

    return this.http.delete<{ success: boolean; message: string }>(`${this.apiUrl}/history`, {
      params,
      withCredentials: true
    });
  }

  /**
   * Fetch quick starter questions.
   */
  getQuickPrompts(): Observable<QuickPromptsResponse> {
    return this.http.get<QuickPromptsResponse>(`${this.apiUrl}/quick-prompts`);
  }
}
