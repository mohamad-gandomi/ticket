import { useState, useEffect } from 'react';
import { useNavigate }         from 'react-router-dom';
import { PageContainer }       from '../components/PageContainer';
import { Field, Input, Select } from '../components/FormControls';
import { Button }              from '../components/Button';
import { Label }               from '../components/Label';
import { BookOpen, Check, ExternalLink, GapGptLogo } from '../icons';
import { PageHeader }          from '../components/PageHeader';
import { adminApi, type Settings } from '../api/admin';
import { applyBrandColor }     from '../utils/color';

// ── Toggle ────────────────────────────────────────────────────────────────────

function Toggle({ checked, onChange, label, hint }: {
  checked: boolean; onChange: (v: boolean) => void; label: string; hint?: string;
}) {
  return (
    <div className="flex items-start justify-between gap-3 w-full">
      <button onClick={() => onChange(!checked)}
        className={`relative w-11 h-6 rounded-full transition shrink-0 ${checked ? 'bg-brand' : 'bg-line'}`}>
        <span className={`absolute top-0.5 size-5 rounded-full bg-white shadow transition-all ${checked ? 'right-0.5' : 'right-[22px]'}`} />
      </button>
      <div className="flex flex-col gap-1 flex-1">
        <span className="text-[13px] font-bold text-ink-900 text-right">{label}</span>
        {hint && <span className="text-[11px] text-ink-500 text-right leading-5">{hint}</span>}
      </div>
    </div>
  );
}

// ── AI providers ──────────────────────────────────────────────────────────────

interface ModelGroup { label: string; models: string[] }
interface AiProvider { id: string; name: string; description: string; badge: string; badgeColor: string; modelGroups: ModelGroup[]; tokenUrl?: string }

const GAPCODE_MODEL_GROUPS: ModelGroup[] = [
  { label: 'گپ‌کد (بومی)', models: [
    'gapgpt-qwen-3.5', 'gapgpt-qwen-3.5-thinking', 'gapgpt-qwen-3.6', 'gapgpt-qwen-3.6-thinking',
  ]},
  { label: 'OpenAI', models: [
    'gpt-4o', 'gpt-4o-mini', 'gpt-4.1', 'gpt-4.1-mini', 'gpt-4.1-nano',
    'gpt-5', 'gpt-5-mini', 'gpt-5-codex', 'o3-mini', 'o4-mini',
  ]},
  { label: 'Anthropic', models: [
    'claude-opus-4-7', 'claude-sonnet-4-6', 'claude-haiku-4-5',
    'claude-3-7-sonnet-20250219', 'claude-3-5-sonnet-20241022', 'claude-3-5-haiku-20241022',
  ]},
  { label: 'Google Gemini', models: [
    'gemini-2.5-pro', 'gemini-2.5-flash', 'gemini-2.5-flash-lite',
    'gemini-2.0-flash', 'gemini-1.5-pro', 'gemini-1.5-flash',
  ]},
  { label: 'Google Gemma', models: ['gemma-3-27b-it'] },
  { label: 'XAI', models: ['grok-4', 'grok-3', 'grok-3-mini', 'grok-3-mini-fast'] },
  { label: 'Deepseek', models: ['deepseek-v4-pro', 'deepseek-v4-flash', 'deepseek-r1'] },
  { label: 'Alibaba (Qwen)', models: [
    'qwen3-235b-a22b', 'qwen3-coder', 'qwen3-coder-480b-a35b-instruct', 'qwen-max', 'qwen-plus', 'qwen-turbo',
  ]},
];

const AI_PROVIDERS: AiProvider[] = [
  { id: 'chatgpt', name: 'ChatGPT',  description: 'مدل‌های هوشمند OpenAI برای پاسخ‌دهی خودکار',             badge: 'GPT', badgeColor: '#10A37F', modelGroups: [{ label: '', models: ['gpt-4o', 'gpt-4-turbo', 'gpt-3.5-turbo'] }] },
  { id: 'claude',  name: 'Claude',   description: 'مدل‌های Anthropic با دقت بالا در پردازش زبان',           badge: 'CLD', badgeColor: '#D97757', modelGroups: [{ label: '', models: ['claude-opus-4-7', 'claude-sonnet-4-6', 'claude-haiku-4-5'] }] },
  { id: 'gemini',  name: 'Gemini',   description: 'مدل‌های Google با قابلیت‌های چندوجهی',                   badge: 'GEM', badgeColor: '#4285F4', modelGroups: [{ label: '', models: ['gemini-2.5-pro', 'gemini-2.5-flash', 'gemini-2.0-flash'] }] },
  { id: 'gapcode', name: 'GapGPT',  description: 'سرویس هوش مصنوعی داخلی با پشتیبانی از زبان فارسی',     badge: 'گپ',  badgeColor: '#7C3AED', modelGroups: GAPCODE_MODEL_GROUPS, tokenUrl: 'https://gapgpt.app/platform-v2/tokens' },
];

