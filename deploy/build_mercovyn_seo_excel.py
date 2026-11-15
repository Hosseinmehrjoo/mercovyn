#!/usr/bin/env python3
"""Generate Mercovyn 6-month SEO execution Excel on Desktop."""
from __future__ import annotations

from datetime import date, timedelta
from pathlib import Path

from openpyxl import Workbook
from openpyxl.styles import Alignment, Font, PatternFill
from openpyxl.utils import get_column_letter

OUT = Path("/home/hossein/Desktop/Mercovyn-SEO-Roadmap-6M.xlsx")
START = date(2026, 11, 19)  # ۲۸ آبان ۱۴۰۵
END = START + timedelta(days=183)  # حدود ۶ ماه
START_JALALI = "۱۴۰۵/۰۸/۲۸"

ARTICLES = [
    (1, "What Is Baler Twine?", "What Is Baler Twine"),
    (1, "Types of Polypropylene Baler Twine", "Types of Baler Twine"),
    (1, "How to Choose the Best Baler Twine", "Best Baler Twine"),
    (1, "PP Twine vs. Sisal Twine", "PP Twine vs Sisal"),
    (2, "Tensile Strength vs. Knot Strength", "Knot Strength"),
    (2, "How UV Resistance Affects Baler Twine", "UV Resistant Twine"),
    (2, "Round vs. Square Baler Twine", "Baler Twine Types"),
    (2, "Why Does Baler Twine Break?", "Baler Twine Breaking"),
    (3, "How Much Twine Does a Hay Baler Use?", "Baler Twine Usage"),
    (3, "How to Store Baler Twine", "Baler Twine Storage"),
    (3, "Baler Twine Weight and Length Explained", "Baler Twine Length"),
    (3, "What Makes High-Quality PP Twine?", "High Quality PP Twine"),
    (4, "Wholesale Baler Twine Buying Guide", "Wholesale Baler Twine"),
    (4, "How to Find a Reliable Twine Supplier", "Baler Twine Supplier"),
    (4, "Custom Color Baler Twine", "Custom Color Twine"),
    (4, "How to Import Agricultural Twine", "Import Baler Twine"),
    (5, "Baler Twine Market in Armenia", "Baler Twine Armenia"),
    (5, "Agricultural Twine Suppliers in Georgia", "Twine Supplier Georgia"),
    (5, "Importing Baler Twine into Iraq", "Baler Twine Iraq"),
    (5, "PP Twine Supply in Central Asia", "PP Twine Central Asia"),
    (6, "How to Select Twine for High-Density Balers", "High Density Baler Twine"),
    (6, "Understanding Baler Twine Specifications", "Baler Twine Specifications"),
    (6, "How to Calculate Baler Twine Requirements", "Baler Twine Calculator"),
    (6, "What Agricultural Distributors Should Know About PP Twine", "PP Twine Distributor"),
]

KEYWORD_MAP = [
    ("Homepage", "Baler Twine Manufacturer", "جذب مشتری عمده", "/"),
    ("Products", "Polypropylene Baler Twine", "معرفی محصول", "/products/"),
    ("Pink Twine", "Pink Baler Twine", "سفارش رنگ صورتی", "/product/pink-baler-twine/"),
    ("Black Twine", "Black Baler Twine", "سفارش رنگ مشکی", "/product/black-baler-twine/"),
    ("Custom Colors", "Custom Color Baler Twine", "سفارش اختصاصی", "/ (section)"),
    ("Specifications", "Baler Twine Specifications", "بررسی فنی", "/feature/ یا صفحه جدید"),
    ("Wholesale", "Wholesale Baler Twine", "مشتری عمده", "/wholesale/ (جدید)"),
    ("Export", "Baler Twine Exporter", "صادرات", "/export/ (جدید)"),
    ("Armenia", "Baler Twine Supplier Armenia", "بازار محلی", "/countries/ یا /hy/"),
    ("Contact", "Baler Twine Supplier", "درخواست قیمت", "/contact/"),
]

