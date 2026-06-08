import { useEffect, useRef, useState } from 'react';
import { Bold, Italic, AlignRight, AlignCenter, AlignLeft, Link, ListIcon, MessageText, Close } from '../icons';

interface RichEditorProps {
  placeholder?: string;
  defaultValue?: string;
  onChange?: (html: string) => void;
  clearTrigger?: number;
}

export function RichEditor({ placeholder = '', defaultValue, onChange, clearTrigger }: RichEditorProps) {
  const editorRef = useRef<HTMLDivElement>(null);
  const savedRange = useRef<Range | null>(null);
  const [isEmpty, setIsEmpty] = useState(!defaultValue);

  useEffect(() => {
    if (defaultValue && editorRef.current) {
      editorRef.current.innerHTML = defaultValue;
    }
  }, []);

  useEffect(() => {
    if (clearTrigger === undefined || clearTrigger === 0) return;
    if (editorRef.current) {
      editorRef.current.innerHTML = '';
      setIsEmpty(true);
      onChange?.('');
    }
  }, [clearTrigger]);
  const [showLinkInput, setShowLinkInput] = useState(false);
  const [linkUrl, setLinkUrl] = useState('');

  function execFormat(command: string, value?: string) {
    editorRef.current?.focus();
    document.execCommand(command, false, value);
  }

  function applyAlign(align: 'Left' | 'Center' | 'Right') {
    editorRef.current?.focus();
    document.execCommand(`justify${align}`);

    // sync list direction so bullets follow the alignment
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return;
    const range = sel.getRangeAt(0);
    const ancestor = range.commonAncestorContainer as HTMLElement;
    const el = ancestor.nodeType === Node.TEXT_NODE ? ancestor.parentElement! : ancestor;
    const lists: HTMLElement[] = [];
    const parentList = el.closest<HTMLElement>('ul,ol');
    if (parentList) lists.push(parentList);
    el.querySelectorAll<HTMLElement>('ul,ol').forEach((l) => lists.push(l));
    lists.forEach((list) => {
      if (align === 'Left') {
        list.style.direction = 'ltr';
        list.style.paddingLeft = '1.5em';
        list.style.paddingRight = '0';
      } else {
        list.style.direction = 'rtl';
        list.style.paddingRight = '1.5em';
        list.style.paddingLeft = '0';
      }
    });
  }

  function insertList() {
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return;
    const range = sel.getRangeAt(0);
    const selectedText = range.toString();

    const ul = document.createElement('ul');
    ul.style.cssText = 'direction:rtl;padding-right:1.5em;margin:4px 0;list-style-type:disc;';
    const lines = selectedText ? selectedText.split('\n') : [''];
    lines.forEach((line) => {
      const li = document.createElement('li');
      li.textContent = line;
      ul.appendChild(li);
    });

    range.deleteContents();
    range.insertNode(ul);

    const lastLi = ul.lastElementChild as HTMLLIElement;
    const newRange = document.createRange();
    newRange.setStart(lastLi, lastLi.childNodes.length);
    newRange.collapse(true);
    sel.removeAllRanges();
    sel.addRange(newRange);
  }

  function insertQuote() {
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return;
    const range = sel.getRangeAt(0);
    const selectedText = range.toString();

    const bq = document.createElement('blockquote');
    bq.style.cssText = 'border-right:3px solid #e5e7eb;margin:4px 0;padding:2px 12px;color:#9ca3af;font-style:italic;';
    bq.textContent = selectedText || '​';

    range.deleteContents();
    range.insertNode(bq);

    const newRange = document.createRange();
    const textNode = bq.firstChild;
    if (textNode) {
      newRange.setStart(textNode, textNode.textContent?.length ?? 0);
    } else {
      newRange.setStart(bq, 0);
    }
    newRange.collapse(true);
    sel.removeAllRanges();
    sel.addRange(newRange);
  }

  function openLinkInput() {
    const sel = window.getSelection();
    if (sel && sel.rangeCount > 0) {
      savedRange.current = sel.getRangeAt(0).cloneRange();
    }
    setLinkUrl('');
    setShowLinkInput(true);
  }

  function confirmLink() {
    const url = linkUrl.trim();
    if (!url) { cancelLink(); return; }

    const range = savedRange.current;
    if (!range) { setShowLinkInput(false); return; }

    const sel = window.getSelection();
    sel?.removeAllRanges();
    sel?.addRange(range);

    const a = document.createElement('a');
    a.href = /^https?:\/\//i.test(url) ? url : `https://${url}`;
    a.target = '_blank';
    a.rel = 'noopener noreferrer';
    a.textContent = range.toString() || url;
    a.style.cssText = 'color:#3b82f6;text-decoration:underline;cursor:pointer;';

    range.deleteContents();
    range.insertNode(a);

    const newRange = document.createRange();
    newRange.setStartAfter(a);
    newRange.collapse(true);
    sel?.removeAllRanges();
    sel?.addRange(newRange);

    savedRange.current = null;
    setShowLinkInput(false);
    editorRef.current?.focus();
  }

  function cancelLink() {
    savedRange.current = null;
    setShowLinkInput(false);
    editorRef.current?.focus();
  }

  const btn = 'size-8 grid place-items-center rounded-md hover:bg-surface-50';

  return (
    <div className="flex flex-col gap-2">
      {/* toolbar */}
      <div className="flex items-center">
        <div className="flex items-center gap-0.5 rounded-xl border border-line bg-white px-2 h-10 text-ink-500">
          <button type="button" onMouseDown={(e) => { e.preventDefault(); execFormat('bold'); }} className={btn} title="Bold">
            <Bold size={16} />
          </button>
          <button type="button" onMouseDown={(e) => { e.preventDefault(); execFormat('italic'); }} className={btn} title="Italic">
            <Italic size={16} />
          </button>
          <span className="w-px h-4 bg-line mx-1" />
          <button type="button" onMouseDown={(e) => { e.preventDefault(); openLinkInput(); }} className={btn} title="Link">
            <Link size={16} />
          </button>
          <button type="button" onMouseDown={(e) => { e.preventDefault(); insertList(); }} className={btn} title="List">
            <ListIcon size={16} />
          </button>
          <button type="button" onMouseDown={(e) => { e.preventDefault(); insertQuote(); }} className={btn} title="Quote">
            <MessageText size={16} />
          </button>
          <span className="w-px h-4 bg-line mx-1" />
          <button type="button" onMouseDown={(e) => { e.preventDefault(); applyAlign('Right'); }} className={btn} title="Align Right">
            <AlignRight size={16} />
          </button>
          <button type="button" onMouseDown={(e) => { e.preventDefault(); applyAlign('Center'); }} className={btn} title="Align Center">
            <AlignCenter size={16} />
          </button>
          <button type="button" onMouseDown={(e) => { e.preventDefault(); applyAlign('Left'); }} className={btn} title="Align Left">
            <AlignLeft size={16} />
          </button>
        </div>
      </div>

      {/* inline link input */}
      {showLinkInput && (
        <div className="flex items-center gap-2 rounded-xl border border-brand bg-white px-3 h-10">
          <input
            autoFocus
            type="text"
            dir="ltr"
            placeholder="https://"
            value={linkUrl}
            onChange={(e) => setLinkUrl(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === 'Enter') { e.preventDefault(); confirmLink(); }
              if (e.key === 'Escape') cancelLink();
            }}
            className="flex-1 text-[13px] text-ink-900 outline-none placeholder:text-ink-400 bg-transparent"
          />
          <button type="button" onClick={confirmLink} className="text-[13px] font-medium text-brand hover:opacity-80 whitespace-nowrap">
            افزودن
          </button>
          <span className="w-px h-4 bg-line" />
          <button type="button" onClick={cancelLink} className="text-ink-400 hover:text-ink-600">
            <Close size={14} />
          </button>
        </div>
      )}

      {/* editor */}
      <div className="relative">
        {isEmpty && (
          <span className="absolute top-4 right-4 text-[13px] text-ink-400 pointer-events-none select-none">
            {placeholder}
          </span>
        )}
        <div
          ref={editorRef}
          contentEditable
          suppressContentEditableWarning
          dir="rtl"
          onInput={(e) => {
            const el = e.currentTarget;
            setIsEmpty(el.textContent?.trim() === '');
            onChange?.(el.innerHTML);
          }}
          onClick={(e) => {
            const a = (e.target as HTMLElement).closest('a');
            if (a) {
              e.preventDefault();
              window.open((a as HTMLAnchorElement).href, '_blank', 'noopener,noreferrer');
            }
          }}
          className="min-h-[200px] w-full rounded-xl border border-line bg-white p-4 text-[13px] leading-7 text-ink-900 focus:border-brand focus:shadow-focus focus:outline-none transition outline-none"
        />
      </div>
    </div>
  );
}
