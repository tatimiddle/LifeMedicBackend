"""Scraper reproducible de Farmacias San Nicolás usando Scrapling (D4Vinci/Scrapling).

Extrae información real de medicamentos y productos no medicinales
(nombre, marca, laboratorio, categoría, precios, fórmula/principios activos,
indicaciones, contraindicaciones e imagen real), descarga las imágenes a
public/images/catalog/ y genera resources/data/sannicolas_catalog.json.

Uso:
  python scripts/scrape_sannicolas.py            # Conserva los 19 ítems ancla y completa ~73 ítems únicos (36 medicamentos + 37 productos)
  python scripts/scrape_sannicolas.py --refresh  # Re-scrapea también las 19 anclas desde cero
"""

from __future__ import annotations

import argparse
from concurrent.futures import ThreadPoolExecutor, as_completed
import json
import re
import threading
import time
from pathlib import Path
from typing import Any
from urllib.parse import quote, urljoin

from scrapling.fetchers import Fetcher

BASE_URL = "https://www.farmaciasannicolas.com"
REPO_ROOT = Path(__file__).resolve().parent.parent
OUTPUT_JSON = REPO_ROOT / "resources" / "data" / "sannicolas_catalog.json"
IMAGES_DIR = REPO_ROOT / "public" / "images" / "catalog"

