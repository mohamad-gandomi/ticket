#!/usr/bin/env python3
"""Generate WP AI Support Persian help PDF."""

import arabic_reshaper
from bidi.algorithm import get_display
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import mm
from reportlab.lib.styles import ParagraphStyle
from reportlab.lib.enums import TA_RIGHT, TA_CENTER
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle,
    HRFlowable, KeepTogether
)
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.lib import colors

FONT_PATH = "website/fonts/Ravi-VF.ttf"
OUTPUT    = "WP-AI-Support-Help-FA.pdf"
BRAND     = colors.HexColor("#0068ff")
BRAND_BG  = colors.HexColor("#e8f1ff")
WARN_BG   = colors.HexColor("#fffbeb")
WARN_BORDER = colors.HexColor("#f59e0b")
TIP_BG    = colors.HexColor("#f0fdf4")
TIP_BORDER = colors.HexColor("#22c55e")
GRAY_100  = colors.HexColor("#f3f4f6")
GRAY_200  = colors.HexColor("#e5e7eb")
GRAY_700  = colors.HexColor("#374151")
GRAY_800  = colors.HexColor("#1f2937")
GRAY_900  = colors.HexColor("#111827")

pdfmetrics.registerFont(TTFont("Ravi", FONT_PATH))


def fa(text):
    """Reshape + apply bidi so Persian renders correctly in PDFs."""
    reshaped = arabic_reshaper.reshape(text)
    return get_display(reshaped)


def make_styles():
    def ps(name, **kwargs):
        kwargs.setdefault("fontName", "Ravi")
        return ParagraphStyle(name, **kwargs)

    title_style = ps("title", alignment=TA_CENTER, fontSize=26, textColor=GRAY_900,
                     leading=32, spaceAfter=4, spaceBefore=0)
    subtitle_style = ps("subtitle", alignment=TA_CENTER, fontSize=13,
                        textColor=colors.HexColor("#6b7280"), leading=20, spaceAfter=2)
    h2_style = ps("h2", alignment=TA_RIGHT, fontSize=18, textColor=GRAY_900,
                  leading=26, spaceBefore=18, spaceAfter=6)
    h3_style = ps("h3", alignment=TA_RIGHT, fontSize=13, textColor=GRAY_800,
                  leading=22, spaceBefore=12, spaceAfter=4)
    body_style = ps("body", alignment=TA_RIGHT, fontSize=11, textColor=GRAY_700,
                    leading=22, spaceAfter=6)
    note_style = ps("note", alignment=TA_RIGHT, fontSize=10,
                    textColor=colors.HexColor("#1e40af"), leading=20)
    warn_style = ps("warn", alignment=TA_RIGHT, fontSize=10,
                    textColor=colors.HexColor("#92400e"), leading=20)
    tip_style = ps("tip", alignment=TA_RIGHT, fontSize=10,
                   textColor=colors.HexColor("#166534"), leading=20)
    li_style = ps("li", alignment=TA_RIGHT, fontSize=11, textColor=GRAY_700,
                  leading=22, spaceAfter=3, rightIndent=14)
    code_style = ps("code", alignment=TA_RIGHT, fontSize=10,
                    textColor=colors.HexColor("#be185d"), leading=18,
                    backColor=GRAY_100, borderPadding=4)
    footer_style = ps("footer", alignment=TA_CENTER, fontSize=9,
                      textColor=colors.HexColor("#9ca3af"), leading=16)
    return {
        "title": title_style, "subtitle": subtitle_style,
        "h2": h2_style, "h3": h3_style, "body": body_style,
        "note": note_style, "warn": warn_style, "tip": tip_style,
        "li": li_style, "code": code_style, "footer": footer_style,
    }


def note_box(text, style_name, bg, border_color, s):
    icon = {"note": "ℹ", "warn": "⚠", "tip": "✓"}[style_name]
    p = Paragraph(fa(f"{icon}  {text}"), s[style_name])
    t = Table([[p]], colWidths=[155 * mm])
    t.setStyle(TableStyle([
        ("BACKGROUND",    (0, 0), (-1, -1), bg),
        ("LINEAFTER",     (0, 0), (0, -1),  3, border_color),
        ("TOPPADDING",    (0, 0), (-1, -1), 8),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 8),
        ("LEFTPADDING",   (0, 0), (-1, -1), 12),
        ("RIGHTPADDING",  (0, 0), (-1, -1), 12),
    ]))
    return t


