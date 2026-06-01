#!/usr/bin/env python3
"""Generate WP AI Support Persian help PDF via WeasyPrint (proper RTL + font)."""

import base64, pathlib
from weasyprint import HTML, CSS

FONT_PATH = pathlib.Path("website/fonts/Ravi-VF.ttf")
OUTPUT    = "WP-AI-Support-Help-FA.pdf"

font_b64 = base64.b64encode(FONT_PATH.read_bytes()).decode()

HTML_CONTENT = f"""<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
@font-face {{
  font-family: 'Ravi';
  src: url('data:font/truetype;base64,{font_b64}') format('truetype');
  font-weight: 100 900;
}}

* {{
  font-family: 'Ravi', sans-serif;
  color: #000000;
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}}

@page {{
  size: A4;
  margin: 18mm 20mm 18mm 20mm;
  @bottom-center {{
    content: counter(page);
    font-family: 'Ravi', sans-serif;
    font-size: 10px;
    color: #000;
  }}
}}

body {{
  direction: rtl;
  font-size: 12px;
  line-height: 2;
  color: #000000;
  background: #fff;
}}

/* ── Cover ── */
.cover {{
  text-align: center;
  padding-top: 60mm;
  page-break-after: always;
}}
.cover h1 {{
  font-size: 32px;
  font-weight: 900;
  color: #000;
  margin-bottom: 6px;
}}
.cover .sub {{
  font-size: 14px;
  color: #000;
  margin-bottom: 4px;
}}
.cover .bar {{
  display: inline-block;
  background: #0068ff;
  color: #fff !important;
  padding: 10px 40px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 700;
  margin-top: 20px;
  width: 100%;
}}
.cover .bar * {{ color: #fff !important; }}

/* ── Typography ── */
h2 {{
  font-size: 20px;
  font-weight: 800;
  color: #000;
  margin: 28px 0 6px;
  padding-bottom: 8px;
  border-bottom: 2px solid #e5e7eb;
}}
h3 {{
  font-size: 14px;
  font-weight: 700;
  color: #000;
  margin: 20px 0 6px;
}}
p {{
  font-size: 12px;
  color: #000;
  line-height: 2;
  margin-bottom: 8px;
}}
ul, ol {{
  margin: 8px 0 10px 0;
  padding-right: 20px;
}}
li {{
  font-size: 12px;
  color: #000;
  line-height: 2;
  margin-bottom: 2px;
}}
code {{
  font-family: 'Ravi', monospace;
  font-size: 11px;
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  border-radius: 4px;
  padding: 1px 5px;
  color: #000;
  direction: ltr;
  display: inline-block;
}}
hr {{
  border: none;
  border-top: 1px solid #e5e7eb;
  margin: 4px 0 10px;
}}

/* ── Note boxes ── */
.note {{
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 11px;
  line-height: 2;
  margin: 12px 0;
  display: block;
}}
.note-info  {{ background: #e8f1ff; border-right: 4px solid #0068ff; color: #000; }}
.note-warn  {{ background: #fffbeb; border-right: 4px solid #f59e0b; color: #000; }}
.note-tip   {{ background: #f0fdf4; border-right: 4px solid #22c55e; color: #000; }}
.note * {{ color: #000 !important; }}

/* ── Step rows ── */
.steps {{ margin: 12px 0; }}
.step {{
  display: flex;
  gap: 12px;
  margin-bottom: 10px;
  align-items: flex-start;
  flex-direction: row-reverse;
}}
.step-badge {{
  min-width: 26px;
  height: 26px;
  background: #0068ff;
  color: #fff !important;
  font-weight: 700;
  font-size: 12px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}}
.step-content strong {{
  display: block;
  font-weight: 700;
  color: #000;
  margin-bottom: 2px;
}}
.step-content p {{
  margin: 0;
  color: #000;
}}

/* ── Table ── */
table {{
  width: 100%;
  border-collapse: collapse;
  font-size: 11px;
  margin: 12px 0;
}}
th {{
  background: #0068ff;
  color: #fff !important;
  font-weight: 700;
  text-align: right;
  padding: 7px 10px;
}}
td {{
  text-align: right;
  padding: 6px 10px;
  color: #000;
  border-bottom: 1px solid #e5e7eb;
}}
tr:nth-child(even) td {{ background: #f9fafb; }}

/* ── Footer ── */
.footer {{
  text-align: center;
  margin-top: 40px;
  padding-top: 12px;
  border-top: 1px solid #e5e7eb;
  font-size: 10px;
  color: #000;
}}
</style>
</head>
<body>

<!-- ═══ COVER ═══ -->
<div class="cover">
  <h1>WP AI Support</h1>
  <p class="sub">راهنمای کامل افزونه</p>
  <p class="sub">پشتیبانی هوشمند برای وردپرس — مدیریت تیکت با هوش مصنوعی</p>
  <div class="bar">wpaisupport.ir</div>
</div>

<!-- ═══ 1. معرفی ═══ -->
<h2>۱. معرفی</h2>
<hr>
<p>
  <strong>WP AI Support</strong> یک افزونه وردپرس برای مدیریت حرفه‌ای تیکت‌های پشتیبانی است.
  این افزونه با کمک هوش مصنوعی، پایگاه دانش شما را می‌خواند و به‌طور خودکار به سوالات مشتریان پاسخ می‌دهد.
</p>
<p>
  اگر هوش مصنوعی پاسخ مناسبی پیدا کند، مشتری آن را می‌بیند و می‌تواند تأیید کند یا از پشتیبان انسانی کمک بخواهد.
  اگر پاسخی یافت نشود، تیکت به صف کارشناسان ارجاع می‌شود.
</p>
<div class="note note-info">
  ℹ این افزونه به <strong>WordPress 6.0</strong> یا بالاتر و <strong>PHP 8.1</strong> یا بالاتر نیاز دارد.
</div>

<h3>قابلیت‌های اصلی</h3>
<ul>
  <li>پاسخ‌دهی هوشمند بر اساس پایگاه دانش اختصاصی شما</li>
  <li>مدیریت تیکت با صف، اولویت، وضعیت و جستجو</li>
  <li>پایگاه دانش با دسته‌بندی و پاسخ‌های آماده</li>
  <li>پیوست فایل و تصویر در تیکت‌ها</li>
  <li>شخصی‌سازی رنگ برند</li>
  <li>پنل جداگانه برای کاربر و ادمین</li>
  <li>پشتیبانی کامل از زبان فارسی و RTL</li>
</ul>

<!-- ═══ 2. نصب ═══ -->
<h2>۲. نصب افزونه</h2>
<hr>
<p>افزونه WP AI Support را از سایت <strong>راستچین</strong> تهیه کنید. پس از دریافت فایل zip، مراحل زیر را دنبال کنید:</p>

<div class="steps">
  <div class="step">
    <div class="step-badge">۱</div>
    <div class="step-content">
      <strong>وارد پیشخوان وردپرس شوید</strong>
      <p>از منو به <code>افزونه‌ها ← افزودن</code> بروید.</p>
    </div>
  </div>
  <div class="step">
    <div class="step-badge">۲</div>
    <div class="step-content">
      <strong>آپلود فایل zip</strong>
      <p>روی «بارگذاری افزونه» کلیک کنید، فایل zip را انتخاب و آپلود کنید.</p>
    </div>
  </div>
  <div class="step">
    <div class="step-badge">۳</div>
    <div class="step-content">
      <strong>فعال‌سازی</strong>
      <p>روی «فعال‌سازی افزونه» کلیک کنید. افزونه جداول پایگاه داده را به‌صورت خودکار می‌سازد.</p>
    </div>
  </div>
  <div class="step">
    <div class="step-badge">۴</div>
    <div class="step-content">
      <strong>دسترسی به پنل</strong>
      <p>پنل کاربر: <code>yoursite.com/helpdesk</code></p>
      <p>پنل ادمین: <code>yoursite.com/helpdesk-admin</code></p>
    </div>
  </div>
</div>

<div class="note note-warn">
  ⚠ برای دسترسی به پنل ادمین باید دسترسی <strong>Administrator</strong> در وردپرس داشته باشید.
</div>

<!-- ═══ 3. راه‌اندازی سریع ═══ -->
<h2>۳. راه‌اندازی سریع</h2>
<hr>
<p>پس از نصب، این مسیر سریع‌ترین راه برای راه‌اندازی کامل افزونه است:</p>
<ol>
  <li>به <strong>پایگاه دانش ← دسته‌بندی‌ها</strong> بروید و دسته‌های مرتبط با کسب‌وکارتان را بسازید.</li>
  <li>در <strong>پایگاه دانش ← پاسخ‌های آماده</strong>، پاسخ سوالات رایج مشتریانتان را وارد کنید.</li>
  <li>به <strong>تنظیمات</strong> بروید و رنگ برند خود را تنظیم کنید.</li>
  <li>در تنظیمات، کلید API GapGPT را وارد کنید و پاسخ هوشمند را فعال کنید.</li>
  <li>لینک <code>yoursite.com/helpdesk</code> را به مشتریانتان بدهید.</li>
</ol>
<div class="note note-tip">
  ✓ هرچه پایگاه دانش شما غنی‌تر باشد، کیفیت پاسخ‌های هوش مصنوعی بهتر است. از همان ابتدا پاسخ‌های دقیق و کامل وارد کنید.
</div>

<!-- ═══ 4. رنگ برند ═══ -->
<h2>۴. رنگ برند</h2>
<hr>
<p>
  در صفحه <strong>تنظیمات</strong> می‌توانید رنگ برند افزونه را تغییر دهید.
  این رنگ روی دکمه‌ها، لینک‌ها و عناصر اصلی رابط کاربری اعمال می‌شود.
</p>
<p>
  کد رنگ را به فرمت HEX وارد کنید (مثلاً <code>#0068ff</code>).
  پس از ذخیره، رنگ جدید برای همه کاربران نمایش داده می‌شود.
</p>
<div class="note note-info">
  ℹ رنگ پیش‌فرض <code>#0068ff</code> است. برای بهترین تجربه، از رنگ‌های با کنتراست کافی استفاده کنید.
</div>

<!-- ═══ 5. تنظیمات هوش مصنوعی ═══ -->
<h2>۵. تنظیمات هوش مصنوعی</h2>
<hr>

<h3>پاسخ هوشمند</h3>
<p>
  با فعال کردن این گزینه، هنگام ثبت هر تیکت جدید، هوش مصنوعی پایگاه دانش را بررسی می‌کند
  و اگر پاسخ مناسبی یافت، آن را به کاربر نشان می‌دهد.
</p>
<div class="note note-warn">
  ⚠ برای فعال کردن پاسخ هوشمند، ابتدا باید یک ارائه‌دهنده هوش مصنوعی را از بخش یکپارچه‌سازی فعال کنید.
</div>

<h3>حالت پاسخ‌دهی</h3>
<ul>
  <li><strong>فقط پایگاه دانش:</strong> هوش مصنوعی فقط از اطلاعات پایگاه دانش شما استفاده می‌کند. اگر پاسخ نباشد، تیکت به کارشناس ارجاع می‌شود.</li>
  <li><strong>پایگاه دانش + دانش هوش مصنوعی:</strong> اگر پایگاه دانش کافی نبود، هوش مصنوعی از دانش عمومی خودش هم کمک می‌گیرد.</li>
</ul>

<h3>تعداد پاسخ ارسالی (TOP K)</h3>
<p>
  سیستم ابتدا پایگاه دانش را بر اساس کلیدواژه‌های تیکت امتیازدهی می‌کند، سپس بهترین N پاسخ را به هوش مصنوعی می‌فرستد.
  عدد بزرگ‌تر = پاسخ دقیق‌تر ولی هزینه بیشتر. پیش‌فرض: ۴
</p>

<h3>حداکثر طول هر پاسخ (MAX BODY)</h3>
<p>
  بدنه هر پاسخ آماده تا این تعداد کاراکتر به هوش مصنوعی فرستاده می‌شود.
  عدد کمتر = هزینه پایین‌تر. پیش‌فرض: ۴۰۰ کاراکتر
</p>

<!-- ═══ 6. GapGPT ═══ -->
<h2>۶. ارائه‌دهنده: GapGPT</h2>
<hr>
<p>
  WP AI Support از سرویس <strong>GapGPT</strong> پشتیبانی می‌کند.
  GapGPT یک سرویس ایرانی هوش مصنوعی با پشتیبانی کامل از زبان فارسی است و به مدل‌های مختلف از جمله
  GPT، Claude، Gemini و مدل‌های بومی دسترسی دارد.
</p>

<h3>دریافت کلید API</h3>
<ol>
  <li>به <code>gapgpt.app/platform-v2/tokens</code> بروید.</li>
  <li>یک توکن جدید بسازید.</li>
  <li>توکن را کپی کنید و در تنظیمات افزونه (بخش یکپارچه‌سازی) وارد کنید.</li>
</ol>

<h3>انتخاب مدل</h3>
<p>پس از وارد کردن کلید، می‌توانید مدل هوش مصنوعی را انتخاب کنید:</p>
<ul>
  <li><code>gapgpt-qwen-3.5</code> — سریع و اقتصادی (پیش‌فرض)</li>
  <li><code>gpt-4o</code> — دقیق‌تر، برای پایگاه دانش پیچیده</li>
  <li><code>gpt-4o-mini</code> — تعادل بین سرعت و دقت</li>
</ul>

<h3>تست اتصال</h3>
<p>
  پس از وارد کردن کلید API، روی دکمه <strong>تست اتصال</strong> کلیک کنید.
  اگر اتصال موفق بود، می‌توانید ارائه‌دهنده را فعال کنید.
</p>
<div class="note note-tip">
  ✓ اگر پایگاه دانش شما شامل مراحل دقیق و اعداد مشخص است، از مدل‌های قوی‌تر مثل <code>gpt-4o</code> استفاده کنید.
</div>

<!-- ═══ 7. دسته‌بندی‌ها ═══ -->
<h2>۷. پایگاه دانش — دسته‌بندی‌ها</h2>
<hr>
<p>
  دسته‌بندی‌ها به شما کمک می‌کنند پایگاه دانش را سازماندهی کنید.
  هر دسته‌بندی یک عنوان و توضیحات دارد.
</p>
<p>
  وقتی مشتری تیکت ثبت می‌کند، می‌تواند دسته‌بندی مرتبط را انتخاب کند.
</p>

<h3>مدیریت دسته‌بندی‌ها</h3>
<ul>
  <li>از منو <strong>پایگاه دانش ← دسته‌بندی‌ها</strong> وارد شوید.</li>
  <li>با دکمه «افزودن دسته» دسته جدید بسازید.</li>
  <li>برای ویرایش یا حذف، از آیکون‌های کنار هر دسته استفاده کنید.</li>
  <li>حذف دسته، پاسخ‌های آماده آن دسته را حذف نمی‌کند — فقط دسته‌بندی آن‌ها برداشته می‌شود.</li>
</ul>

<!-- ═══ 8. پاسخ‌های آماده ═══ -->
<h2>۸. پاسخ‌های آماده</h2>
<hr>
<p>
  پاسخ‌های آماده همان پایگاه دانش شما هستند. هوش مصنوعی این پاسخ‌ها را می‌خواند
  و بر اساس آن‌ها به کاربر جواب می‌دهد.
</p>

<h3>نکات مهم برای نوشتن پاسخ‌های مؤثر</h3>
<ul>
  <li>مراحل را شماره‌گذاری کنید — هوش مصنوعی همه مراحل را دقیقاً بازتولید می‌کند.</li>
  <li>اعداد و مشخصات دقیق را عیناً بنویسید.</li>
  <li>هر پاسخ یک موضوع مشخص داشته باشد.</li>
  <li>از HTML ویرایشگر استفاده کنید تا پاسخ‌ها خوانا باشند.</li>
</ul>

<div class="note note-info">
  ℹ هوش مصنوعی همیشه کل پایگاه دانش را بررسی می‌کند — حتی اگر کاربر دسته اشتباهی انتخاب کرده باشد.
</div>

<!-- ═══ 9. تیکت کاربر ═══ -->
<h2>۹. تیکت از دید کاربر</h2>
<hr>
<p>
  مشتریان از آدرس <code>yoursite.com/helpdesk</code> وارد می‌شوند.
  برای ثبت تیکت باید در وردپرس حساب کاربری داشته باشند.
</p>

<h3>ثبت تیکت جدید</h3>
<ol>
  <li>روی «تیکت جدید» کلیک کنید.</li>
  <li>عنوان، دسته‌بندی و اولویت را مشخص کنید.</li>
  <li>متن سوال را بنویسید (می‌توانید فایل پیوست کنید).</li>
  <li>روی «ارسال» کلیک کنید.</li>
</ol>

<p>اگر هوش مصنوعی پاسخی پیدا کند، کاربر به صفحه «پاسخ هوشمند» هدایت می‌شود و دو گزینه دارد:</p>
<ul>
  <li><strong>مشکل حل شد:</strong> تیکت با پاسخ هوشمند بسته می‌شود.</li>
  <li><strong>ادامه با پشتیبان:</strong> پیام ارجاع به کارشناس در چت ظاهر می‌شود.</li>
</ul>

<!-- ═══ 10. مدیریت ادمین ═══ -->
<h2>۱۰. مدیریت تیکت (ادمین)</h2>
<hr>
<p>ادمین از آدرس <code>yoursite.com/helpdesk-admin</code> وارد پنل مدیریت می‌شود.</p>

<h3>صف تیکت‌ها</h3>
<p>تیکت‌ها بر اساس وضعیت نمایش داده می‌شوند. می‌توانید:</p>
<ul>
  <li>با کلیک روی تیکت، مکالمه را باز کنید و پاسخ دهید.</li>
  <li>وضعیت تیکت را تغییر دهید.</li>
  <li>تیکت را حذف کنید.</li>
  <li>با فیلتر وضعیت و جستجو، تیکت‌ها را پیدا کنید.</li>
</ul>

<h3>پاسخ دادن</h3>
<p>
  در صفحه تیکت، پاسخ خود را تایپ کنید و ارسال کنید.
  پس از ارسال پاسخ ادمین، وضعیت تیکت به «پاسخ داده شده» تغییر می‌کند.
</p>

<!-- ═══ 11. وضعیت‌ها ═══ -->
<h2>۱۱. وضعیت‌های تیکت</h2>
<hr>
<p>هر تیکت یکی از وضعیت‌های زیر را دارد:</p>
<table>
  <thead>
    <tr>
      <th>وضعیت</th>
      <th>توضیح</th>
      <th>نمایش به کاربر</th>
    </tr>
  </thead>
  <tbody>
    <tr><td>بررسی نشده</td><td>تیکت تازه ثبت شده</td><td>در انتظار</td></tr>
    <tr><td>درحال بررسی</td><td>ادمین در حال بررسی است</td><td>در انتظار</td></tr>
    <tr><td>در انتظار پاسخ</td><td>ادمین پاسخ داده، منتظر کاربر</td><td>پاسخ داده شده</td></tr>
    <tr><td>پاسخ داده شده</td><td>کاربر جواب داده</td><td>پاسخ داده شده</td></tr>
    <tr><td>بسته شده</td><td>تیکت بسته شده</td><td>بسته شده</td></tr>
    <tr><td>پاسخ هوشمند</td><td>با تأیید کاربر از پاسخ AI بسته شد</td><td>بسته شده</td></tr>
    <tr><td>اسپم</td><td>تیکت اسپم</td><td>در انتظار</td></tr>
  </tbody>
</table>

<!-- ═══ 12. جریان هوشمند ═══ -->
<h2>۱۲. جریان پاسخ هوشمند</h2>
<hr>
<p>هنگام ثبت تیکت جدید، اگر هوش مصنوعی فعال باشد، سیستم این مراحل را طی می‌کند:</p>
<ol>
  <li>پایگاه دانش بر اساس کلیدواژه‌های تیکت امتیازدهی می‌شود.</li>
  <li>اگر هیچ همپوشانی کلیدواژه‌ای وجود نداشته باشد، تیکت مستقیم به صف ادمین می‌رود.</li>
  <li>بهترین N پاسخ (TOP K) به هوش مصنوعی فرستاده می‌شود.</li>
  <li>هوش مصنوعی یک پاسخ جدید بر اساس پایگاه دانش می‌نویسد.</li>
  <li>اگر پاسخ تولید شد، کاربر به صفحه «پاسخ هوشمند» هدایت می‌شود.</li>
  <li>اگر پاسخی یافت نشد، پیام ارجاع به کارشناس ظاهر می‌شود.</li>
</ol>
<div class="note note-tip">
  ✓ تیکت‌های حل‌شده با AI در پنل ادمین با بج «پاسخ هوشمند» مشخص می‌شوند.
</div>

<!-- ═══ 13. سوالات متداول ═══ -->
<h2>۱۳. سوالات متداول</h2>
<hr>

<h3>آیا می‌توانم چند ارائه‌دهنده هوش مصنوعی را همزمان فعال کنم؟</h3>
<p>خیر. در هر زمان فقط یک ارائه‌دهنده می‌تواند فعال باشد. با فعال کردن یک ارائه‌دهنده، بقیه به‌صورت خودکار غیرفعال می‌شوند.</p>

<h3>اگر کاربر دسته اشتباهی انتخاب کند چه می‌شود؟</h3>
<p>هیچ مشکلی نیست. سیستم همیشه کل پایگاه دانش را بررسی می‌کند و بر اساس کلیدواژه‌های متن تیکت، مرتبط‌ترین پاسخ‌ها را پیدا می‌کند.</p>

<h3>آیا هوش مصنوعی اطلاعات بیرون از پایگاه دانش استفاده می‌کند؟</h3>
<p>در حالت «فقط پایگاه دانش» خیر — پاسخ کاملاً بر اساس اطلاعات شماست. در حالت «پایگاه دانش + هوش مصنوعی» اگر اطلاعات کافی نباشد، از دانش عمومی هم کمک می‌گیرد.</p>

<h3>آیا پاسخ‌های AI ذخیره می‌شوند؟</h3>
<p>بله. هر پاسخ AI در پایگاه داده وردپرس ذخیره می‌شود و ادمین می‌تواند آن را در پنل مدیریت ببیند.</p>

<h3>آیا کاربران برای ثبت تیکت باید عضو شوند؟</h3>
<p>بله. کاربران باید حساب کاربری وردپرس داشته باشند و وارد شده باشند تا بتوانند تیکت ثبت کنند.</p>

<h3>آیا می‌توانم افزونه را در وردپرس نصب‌شده در زیرشاخه استفاده کنم؟</h3>
<p>بله. افزونه مسیر وردپرس را به‌صورت خودکار تشخیص می‌دهد و React Router را بر اساس آن تنظیم می‌کند.</p>

<div class="footer">
  <p>WP AI Support — wpaisupport.ir</p>
  <p>خرید افزونه: www.rtl-theme.com</p>
</div>

</body>
</html>"""

html = HTML(string=HTML_CONTENT, base_url=".")
html.write_pdf(OUTPUT)
print(f"PDF saved: {OUTPUT}")