def build_article_slots() -> list[tuple[date, str, str, int]]:
    """۳ مقاله در هفته — دوشنبه، چهارشنبه، جمعه (۰، ۲، ۴)."""
    slots: list[tuple[date, str, str, int]] = []
    d = START
    while d.weekday() not in (0, 2, 4):
        d += timedelta(days=1)
    ai = 0
    while ai < len(ARTICLES) and d <= END:
        if d.weekday() in (0, 2, 4):
            m, title, kw = ARTICLES[ai]
            slots.append((d, title, kw, m))
            ai += 1
        d += timedelta(days=1)
    return slots

# Base weekly templates (extra tasks per phase)
def phase_for_day(offset: int) -> str:
    if offset < 30:
        return "ماه ۱ — زیرساخت"
    if offset < 60:
        return "ماه ۲ — رشد"
    if offset < 90:
        return "ماه ۳ — اعتبار"
    if offset < 120:
        return "ماه ۴ — محتوا و RU"
    if offset < 150:
        return "ماه ۵ — بازار عراق و CA"
    return "ماه ۶ — بهینه‌سازی"


def tasks_for_day(
    cur: date,
    offset: int,
    week: int,
    article_slots: list[tuple[date, str, str, int]],
) -> list[tuple[str, str, str, str]]:
    wd = cur.weekday()
    out: list[tuple[str, str, str, str]] = []
    art_today = [a for a in article_slots if a[0] == cur]

    for _, title, kw, _m in art_today:
        out.append(
            (
                "محتوا",
                f"انتشار مقاله: {title}",
                "بالا",
                f"Keyword: {kw} | چک لیست مقاله + FAQ + CTA + لینک داخلی",
            )
        )

    if offset < 7:
        week1 = [
            ("آغاز", f"شروع رسمی برنامه سئو — {START_JALALI}", "حیاتی", "چک لیست تیم و دسترسی‌ها"),
            ("فنی", "GSC: Performance + Indexing + Coverage", "بحرانی", "گزارش هفته ۱"),
            ("فنی", "بررسی robots.txt و sitemap.xml", "بحرانی", "رفع خطاها"),
            ("فنی", "Canonical و ریدایرکت‌ها (www/https)", "بالا", "لیست URLهای مشکل‌دار"),
            ("استراتژی", "Keyword Mapping نهایی (جدول صفحات)", "بالا", "سند تأییدشده"),
            ("صفحات", "بهینه‌سازی صفحه اصلی (H1/متا/سکشن‌ها)", "حیاتی", "مطابق roadmap"),
            ("صفحات", "بهینه‌سازی /products و کارت محصول", "بالا", "متا + لینک داخلی"),
        ]
        if offset < len(week1):
            cat, task, pri, deliv = week1[offset]
            out.append((cat, task, pri, deliv))
        if offset == 6:
            out.append(("فنی", "Hreflang en/ru/hy/ar + x-default", "بالا", "تست URLها"))
            out.append(("گزارش", "جمع‌بندی هفته ۱ — اولویت‌های هفته بعد", "متوسط", "یادداشت داخلی"))
    elif offset < 14:
        if wd == 0:
            out.append(("محصول", "مشخصات فنی: Tensile vs Knot جدا + جدول", "حیاتی", "داده آزمایشگاه"))
        elif wd == 1:
            out.append(("Schema", "Organization + Product + Breadcrumb", "بالا", "Rich Results Test"))
        elif wd == 2 and not art_today:
            out.append(("صفحات", "صفحات محصول رنگی (Pink/Black/Custom)", "حیاتی", "محتوای غیرتکراری"))
        elif wd == 3:
            out.append(("CRO", "فرم Wholesale Quote + رویدادهای GA4", "بالا", "generate_lead"))
        elif wd == 4:
            out.append(("صفحات", "پیش‌نویس صفحه Wholesale", "بسیار بالا", "URL /wholesale/"))
        elif wd == 5:
            out.append(("صفحات", "پیش‌نویس صفحه Export", "بسیار بالا", "URL /export/"))
        elif wd == 6:
            out.append(("گزارش", "گزارش هفته ۲ — ایندکس و کلیک", "متوسط", "GSC export"))
    elif offset < 30:
        if wd == 0 and week % 2 == 0:
            out.append(("بین‌الملل", "بازبینی ترجمه RU/HY/AR (نه ماشینی خام)", "بالا", "صفحات کلیدی"))
        if wd == 1 and offset == 21:
            out.append(("صفحات", "صفحه بازار Armenia", "بالا", "Baler Twine Supplier Armenia"))
        if wd == 3 and offset == 24:
            out.append(("لینک", "ثبت ۳ دایرکتوری B2B/کشاورزی", "متوسط", "NAP یکسان"))
        if wd == 5 and not art_today and offset > 14:
            out.append(("داخلی", "لینک‌سازی داخلی: بلاگ → محصول → تماس", "بالا", "حداقل ۳ لینک/صفحه"))
        if wd == 6:
            out.append(("گزارش", "KPI ماه ۱: کلیک، Impression، Lead", "بالا", "جدول KPI"))
    elif offset < 60:
        if wd == 0:
            out.append(("صفحات", "صفحه Georgia (صادرات)", "بالا", "Twine Supplier Georgia"))
        if wd == 2 and offset == 38:
            out.append(("لینک", "۳ بک‌لینک ماه ۲", "متوسط", "ثبت + پیگیری"))
        if wd == 4:
            out.append(("SEO", "بررسی CTR عناوین پایین — اصلاح Title/Meta", "بالا", "A/B در GSC"))
        if wd == 6:
            out.append(("گزارش", "گزارش ماه ۲", "بالا", "KPI + Lead by country"))
    elif offset < 90:
        if wd == 0:
            out.append(("صفحات", "صفحه Iraq / بازارهای هدف", "بالا", "Baler Twine Iraq"))
        if wd == 1 and offset == 65:
            out.append(("اعتبار", "رپورتاژ/مقاله مهمان کشاورزی", "بالا", "۱ فرصت محتوا"))
        if wd == 3:
            out.append(("SEO", "Keyword Gap vs رقبا (Ahrefs/Semrush)", "متوسط", "لیست فرصت"))
        if wd == 5:
            out.append(("CRO", "تحلیل صفحات با Impression بدون Lead", "بالا", "اقدام اصلاح"))
        if wd == 6:
            out.append(("گزارش", "گزارش ماه ۳ — ۱۲ مقاله؟", "بالا", "KPI"))
    elif offset < 120:
        if wd == 0:
            out.append(("محتوا", "به‌روزرسانی مقالات قدیمی + لینک داخلی", "متوسط", "۲ پست"))
        if wd == 2:
            out.append(("بین‌الملل", "توسعه محتوای RU برای صفحات Export", "بالا", ""))
        if wd == 4:
            out.append(("لینک", "۴ دامنه ارجاع جدید (ماه ۴)", "متوسط", ""))
        if wd == 6:
            out.append(("گزارش", "گزارش ماه ۴", "بالا", ""))
    elif offset < 150:
        if wd == 0:
            out.append(("بازار", "تمرکز عراق + آسیای مرکزی — محتوا/صفحه", "بالا", ""))
        if wd == 3:
            out.append(("لینک", "۵ دامنه جدید", "متوسط", ""))
        if wd == 6:
            out.append(("گزارش", "گزارش ماه ۵ — Lead by keyword", "بالا", ""))
    else:
        if wd == 0:
            out.append(("SEO", "بهبود صفحات رتبه ۱۱–۲۰", "بالا", ""))
        if wd == 2:
            out.append(("لینک", "۵ دامنه نهایی + غیرطبیعی?", "متوسط", "فقط کیفیت"))
        if wd == 4:
            out.append(("استراتژی", "بازنگری ۶ ماهه + برنامه فاز بعد", "بالا", "سند نهایی"))
        if wd == 6:
            out.append(("گزارش", "گزارش پایانی KPI و Lead", "حیاتی", ""))

    if not out and wd < 5:
        out.append(("پیگیری", "پاسخ به سرنخ‌ها / به‌روزرسانی GSC", "متوسط", "روزانه ۱۵ دقیقه"))

    return out