# 19 ítems ancla (preservan códigos e historial de pruebas/órdenes existentes)
TARGETS: list[dict[str, Any]] = [
    # --- MEDICAMENTOS CLÍNICOS ANCLA (MED-00101 .. MED-00110) ---
    {
        "codigo": "MED-00101",
        "catalog_type": "medicine",
        "category": "Medicamentos",
        "override_name": "Paracetamol 500mg (Caja x 20)",
        "query_url": f"{BASE_URL}/productos/keyword/acetaminofen%20500mg",
        "match_any": ["acetaminofen", "paracetamol"],
        "exclude": ["gotas", "jarabe", "codeina", "pediatrico"],
        "prescription": "Venta Libre",
        "batch": "LOT-25A09",
        "expiry": "Nov 2027",
        "expiry_status": "Vigente",
        "stock": 85,
        "min_stock": 20,
        "is_active": True,
    },
    {
        "codigo": "MED-00102",
        "catalog_type": "medicine",
        "category": "Medicamentos",
        "override_name": "Ibuprofeno 400mg (Blíster x 10)",
        "query_url": f"{BASE_URL}/productos/keyword/ibuprofeno%20400",
        "match_any": ["ibuprofeno"],
        "exclude": ["jarabe", "susp"],
        "prescription": "Venta Libre",
        "batch": "LOT-24K18",
        "expiry": "Mar 2025",
        "expiry_status": "Por Vencer",
        "stock": 42,
        "min_stock": 20,
        "is_active": True,
    },
    {
        "codigo": "MED-00103",
        "catalog_type": "medicine",
        "category": "Medicamentos",
        "override_name": "Amoxicilina 500mg (Cápsulas x 12)",
        "query_url": f"{BASE_URL}/productos/keyword/amoxicilina%20500",
        "match_any": ["amoxicilina"],
        "exclude": ["suspension", "jarabe", "acido"],
        "prescription": "Con Receta",
        "batch": "LOT-25B02",
        "expiry": "Ago 2027",
        "expiry_status": "Vigente",
        "stock": 28,
        "min_stock": 15,
        "is_active": True,
    },
    {
        "codigo": "MED-00104",
        "catalog_type": "medicine",
        "category": "Medicamentos",
        "override_name": "Metformina 850mg (Tabletas x 30)",
        "query_url": f"{BASE_URL}/productos/keyword/metformina%20850",
        "match_any": ["metformina", "adiamet", "glucophage", "glafornil"],
        "exclude": [],
        "prescription": "Con Receta",
        "batch": "LOT-24H14",
        "expiry": "Ene 2026",
        "expiry_status": "Vigente",
        "stock": 0,
        "min_stock": 20,
        "is_active": False,
    },
    {
        "codigo": "MED-00105",
        "catalog_type": "medicine",
        "category": "Medicamentos",
        "override_name": "Acetaminofén 500mg Pediátrico Jarabe",
        "query_url": f"{BASE_URL}/productos/keyword/acetaminofen%20jarabe",
        "match_any": ["acetaminofen", "panadol", "fardel"],
        "exclude": ["tableta"],
        "prescription": "Venta Libre",
        "batch": "LOT-24M03",
        "expiry": "Abr 2025",
        "expiry_status": "Por Vencer",
        "stock": 19,
        "min_stock": 20,
        "is_active": True,
    },
    {
        "codigo": "MED-00106",
        "catalog_type": "medicine",
        "category": "Medicamentos",
        "query_url": f"{BASE_URL}/productos/keyword/loratadina%2010mg",
        "match_any": ["loratadina"],
        "exclude": ["jarabe"],
        "prescription": "Venta Libre",
        "batch": "LOT-26C11",
        "expiry": "Sep 2027",
        "expiry_status": "Vigente",
        "stock": 64,
        "min_stock": 15,
        "is_active": True,
    },
    {
        "codigo": "MED-00107",
        "catalog_type": "medicine",
        "category": "Medicamentos",
        "query_url": f"{BASE_URL}/productos/keyword/omeprazol%2020mg",
        "match_any": ["omeprazol"],
        "exclude": ["ampolla"],
        "prescription": "Venta Libre",
        "batch": "LOT-26D04",
        "expiry": "Nov 2027",
        "expiry_status": "Vigente",
        "stock": 52,
        "min_stock": 20,
        "is_active": True,
    },
    {
        "codigo": "MED-00108",
        "catalog_type": "medicine",
        "category": "Medicamentos",
        "query_url": f"{BASE_URL}/productos/keyword/azitromicina%20500",
        "match_any": ["azitromicina"],
        "exclude": ["suspension"],
        "prescription": "Con Receta",
        "batch": "LOT-26E19",
        "expiry": "Dic 2027",
        "expiry_status": "Vigente",
        "stock": 30,
        "min_stock": 10,
        "is_active": True,
    },
    {
        "codigo": "MED-00109",
        "catalog_type": "medicine",
        "category": "Medicamentos",
        "query_url": f"{BASE_URL}/productos/keyword/losartan%2050mg",
        "match_any": ["losartan"],
        "exclude": [],
        "prescription": "Con Receta",
        "batch": "LOT-26F02",
        "expiry": "Oct 2027",
        "expiry_status": "Vigente",
        "stock": 40,
        "min_stock": 15,
        "is_active": True,
    },
    {
        "codigo": "MED-00110",
        "catalog_type": "medicine",
        "category": "Medicamentos",
        "query_url": f"{BASE_URL}/category/gripe-y-tos/01007002",
        "match_any": ["abrilar", "jarabe", "tos"],
        "exclude": [],
        "prescription": "Venta Libre",
        "batch": "LOT-26G08",
        "expiry": "Jul 2027",
        "expiry_status": "Vigente",
        "stock": 33,
        "min_stock": 12,
        "is_active": True,
    },
    # --- PRODUCTOS NO MEDICINALES ANCLA ---
    {
        "codigo": "DER-00205",
        "catalog_type": "product",
        "category": "Dermocosmética",
        "override_name": "Protector Solar Facial FPS 50+ Oil Control",
        "query_url": f"{BASE_URL}/productos/keyword/eucerin%20oil%20control",
        "match_any": ["oil control", "eucerin", "fps", "50"],
        "exclude": [],
        "prescription": "Venta Libre",
        "stock": 35,
        "min_stock": 12,
        "is_active": True,
    },
    {
        "codigo": "VIT-00310",
        "catalog_type": "product",
        "category": "Vitaminas",
        "override_name": "Vitamina C + Zinc 1000mg Efervescente",
        "query_url": f"{BASE_URL}/productos/keyword/vitamina%20c%20efervescente",
        "match_any": ["vitamina c", "efervescente", "redoxon", "cebion", "ademar"],
        "exclude": [],
        "prescription": "Venta Libre",
        "stock": 62,
        "min_stock": 15,
        "is_active": True,
    },
    {
        "codigo": "PER-00412",
        "catalog_type": "product",
        "category": "Cuidado Personal",
        "override_name": "Gel Limpiador Facial Purificante 200ml",
        "query_url": f"{BASE_URL}/productos/keyword/effaclar%20gel",
        "match_any": ["effaclar", "gel", "limpiador", "purificante"],
        "exclude": [],
        "prescription": "Venta Libre",
        "stock": 14,
        "min_stock": 20,
        "is_active": True,
    },
    {
        "codigo": "BEB-00504",
        "catalog_type": "product",
        "category": "Bebé",
        "override_name": "Crema Protectora Pañalitis 110g",
        "query_url": f"{BASE_URL}/productos/keyword/mustela",
        "match_any": ["mustela", "crema", "cicastela", "hidratante", "balsamo"],
        "exclude": ["solar"],
        "prescription": "Venta Libre",
        "stock": 18,
        "min_stock": 10,
        "is_active": True,
    },
    {
        "codigo": "FRA-00608",
        "catalog_type": "product",
        "category": "Fragancias",
        "override_name": "Agua Micelar Termal Suave 400ml",
        "query_url": f"{BASE_URL}/productos/keyword/agua%20micelar",
        "match_any": ["micelar", "sensibio", "h2o", "bioderma", "garnier"],
        "exclude": [],
        "prescription": "Venta Libre",
        "stock": 27,
        "min_stock": 10,
        "is_active": True,
    },
    {
        "codigo": "DER-00206",
        "catalog_type": "product",
        "category": "Dermocosmética",
        "query_url": f"{BASE_URL}/category/proteccion-solar-facial/02103004",
        "match_any": ["anthelios", "spf", "uv"],
        "exclude": [],
        "prescription": "Venta Libre",
        "stock": 22,
        "min_stock": 10,
        "is_active": True,
    },
    {
        "codigo": "PER-00413",
        "catalog_type": "product",
        "category": "Cuidado Personal",
        "query_url": f"{BASE_URL}/category/higiene-y-cuidado-corporal/02107",
        "match_any": ["abradermol", "aceite", "coco", "exfoliante"],
        "exclude": [],
        "prescription": "Venta Libre",
        "stock": 31,
        "min_stock": 10,
        "is_active": True,
    },
    {
        "codigo": "BEB-00505",
        "catalog_type": "product",
        "category": "Bebé",
        "query_url": f"{BASE_URL}/category/cuidados-del-bebe/02111003",
        "match_any": ["johnson", "bebe", "aceite"],
        "exclude": [],
        "prescription": "Venta Libre",
        "stock": 45,
        "min_stock": 12,
        "is_active": True,
    },
    {
        "codigo": "FRA-00609",
        "catalog_type": "product",
        "category": "Fragancias",
        "query_url": f"{BASE_URL}/category/perfumeria/02107012",
        "match_any": ["perfume", "betres"],
        "exclude": [],
        "prescription": "Venta Libre",
        "stock": 16,
        "min_stock": 8,
        "is_active": True,
    },
]

