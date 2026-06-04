export interface AtsUser {
  id: number;
  name: string;
  email: string;
  isAdmin: boolean;
}

export interface AtsConfig {
  mode: 'user' | 'admin';
  restUrl: string;
  nonce: string;
  assetsUrl: string;
  basePath: string;
  allowedMimeTypes: string[];
  maxUploadSize: number;
  user: AtsUser;
}

declare global {
  interface Window {
    atsConfig: AtsConfig;
  }
}

/** Falls back to dev-mode defaults so `npm run dev` works without WordPress. */
export function getConfig(): AtsConfig {
  if (typeof window !== 'undefined' && window.atsConfig) {
    return window.atsConfig;
  }
  // Dev-mode fallback
  const isAdmin = window.location.pathname.startsWith('/helpdesk-admin');
  return {
    mode:             isAdmin ? 'admin' : 'user',
    restUrl:          'http://localhost/wp-json/ats/v1',
    nonce:            '',
    assetsUrl:        '/',
    basePath:         isAdmin ? '/helpdesk-admin' : '/helpdesk',
    allowedMimeTypes: ['jpg|jpeg|jpe', 'png', 'gif', 'webp', 'pdf', 'txt', 'zip', 'doc', 'docx', 'xls', 'xlsx'],
    maxUploadSize:    5120,
    user: { id: 1, name: 'Dev User', email: 'dev@example.com', isAdmin },
  };
}