def style_header(ws, row: int = 1):
    fill = PatternFill("solid", fgColor="15321A")
    font = Font(color="FFFFFF", bold=True)
    for cell in ws[row]:
        if cell.value:
            cell.fill = fill
            cell.font = font
            cell.alignment = Alignment(horizontal="center", vertical="center", wrap_text=True)


def autosize(ws, max_w=55):
    for col in ws.columns:
        letter = get_column_letter(col[0].column)
        width = min(max_w, max((len(str(c.value or "")) for c in col)) + 2)
        ws.column_dimensions[letter].width = width


def main():
    article_slots = build_article_slots()
    wb = Workbook()

    # --- Overview ---
    ws = wb.active
    ws.title = "راهنما"
    overview = [
        ["Mercovyn SEO Roadmap — برنامه اجرایی ۶ ماهه"],
        [f"شروع: {START_JALALI} ({START.isoformat()}) | پایان: {END.isoformat()}"],
        [""],
        ["محورها", "Commercial SEO | International SEO | Topical Authority | Conversion SEO"],
        ["مقالات", "۲۴ مقاله | ۳ مقاله در هفته (دوشنبه، چهارشنبه، جمعه)"],
        ["KPI هدف (پیشنهادی)", "۸–۱۲ صفحه تجاری | ۲۰ کلمه در ۲۰ اول | ۱۰–۲۰ Lead | ۱۵–۲۵ دامنه لینک"],
        [""],
        ["اولویت فوری (قبل/همزمان با سئو)"],
        ["۱", "جداسازی Tensile Strength و Knot Strength با اعداد واقعی"],
        ["۲", "یکسان‌سازی داستان تولید ایران / توزیع ارمنستان"],
        ["۳", "شفاف‌سازی سابقه ۲۰ سال تولید vs صادرات از ۲۰۱۴"],
        ["۴", "ایمیل sales@mercovyn.com و NAP یکسان در دایرکتوری‌ها"],
        ["۵", "تصاویر واقعی تولید، بسته‌بندی، QC"],
        [""],
        ["زبان‌ها", "en (ریشه) | /ru/ | /hy/ | /ar/ — hreflang"],
        ["بازارهای هدف", "AM → GE → IQ → UZ → TM → TJ (+ ایران به‌عنوان مبدأ تولید در محتوا)"],
    ]
    for row in overview:
        ws.append(row)
    ws["A1"].font = Font(bold=True, size=14)

    # --- Keyword mapping ---
    ws2 = wb.create_sheet("Keyword Mapping")
    ws2.append(["صفحه", "کلمه کلیدی اصلی", "هدف", "URL پیشنهادی"])
    for r in KEYWORD_MAP:
        ws2.append(r)
    style_header(ws2)
    autosize(ws2)

    # --- Articles ---
    ws3 = wb.create_sheet("تقویم مقالات")
    ws3.append(["تاریخ انتشار", "روز", "ماه برنامه", "عنوان مقاله", "کلمه کلیدی", "وضعیت", "یادداشت"])
    fa_days = ["دوشنبه", "سه‌شنبه", "چهارشنبه", "پنجشنبه", "جمعه", "شنبه", "یکشنبه"]
    for d, title, kw, m in article_slots:
        ws3.append([d.isoformat(), fa_days[d.weekday()], f"ماه {m}", title, kw, "برنامه‌ریزی", "FAQ + CTA + لینک محصول"])
    style_header(ws3)
    autosize(ws3)

    # --- Daily plan ---
    ws4 = wb.create_sheet("برنامه روزانه")
    headers = [
        "تاریخ",
        "روز",
        "هفته",
        "ماه",
        "فاز",
        "دسته",
        "کار",
        "اولویت",
        "خروجی / معیار",
        "وضعیت",
    ]
    ws4.append(headers)
    cur = START
    offset = 0
    week = 1
    while cur <= END:
        if cur.weekday() == 0 and offset > 0:
            week += 1
        month_num = min(6, offset // 30 + 1)
        for cat, task, pri, deliv in tasks_for_day(cur, offset, week, article_slots):
            ws4.append(
                [
                    cur.isoformat(),
                    fa_days[cur.weekday()],
                    week,
                    month_num,
                    phase_for_day(offset),
                    cat,
                    task,
                    pri,
                    deliv,
                    "انجام نشده",
                ]
            )
        cur += timedelta(days=1)
        offset += 1

    style_header(ws4)
    ws4.freeze_panes = "A2"
    autosize(ws4)

    # --- 90-day focus ---
    ws5 = wb.create_sheet("۹۰ روز اول")
    ws5.append(["هفته", "فعالیت", "خروجی"])
    ninety = [
        (1, "GSC + Indexing + robots + sitemap", "گزارش فنی"),
        (1, "Keyword Mapping", "جدول تأییدشده"),
        (2, "صفحه اصلی + محصولات + Schema", "صفحات بهینه"),
        (2, "۴ مقاله (۳+۱)", "انتشار"),
        (3, "Wholesale + Export + فرم Lead", "صفحات + GA4 events"),
        (3, "مشخصات فنی جدول", "Tensile/Knot"),
        (4, "صفحات رنگ + Armenia", "صفحات جدید"),
        (4, "۴ مقاله", "انتشار"),
        (5, "Georgia + لینک‌سازی", "۳ دامنه"),
        (6, "RU/HY/AR بازبینی", "ترجمه انسانی"),
        (7, "عراق + رپورتاژ", "صفحه + لینک"),
        (8, "CTR + Keyword Gap", "اصلاحات"),
        ("9-12", "ادامه ۳ مقاله/هفته + KPI ماهانه", "Lead tracking"),
        (13, "تحلیل Conversion", "گزارش ماه ۳"),
    ]
    for row in ninety:
        ws5.append(list(row))
    style_header(ws5)
    autosize(ws5)

    # --- Link building ---
    ws6 = wb.create_sheet("لینک‌سازی")
    ws6.append(["ماه", "دامنه جدید (هدف)", "نوع", "انکر پیشنهادی", "وضعیت"])
    link_plan = [
        (1, 3, "Agricultural / B2B Directory", "Mercovyn Baler Twine"),
        (2, 3, "Export Directory", "Polypropylene Baler Twine"),
        (3, 4, "Farming blog guest post", "Agricultural Twine Supplier"),
        (4, 4, "Industrial directory", "Baler Twine Manufacturer"),
        (5, 5, "Packaging / trade news", "Mercovyn"),
        (6, 5, "کیفیت > کمیت", "Brand"),
    ]
    for m, n, typ, anchor in link_plan:
        ws6.append([m, n, typ, anchor, ""])
    style_header(ws6)
    autosize(ws6)

    # --- KPI ---
    ws7 = wb.create_sheet("KPI ماهانه")
    ws7.append(
        [
            "ماه",
            "Organic Clicks",
            "Impressions",
            "Avg Position",
            "CTR%",
            "Indexed Pages",
            "Sessions",
            "Leads",
            "Referring Domains",
            "کلیدواژه Top20",
            "یادداشت",
        ]
    )
    for m in range(1, 7):
        ws7.append([f"ماه {m}", "", "", "", "", "", "", "", "", "", ""])
    style_header(ws7)
    autosize(ws7)

    # --- Technical checklist ---
    ws8 = wb.create_sheet("چک‌لیست فنی")
    ws8.append(["اقدام", "اولویت", "هدف زمانی", "انجام شد؟", "یادداشت"])
    tech = [
        ("Indexing / Coverage در GSC", "بحرانی", "هفته ۱", "", ""),
        ("robots.txt", "بالا", "هفته ۱", "", ""),
        ("sitemap.xml بازتولید", "بالا", "هفته ۱", "", ""),
        ("Canonical صفحات چندزبانه", "بالا", "هفته ۱–۲", "", ""),
        ("Hreflang en ru hy ar", "بالا", "هفته ۲", "", ""),
        ("Core Web Vitals", "بالا", "ماه ۱", "", ""),
        ("تصاویر WebP + alt", "متوسط", "ماه ۱", "", ""),
        ("Schema Organization/Product/Article", "بالا", "ماه ۱", "", ""),
        ("GA4: generate_lead, whatsapp_click, …", "بالا", "ماه ۱", "", ""),
        ("CWV موبایل", "بالا", "ماه ۱", "", ""),
    ]
    for row in tech:
        ws8.append(row)
    style_header(ws8)
    autosize(ws8)

    wb.save(OUT)
    print(f"Saved {OUT} ({OUT.stat().st_size} bytes)")


if __name__ == "__main__":
    main()