# Expansión curada por consultas diversas para alcanzar exactamente:
# - 36 Medicamentos clínicos (MED-00101 .. MED-00136)
# - 37 Productos comerciales no medicinales (DER/VIT/PER/BEB/FRA)
# = 73 ítems únicos de Farmacias San Nicolás
EXPANSION_BUCKETS: list[dict[str, Any]] = [
    {
        "catalog_type": "medicine",
        "category": "Medicamentos",
        "prefix": "MED",
        "start_num": 111,
        "target_count": 26,
        "queries": [
            {"q": "naproxeno", "prescription": "Venta Libre", "take": 1},
            {"q": "diclofenaco", "prescription": "Venta Libre", "take": 1},
            {"q": "meloxicam", "prescription": "Con Receta", "take": 1},
            {"q": "aspirina forte", "prescription": "Venta Libre", "take": 1},
            {"q": "cetirizina", "prescription": "Venta Libre", "take": 1},
            {"q": "desloratadina", "prescription": "Venta Libre", "take": 1},
            {"q": "fexofenadina", "prescription": "Venta Libre", "take": 1},
            {"q": "ciprofloxacina", "prescription": "Con Receta", "take": 1},
            {"q": "levofloxacina", "prescription": "Con Receta", "take": 1},
            {"q": "cefadroxilo", "prescription": "Con Receta", "take": 1},
            {"q": "amoxicilina acido clavulanico", "prescription": "Con Receta", "take": 1},
            {"q": "enalapril", "prescription": "Con Receta", "take": 1},
            {"q": "amlodipina", "prescription": "Con Receta", "take": 1},
            {"q": "valsartan", "prescription": "Con Receta", "take": 1},
            {"q": "atorvastatina", "prescription": "Con Receta", "take": 1},
            {"q": "rosuvastatina", "prescription": "Con Receta", "take": 1},
            {"q": "glimepirida", "prescription": "Con Receta", "take": 1},
            {"q": "pantoprazol", "prescription": "Venta Libre", "take": 1},
            {"q": "lansoprazol", "prescription": "Venta Libre", "take": 1},
            {"q": "enterogermina", "prescription": "Venta Libre", "take": 1},
            {"q": "loperamida", "prescription": "Venta Libre", "take": 1},
            {"q": "salbutamol", "prescription": "Con Receta", "take": 1},
            {"q": "montelukast", "prescription": "Con Receta", "take": 1},
            {"q": "metocarbamol", "prescription": "Venta Libre", "take": 1},
            {"q": "clotrimazol crema", "prescription": "Venta Libre", "take": 1},
            {"q": "systane", "prescription": "Venta Libre", "take": 1},
            {"q": "afrin", "prescription": "Venta Libre", "take": 1},
            {"q": "albendazol", "prescription": "Venta Libre", "take": 1},
            {"q": "fluconazol", "prescription": "Con Receta", "take": 1},
            {"q": "dolorfin", "prescription": "Venta Libre", "take": 1},
            {"url": f"{BASE_URL}/category/dolor-de-cabeza-y-migrana/01005001", "prescription": "Venta Libre", "take": 5},
            {"url": f"{BASE_URL}/category/antihipertensivo/01003002", "prescription": "Con Receta", "take": 5},
        ],
    },
    {
        "catalog_type": "product",
        "category": "Dermocosmética",
        "prefix": "DER",
        "start_num": 207,
        "target_count": 6,
        "queries": [
            {"q": "cerave", "prescription": "Venta Libre", "take": 2},
            {"q": "isdin", "prescription": "Venta Libre", "take": 2},
            {"q": "vichy", "prescription": "Venta Libre", "take": 1},
            {"q": "avene", "prescription": "Venta Libre", "take": 1},
            {"q": "cetaphil", "prescription": "Venta Libre", "take": 1},
            {"url": f"{BASE_URL}/category/cremas-y-serums/02103001", "prescription": "Venta Libre", "take": 5},
            {"url": f"{BASE_URL}/category/acne/02103003", "prescription": "Venta Libre", "take": 4},
        ],
    },
    {
        "catalog_type": "product",
        "category": "Vitaminas",
        "prefix": "VIT",
        "start_num": 311,
        "target_count": 7,
        "queries": [
            {"q": "centrum", "prescription": "Venta Libre", "take": 1},
            {"q": "pharmaton", "prescription": "Venta Libre", "take": 1},
            {"q": "neurobion", "prescription": "Venta Libre", "take": 1},
            {"q": "magnesio", "prescription": "Venta Libre", "take": 1},
            {"q": "omega 3", "prescription": "Venta Libre", "take": 1},
            {"q": "colageno", "prescription": "Venta Libre", "take": 1},
            {"q": "vitamina d", "prescription": "Venta Libre", "take": 1},
            {"q": "supradyn", "prescription": "Venta Libre", "take": 1},
            {"url": f"{BASE_URL}/category/sistema-inmunologico/01019006", "prescription": "Venta Libre", "take": 5},
            {"url": f"{BASE_URL}/category/fuerza-y-energia/01019003", "prescription": "Venta Libre", "take": 5},
        ],
    },
    {
        "catalog_type": "product",
        "category": "Cuidado Personal",
        "prefix": "PER",
        "start_num": 414,
        "target_count": 6,
        "queries": [
            {"q": "listerine", "prescription": "Venta Libre", "take": 1},
            {"q": "sensodyne", "prescription": "Venta Libre", "take": 1},
            {"q": "colgate total", "prescription": "Venta Libre", "take": 1},
            {"q": "nivea desodorante", "prescription": "Venta Libre", "take": 1},
            {"q": "dove jabon", "prescription": "Venta Libre", "take": 1},
            {"q": "lubriderm", "prescription": "Venta Libre", "take": 1},
            {"q": "pantene", "prescription": "Venta Libre", "take": 1},
            {"url": f"{BASE_URL}/category/desodorantes/02107003", "prescription": "Venta Libre", "take": 4},
            {"url": f"{BASE_URL}/category/cremas-corporales/02107001", "prescription": "Venta Libre", "take": 4},
        ],
    },
    {
        "catalog_type": "product",
        "category": "Bebé",
        "prefix": "BEB",
        "start_num": 506,
        "target_count": 5,
        "queries": [
            {"q": "huggies", "prescription": "Venta Libre", "take": 1},
            {"q": "pampers", "prescription": "Venta Libre", "take": 1},
            {"q": "bepanthen", "prescription": "Venta Libre", "take": 1},
            {"q": "desitin", "prescription": "Venta Libre", "take": 1},
            {"q": "johnson bebe", "prescription": "Venta Libre", "take": 2},
            {"url": f"{BASE_URL}/category/cuidados-del-bebe/02111003", "prescription": "Venta Libre", "take": 5},
            {"url": f"{BASE_URL}/category/panales-y-toallitas/02111002", "prescription": "Venta Libre", "take": 4},
        ],
    },
    {
        "catalog_type": "product",
        "category": "Fragancias",
        "prefix": "FRA",
        "start_num": 610,
        "target_count": 4,
        "queries": [
            {"q": "perfume", "prescription": "Venta Libre", "take": 2},
            {"q": "colonia", "prescription": "Venta Libre", "take": 2},
            {"q": "garnier agua micelar", "prescription": "Venta Libre", "take": 2},
            {"q": "desmaquillante", "prescription": "Venta Libre", "take": 2},
            {"url": f"{BASE_URL}/category/perfumeria/02107012", "prescription": "Venta Libre", "take": 6},
            {"url": f"{BASE_URL}/category/desmaquillantes-y-tonicos/02103002", "prescription": "Venta Libre", "take": 6},
        ],
    },
]

