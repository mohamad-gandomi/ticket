import { useState, useEffect } from 'react';
import { Field, Input, Select } from './FormControls';
import { Button } from './Button';
import { RichEditor } from './RichEditor';
import { AttachmentsUploader } from './AttachmentsUploader';
import { ticketsApi, type Category } from '../api/tickets';

interface SubmitPayload {
  title: string;
  body: string;
  priority: string;
  category_id: number | null;
}

interface TicketComposerProps {
  showCancel?: boolean;
  showSubmit?: boolean;
  onSubmit?: (payload: SubmitPayload) => Promise<void> | void;
  onCancel?: () => void;
  onFilesChange?: (files: File[]) => void;
  submitLabel?: string;
  defaultMessage?: string;
  allowedMimeTypes?: string[];
  maxUploadSizeKb?: number;
}

const PRIORITIES = [
  { value: 'low',    label: 'الویت کم' },
  { value: 'medium', label: 'الویت متوسط' },
  { value: 'high',   label: 'الویت بالا' },
];

export function TicketComposer({
  showCancel = true,
  showSubmit = true,
  onSubmit,
  onCancel,
  onFilesChange,
  submitLabel = 'ارسال تیکت',
  defaultMessage: _defaultMessage,
  allowedMimeTypes,
  maxUploadSizeKb,
}: TicketComposerProps) {
  const [categories, setCategories] = useState<Category[]>([]);
  const [categoryId, setCategoryId] = useState<string>('');
  const [title, setTitle]           = useState('');
  const [priority, setPriority]     = useState<string>('');
  const [body, setBody]             = useState('');
  const [submitted, setSubmitted]   = useState(false);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    ticketsApi.categories().then(setCategories).catch(() => {});
  }, []);

  const errors = {
    category: submitted && !categoryId,
    title:    submitted && !title.trim(),
    priority: submitted && !priority,
    body:     submitted && !body.trim(),
  };

  const isValid = !!categoryId && !!title.trim() && !!priority && !!body.trim();

  async function handleSubmit() {
    setSubmitted(true);
    if (!isValid) return;
    setSubmitting(true);
    try {
      await onSubmit?.({
        title:       title.trim(),
        body:        body.trim(),
        priority,
        category_id: categoryId ? Number(categoryId) : null,
      });
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <section className="rounded-3xl border border-line bg-white p-6 flex flex-col gap-4 w-full max-w-[583px]">
      <Field label="دسته‌بندی" hint="دسته مرتبط با مشکل خود را انتخاب کنید">
        <Select
          value={categoryId}
          onChange={(e) => setCategoryId(e.target.value)}
          className={errors.category ? 'border-danger' : ''}
        >
          <option value="" disabled>انتخاب کنید</option>
          {categories.map((c) => (
            <option key={c.id} value={c.id}>{c.title}</option>
          ))}
        </Select>
        {errors.category && <span className="text-[12px] text-danger text-right">لطفاً موضوع تیکت را انتخاب کنید</span>}
      </Field>

      <Field label="عنوان تیکت" hint="یک عنوان کوتاه برای تیکت انتخاب کنید">
        <Input
          placeholder="مشکل وب"
          value={title}
          onChange={(e) => setTitle(e.target.value)}
          className={errors.title ? 'border-danger' : ''}
        />
        {errors.title && <span className="text-[12px] text-danger text-right">لطفاً عنوان تیکت را وارد کنید</span>}
      </Field>

      <Field label="اولویت" hint="اولویت تیکت خود را مشخص کنید">
        <Select
          value={priority}
          onChange={(e) => setPriority(e.target.value)}
          className={errors.priority ? 'border-danger' : ''}
        >
          <option value="" disabled>انتخاب کنید</option>
          {PRIORITIES.map((p) => (
            <option key={p.value} value={p.value}>{p.label}</option>
          ))}
        </Select>
        {errors.priority && <span className="text-[12px] text-danger text-right">لطفاً اولویت را انتخاب کنید</span>}
      </Field>

      <div className="flex flex-col gap-1.5">
        <span className="text-[13px] font-bold text-ink-900 text-right">پیام تیکت</span>
        <RichEditor placeholder="مشکل خود را با جزئیات کامل توضیح دهید..." onChange={setBody} />
        {errors.body && <span className="text-[12px] text-danger text-right">لطفاً پیام تیکت را وارد کنید</span>}
      </div>

      <AttachmentsUploader
        onFilesChange={onFilesChange}
        allowedMimeTypes={allowedMimeTypes}
        maxUploadSizeKb={maxUploadSizeKb}
      />

      <div className="h-px bg-line my-1" />

      <div className="flex items-center gap-3">
        {showSubmit && (
          <Button variant="primary" size="md" onClick={handleSubmit} disabled={submitting}>
            {submitting ? 'در حال ارسال...' : submitLabel}
          </Button>
        )}
        {showCancel && (
          <Button variant="danger" size="md" onClick={onCancel}>
            لغو ارسال
          </Button>
        )}
      </div>
    </section>
  );
}
