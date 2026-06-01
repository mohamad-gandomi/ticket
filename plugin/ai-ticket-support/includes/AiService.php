<?php
declare(strict_types=1);

namespace ATS;

/**
 * Generates AI-powered answer suggestions for newly submitted tickets.
 *
 * Cost strategy
 * ─────────────
 * 1. Filter saved answers by the ticket's category (falls back to all).
 * 2. Score each answer by keyword overlap with the user's message.
 *    Answers with zero overlap are skipped entirely — no AI call made.
 * 3. Take only the top aiTopK answers as a knowledge base (admin-configurable).
 * 4. Single AI call: model generates a fresh answer from that knowledge base.
 *    It does not copy-paste; it synthesises a clear, concise response.
 */
final class AiService {

    private const DEFAULT_MODEL  = 'gapgpt-qwen-3.5';
    private const TOP_K          = 4;   // fallback when setting is missing
    private const MAX_BODY_CHARS = 1200; // fallback when setting is missing

    private const SYSTEM_PROMPT_KB_ONLY = <<<'SYS'
تو یک دستیار پشتیبانی فنی هستی. وظیفه‌ات نوشتن یک پاسخ دقیق برای سوال کاربر است.

قوانین:
- فقط از اطلاعات موجود در پایگاه دانش زیر استفاده کن. هیچ اطلاعات اضافه‌ای خارج از آن ذکر نکن.
- پاسخ را با کلمات خودت بنویس. متن پایگاه دانش را کپی نکن.
- اگر پایگاه دانش مراحل شماره‌گذاری شده دارد، همه آن مراحل را بنویس — هیچ مرحله‌ای را حذف، ادغام یا خلاصه نکن. اعداد دقیق (مثل ۷۵۵، ۶۴۴، ۷۲ ساعت) را عیناً بنویس.
- اگر سوال کاربر چند موضوع دارد، برای هر موضوع جداگانه پاسخ بده.
- مراحل را با اعداد فارسی شماره‌گذاری کن: ۱. ۲. ۳.
- اگر در پایگاه دانش لینکی وجود دارد، آن را عیناً در پاسخ ذکر کن.
- از ایموجی استفاده نکن.
- زبان ساده و قابل فهم به کار ببر.
- هرگز پاسخ را با جملاتی مثل این‌ها تمام نکن: «اگر سوال دیگری دارید در خدمتم»، «موفق باشید»، «در صورت نیاز با پشتیبانی تماس بگیرید»، «امیدوارم مشکلتان حل شده باشد»، «لطفاً مجدداً تماس بگیرید». فقط پاسخ را بنویس و تمام.
- از علامت‌های مارک‌داون مثل ** یا # یا * استفاده نکن. به جای **متن** فقط بنویس: متن.
- اگر اطلاعات کافی در پایگاه دانش برای پاسخ دادن وجود ندارد، دقیقاً بنویس: پاسخ مناسبی یافت نشد
SYS;

    private const SYSTEM_PROMPT_AI_ENHANCED = <<<'SYS'
تو یک دستیار پشتیبانی فنی هستی. وظیفه‌ات نوشتن یک پاسخ دقیق برای سوال کاربر است.

قوانین:
- ابتدا از اطلاعات پایگاه دانش زیر استفاده کن. اگر پایگاه دانش کافی نبود، می‌توانی از دانش فنی عمومی خودت کمک بگیری.
- اطلاعاتی که مطمئن نیستی درست باشند را ذکر نکن.
- پاسخ را با کلمات خودت بنویس.
- اگر پایگاه دانش مراحل شماره‌گذاری شده دارد، همه آن مراحل را بنویس — هیچ مرحله‌ای را حذف، ادغام یا خلاصه نکن. اعداد دقیق را عیناً بنویس.
- اگر سوال کاربر چند موضوع دارد، برای هر موضوع جداگانه پاسخ بده.
- مراحل را با اعداد فارسی شماره‌گذاری کن: ۱. ۲. ۳.
- اگر در پایگاه دانش لینکی وجود دارد، آن را عیناً در پاسخ ذکر کن.
- از ایموجی استفاده نکن.
- زبان ساده و قابل فهم به کار ببر.
- هرگز پاسخ را با جملاتی مثل این‌ها تمام نکن: «اگر سوال دیگری دارید در خدمتم»، «موفق باشید»، «در صورت نیاز با پشتیبانی تماس بگیرید»، «امیدوارم مشکلتان حل شده باشد». فقط پاسخ را بنویس و تمام.
- از علامت‌های مارک‌داون مثل ** یا # یا * استفاده نکن. به جای **متن** فقط بنویس: متن.
- اگر اطلاعات کافی نداری، دقیقاً بنویس: پاسخ مناسبی یافت نشد
SYS;