EXPIRY_ROTATION = [
    ("Mar 2027", "Vigente"),
    ("Ago 2027", "Vigente"),
    ("Nov 2027", "Vigente"),
    ("Ene 2028", "Vigente"),
    ("Jun 2028", "Vigente"),
    ("Dic 2026", "Por Vencer"),
    ("Sep 2027", "Vigente"),
    ("Abr 2028", "Vigente"),
]
STOCK_ROTATION = [48, 32, 65, 18, 24, 54, 14, 72, 29, 0, 41, 36]
BATCH_LETTERS = "ABCDEFGHJKLMNPQRSTVWXYZ"


def clean_text(raw: str | None) -> str:
    if not raw:
        return ""
    text = " ".join(raw.split()).strip()
    text = re.sub(r"\.{2,}", ".", text)
    text = re.sub(r"\.(?=[A-ZÁÉÍÓÚÑ])", ". ", text)
    parts = [s.strip() for s in re.split(r"(?<=[.!?])\s+", text) if s.strip()]
    deduped: list[str] = []
    for part in parts:
        if not deduped or part.lower() != deduped[-1].lower():
            deduped.append(part)
    text = " ".join(deduped).strip()
    return re.sub(r"\.{2,}", ".", text)


def parse_price(raw: str | None) -> float | None:
    if not raw:
        return None
    m = re.search(r"\$\s*([0-9]+(?:[.,][0-9]{1,2})?)", raw)
    if not m:
        return None
    return round(float(m.group(1).replace(",", "")), 2)


