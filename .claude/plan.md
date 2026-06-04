# Plugin Fix Plan

Complete scan found **10 issues** (1 critical, 2 high, 5 medium, 2 low) plus 1 feature request.
All changes are in the PHP plugin only — no frontend changes needed.

---

## Issue 1 — CRITICAL: Unrestricted file upload (potential RCE)
**File:** `TicketsController.php` line 315  
**Problem:** `wp_handle_upload()` is called with no `mimes` restriction. WordPress falls back to site-wide allowed types, which on misconfigured installs can include `.php`. An authenticated user could upload a PHP webshell.  
**Fix:** Pass an explicit safe-only MIME allowlist to `wp_handle_upload()`:
```php
$upload = wp_handle_upload($_FILES['file'], [
    'test_form' => false,
    'mimes'     => [
        'jpg|jpeg|jpe' => 'image/jpeg',
        'png'           => 'image/png',
        'gif'           => 'image/gif',
        'pdf'           => 'application/pdf',
        'txt'           => 'text/plain',
        'zip'           => 'application/zip',
        'doc'           => 'application/msword',
        'docx'          => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ],
]);
```

---

## Issue 2 — HIGH: IDOR — cross-ticket `message_id` on attachment upload
**File:** `TicketsController.php` line 320  
**Problem:** `message_id` from the request is stored without verifying it belongs to the ticket in the URL. User A can link their attachment to a message in User B's ticket, causing it to surface in B's conversation.  
**Fix:** After resolving `$message_id`, verify it belongs to the current ticket:
```php
if ($message_id !== null) {
    $msg = $db->get_message($message_id);
    if ($msg === null || (int) $msg['ticket_id'] !== $id) {
        return $this->error('invalid_message', 'Message does not belong to this ticket.', 422);
    }
}
```
Add `Database::get_message(int $id): ?array` — a simple `SELECT` by primary key.

---

## Issue 3 — HIGH: Silent ticket black hole when AI is disabled
**File:** `TicketsController.php` lines 162–170  
**Problem:** When `aiEnabled = false`, `AiService::suggest()` is still called (wasting DB work), and the routing system message is never created because `if ($ai_enabled && $suggestion === null)` is false. Tickets sit in `unreviewed` with no signal to admin or user.  
**Fix:** Gate the AI call on `$ai_enabled`; always create a routing message when there's no AI answer:
```php
if ($ai_enabled) {
    $suggestion = (new AiService())->suggest($ticket, $body);
    $db->save_ai_suggestion($ticket_id, $suggestion);
    if ($suggestion === null) {
        $db->create_message($ticket_id, 0, 'system', 'سوال شما به کارشناس ارجاع داده شد و به زودی پاسخ خواهید گرفت.');
    }
} else {
    $db->create_message($ticket_id, 0, 'system', 'سوال شما به کارشناس ارجاع داده شد و به زودی پاسخ خواهید گرفت.');
}
```

---

## Issue 4 — MEDIUM: Duplicate routing messages (`route_to_support` not idempotent)
**File:** `TicketsController.php` line 284  
**Problem:** Guard only blocks `closed` / `ai_resolved`. A ticket already in `unreviewed` can be routed again, appending duplicate "referred to specialist" messages on every call.  
**Fix:** Add `'unreviewed'` to the blocked-status set:
```php
if (in_array($ticket['status'], ['closed', 'ai_resolved', 'unreviewed'], true)) {
    return $this->error('already_routed', 'تیکت قبلاً به کارشناس ارجاع داده شده است.', 422);
}
```

---

## Issue 5 — MEDIUM: `resolve_ticket_with_ai` ignores message insert failure
**File:** `Database.php` line 272  
**Problem:** If the AI reply message INSERT fails (disk full, deadlock), the ticket is still marked `ai_resolved = 1`. The ticket appears resolved to both user and admin, but contains no reply message — the AI answer is permanently lost.  
**Fix:** Check the insert return value and abort if it fails:
```php
$inserted = $this->db->insert(
    $this->db->prefix . 'ats_messages',
    ['ticket_id' => $ticket_id, 'user_id' => 0, 'author_type' => 'support', 'body' => $ai_body],
    ['%d', '%d', '%s', '%s']
);
if ($inserted === false) {
    return false;
}
return (bool) $this->db->update(...);
```

