import { useState, useRef }  from 'react';
import { useNavigate }        from 'react-router-dom';
import { PageContainer }      from '../components/PageContainer';
import { PageHeader }         from '../components/PageHeader';
import { TicketComposer }     from '../components/TicketComposer';
import { AiLoadingPanel }     from '../components/AiLoadingPanel';
import { ticketsApi }         from '../api/tickets';
import { getConfig }          from '../config';

export function TicketNewPage() {
  const navigate    = useNavigate();
  const filesRef    = useRef<File[]>([]);
  const config      = getConfig();
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit(payload: { title: string; body: string; priority: string; category_id?: number | null }) {
    setSubmitting(true);
    try {
      const ticket = await ticketsApi.create(payload);
      const files = filesRef.current;
      if (files.length > 0) {
        await Promise.all(
          files.map((f) => ticketsApi.uploadAttachment(ticket.id, f, ticket.firstMessageId))
        );
      }
      if (ticket.aiStatus === 'done' && ticket.aiSuggestion) {
        navigate(`/tickets/${ticket.id}/ai-show`);
      } else {
        navigate(`/tickets/${ticket.id}`);
      }
    } catch {
      setSubmitting(false);
    }
  }

  if (submitting) {
    return (
      <PageContainer>
        <PageHeader title="در حال تولید پاسخ" subtitle="پاسخ هوشمند ما در حال آماده شدن است" />
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
          <div className="flex justify-end">
            <TicketComposer showSubmit={false} showCancel={false} onSubmit={handleSubmit} onCancel={() => {}} />
          </div>
          <AiLoadingPanel />
        </div>
      </PageContainer>
    );
  }

  return (
    <PageContainer>
      <PageHeader title="ثبت تیکت جدید" subtitle="پشتیبانی هوشمند، آماده پاسخگویی به سوالات شما" />
      <div className="flex">
        <TicketComposer
          onSubmit={handleSubmit}
          onCancel={() => navigate('/tickets')}
          onFilesChange={(files) => { filesRef.current = files; }}
          allowedMimeTypes={config.allowedMimeTypes}
          maxUploadSizeKb={config.maxUploadSize}
        />
      </div>
    </PageContainer>
  );
}