def extract_root_sku(url: str) -> str:
    slug = url.rstrip("/").split("/")[-1]
    m = re.match(r"^([A-Za-z]+\d+)", slug)
    return m.group(1).upper() if m else slug.upper()


def extract_detail(url: str) -> dict[str, Any] | None:
    try:
        page = Fetcher.get(url, timeout=20)
    except Exception:
        return None
    if page.status != 200:
        return None

    det = page.css(".pg-product-detail")
    if not det:
        return None
    root = det[0]

    name = clean_text(root.css("h1.prod-name::text").get())
    if not name:
        return None

    image_urls: list[str] = []
    for img in root.css(".carousel-product-images img"):
        src = (img.attrib.get("src") or img.attrib.get("data-src") or "").strip()
        if not src or "nodisponible" in src.lower():
            continue
        full_img = urljoin(BASE_URL, src)
        if full_img not in image_urls:
            image_urls.append(full_img)

    if not image_urls:
        return None

    metas: dict[str, str] = {}
    for meta in root.css(".prodd-metas .meta"):
        full = clean_text(" ".join(meta.css("::text").getall()))
        val = clean_text(" ".join(meta.css("a::text").getall()))
        if ":" in full:
            key = clean_text(full.split(":", 1)[0]).lower()
            metas[key] = val or clean_text(full.split(":", 1)[1])

    current_price = parse_price(
        clean_text(" ".join(root.css(".prodd-prices .price-box-item.general strong.price::text").getall()))
    )
    if current_price is None:
        current_price = parse_price(
            clean_text(" ".join(root.css(".prodd-prices strong.price::text").getall()))
        )

    previous_price = parse_price(
        clean_text(" ".join(root.css(".prodd-prices .pri-before::text, .prodd-prices .pri-before strong::text").getall()))
    )

    formula: list[dict[str, str]] = []
    for row in root.css("table tbody tr, table tr"):
        cols = [clean_text(" ".join(td.css("::text").getall())) for td in row.css("td")]
        if len(cols) >= 2 and cols[0]:
            formula.append({"ingredient": cols[0], "amount": cols[1]})

    sections: dict[str, str] = {}
    headers = root.css(".rz-accordion-header")
    panels = root.css(".rz-accordion-content-wrapper")
    for idx, hdr in enumerate(headers):
        title = clean_text(" ".join(hdr.css("::text").getall())).lower()
        if idx < len(panels):
            body = clean_text(" ".join(panels[idx].css(".prodd-info-content ::text").getall()))
            if title and body:
                sections[title] = body

    return {
        "source_name": name,
        "san_nicolas_category": metas.get("categoría") or metas.get("categoria") or "",
        "brand": metas.get("marca") or "",
        "laboratory": metas.get("laboratorio") or metas.get("marca") or "",
        "price": current_price,
        "previous_price": previous_price,
        "formula": formula,
        "indications": sections.get("indicaciones", ""),
        "contraindications": sections.get("contra-indicaciones") or sections.get("contraindicaciones") or "",
        "usage": sections.get("modo de uso") or sections.get("posología") or sections.get("dosis") or "",
        "raw_description": sections.get("descripción") or sections.get("descripcion") or sections.get("indicaciones") or "",
        "source_image_url": image_urls[0],
        "source_url": url,
    }


