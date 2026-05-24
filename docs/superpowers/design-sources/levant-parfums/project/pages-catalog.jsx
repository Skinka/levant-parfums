/* ===================================================
   LEVANT — Catalog (feed) page
   =================================================== */

function parseQuery(hash) {
  // accept #/catalog?series=onyx&page=2
  const q = hash.split("?")[1] || "";
  const out = {};
  q.split("&").filter(Boolean).forEach((kv) => {
    const [k, v] = kv.split("=");
    out[k] = decodeURIComponent(v || "");
  });
  return out;
}
function buildHash(base, params) {
  const qs = Object.entries(params).filter(([_, v]) => v !== undefined && v !== "" && v !== null).map(([k, v]) => `${k}=${encodeURIComponent(v)}`).join("&");
  return base + (qs ? "?" + qs : "");
}

const CatalogPage = ({ t, lang, route }) => {
  const params = parseQuery(route);
  const series = params.series || "all";
  const sort = params.sort || "pop";
  const page = parseInt(params.page || "1", 10);
  const PER_PAGE = 8;

  let products = window.LEVANT.PRODUCTS.slice();
  if (series !== "all") products = products.filter((p) => p.series === series);
  if (sort === "new") products = products.sort((a, b) => Number(b.new) - Number(a.new));else
  if (sort === "priceA") products = products.sort((a, b) => a.price - b.price);else
  if (sort === "priceB") products = products.sort((a, b) => b.price - a.price);else
  products = products.sort((a, b) => Number(b.best) - Number(a.best));

  const total = products.length;
  const totalPages = Math.max(1, Math.ceil(total / PER_PAGE));
  const slice = products.slice((page - 1) * PER_PAGE, page * PER_PAGE);

  const setParam = (k, v) => {
    const next = { ...params, [k]: v };
    if (k !== "page") next.page = 1;
    navigate(buildHash("#/catalog", next));
  };

  return (
    <div className="catalog">
      <div className="catalog-head" style={{ padding: "24px 0px 40px" }}>
        <div className="container">
          <div className="crumbs" style={{ padding: "0 0 24px" }}>
            <a href="#/">{t.nav.home}</a><span className="sep">/</span>
            <span className="current">{t.catalog_title}</span>
          </div>
          <div className="row">
            <div>
              <div className="eyebrow">{lang === "uk" ? "Каталог 2026" : "Catalogue 2026"}</div>
              <h1 style={{ marginTop: 16 }}>{t.catalog_title}</h1>
            </div>
            <div style={{ display: "flex", flexDirection: "column", gap: 8, alignItems: "flex-end" }}>
              <div className="eyebrow">{lang === "uk" ? "Всього композицій" : "Total compositions"}</div>
              <div style={{ fontFamily: "var(--font-serif)", fontSize: 72, color: "var(--accent)", lineHeight: 1 }}>{total}</div>
            </div>
          </div>
        </div>
      </div>
      <div className="container">
        <div className="catalog-filters">
          <div className="chip-row">
            <button className={`chip ${series === "all" ? "active" : ""}`} onClick={() => setParam("series", "all")}>{t.filter_all}</button>
            <button className={`chip ${series === "onyx" ? "active" : ""}`} onClick={() => setParam("series", "onyx")}>{t.filter_onyx}</button>
            <button className={`chip ${series === "luxury" ? "active" : ""}`} onClick={() => setParam("series", "luxury")}>{t.filter_luxury}</button>
          </div>
          <div className="sort">
            <span>{t.sort_label}</span>
            <select value={sort} onChange={(e) => setParam("sort", e.target.value)}>
              <option value="pop">{t.sort_pop}</option>
              <option value="new">{t.sort_new}</option>
              <option value="priceA">{t.sort_priceA}</option>
              <option value="priceB">{t.sort_priceB}</option>
            </select>
          </div>
        </div>

        <div className="product-grid">
          {slice.map((p) => <ProductCard key={p.slug} p={p} t={t} lang={lang} />)}
        </div>

        <div className="pagination">
          <button className="arrow" disabled={page <= 1} onClick={() => setParam("page", page - 1)}>← {t.prev}</button>
          {Array.from({ length: totalPages }).map((_, i) =>
          <button key={i} className={i + 1 === page ? "active" : ""} onClick={() => setParam("page", i + 1)}>{i + 1}</button>
          )}
          <button className="arrow" disabled={page >= totalPages} onClick={() => setParam("page", page + 1)}>{t.next} →</button>
        </div>

        <div style={{ textAlign: "center", color: "var(--ink-mute)", fontSize: 11, letterSpacing: "0.22em", textTransform: "uppercase", marginTop: 12 }}>
          {lang === "uk" ? "Сторінка" : "Page"} {page} {t.page_of} {totalPages}
        </div>
        <p className="lead" style={{ marginTop: 24 }}>{t.catalog_sub}</p>
      </div>
    </div>);

};

Object.assign(window, { CatalogPage });