    public function suggest(array $ticket, string $user_message): ?string {
        // Load settings once for this request.
        $settings = (array) get_option('ats_settings', []);

        $client = $this->resolve_client($settings);
        if ($client === null) {
            return null;
        }

        $category_id = $ticket['category_id'] ? (int) $ticket['category_id'] : null;
        $pool        = $this->load_answers($category_id);
        if (empty($pool)) {
            return null;
        }

        $top_k = max(1, min(10, (int) ($settings['aiTopK'] ?? self::TOP_K)));
        $top   = $this->top_k($user_message, $pool, $top_k);
        if (empty($top)) {
            return null; // no keyword overlap — skip AI call entirely
        }

        $max_body     = max(100, min(2000, (int) ($settings['aiMaxBodyChars'] ?? self::MAX_BODY_CHARS)));
        $prompt       = $this->build_prompt($ticket['title'], $user_message, $top, $max_body);
        $model        = $this->resolve_model($settings);
        $system_prompt = ($settings['aiMode'] ?? 'kb_only') === 'ai_enhanced'
            ? self::SYSTEM_PROMPT_AI_ENHANCED
            : self::SYSTEM_PROMPT_KB_ONLY;

        $result = $client->respond($model, $system_prompt, $prompt);

        if ($result === null || mb_stripos($result, 'پاسخ مناسبی یافت نشد') !== false) {
            return null;
        }

        $result = trim($result);
        return $result !== '' ? $result : null;
    }

    // ── Retrieval ─────────────────────────────────────────────────────────────

    private function load_answers(?int $category_id): array {
        // Always search the full KB so a wrong-category ticket still gets help.
        // The keyword scorer naturally surfaces the most relevant answers.
        return Database::instance()->get_saved_answers();
    }

    private function top_k(string $user_message, array $answers, int $top_k): array {
        $words = $this->tokenize($user_message);
        if (empty($words)) {
            return [];
        }

        $scored = [];
        foreach ($answers as $answer) {
            $score = $this->score($words, $answer);
            if ($score > 0) {
                $scored[] = ['answer' => $answer, 'score' => $score];
            }
        }

        if (empty($scored)) {
            return [];
        }

        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_column(array_slice($scored, 0, $top_k), 'answer');
    }

    private function score(array $words, array $answer): int {
        $haystack = mb_strtolower(strip_tags($answer['title'] . ' ' . $answer['body']));
        $score    = 0;
        foreach ($words as $word) {
            if (str_contains($haystack, $word)) {
                $score++;
            }
        }
        return $score;
    }

    private function html_to_text(string $html): string {
        $text = preg_replace_callback(
            '/<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is',
            static fn($m) => trim(strip_tags($m[2])) . ' (' . $m[1] . ')',
            $html
        );
        return strip_tags((string) $text);
    }

    private function tokenize(string $text): array {
        $text  = mb_strtolower(strip_tags($text));
        $words = preg_split('/[\s\.,،؛:!\?؟\-\/\(\)]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_unique(array_filter($words, fn($w) => mb_strlen($w) > 2)));
    }

    // ── Prompt ────────────────────────────────────────────────────────────────

    private function build_prompt(string $title, string $message, array $answers, int $max_body): string {
        $kb = '';
        foreach ($answers as $i => $a) {
            $body = $this->html_to_text((string) $a['body']);
            $body = preg_replace('/\s+/', ' ', trim($body));
            if (mb_strlen($body) > $max_body) {
                $body = mb_substr($body, 0, $max_body) . '…';
            }
            $n   = $i + 1;
            $kb .= "--- مورد {$n} ---\nعنوان: {$a['title']}\nمحتوا: {$body}\n\n";
        }

        $user_text = trim(strip_tags($message));

        return <<<PROMPT
پایگاه دانش:
{$kb}
سوال کاربر:
عنوان تیکت: {$title}
متن: {$user_text}

بر اساس پایگاه دانش بالا، پاسخ مناسب را بنویس.
PROMPT;
    }

    // ── Config ────────────────────────────────────────────────────────────────

    private function resolve_client(array $settings): ?GapGptClient {
        $providers = (array) ($settings['providers'] ?? []);
        $gapcode   = (array) ($providers['gapcode']  ?? []);

        if (empty($gapcode['enabled']) || empty($gapcode['apiKey'])) {
            return null;
        }

        return new GapGptClient((string) $gapcode['apiKey']);
    }

    private function resolve_model(array $settings): string {
        $providers = (array) ($settings['providers'] ?? []);
        $gapcode   = (array) ($providers['gapcode']  ?? []);
        return ! empty($gapcode['model']) ? (string) $gapcode['model'] : self::DEFAULT_MODEL;
    }
}
