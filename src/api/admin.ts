import { api } from './client';
import type { Message } from './tickets';

export type AdminState = 'unreviewed' | 'reviewing' | 'pending' | 'answered' | 'closed' | 'spam' | 'ai_resolved';

export interface AdminTicket {
  id: string;
  userId: number;
  user: string;
  title: string;
  state: AdminState;
  priority: 'low' | 'medium' | 'high';
  categoryId: number | null;
  categoryTitle: string | null;
  preview: string;
  createdAt: string;
  updatedAt: string;
}

export interface AdminTicketListResponse {
  items: AdminTicket[];
  total: number;
  page: number;
  total_pages: number;
  counts: Record<AdminState | 'all', number>;
  aiResolved: number;
}

export interface Category {
  id: string;
  title: string;
  description: string;
  count: number;
}

export interface SavedAnswer {
  id: number;
  categoryId: number | null;
  categoryTitle: string | null;
  title: string;
  body: string;
  createdAt: string;
  updatedAt: string;
}

export interface Settings {
  aiEnabled: boolean;
  aiMode: 'kb_only' | 'ai_enhanced';
  brandColor: string;
  providers: Record<string, { enabled: boolean; apiKey: string; model: string }>;
  aiTopK: number;
  aiMaxBodyChars: number;
  allowedMimeTypes: string[];
  maxUploadSize: number;
}

export const adminStateMap: Record<AdminState, { color: 'default'|'primary'|'success'|'warning'|'danger'|'violet'; label: string }> = {
  unreviewed:  { color: 'danger',  label: 'بررسی نشده' },
  reviewing:   { color: 'primary', label: 'درحال بررسی' },
  pending:     { color: 'warning', label: 'در انتظار پاسخ' },
  answered:    { color: 'success', label: 'پاسخ داده شده' },
  closed:      { color: 'default', label: 'بسته شده' },
  spam:        { color: 'violet',  label: 'اسپم' },
  ai_resolved: { color: 'success', label: 'پاسخ هوشمند' },
};

export const adminApi = {
  // Tickets
  tickets(params: { page?: number; perPage?: number; status?: string; priority?: string; search?: string } = {}): Promise<AdminTicketListResponse> {
    const q = new URLSearchParams();
    if (params.page)     q.set('page',     String(params.page));
    if (params.perPage)  q.set('per_page', String(params.perPage));
    if (params.status)   q.set('status',   params.status);
    if (params.priority) q.set('priority', params.priority);
    if (params.search)   q.set('search',   params.search);
    return api.get(`/admin/tickets?${q}`);
  },

  updateTicketState(id: string, status: AdminState): Promise<AdminTicket> {
    return api.put(`/admin/tickets/${id}`, { status });
  },

  replyToTicket(id: string, body: string): Promise<{ messages: Message[] }> {
    return api.post(`/admin/tickets/${id}/messages`, { body });
  },

  deleteTicket(id: string): Promise<void> {
    return api.delete(`/admin/tickets/${id}`);
  },

  // Categories
  categories(): Promise<Category[]> {
    return api.get('/admin/categories');
  },

  createCategory(title: string, description: string): Promise<Category> {
    return api.post('/admin/categories', { title, description });
  },

  updateCategory(id: string, title: string, description: string): Promise<Category> {
    return api.put(`/admin/categories/${id}`, { title, description });
  },

  deleteCategory(id: string): Promise<void> {
    return api.delete(`/admin/categories/${id}`);
  },

  // Saved answers
  savedAnswers(): Promise<SavedAnswer[]> {
    return api.get('/admin/saved-answers');
  },

  createSavedAnswer(payload: { title: string; body: string; category_id?: number | null }): Promise<SavedAnswer> {
    return api.post('/admin/saved-answers', payload);
  },

  updateSavedAnswer(id: number, payload: { title: string; body: string; category_id?: number | null }): Promise<SavedAnswer> {
    return api.put(`/admin/saved-answers/${id}`, payload);
  },

  deleteSavedAnswer(id: number): Promise<void> {
    return api.delete(`/admin/saved-answers/${id}`);
  },

  // Settings
  settings(): Promise<Settings> {
    return api.get('/admin/settings');
  },

  saveSettings(payload: Settings): Promise<Settings> {
    return api.post('/admin/settings', payload);
  },

  testProvider(provider: string, apiKey: string, model: string): Promise<{ success: boolean; message: string }> {
    return api.post('/admin/test-provider', { provider, apiKey, model });
  },
};