---

## Issue 6 — MEDIUM: File leak on ticket delete when URL scheme changes
**File:** `Database.php` lines 258–265  
**Problem:** `str_replace($upload_dir['baseurl'], '', $att['file_url'])` silently fails if the stored URL was `http://` but the site now uses `https://` (or different domain). The constructed file path is garbage, `file_exists()` returns false, and the physical file is never deleted.  
**Fix:** Normalise both sides to HTTPS before comparison, falling back to path extraction by upload dir suffix:
```php
private function resolve_attachment_path(string $file_url): ?string {
    $upload_dir = wp_upload_dir();
    $base_url   = $upload_dir['baseurl'];
    // Normalise schemes so http:// and https:// both match
    $norm_url    = preg_replace('#^https?://#', '//', $file_url);
    $norm_base   = preg_replace('#^https?://#', '//', $base_url);
    if (str_starts_with($norm_url, $norm_base)) {
        $relative = ltrim(substr($norm_url, strlen($norm_base)), '/');
        return trailingslashit($upload_dir['basedir']) . $relative;
    }
    return null;
}
```
Use this helper in `delete_ticket()` in place of the current inline logic.

---

## Issue 7 — MEDIUM: `updated_at` timezone mismatch on ticket creation
**File:** `Database.php` line 52 (schema) + line 248 (`update_ticket_status`)  
**Problem:** The schema uses `DEFAULT CURRENT_TIMESTAMP` (MySQL server timezone) but all subsequent `updated_at` writes use `current_time('mysql')` (WordPress local timezone). When WP and MySQL timezones differ, the initial `updated_at` is in a different timezone, corrupting `ORDER BY updated_at DESC` for un-updated tickets.  
**Fix:** Set `updated_at` explicitly in `create_ticket()` using `current_time('mysql')`:
```php
'updated_at'  => current_time('mysql'),
```
Also change the schema column default to `'0000-00-00 00:00:00'` so the inconsistency can't silently creep back in.

---

## Issue 8 — LOW: `uninstall.php` leaks `ats_attachments` table and uploaded files
**File:** `uninstall.php` lines 12–17  
**Problem:** `ats_attachments` table is not in the drop list. Additionally, uploaded files in `wp-content/uploads/` are never deleted on uninstall — sensitive support documents remain on disk indefinitely.  
**Fix:**
1. Query all attachment file URLs before dropping tables.
2. Delete each physical file using the same URL→path logic from Issue 6.
3. Add `ats_attachments` to the `$tables` array.

---

## Issue 9 — LOW: Debug `console.log` left in production template
**File:** `app-shell.php` lines 76–80  
**Problem:** A debug `console.log` block prints internal config keys (`--brand` CSS value, `window.atsConfig.brandColor`) to every visitor's browser console. Not a security hole, but unprofessional and leaks implementation details.  
**Fix:** Remove the entire `<script>` block containing the `console.log`.

---

## Issue 10 — FEATURE: Activation notice with helpdesk links
**File:** `Plugin.php`  
**Request:** After activation, show a one-time admin notice with direct links to both helpdesk URLs so admins can verify the install immediately.  
**Implementation:**
1. In `activate()`: `set_transient('ats_activation_notice', true, 30)`.
2. In `boot()`: `add_action('admin_notices', [$this, 'maybe_show_activation_notice'])`.
3. New method `maybe_show_activation_notice()`: reads and deletes the transient, renders a `notice-success` div with links to `home_url('helpdesk')` and `home_url('helpdesk-admin')`, plus a note about saving permalinks if 404.

---

## Execution order

| Step | File(s) | Issues |
|------|---------|--------|
| 1 | `TicketsController.php` | #1 (MIME), #2 (IDOR), #3 (AI gate), #4 (dedup route) |
| 2 | `Database.php` | #2 helper method, #5 (insert check), #6 (path helper), #7 (updated_at) |
| 3 | `uninstall.php` | #8 (drop table + files) |
| 4 | `app-shell.php` | #9 (remove console.log) |
| 5 | `Plugin.php` | #10 (activation notice) |