def download_image(image_url: str, codigo: str) -> str | None:
    IMAGES_DIR.mkdir(parents=True, exist_ok=True)
    try:
        resp = Fetcher.get(image_url, timeout=20)
    except Exception:
        return None
    if resp.status != 200 or not getattr(resp, "body", None):
        return None
    raw = resp.body
    if len(raw) < 1500:
        return None

    ext = "jpg"
    if raw[:8] == b"\x89PNG\r\n\x1a\n":
        ext = "png"
    elif raw[:4] == b"RIFF" and raw[8:12] == b"WEBP":
        ext = "webp"

    target = IMAGES_DIR / f"{codigo}.{ext}"
    target.write_bytes(raw)
    return f"/images/catalog/{codigo}.{ext}"


def rank_links(links: list[str], match_any: list[str], exclude: list[str]) -> list[str]:
    scored: list[tuple[int, str]] = []
    for href in links:
        low = href.lower()
        if any(ex in low for ex in exclude):
            continue
        score = sum(2 for kw in match_any if kw in low)
        if "x-1-" not in low and "x1" not in low.split("/")[-1].lower():
            score += 2
        scored.append((score, href))
    scored.sort(key=lambda pair: pair[0], reverse=True)
    return [href for _, href in scored]


LAB_NORMALIZATION = {
    "glaxo_consuma": "GlaxoSmithKline",
    "bayer-otc": "Bayer Salud",
    "procter-gamble neurobion": "Procter & Gamble",
    "sanofi otc": "Sanofi",
    "colgate palmolive": "Colgate-Palmolive",
    "cerave": "CeraVe",
    "loreal paris": "L'Oréal Paris",
    "palmers": "Palmer's",
    "natures garden": "Nature's Garden",
    "betres": "Betrés",
    "suizos eticos": "Laboratorios Suizos",
    "suizos naturales": "Laboratorios Suizos",
    "suizos pop": "Laboratorios Suizos",
}


def normalize_laboratory_name(raw: str) -> str:
    cleaned = clean_text(raw)
    if not cleaned:
        return "Genérico"
    lowered = cleaned.lower()
    if lowered in LAB_NORMALIZATION:
        return LAB_NORMALIZATION[lowered]
    return cleaned.title()


def build_record(spec: dict[str, Any], picked: dict[str, Any]) -> dict[str, Any]:
    formula_summary = ", ".join(
        f"{f['ingredient']} ({f['amount']})" for f in picked["formula"] if f.get("ingredient")
    )
    primary_ingredient = (
        picked["formula"][0]["ingredient"].title()
        if picked["formula"]
        else spec["category"]
    )
    brand_label = normalize_laboratory_name(picked["brand"] or picked["laboratory"] or "Genérico")
    if brand_label == "Bayer Salud" and (picked["brand"] or "").lower() in {"aspirina", "bepanthene"}:
        brand_label = picked["brand"].title()
    return {
        "codigo": spec["codigo"],
        "catalog_type": spec["catalog_type"],
        "name": spec.get("override_name") or picked["source_name"],
        "source_name": picked["source_name"],
        "category": spec["category"],
        "san_nicolas_category": picked["san_nicolas_category"],
        "active_ingredient": primary_ingredient if spec["catalog_type"] == "medicine" else spec["category"],
        "formula": picked["formula"],
        "formula_summary": formula_summary,
        "brand": brand_label,
        "laboratory": normalize_laboratory_name(picked["laboratory"] or picked["brand"] or "Genérico"),
        "batch": spec.get("batch"),
        "expiry": spec.get("expiry"),
        "expiry_status": spec.get("expiry_status", "Vigente"),
        "prescription": spec["prescription"],
        "price": picked["price"] if picked["price"] and picked["price"] > 0 else 9.95,
        "previous_price": picked["previous_price"],
        "stock": spec["stock"],
        "min_stock": spec["min_stock"],
        "is_active": spec["is_active"],
        "indications": picked["indications"],
        "contraindications": picked["contraindications"],
        "description": clean_text(picked["indications"] or picked["raw_description"] or picked["source_name"]),
        "image_url": picked["image_url"],
        "source_url": picked["source_url"],
    }