function ProviderLogo({ provider }: { provider: AiProvider }) {
  if (provider.id === 'gapcode') return <GapGptLogo size={48} />;
  return (
    <div className="size-12 rounded-xl grid place-items-center text-white text-[13px] font-bold shrink-0"
         style={{ background: provider.badgeColor }}>{provider.badge}</div>
  );
}

type TestState = 'idle' | 'loading' | 'ok' | 'fail';

function AiProviderCard({ provider, config, onChange }: {
  provider: AiProvider;
  config: { enabled: boolean; apiKey: string; model: string };
  onChange: (c: { enabled: boolean; apiKey: string; model: string }) => void;
}) {
  const [testState, setTestState] = useState<TestState>('idle');
  const [testMsg, setTestMsg]     = useState('');

  // reset test result whenever the API key changes
  useEffect(() => { setTestState('idle'); setTestMsg(''); }, [config.apiKey]);

  async function runTest() {
    setTestState('loading');
    setTestMsg('');
    try {
      const res = await adminApi.testProvider(provider.id, config.apiKey, config.model);
      setTestState(res.success ? 'ok' : 'fail');
      setTestMsg(res.message);
    } catch {
      setTestState('fail');
      setTestMsg('خطا در ارتباط با سرور');
    }
  }

  return (
    <div className={`rounded-2xl border bg-white p-5 flex flex-col gap-4 transition ${config.enabled ? 'border-brand shadow-[0_2px_18px_rgba(0,104,255,0.06)]' : 'border-line'}`}>

      {/* Header: toggle + provider identity */}
      <div className="flex items-start justify-between gap-3">
        <button type="button" onClick={() => onChange({ ...config, enabled: !config.enabled })}
          className={`relative w-11 h-6 rounded-full transition shrink-0 mt-0.5 ${config.enabled ? 'bg-brand' : 'bg-line'}`}>
          <span className={`absolute top-0.5 size-5 rounded-full bg-white shadow transition-all ${config.enabled ? 'right-0.5' : 'right-[22px]'}`} />
        </button>
        <div className="flex items-center gap-3 flex-1 min-w-0" dir="rtl">
          <div className="size-12 shrink-0 rounded-xl overflow-hidden grid place-items-center">
            <ProviderLogo provider={provider} />
          </div>
          <div className="flex flex-col gap-1 min-w-0">
            <span className="text-[14px] font-bold text-ink-900">{provider.name}</span>
            <span className="text-[12px] text-ink-500 leading-5">{provider.description}</span>
          </div>
        </div>
      </div>

      {/* Expanded config + connection test */}
      {config.enabled && (
        <div className="flex flex-col gap-3 pt-3 border-t border-line">
          {provider.tokenUrl && (
            <a
              href={provider.tokenUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="self-start inline-flex items-center gap-1.5 h-7 px-3 rounded-lg bg-surface-50 border border-line text-[11px] text-ink-600 hover:border-brand hover:text-brand transition"
            >
              <ExternalLink size={11} />
              <span>دریافت کلید API از {provider.name}</span>
            </a>
          )}
          <Field label="کلید API">
            <Input dir="ltr" placeholder="sk-..." value={config.apiKey}
              onChange={(e) => onChange({ ...config, apiKey: e.target.value })}
              className="text-left font-mono text-[12px]" />
          </Field>
          <Field label="مدل">
            <Select value={config.model} onChange={(e) => onChange({ ...config, model: e.target.value })}>
              {provider.modelGroups.map((group) =>
                group.label
                  ? <optgroup key={group.label} label={group.label}>
                      {group.models.map((m) => <option key={m} value={m}>{m}</option>)}
                    </optgroup>
                  : group.models.map((m) => <option key={m} value={m}>{m}</option>)
              )}
            </Select>
          </Field>

          {/* Connection test row */}
          <div className="flex items-center gap-3 pt-1">
            <button
              type="button"
              onClick={runTest}
              disabled={!config.apiKey.trim() || testState === 'loading'}
              className="h-8 px-4 rounded-lg border border-line text-[12px] text-ink-700 hover:border-brand hover:text-brand transition disabled:opacity-40 disabled:cursor-not-allowed shrink-0"
            >
              {testState === 'loading' ? 'در حال تست...' : 'تست اتصال'}
            </button>
            {testState === 'ok' && (
              <span className="flex items-center gap-1.5 text-[12px] text-emerald-600">
                <Check size={14} />
                {testMsg}
              </span>
            )}
            {testState === 'fail' && (
              <span className="text-[12px] text-danger">{testMsg}</span>
            )}
          </div>
        </div>
      )}
    </div>
  );
}

// ── Main page ─────────────────────────────────────────────────────────────────

export function AdminSettingsPage() {
  const nav = useNavigate();
  const ALL_MIME_OPTIONS: { key: string; label: string }[] = [
    { key: 'jpg|jpeg|jpe', label: 'تصویر JPEG' },
    { key: 'png',          label: 'تصویر PNG' },
    { key: 'gif',          label: 'تصویر GIF' },
    { key: 'webp',         label: 'تصویر WebP' },
    { key: 'pdf',          label: 'PDF' },
    { key: 'txt',          label: 'متن ساده (TXT)' },
    { key: 'zip',          label: 'فایل فشرده ZIP' },
    { key: 'doc',          label: 'Word (doc)' },
    { key: 'docx',         label: 'Word (docx)' },
    { key: 'xls',          label: 'Excel (xls)' },
    { key: 'xlsx',         label: 'Excel (xlsx)' },
  ];

  const DEFAULT_MIME_KEYS = ALL_MIME_OPTIONS.map((o) => o.key);

  const [settings, setSettings] = useState<Settings>({
    aiEnabled: false, aiMode: 'kb_only', brandColor: '#0068ff', providers: {}, aiTopK: 4, aiMaxBodyChars: 400,
    allowedMimeTypes: DEFAULT_MIME_KEYS, maxUploadSize: 5120,
  });
  const [saving, setSaving]               = useState(false);
  const [noProviderModal, setNoProviderModal] = useState(false);

  useEffect(() => {
    adminApi.settings().then(setSettings).catch(() => {});
  }, []);

  async function saveSettings() {
    setSaving(true);
    try {
      const updated = await adminApi.saveSettings(settings);
      setSettings(updated);
    } finally {
      setSaving(false);
    }
  }

  const providers   = settings.providers ?? {};
  const activeCount = Object.values(providers).filter((c) => c.enabled).length;

  function handleAiToggle(v: boolean) {
    if (v && activeCount === 0) {
      setNoProviderModal(true);
      return; // don't activate — no provider ready
    }
    setSettings((s) => ({ ...s, aiEnabled: v }));
  }

  // Enabling a provider disables all others (only one active at a time).
  // Disabling the last active provider also turns off پاسخ هوشمند.
  function updateProvider(id: string, c: { enabled: boolean; apiKey: string; model: string }) {
    setSettings((s) => {
      const base = { ...s.providers, [id]: c };
      const next = c.enabled
        ? Object.fromEntries(Object.entries(base).map(([k, v]) => [k, k === id ? v : { ...v, enabled: false }]))
        : base;
      const anyActive = Object.values(next).some((v) => v.enabled);
      return { ...s, providers: next, aiEnabled: anyActive ? s.aiEnabled : false };
    });
  }

  return (
    <PageContainer>
      <PageHeader
        title="تنظیمات"
        subtitle="رنگ برند، پاسخ هوشمند و اتصال به ارائه دهندگان"
        action={
          <button onClick={() => nav('/knowledge')}
            className="inline-flex items-center gap-2 h-12 px-5 rounded-xl bg-brand text-white text-[13px] font-medium hover:bg-brand-dark transition">
            <BookOpen size={18} />
            <span>پایگاه دانش</span>
          </button>
        }
      />

      <div className="flex flex-col lg:flex-row gap-8 lg:gap-12">

        {/* ── Right column: brand + AI toggle ───────────────────────────── */}
        <div className="flex flex-col gap-6 flex-1 min-w-0">
          <h3 className="text-[15px] font-bold text-ink-900">شخصی سازی</h3>

          <Toggle
            checked={settings.aiEnabled}
            onChange={handleAiToggle}
            label="پاسخ هوشمند"
            hint="قابلیت پاسخ‌دهی هوشمند بر اساس پایگاه دانش فعال می‌شود"
          />

          {settings.aiEnabled && (
            <div className="flex flex-col gap-4 rounded-2xl border border-brand-soft bg-brand-tint/40 p-4">
              <div className="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5">
                <span className="text-amber-500 text-[16px] shrink-0 mt-px">⚠</span>
                <p className="text-[12px] text-amber-800 leading-6 text-right">
                  هر درخواست به هوش مصنوعی هزینه دارد. مقادیر بزرگ‌تر پاسخ‌های دقیق‌تر اما هزینه‌ی بیشتری ایجاد می‌کنند.
                </p>
              </div>
              {/* AI mode selector */}
              <div className="flex flex-col gap-2">
                <span className="text-[13px] font-bold text-ink-900 text-right">حالت پاسخ‌دهی</span>
                {([
                  { value: 'kb_only',      label: 'فقط پایگاه دانش',            hint: 'اگر جواب در پایگاه دانش نباشد، سوال به کارشناس ارجاع می‌شود' },
                  { value: 'ai_enhanced',  label: 'پایگاه دانش + دانش هوش مصنوعی', hint: 'اگر پایگاه دانش کافی نبود، هوش مصنوعی از دانش خودش کمک می‌گیرد' },
                ] as const).map((opt) => (
                  <label key={opt.value} className="flex items-start gap-3 cursor-pointer">
                    <input
                      type="radio"
                      name="aiMode"
                      value={opt.value}
                      checked={(settings.aiMode ?? 'kb_only') === opt.value}
                      onChange={() => setSettings((s) => ({ ...s, aiMode: opt.value }))}
                      className="mt-0.5 accent-brand shrink-0"
                    />
                    <div className="flex flex-col gap-0.5">
                      <span className="text-[13px] text-ink-900">{opt.label}</span>
                      <span className="text-[11px] text-ink-400 leading-5">{opt.hint}</span>
                    </div>
                  </label>
                ))}
              </div>

              <Field
                label="تعداد پاسخ‌های ارسالی به هوش مصنوعی (TOP K)"
                hint="سیستم بهترین N پاسخ را به هوش مصنوعی می‌فرستد. عدد بزرگ‌تر = هزینه بالاتر."
              >
                <Input
                  type="number" dir="ltr" className="text-left"
                  value={String(settings.aiTopK ?? 4)}
                  onChange={(e) => setSettings((s) => ({ ...s, aiTopK: Math.max(1, Math.min(10, Number(e.target.value))) }))}
                />
              </Field>
              <Field
                label="حداکثر طول هر پاسخ — کاراکتر (MAX BODY)"
                hint="بدنه‌ی هر پاسخ آماده تا این تعداد کوتاه می‌شود. عدد کمتر = هزینه پایین‌تر."
              >
                <Input
                  type="number" dir="ltr" className="text-left"
                  value={String(settings.aiMaxBodyChars ?? 400)}
                  onChange={(e) => setSettings((s) => ({ ...s, aiMaxBodyChars: Math.max(100, Math.min(2000, Number(e.target.value))) }))}
                />
              </Field>
            </div>
          )}

          {/* ── File upload settings ─────────────────────────────── */}
          <div className="flex flex-col gap-3">
            <h3 className="text-[15px] font-bold text-ink-900">آپلود فایل</h3>

            <Field
              label="حداکثر حجم فایل (کیلوبایت)"
              hint="کاربران نمی‌توانند فایلی بزرگ‌تر از این مقدار آپلود کنند. پیش‌فرض: ۵۱۲۰ (پنج مگابایت)."
            >
              <Input
                type="number" dir="ltr" className="text-left"
                value={String(settings.maxUploadSize ?? 5120)}
                onChange={(e) => setSettings((s) => ({ ...s, maxUploadSize: Math.max(1, Math.min(102400, Number(e.target.value))) }))}
              />
            </Field>

            <div className="flex flex-col gap-2">
              <span className="text-[13px] font-bold text-ink-900 text-right">فرمت‌های مجاز</span>
              <div className="grid grid-cols-2 gap-2">
                {ALL_MIME_OPTIONS.map((opt) => {
                  const checked = (settings.allowedMimeTypes ?? DEFAULT_MIME_KEYS).includes(opt.key);
                  return (
                    <label key={opt.key} className="flex items-center gap-2 cursor-pointer select-none">
                      <input
                        type="checkbox"
                        checked={checked}
                        className="accent-brand"
                        onChange={() => {
                          setSettings((s) => {
                            const current = s.allowedMimeTypes ?? DEFAULT_MIME_KEYS;
                            const next = checked
                              ? current.filter((k) => k !== opt.key)
                              : [...current, opt.key];
                            return { ...s, allowedMimeTypes: next };
                          });
                        }}
                      />
                      <span className="text-[12px] text-ink-700">{opt.label}</span>
                    </label>
                  );
                })}
              </div>
            </div>
          </div>

          <Field label="رنگ برند" hint="کد رنگ HEX برند — رنگ دکمه‌ها و عناصر اصلی همین رنگ می‌شوند.">
            <div className="relative">
              <Input
                value={settings.brandColor}
                onChange={(e) => {
                  setSettings((s) => ({ ...s, brandColor: e.target.value }));
                  applyBrandColor(e.target.value);
                }}
                placeholder="#0068ff"
                dir="ltr"
                className="pl-12 text-left"
              />
              <span
                className="absolute left-3 top-1/2 -translate-y-1/2 size-6 rounded-full border border-line"
                style={{ background: settings.brandColor }}
              />
            </div>
          </Field>

          <div className="pt-2">
            <Button variant="primary" size="md" onClick={saveSettings} disabled={saving}>
              {saving ? 'در حال ذخیره...' : 'ذخیره تنظیمات'}
            </Button>
          </div>
        </div>

        {/* Divider */}
        <div className="hidden lg:block w-px bg-line" />
        <div className="h-px bg-line lg:hidden" />

        {/* ── Left column: AI providers ──────────────────────────────────── */}
        <div className="flex flex-col gap-4 flex-1 min-w-0">
          <div className="flex items-center gap-3">
            <h3 className="text-[15px] font-bold text-ink-900">یکپارچه‌سازی هوش مصنوعی</h3>
            {activeCount > 0 && <Label color="primary" size="sm">{activeCount} فعال</Label>}
          </div>
          <div className="flex flex-col gap-3">
            {AI_PROVIDERS.filter((p) => p.id === 'gapcode').map((p) => (
              <AiProviderCard
                key={p.id}
                provider={p}
                config={providers[p.id] ?? { enabled: false, apiKey: '', model: p.modelGroups[0].models[0] }}
                onChange={(c) => updateProvider(p.id, c)}
              />
            ))}
          </div>
        </div>

      </div>
      {/* No-provider modal */}
      {noProviderModal && (
        <div
          className="fixed inset-0 z-50 grid place-items-center bg-ink-900/40 p-4"
          onClick={() => setNoProviderModal(false)}
        >
          <div
            dir="rtl"
            onClick={(e) => e.stopPropagation()}
            className="bg-white rounded-3xl border border-line p-6 max-w-sm w-full flex flex-col gap-4 shadow-xl"
          >
            <div className="flex flex-col gap-1.5">
              <h2 className="text-[16px] font-bold text-ink-900">هوش مصنوعی فعال نیست</h2>
              <p className="text-[13px] text-ink-500 leading-7">
                برای فعال‌سازی پاسخ هوشمند، ابتدا باید حداقل یک ارائه‌دهنده هوش مصنوعی را از ستون مقابل
                فعال کرده و کلید API آن را وارد کنید.
              </p>
            </div>
            <Button variant="primary" onClick={() => setNoProviderModal(false)}>متوجه شدم</Button>
          </div>
        </div>
      )}
    </PageContainer>
  );
}