def bullet(text, s, num=None):
    prefix = f"{num}." if num else "•"
    return Paragraph(fa(f"{prefix}  {text}"), s["li"])


def section_divider():
    return HRFlowable(width="100%", thickness=1, color=GRAY_200, spaceAfter=4, spaceBefore=4)


def build_pdf():
    s = make_styles()
    doc = SimpleDocTemplate(
        OUTPUT, pagesize=A4,
        rightMargin=20 * mm, leftMargin=20 * mm,
        topMargin=18 * mm, bottomMargin=18 * mm,
        title="WP AI Support — راهنمای کامل",
        author="wpaisupport.ir",
    )

    story = []

    # ── Cover ────────────────────────────────────────────────────────────────────
    story.append(Spacer(1, 30 * mm))
    story.append(Paragraph(fa("WP AI Support"), s["title"]))
    story.append(Spacer(1, 4 * mm))
    story.append(Paragraph(fa("راهنمای کامل افزونه"), s["subtitle"]))
    story.append(Spacer(1, 2 * mm))
    story.append(Paragraph(fa("پشتیبانی هوشمند برای وردپرس — مدیریت تیکت با هوش مصنوعی"), s["subtitle"]))
    story.append(Spacer(1, 8 * mm))

    cover_bar = Table([[ Paragraph(fa("wpaisupport.ir"), s["subtitle"]) ]], colWidths=[155 * mm])
    cover_bar.setStyle(TableStyle([
        ("BACKGROUND",    (0, 0), (-1, -1), BRAND),
        ("TOPPADDING",    (0, 0), (-1, -1), 10),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 10),
        ("TEXTCOLOR",     (0, 0), (-1, -1), colors.white),
    ]))
    story.append(cover_bar)
    story.append(Spacer(1, 30 * mm))

    # ══════════════════════════════════════════════════════════════════════════
    # 1. معرفی
    # ══════════════════════════════════════════════════════════════════════════
    story.append(Paragraph(fa("۱. معرفی"), s["h2"]))
    story.append(section_divider())
    story.append(Paragraph(
        fa("WP AI Support یک افزونه وردپرس برای مدیریت حرفه‌ای تیکت‌های پشتیبانی است. "
           "این افزونه با کمک هوش مصنوعی، پایگاه دانش شما را می‌خواند و به طور خودکار "
           "به سوالات مشتریان پاسخ می‌دهد."), s["body"]))
    story.append(Paragraph(
        fa("اگر هوش مصنوعی پاسخ مناسبی پیدا کند، مشتری آن را می‌بیند و می‌تواند تأیید کند "
           "یا از پشتیبان انسانی کمک بخواهد. اگر پاسخی یافت نشود، تیکت به صف کارشناسان "
           "ارجاع می‌شود."), s["body"]))
    story.append(Spacer(1, 2 * mm))
    story.append(note_box(
        "این افزونه به WordPress 6.0 یا بالاتر و PHP 8.1 یا بالاتر نیاز دارد.",
        "note", BRAND_BG, BRAND, s))
    story.append(Spacer(1, 4 * mm))

    story.append(Paragraph(fa("قابلیت‌های اصلی"), s["h3"]))
    features = [
        "پاسخ‌دهی هوشمند بر اساس پایگاه دانش اختصاصی شما",
        "مدیریت تیکت با صف، اولویت، وضعیت و جستجو",
        "پایگاه دانش با دسته‌بندی و پاسخ‌های آماده",
        "پیوست فایل و تصویر در تیکت‌ها",
        "شخصی‌سازی رنگ برند",
        "پنل جداگانه برای کاربر و ادمین",
        "پشتیبانی کامل از زبان فارسی و RTL",
    ]
    for f in features:
        story.append(bullet(f, s))
    story.append(Spacer(1, 6 * mm))

    # ══════════════════════════════════════════════════════════════════════════
    # 2. نصب
    # ══════════════════════════════════════════════════════════════════════════
    story.append(Paragraph(fa("۲. نصب افزونه"), s["h2"]))
    story.append(section_divider())
    story.append(Paragraph(
        fa("افزونه WP AI Support را از سایت راستچین تهیه کنید. "
           "پس از دریافت فایل zip، مراحل زیر را دنبال کنید:"), s["body"]))
    story.append(Spacer(1, 2 * mm))

    steps = [
        ("۱", "وارد پیشخوان وردپرس شوید",
         "از منو به «افزونه‌ها ← افزودن» بروید."),
        ("۲", "آپلود فایل zip",
         "روی «بارگذاری افزونه» کلیک کنید، فایل zip را انتخاب و آپلود کنید."),
        ("۳", "فعال‌سازی",
         "روی «فعال‌سازی افزونه» کلیک کنید. افزونه جداول پایگاه داده را به صورت خودکار می‌سازد."),
        ("۴", "دسترسی به پنل",
         "پنل کاربر: yoursite.com/helpdesk\nپنل ادمین: yoursite.com/helpdesk-admin"),
    ]
    for num, title, desc in steps:
        badge_style = ParagraphStyle(
            "badge", alignment=TA_CENTER, fontSize=12, textColor=colors.white,
            fontName="Ravi", leading=18,
        )
        badge_cell = Paragraph(fa(num), badge_style)
        badge_table = Table([[badge_cell]], colWidths=[8 * mm], rowHeights=[8 * mm])
        badge_table.setStyle(TableStyle([
            ("BACKGROUND",    (0, 0), (-1, -1), BRAND),
            ("TOPPADDING",    (0, 0), (-1, -1), 0),
            ("BOTTOMPADDING", (0, 0), (-1, -1), 0),
            ("LEFTPADDING",   (0, 0), (-1, -1), 0),
            ("RIGHTPADDING",  (0, 0), (-1, -1), 0),
        ]))
        title_p = Paragraph(fa(title), ParagraphStyle(
            "steptitle", fontName="Ravi", fontSize=11, textColor=GRAY_900,
            alignment=TA_RIGHT, leading=20,
        ))
        desc_p = Paragraph(fa(desc), s["body"])
        content = Table([[desc_p]], colWidths=[140 * mm])
        row_table = Table(
            [[badge_table, Table([[title_p], [content]], colWidths=[140 * mm])]],
            colWidths=[12 * mm, 143 * mm]
        )
        row_table.setStyle(TableStyle([
            ("VALIGN",        (0, 0), (-1, -1), "TOP"),
            ("TOPPADDING",    (0, 0), (-1, -1), 2),
            ("BOTTOMPADDING", (0, 0), (-1, -1), 2),
            ("LEFTPADDING",   (0, 0), (-1, -1), 0),
            ("RIGHTPADDING",  (0, 0), (-1, -1), 0),
        ]))
        story.append(row_table)
        story.append(Spacer(1, 2 * mm))

    story.append(Spacer(1, 2 * mm))
    story.append(note_box(
        "برای دسترسی به پنل ادمین باید دسترسی Administrator در وردپرس داشته باشید.",
        "warn", WARN_BG, WARN_BORDER, s))
    story.append(Spacer(1, 6 * mm))

    # ══════════════════════════════════════════════════════════════════════════
    # 3. راه‌اندازی سریع
    # ══════════════════════════════════════════════════════════════════════════
    story.append(Paragraph(fa("۳. راه‌اندازی سریع"), s["h2"]))
    story.append(section_divider())
    story.append(Paragraph(
        fa("پس از نصب، این مسیر سریع‌ترین راه برای راه‌اندازی کامل افزونه است:"), s["body"]))

    quick_steps = [
        "به «پایگاه دانش ← دسته‌بندی‌ها» بروید و دسته‌های مرتبط با کسب‌وکارتان را بسازید.",
        "در «پایگاه دانش ← پاسخ‌های آماده»، پاسخ سوالات رایج مشتریانتان را وارد کنید.",
        "به «تنظیمات» بروید و رنگ برند خود را تنظیم کنید.",
        "در تنظیمات، کلید API GapGPT را وارد کنید و پاسخ هوشمند را فعال کنید.",
        "لینک yoursite.com/helpdesk را به مشتریانتان بدهید.",
    ]
    for i, step in enumerate(quick_steps, 1):
        story.append(bullet(step, s, num=i))

    story.append(Spacer(1, 3 * mm))
    story.append(note_box(
        "هرچه پایگاه دانش شما غنی‌تر باشد، کیفیت پاسخ‌های هوش مصنوعی بهتر است. "
        "از همان ابتدا پاسخ‌های دقیق و کامل وارد کنید.",
        "tip", TIP_BG, TIP_BORDER, s))
    story.append(Spacer(1, 6 * mm))

    # ══════════════════════════════════════════════════════════════════════════
    # 4. رنگ برند
    # ══════════════════════════════════════════════════════════════════════════
    story.append(Paragraph(fa("۴. رنگ برند"), s["h2"]))
    story.append(section_divider())
    story.append(Paragraph(
        fa("در صفحه «تنظیمات» می‌توانید رنگ برند افزونه را تغییر دهید. "
           "این رنگ روی دکمه‌ها، لینک‌ها و عناصر اصلی رابط کاربری اعمال می‌شود."), s["body"]))
    story.append(Paragraph(
        fa("کد رنگ را به فرمت HEX وارد کنید (مثلاً #0068ff). تغییر رنگ بلافاصله در پیش‌نمایش "
           "قابل مشاهده است. پس از ذخیره، رنگ جدید برای همه کاربران نمایش داده می‌شود."), s["body"]))
    story.append(note_box(
        "رنگ پیش‌فرض #0068ff است. برای بهترین تجربه، از رنگ‌های با کنتراست کافی استفاده کنید.",
        "note", BRAND_BG, BRAND, s))
    story.append(Spacer(1, 6 * mm))

    # ══════════════════════════════════════════════════════════════════════════
    # 5. تنظیمات هوش مصنوعی
    # ══════════════════════════════════════════════════════════════════════════
    story.append(Paragraph(fa("۵. تنظیمات هوش مصنوعی"), s["h2"]))
    story.append(section_divider())

    story.append(Paragraph(fa("پاسخ هوشمند"), s["h3"]))
    story.append(Paragraph(
        fa("با فعال کردن این گزینه، هنگام ثبت هر تیکت جدید، هوش مصنوعی پایگاه دانش را "
           "بررسی می‌کند و اگر پاسخ مناسبی یافت، آن را به کاربر نشان می‌دهد."), s["body"]))
    story.append(note_box(
        "برای فعال کردن پاسخ هوشمند، ابتدا باید یک ارائه‌دهنده هوش مصنوعی را "
        "از بخش یکپارچه‌سازی فعال کنید.",
        "warn", WARN_BG, WARN_BORDER, s))
    story.append(Spacer(1, 3 * mm))

    story.append(Paragraph(fa("حالت پاسخ‌دهی"), s["h3"]))
    story.append(bullet(
        "فقط پایگاه دانش: هوش مصنوعی فقط از اطلاعات پایگاه دانش شما استفاده می‌کند. "
        "اگر پاسخ نباشد، تیکت به کارشناس ارجاع می‌شود.", s))
    story.append(bullet(
        "پایگاه دانش + دانش هوش مصنوعی: اگر پایگاه دانش کافی نبود، "
        "هوش مصنوعی از دانش عمومی خودش هم کمک می‌گیرد.", s))
    story.append(Spacer(1, 3 * mm))

    story.append(Paragraph(fa("تعداد پاسخ ارسالی (TOP K)"), s["h3"]))
    story.append(Paragraph(
        fa("سیستم ابتدا پایگاه دانش را بر اساس کلیدواژه‌های تیکت امتیازدهی می‌کند، سپس بهترین N "
           "پاسخ را به هوش مصنوعی می‌فرستد. عدد بزرگ‌تر = پاسخ دقیق‌تر ولی هزینه بیشتر. "
           "پیش‌فرض: ۴"), s["body"]))

    story.append(Paragraph(fa("حداکثر طول هر پاسخ (MAX BODY)"), s["h3"]))
    story.append(Paragraph(
        fa("بدنه هر پاسخ آماده تا این تعداد کاراکتر به هوش مصنوعی فرستاده می‌شود. "
           "عدد کمتر = هزینه پایین‌تر. پیش‌فرض: ۴۰۰ کاراکتر"), s["body"]))
    story.append(Spacer(1, 6 * mm))

    # ══════════════════════════════════════════════════════════════════════════
    # 6. GapGPT
    # ══════════════════════════════════════════════════════════════════════════
    story.append(Paragraph(fa("۶. ارائه‌دهنده: GapGPT"), s["h2"]))
    story.append(section_divider())
    story.append(Paragraph(
        fa("WP AI Support در حال حاضر از سرویس GapGPT پشتیبانی می‌کند. GapGPT یک سرویس "
           "ایرانی هوش مصنوعی با پشتیبانی کامل از زبان فارسی است و به مدل‌های مختلف از جمله "
           "GPT، Claude، Gemini و مدل‌های بومی دسترسی دارد."), s["body"]))

    story.append(Paragraph(fa("دریافت کلید API"), s["h3"]))
    api_steps = [
        "به gapgpt.app/platform-v2/tokens بروید.",
        "یک توکن جدید بسازید.",
        "توکن را کپی کنید و در تنظیمات افزونه (بخش یکپارچه‌سازی) وارد کنید.",
    ]
    for i, step in enumerate(api_steps, 1):
        story.append(bullet(step, s, num=i))

    story.append(Paragraph(fa("انتخاب مدل"), s["h3"]))
    story.append(Paragraph(fa("پس از وارد کردن کلید، می‌توانید مدل هوش مصنوعی را انتخاب کنید:"), s["body"]))
    models = [
        "gapgpt-qwen-3.5 — سریع و اقتصادی (پیش‌فرض)",
        "gpt-4o — دقیق‌تر، برای پایگاه دانش پیچیده",
        "gpt-4o-mini — تعادل بین سرعت و دقت",
    ]
    for m in models:
        story.append(bullet(m, s))

    story.append(Paragraph(fa("تست اتصال"), s["h3"]))
    story.append(Paragraph(
        fa("پس از وارد کردن کلید API، روی دکمه «تست اتصال» کلیک کنید. "
           "اگر اتصال موفق بود، می‌توانید ارائه‌دهنده را فعال کنید."), s["body"]))
    story.append(note_box(
        "اگر پایگاه دانش شما شامل مراحل دقیق و اعداد مشخص است، از مدل‌های قوی‌تر مثل "
        "gpt-4o استفاده کنید تا هیچ جزئیاتی حذف نشود.",
        "tip", TIP_BG, TIP_BORDER, s))
    story.append(Spacer(1, 6 * mm))

    # ══════════════════════════════════════════════════════════════════════════
    # 7. پایگاه دانش — دسته‌بندی‌ها
    # ══════════════════════════════════════════════════════════════════════════
    story.append(Paragraph(fa("۷. پایگاه دانش — دسته‌بندی‌ها"), s["h2"]))
    story.append(section_divider())
    story.append(Paragraph(
        fa("دسته‌بندی‌ها به شما کمک می‌کنند پایگاه دانش را سازماندهی کنید. "
           "هر دسته‌بندی یک عنوان و توضیحات دارد."), s["body"]))
    story.append(Paragraph(
        fa("وقتی مشتری تیکت ثبت می‌کند، می‌تواند دسته‌بندی مرتبط را انتخاب کند. "
           "بج «مرتبط» روی هر دسته، تعداد پاسخ‌های آماده آن دسته را نشان می‌دهد."), s["body"]))

    story.append(Paragraph(fa("مدیریت دسته‌بندی‌ها"), s["h3"]))
    cat_items = [
        "از منو «پایگاه دانش ← دسته‌بندی‌ها» وارد شوید.",
        "با دکمه «افزودن دسته» دسته جدید بسازید.",
        "برای ویرایش یا حذف، از آیکون‌های کنار هر دسته استفاده کنید.",
        "حذف دسته، پاسخ‌های آماده آن دسته را حذف نمی‌کند — فقط دسته‌بندی آن‌ها برداشته می‌شود.",
    ]
    for item in cat_items:
        story.append(bullet(item, s))
    story.append(Spacer(1, 6 * mm))

    # ══════════════════════════════════════════════════════════════════════════
    # 8. پاسخ‌های آماده
    # ══════════════════════════════════════════════════════════════════════════
    story.append(Paragraph(fa("۸. پاسخ‌های آماده"), s["h2"]))
    story.append(section_divider())
    story.append(Paragraph(
        fa("پاسخ‌های آماده همان پایگاه دانش شما هستند. هوش مصنوعی این پاسخ‌ها را می‌خواند "
           "و بر اساس آن‌ها به کاربر جواب می‌دهد."), s["body"]))

    story.append(Paragraph(fa("نکات مهم برای نوشتن پاسخ‌های مؤثر"), s["h3"]))
    tips_items = [
        "مراحل را شماره‌گذاری کنید — هوش مصنوعی همه مراحل را دقیقاً بازتولید می‌کند.",
        "اعداد و مشخصات دقیق را عیناً بنویسید.",
        "هر پاسخ یک موضوع مشخص داشته باشد.",
        "از HTML ویرایشگر استفاده کنید تا پاسخ‌ها خوانا باشند.",
    ]
    for item in tips_items:
        story.append(bullet(item, s))

    story.append(Paragraph(fa("فیلتر بر اساس دسته"), s["h3"]))
    story.append(Paragraph(
        fa("در صفحه پاسخ‌های آماده، می‌توانید با منوی بالای صفحه فقط پاسخ‌های یک دسته "
           "خاص را ببینید."), s["body"]))
    story.append(note_box(
        "هوش مصنوعی همیشه کل پایگاه دانش را بررسی می‌کند — حتی اگر کاربر دسته اشتباهی "
        "انتخاب کرده باشد. امتیازدهی کلیدواژه‌ای مرتبط‌ترین پاسخ‌ها را پیدا می‌کند.",
        "note", BRAND_BG, BRAND, s))
    story.append(Spacer(1, 6 * mm))

    # ══════════════════════════════════════════════════════════════════════════
    # 9. تیکت از دید کاربر
    # ══════════════════════════════════════════════════════════════════════════
    story.append(Paragraph(fa("۹. تیکت از دید کاربر"), s["h2"]))
    story.append(section_divider())
    story.append(Paragraph(
        fa("مشتریان از آدرس yoursite.com/helpdesk وارد می‌شوند. "
           "برای ثبت تیکت باید در وردپرس حساب کاربری داشته باشند."), s["body"]))

    story.append(Paragraph(fa("ثبت تیکت جدید"), s["h3"]))
    user_steps = [
        "روی «تیکت جدید» کلیک کنید.",
        "عنوان، دسته‌بندی و اولویت را مشخص کنید.",
        "متن سوال را بنویسید (می‌توانید فایل پیوست کنید).",
        "روی «ارسال» کلیک کنید.",
    ]
    for i, step in enumerate(user_steps, 1):
        story.append(bullet(step, s, num=i))

    story.append(Spacer(1, 3 * mm))
    story.append(Paragraph(
        fa("اگر هوش مصنوعی پاسخی پیدا کند، کاربر به صفحه «پاسخ هوشمند» هدایت می‌شود "
           "و دو گزینه دارد:"), s["body"]))
    story.append(bullet("مشکل حل شد: تیکت با پاسخ هوشمند بسته می‌شود.", s))
    story.append(bullet("ادامه با پشتیبان: پیام ارجاع به کارشناس در چت ظاهر می‌شود.", s))
    story.append(Spacer(1, 6 * mm))

    # ══════════════════════════════════════════════════════════════════════════
    # 10. مدیریت تیکت (ادمین)
    # ══════════════════════════════════════════════════════════════════════════
    story.append(Paragraph(fa("۱۰. مدیریت تیکت (ادمین)"), s["h2"]))
    story.append(section_divider())
    story.append(Paragraph(
        fa("ادمین از آدرس yoursite.com/helpdesk-admin وارد پنل مدیریت می‌شود."), s["body"]))

    story.append(Paragraph(fa("صف تیکت‌ها"), s["h3"]))
    story.append(Paragraph(fa("تیکت‌ها بر اساس وضعیت نمایش داده می‌شوند. می‌توانید:"), s["body"]))
    admin_items = [
        "با کلیک روی تیکت، مکالمه را باز کنید و پاسخ دهید.",
        "وضعیت تیکت را تغییر دهید.",
        "تیکت را حذف کنید.",
        "با فیلتر وضعیت و جستجو، تیکت‌ها را پیدا کنید.",
    ]
    for item in admin_items:
        story.append(bullet(item, s))

    story.append(Paragraph(fa("پاسخ دادن"), s["h3"]))
    story.append(Paragraph(
        fa("در صفحه تیکت، پاسخ خود را تایپ کنید و ارسال کنید. "
           "پس از ارسال پاسخ ادمین، وضعیت تیکت به «پاسخ داده شده» تغییر می‌کند."), s["body"]))
    story.append(Spacer(1, 6 * mm))

    # ══════════════════════════════════════════════════════════════════════════
    # 11. وضعیت‌های تیکت
    # ══════════════════════════════════════════════════════════════════════════
    story.append(Paragraph(fa("۱۱. وضعیت‌های تیکت"), s["h2"]))
    story.append(section_divider())
    story.append(Paragraph(fa("هر تیکت یکی از وضعیت‌های زیر را دارد:"), s["body"]))
    story.append(Spacer(1, 3 * mm))

    header_style = ParagraphStyle(
        "th", fontName="Ravi", fontSize=10, textColor=GRAY_900,
        alignment=TA_RIGHT, leading=18,
    )
    cell_style = ParagraphStyle(
        "td", fontName="Ravi", fontSize=10, textColor=GRAY_700,
        alignment=TA_RIGHT, leading=18,
    )
    table_data = [
        [Paragraph(fa("وضعیت"), header_style),
         Paragraph(fa("توضیح"), header_style),
         Paragraph(fa("نمایش به کاربر"), header_style)],
        [Paragraph(fa("بررسی نشده"), cell_style),  Paragraph(fa("تیکت تازه ثبت شده"), cell_style),        Paragraph(fa("در انتظار"), cell_style)],
        [Paragraph(fa("درحال بررسی"), cell_style),  Paragraph(fa("ادمین در حال بررسی است"), cell_style),   Paragraph(fa("در انتظار"), cell_style)],
        [Paragraph(fa("در انتظار پاسخ"), cell_style), Paragraph(fa("ادمین پاسخ داده، منتظر کاربر"), cell_style), Paragraph(fa("پاسخ داده شده"), cell_style)],
        [Paragraph(fa("پاسخ داده شده"), cell_style), Paragraph(fa("کاربر جواب داده"), cell_style),         Paragraph(fa("پاسخ داده شده"), cell_style)],
        [Paragraph(fa("بسته شده"), cell_style),     Paragraph(fa("تیکت بسته شده"), cell_style),            Paragraph(fa("بسته شده"), cell_style)],
        [Paragraph(fa("پاسخ هوشمند"), cell_style),  Paragraph(fa("با تأیید کاربر از پاسخ AI بسته شد"), cell_style), Paragraph(fa("بسته شده"), cell_style)],
        [Paragraph(fa("اسپم"), cell_style),          Paragraph(fa("تیکت اسپم"), cell_style),                Paragraph(fa("در انتظار"), cell_style)],
    ]
    col_w = [42 * mm, 73 * mm, 40 * mm]
    status_table = Table(table_data, colWidths=col_w, repeatRows=1)
    status_table.setStyle(TableStyle([
        ("BACKGROUND",    (0, 0), (-1, 0), BRAND),
        ("TEXTCOLOR",     (0, 0), (-1, 0), colors.white),
        ("ROWBACKGROUNDS",(0, 1), (-1, -1), [colors.white, GRAY_100]),
        ("GRID",          (0, 0), (-1, -1), 0.5, GRAY_200),
        ("TOPPADDING",    (0, 0), (-1, -1), 6),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 6),
        ("LEFTPADDING",   (0, 0), (-1, -1), 8),
        ("RIGHTPADDING",  (0, 0), (-1, -1), 8),
        ("VALIGN",        (0, 0), (-1, -1), "MIDDLE"),
    ]))
    story.append(status_table)
    story.append(Spacer(1, 6 * mm))

    # ══════════════════════════════════════════════════════════════════════════
    # 12. پاسخ هوشمند — جریان کار
    # ══════════════════════════════════════════════════════════════════════════
    story.append(Paragraph(fa("۱۲. جریان پاسخ هوشمند"), s["h2"]))
    story.append(section_divider())
    story.append(Paragraph(
        fa("هنگام ثبت تیکت جدید، اگر هوش مصنوعی فعال باشد، سیستم این مراحل را طی می‌کند:"), s["body"]))

    ai_steps = [
        "پایگاه دانش بر اساس کلیدواژه‌های تیکت امتیازدهی می‌شود.",
        "اگر هیچ همپوشانی کلیدواژه‌ای وجود نداشته باشد، تیکت مستقیم به صف ادمین می‌رود.",
        "بهترین N پاسخ (TOP K) به هوش مصنوعی فرستاده می‌شود.",
        "هوش مصنوعی یک پاسخ جدید بر اساس پایگاه دانش می‌نویسد.",
        "اگر پاسخ تولید شد، کاربر به صفحه «پاسخ هوشمند» هدایت می‌شود.",
        "اگر پاسخی یافت نشد، پیام ارجاع به کارشناس ظاهر می‌شود.",
    ]
    for i, step in enumerate(ai_steps, 1):
        story.append(bullet(step, s, num=i))

    story.append(Spacer(1, 3 * mm))
    story.append(note_box(
        "تیکت‌های حل‌شده با AI در پنل ادمین با بج «پاسخ هوشمند» مشخص می‌شوند "
        "و در آمار جداگانه شمرده می‌شوند.",
        "tip", TIP_BG, TIP_BORDER, s))
    story.append(Spacer(1, 6 * mm))

    # ══════════════════════════════════════════════════════════════════════════
    # 13. سوالات متداول
    # ══════════════════════════════════════════════════════════════════════════
    story.append(Paragraph(fa("۱۳. سوالات متداول"), s["h2"]))
    story.append(section_divider())

    faqs = [
        ("آیا می‌توانم چند ارائه‌دهنده هوش مصنوعی را همزمان فعال کنم؟",
         "خیر. در هر زمان فقط یک ارائه‌دهنده می‌تواند فعال باشد. با فعال کردن یک ارائه‌دهنده، "
         "بقیه به صورت خودکار غیرفعال می‌شوند."),
        ("اگر کاربر دسته اشتباهی انتخاب کند چه می‌شود؟",
         "هیچ مشکلی نیست. سیستم همیشه کل پایگاه دانش را بررسی می‌کند و بر اساس کلیدواژه‌های "
         "متن تیکت، مرتبط‌ترین پاسخ‌ها را پیدا می‌کند."),
        ("آیا هوش مصنوعی اطلاعات بیرون از پایگاه دانش استفاده می‌کند؟",
         "در حالت «فقط پایگاه دانش» خیر — پاسخ کاملاً بر اساس اطلاعات شماست. "
         "در حالت «پایگاه دانش + هوش مصنوعی» اگر اطلاعات کافی نباشد، "
         "از دانش عمومی هم کمک می‌گیرد."),
        ("آیا پاسخ‌های AI ذخیره می‌شوند؟",
         "بله. هر پاسخ AI در پایگاه داده وردپرس ذخیره می‌شود و ادمین می‌تواند "
         "آن را در پنل مدیریت ببیند."),
        ("آیا کاربران برای ثبت تیکت باید عضو شوند؟",
         "بله. کاربران باید حساب کاربری وردپرس داشته باشند و وارد شده باشند "
         "تا بتوانند تیکت ثبت کنند."),
        ("آیا می‌توانم افزونه را در وردپرس نصب‌شده در زیرشاخه استفاده کنم؟",
         "بله. افزونه مسیر وردپرس را به صورت خودکار تشخیص می‌دهد و React Router را "
         "بر اساس آن تنظیم می‌کند."),
    ]
    for q, a in faqs:
        story.append(KeepTogether([
            Paragraph(fa(q), s["h3"]),
            Paragraph(fa(a), s["body"]),
            Spacer(1, 2 * mm),
        ]))

    # ── Footer ───────────────────────────────────────────────────────────────
    story.append(Spacer(1, 10 * mm))
    story.append(HRFlowable(width="100%", thickness=1, color=GRAY_200))
    story.append(Spacer(1, 4 * mm))
    story.append(Paragraph(fa("WP AI Support — wpaisupport.ir"), s["footer"]))
    story.append(Paragraph(fa("خرید افزونه: www.rtl-theme.com"), s["footer"]))

    doc.build(story)
    print(f"PDF saved: {OUTPUT}")


if __name__ == "__main__":
    build_pdf()