def fetch_listing_links(url: str) -> list[str]:
    try:
        listing = Fetcher.get(url, timeout=20)
    except Exception:
        return []
    if listing.status != 200:
        return []
    return list(
        dict.fromkeys(
            a.attrib.get("href", "").strip()
            for a in listing.css("a[href*='/producto/']")
            if a.attrib.get("href")
        )
    )


def process_anchor(
    target: dict[str, Any],
    used_urls: set[str],
    used_roots: set[str],
    lock: threading.Lock,
) -> dict[str, Any] | None:
    codigo = target["codigo"]
    print(f"[{codigo}] Buscando ancla en {target['query_url']} ...")
    raw_hrefs = fetch_listing_links(target["query_url"])
    candidates = rank_links(raw_hrefs, target["match_any"], target["exclude"]) or raw_hrefs

    for href in candidates[:10]:
        full_url = urljoin(BASE_URL, href)
        root_sku = extract_root_sku(full_url)
        with lock:
            if full_url in used_urls or root_sku in used_roots:
                continue
        detail = extract_detail(full_url)
        if detail is None:
            continue
        local_img = download_image(detail["source_image_url"], codigo)
        if local_img is None:
            continue
        with lock:
            if full_url in used_urls or root_sku in used_roots:
                continue
            used_urls.add(full_url)
            used_roots.add(root_sku)
        detail["image_url"] = local_img
        return build_record(target, detail)

    print(f"  WARN: No se encontró candidato con imagen válida para {codigo}")
    return None


