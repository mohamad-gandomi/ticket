import { useRef, useState, useEffect } from 'react';
import { Close } from '../icons';

interface AttachedFile {
  id: string;
  file: File;
}

export interface ExistingFile {
  id: string;
  name: string;
  size: number;
}

function formatSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function getExt(name: string): string {
  const parts = name.split('.');
  return parts.length > 1 ? parts[parts.length - 1].toUpperCase() : 'FILE';
}

/** Convert MIME key like "jpg|jpeg|jpe" to an accept-attribute fragment ".jpg,.jpeg,.jpe" */
function mimeKeyToAcceptExts(key: string): string {
  return key.split('|').map((e) => `.${e}`).join(',');
}

/** Human-readable label: first extension in uppercase */
function mimeKeyToLabel(key: string): string {
  return key.split('|')[0].toUpperCase();
}

/** Build the full accept string for <input type="file"> */
function buildAccept(mimeKeys: string[]): string {
  return mimeKeys.map(mimeKeyToAcceptExts).join(',');
}

/** Check if a File's extension matches any allowed MIME key */
function isExtAllowed(file: File, mimeKeys: string[]): boolean {
  const ext = file.name.split('.').pop()?.toLowerCase() ?? '';
  return mimeKeys.some((key) => key.split('|').includes(ext));
}

function FileChip({ name, size, onRemove }: { name: string; size: number; onRemove: () => void }) {
  return (
    <div className="flex items-center gap-2 h-9 pl-2 pr-3 rounded-lg border border-line bg-white text-[12px] max-w-full">
      <span className="shrink-0 px-1.5 py-0.5 rounded-md bg-brand-tint text-brand text-[10px] font-bold">
        {getExt(name)}
      </span>
      <span className="truncate text-ink-700 min-w-0">{name}</span>
      <span className="shrink-0 text-ink-400 tabular">{formatSize(size)}</span>
      <button
        type="button"
        onClick={onRemove}
        className="shrink-0 size-4 grid place-items-center text-ink-400 hover:text-danger transition"
      >
        <Close size={12} />
      </button>
    </div>
  );
}

export function AttachmentsUploader({
  defaultFiles = [],
  allowedMimeTypes,
  maxUploadSizeKb,
  uploadError,
  onFilesChange,
}: {
  defaultFiles?: ExistingFile[];
  allowedMimeTypes?: string[];
  maxUploadSizeKb?: number;
  uploadError?: string | null;
  onFilesChange?: (files: File[]) => void;
}) {
  const inputRef = useRef<HTMLInputElement>(null);
  const [existing, setExisting] = useState<ExistingFile[]>(defaultFiles);
  const [files, setFiles]       = useState<AttachedFile[]>([]);
  const [validationError, setValidationError] = useState<string | null>(null);

  const mimeKeys   = allowedMimeTypes ?? ['jpg|jpeg|jpe', 'png', 'gif', 'webp', 'pdf'];
  const maxKb      = maxUploadSizeKb ?? 5120;
  const maxBytes   = maxKb * 1024;
  const acceptAttr = buildAccept(mimeKeys);
  const formatList = mimeKeys.map(mimeKeyToLabel).join('، ');
  const maxLabel   = maxKb >= 1024 ? `${(maxKb / 1024).toFixed(maxKb % 1024 === 0 ? 0 : 1)} MB` : `${maxKb} KB`;

  useEffect(() => {
    onFilesChange?.(files.map((f) => f.file));
  }, [files]); // eslint-disable-line react-hooks/exhaustive-deps

  function handleAdd(e: React.ChangeEvent<HTMLInputElement>) {
    setValidationError(null);
    const selected = Array.from(e.target.files ?? []);
    e.target.value = '';

    const errors: string[] = [];
    const valid: AttachedFile[] = [];

    for (const file of selected) {
      if (!isExtAllowed(file, mimeKeys)) {
        errors.push(`فرمت "${getExt(file.name)}" مجاز نیست.`);
        continue;
      }
      if (file.size > maxBytes) {
        errors.push(`فایل "${file.name}" بزرگ‌تر از حد مجاز (${maxLabel}) است.`);
        continue;
      }
      valid.push({
        id: `${file.name}-${file.size}-${Date.now()}-${Math.random()}`,
        file,
      });
    }

    if (errors.length > 0) {
      setValidationError(errors.join(' '));
    }
    if (valid.length > 0) {
      setFiles((prev) => [...prev, ...valid]);
    }
  }

  function removeFile(id: string) {
    setFiles((prev) => prev.filter((x) => x.id !== id));
  }

  const hasFiles    = existing.length > 0 || files.length > 0;
  const displayError = uploadError ?? validationError;

  return (
    <div className="flex flex-col gap-2">
      <span className="text-[13px] font-bold text-ink-900 text-right">ضمیمه فایل</span>

      <button
        type="button"
        onClick={() => { setValidationError(null); inputRef.current?.click(); }}
        className="w-full flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-line bg-surface-50 hover:border-brand hover:bg-brand-tint/30 transition py-5 px-4 cursor-pointer"
      >
        <div className="size-9 rounded-full bg-white border border-line grid place-items-center text-ink-400">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            <polyline points="17 8 12 3 7 8" />
            <line x1="12" y1="3" x2="12" y2="15" />
          </svg>
        </div>
        <div className="text-center">
          <p className="text-[13px] font-medium text-ink-700">برای آپلود کلیک کنید</p>
          <p className="text-[11px] text-ink-400 mt-0.5">{formatList}</p>
          <p className="text-[11px] text-ink-400">حداکثر {maxLabel}</p>
        </div>
      </button>

      <input
        ref={inputRef}
        type="file"
        accept={acceptAttr}
        multiple
        className="hidden"
        onChange={handleAdd}
      />

      {displayError && (
        <p className="text-[12px] text-danger text-right leading-5 rounded-lg border border-red-200 bg-red-50 px-3 py-2">
          {displayError}
        </p>
      )}

      {hasFiles && (
        <div className="flex flex-wrap gap-2 pt-1">
          {existing.map((f) => (
            <FileChip
              key={f.id}
              name={f.name}
              size={f.size}
              onRemove={() => setExisting((prev) => prev.filter((x) => x.id !== f.id))}
            />
          ))}
          {files.map(({ id, file }) => (
            <FileChip
              key={id}
              name={file.name}
              size={file.size}
              onRemove={() => removeFile(id)}
            />
          ))}
        </div>
      )}
    </div>
  );
}
