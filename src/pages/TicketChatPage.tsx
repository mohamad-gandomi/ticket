import { useParams }         from 'react-router-dom';
import { PageContainer }      from '../components/PageContainer';
import { PageHeader }         from '../components/PageHeader';
import { ChatBubble, ChatHeaderBar } from '../components/Chat';
import { Button }             from '../components/Button';
import { Label }              from '../components/Label';
import { Select }             from '../components/FormControls';
import { useState, useEffect } from 'react';
import { RichEditor }         from '../components/RichEditor';
import { AttachmentsUploader } from '../components/AttachmentsUploader';
import { getConfig }          from '../config';
import { ApiError }           from '../api/client';
import { ticketsApi, type Ticket, type Message } from '../api/tickets';
import { adminApi, type AdminState, adminStateMap } from '../api/admin';
import { toShamsi, relativeTime } from '../utils/date';


export function TicketChatPage() {
  const { id }     = useParams<{ id: string }>();
  const config     = getConfig();
  const { mode }   = config;
  const isAdmin    = mode === 'admin';

  const [ticket, setTicket]     = useState<Ticket | null>(null);
  const [messages, setMessages] = useState<Message[]>([]);
  const [draft, setDraft]             = useState('');
  const [pendingFiles, setPendingFiles] = useState<File[]>([]);
  const [loading, setLoading]         = useState(true);
  const [error, setError]             = useState<string | null>(null);
  const [sending, setSending]         = useState(false);
  const [uploadError, setUploadError] = useState<string | null>(null);

  useEffect(() => {
    if (!id) return;
    let active = true;
    setLoading(true);
    setError(null);

    ticketsApi.detail(id)
      .then((res) => {
        if (!active) return;
        setTicket(res.ticket);
        setMessages(res.messages);
      })
      .catch((e) => { if (active) setError(e.message); })
      .finally(() => { if (active) setLoading(false); });

    return () => { active = false; };
  }, [id]);

  async function send() {
    if (!draft.trim() || !id) return;
    setSending(true);
    setUploadError(null);
    try {
      const res = isAdmin
        ? await adminApi.replyToTicket(id, draft)
        : await ticketsApi.sendMessage(id, draft);

      const newMsgId = res.messages[res.messages.length - 1]?.id;
      if (newMsgId && pendingFiles.length > 0) {
        const results = await Promise.allSettled(
          pendingFiles.map((f) => ticketsApi.uploadAttachment(id, f, newMsgId))
        );
        const failures = results.filter((r): r is PromiseRejectedResult => r.status === 'rejected');
        if (failures.length > 0) {
          const msgs = failures.map((r) =>
            r.reason instanceof ApiError ? r.reason.message : 'خطا در آپلود فایل'
          );
          setUploadError(msgs.join(' — '));
        } else {
          setPendingFiles([]);
        }
      }

      const detail = await ticketsApi.detail(id);
      setMessages(detail.messages);
      setDraft('');
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : 'خطا در ارسال پیام');
    } finally {
      setSending(false);
    }
  }

  async function changeState(newState: AdminState) {
    if (!id || !ticket) return;
    const updated = await adminApi.updateTicketState(id, newState);
    setTicket((prev) => prev ? { ...prev, adminState: updated.state } : prev);
  }

  if (loading) {
    return (
      <PageContainer>
        <div className="text-center py-20 text-ink-500 text-[13px]">در حال بارگذاری...</div>
      </PageContainer>
    );
  }

  if (error || !ticket) {
    return (
      <PageContainer>
        <div className="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-[13px] text-danger">
          {error ?? 'تیکت یافت نشد.'}
        </div>
      </PageContainer>
    );
  }

  const stateInfo = isAdmin && ticket.adminState ? adminStateMap[ticket.adminState as AdminState] : null;

  return (
    <PageContainer>
      <PageHeader title={ticket.title} subtitle="گفتگو با تیم پشتیبانی" />

      {isAdmin && stateInfo && (
        <div className="flex flex-col sm:flex-row sm:items-center justify-between rounded-2xl border border-line bg-white px-4 sm:px-5 py-3 gap-3 sm:gap-4">
          <div className="flex items-center gap-2">
            <span className="text-[12px] font-medium text-ink-500">وضعیت:</span>
            <Label color={stateInfo.color}>{stateInfo.label}</Label>
          </div>
          <div className="flex items-center gap-2">
            <span className="text-[12px] text-ink-500 shrink-0">تغییر وضعیت:</span>
            <Select
              value={ticket.adminState}
              onChange={(e) => changeState(e.target.value as AdminState)}
              className="!w-full sm:!w-auto sm:min-w-[160px] !h-8 !text-[12px]"
            >
              <option value="unreviewed">بررسی نشده</option>
              <option value="reviewing">درحال بررسی</option>
              <option value="pending">در انتظار پاسخ</option>
              <option value="answered">پاسخ داده شده</option>
              <option value="ai_resolved">پاسخ هوشمند</option>
              <option value="closed">بسته شده</option>
              <option value="spam">اسپم</option>
            </Select>
          </div>
        </div>
      )}

      <section className="rounded-3xl border border-line bg-white p-4 sm:p-6 flex flex-col gap-4">
        <ChatHeaderBar
          id={ticket.id}
          date={toShamsi(ticket.createdAt)}
          time={ticket.createdAt.slice(11, 16)}
          ago={relativeTime(ticket.updatedAt)}
          status="پاسخ پشتیبان"
        />

        <div className="flex flex-col gap-3 max-h-[320px] overflow-auto thin-scroll p-1">
          {messages.map((m) => (
            <ChatBubble key={m.id} msg={{
              id:          m.id,
              author:      m.authorType,
              authorName:  m.authorName,
              body:        m.body,
              date:        toShamsi(m.createdAt),
              time:        m.createdAt.slice(11, 16),
              attachments: m.attachments,
            }} />
          ))}
        </div>

        {ticket.adminState !== 'closed' && ticket.adminState !== 'ai_resolved' && (
          <>
            <div className="h-px bg-line" />
            <div className="flex flex-col gap-1.5">
              <span className="text-[13px] font-bold text-ink-900 text-right">پیام تیکت</span>
              <RichEditor
                placeholder="مشکل خود را با جزئیات کامل توضیح دهید..."
                onChange={setDraft}
              />
            </div>
            <AttachmentsUploader
              onFilesChange={setPendingFiles}
              allowedMimeTypes={config.allowedMimeTypes}
              maxUploadSizeKb={config.maxUploadSize}
              uploadError={uploadError}
            />
            <div className="h-px bg-line" />
            <div className="flex">
              <Button variant="primary" size="md" onClick={send} disabled={sending}>
                {sending ? 'در حال ارسال...' : 'ثبت پیام'}
              </Button>
            </div>
          </>
        )}
      </section>
    </PageContainer>
  );
}