def gather_bucket_candidates(bucket: dict[str, Any], used_roots: set[str]) -> list[tuple[str, str]]:
    """Recopila pares únicos (url, prescription) para un bucket sin duplicar producto raíz."""
    pool: list[tuple[str, str]] = []
    seen_local_roots: set[str] = set(used_roots)

    # Primero recolectamos páginas de búsqueda/categoría en paralelo
    queries = bucket["queries"]

    def load_q(item: dict[str, Any]) -> tuple[dict[str, Any], list[str]]:
        url = item.get("url") or f"{BASE_URL}/productos/keyword/{quote(item['q'])}"
        links = fetch_listing_links(url)
        if "q" in item:
            kws = [w for w in item["q"].lower().split() if len(w) >= 3]
            links = rank_links(links, kws, [])
        else:
            links = rank_links(links, [], [])
        return item, links

    with ThreadPoolExecutor(max_workers=4) as executor:
        q_results = list(executor.map(load_q, queries))

    for q_item, links in q_results:
        picked_for_q = 0
        limit_for_q = q_item.get("take", 2) + 1  # holgura por si alguna falla imagen
        for href in links:
            full_url = urljoin(BASE_URL, href)
            root_sku = extract_root_sku(full_url)
            if root_sku in seen_local_roots:
                continue
            seen_local_roots.add(root_sku)
            pool.append((full_url, q_item["prescription"]))
            picked_for_q += 1
            if picked_for_q >= limit_for_q:
                break

    return pool


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--refresh", action="store_true", help="Re-descargar incluso las anclas existentes")
    args = parser.parse_args()

    existing_by_code: dict[str, dict[str, Any]] = {}
    if OUTPUT_JSON.is_file():
        try:
            loaded = json.loads(OUTPUT_JSON.read_text(encoding="utf-8"))
            if isinstance(loaded, list):
                for item in loaded:
                    code = item.get("codigo")
                    img = (item.get("image_url") or "").lstrip("/")
                    if code and img and (REPO_ROOT / "public" / img).is_file():
                        existing_by_code[code] = item
        except Exception:
            existing_by_code = {}

    results_by_code: dict[str, dict[str, Any]] = {}
    used_urls: set[str] = set()
    used_roots: set[str] = set()
    lock = threading.Lock()

    # 1. Mantener los 19 ítems ancla exactos
    for target in TARGETS:
        code = target["codigo"]
        if code in existing_by_code and not args.refresh:
            item = existing_by_code[code]
            results_by_code[code] = item
            if item.get("source_url"):
                used_urls.add(item["source_url"])
                used_roots.add(extract_root_sku(item["source_url"]))
        else:
            rec = process_anchor(target, used_urls, used_roots, lock)
            if rec is not None:
                results_by_code[rec["codigo"]] = rec
                time.sleep(0.1)

    # 2. Expandir cada bucket hasta alcanzar exactamente su cuota con imagen válida
    for bucket in EXPANSION_BUCKETS:
        target_count = bucket["target_count"]
        print(f"\n[BUCKET] {bucket['catalog_type']} / {bucket['category']} (meta +{target_count}) ...")
        candidates = gather_bucket_candidates(bucket, used_roots)
        print(f"  -> {len(candidates)} candidatos únicos encontrados para verificar detalle e imagen")

        valid_details: list[tuple[dict[str, Any], str]] = []
        batch_size = 8
        cursor = 0
        while len(valid_details) < target_count and cursor < len(candidates):
            chunk = candidates[cursor : cursor + batch_size]
            cursor += batch_size

            with ThreadPoolExecutor(max_workers=4) as pool:
                fut_map = {pool.submit(extract_detail, url): (url, presc) for url, presc in chunk}
                for fut in as_completed(fut_map):
                    url, presc = fut_map[fut]
                    detail = fut.result()
                    if detail is not None:
                        root_sku = extract_root_sku(url)
                        with lock:
                            if root_sku not in used_roots:
                                used_roots.add(root_sku)
                                used_urls.add(url)
                                valid_details.append((detail, presc))

        valid_details = valid_details[:target_count]
        is_med = bucket["catalog_type"] == "medicine"

        def finalize_item(offset_pair: tuple[int, tuple[dict[str, Any], str]]) -> dict[str, Any] | None:
            idx_offset, (detail, presc) = offset_pair
            num = bucket["start_num"] + idx_offset
            codigo = f"{bucket['prefix']}-{num:05d}"
            local_img = download_image(detail["source_image_url"], codigo)
            if local_img is None:
                return None
            detail["image_url"] = local_img

            rot = idx_offset + num
            stock_val = STOCK_ROTATION[rot % len(STOCK_ROTATION)]
            is_active = stock_val > 0
            min_stock = 15 if is_med else 12
            expiry_val, expiry_status = EXPIRY_ROTATION[rot % len(EXPIRY_ROTATION)]
            letter = BATCH_LETTERS[rot % len(BATCH_LETTERS)]

            spec = {
                "codigo": codigo,
                "catalog_type": bucket["catalog_type"],
                "category": bucket["category"],
                "prescription": presc,
                "batch": f"LOT-26{letter}{num % 90 + 10:02d}" if is_med else None,
                "expiry": expiry_val if is_med else None,
                "expiry_status": expiry_status if is_med else "Vigente",
                "stock": stock_val,
                "min_stock": min_stock,
                "is_active": is_active,
            }
            return build_record(spec, detail)

        with ThreadPoolExecutor(max_workers=4) as pool:
            for rec in pool.map(finalize_item, enumerate(valid_details)):
                if rec is not None:
                    results_by_code[rec["codigo"]] = rec
                    print(
                        f"  OK [{rec['codigo']}] {rec['name']} | {rec['laboratory']} | "
                        f"${rec['price']:.2f} | Formula: {len(rec['formula'])}"
                    )

    # 3. Ordenar y limpiar imágenes huérfanas previas en public/images/catalog/
    anchor_order = {t["codigo"]: i for i, t in enumerate(TARGETS)}
    ordered = sorted(
        results_by_code.values(),
        key=lambda r: (0 if r["codigo"] in anchor_order else 1, anchor_order.get(r["codigo"], 9999), r["codigo"]),
    )

    valid_files = {Path(item["image_url"]).name for item in ordered if item.get("image_url")}
    if IMAGES_DIR.is_dir():
        for existing_file in IMAGES_DIR.iterdir():
            if existing_file.is_file() and existing_file.name not in valid_files:
                existing_file.unlink()

    med_count = sum(1 for r in ordered if r["catalog_type"] == "medicine")
    prod_count = sum(1 for r in ordered if r["catalog_type"] == "product")

    OUTPUT_JSON.parent.mkdir(parents=True, exist_ok=True)
    OUTPUT_JSON.write_text(json.dumps(ordered, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"\nGuardado catálogo scrapeado: {len(ordered)} ítems ({med_count} medicamentos, {prod_count} productos) en {OUTPUT_JSON}")


if __name__ == "__main__":
    main()
