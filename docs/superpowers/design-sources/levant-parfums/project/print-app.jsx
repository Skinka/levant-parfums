/* ===================================================
   LEVANT — Print build (all pages stacked for PDF)
   =================================================== */

function PrintApp() {
  // Force defaults — no tweaks UI on print build.
  const lang = "uk";
  const t = window.LEVANT.I18N[lang];

  // Apply CSS variables for the default cream theme + Fraunces/Inter pair.
  useEffect(() => {
    const root = document.documentElement;
    root.style.setProperty("--font-serif", '"Fraunces", Georgia, serif');
    root.style.setProperty("--font-sans",  '"Inter", system-ui, sans-serif');
    root.style.setProperty("--accent",   "#a07a2a");
    root.style.setProperty("--accent-2", "#c79a45");
    root.style.setProperty("--gold",     "#b08a3e");
    document.body.classList.add("theme-cream");
  }, []);

  // Pick a representative product for the product page.
  const featured = window.LEVANT.PRODUCTS.find(p => p.slug === "luxury-2")
                || window.LEVANT.PRODUCTS[0];

  // A header that doesn't show language dropdown / etc. — reuse Header but
  // strip stickiness via print CSS.
  const noop = () => {};

  // Each section in the print doc.
  const PageBreak = () => <div className="print-page-break" aria-hidden="true"></div>;

  return (
    <div className="print-root">
      <Header lang={lang} setLang={noop} t={t} route="#/" />

      {/* ====== HOME ====== */}
      <section className="print-section" data-print-label="Home">
        <HomePage t={t} lang={lang} />
      </section>

      <PageBreak/>

      {/* ====== CATALOG ====== */}
      <section className="print-section" data-print-label="Catalog">
        <CatalogPage t={t} lang={lang} route="#/catalog" />
      </section>

      <PageBreak/>

      {/* ====== PRODUCT ====== */}
      <section className="print-section" data-print-label="Product">
        <ProductPage slug={featured.slug} t={t} lang={lang} />
      </section>

      <PageBreak/>

      {/* ====== ABOUT ====== */}
      <section className="print-section" data-print-label="About">
        <AboutPage t={t} lang={lang} />
      </section>

      <PageBreak/>

      {/* ====== ARTICLES ====== */}
      <section className="print-section" data-print-label="Articles">
        <ArticlesPage t={t} lang={lang} />
      </section>

      <PageBreak/>

      {/* ====== CONTACTS ====== */}
      <section className="print-section" data-print-label="Contacts">
        <ContactsPage t={t} lang={lang} />
      </section>

      <Footer t={t} lang={lang} />
    </div>
  );
}

const printRoot = ReactDOM.createRoot(document.getElementById("root"));
printRoot.render(<PrintApp/>);

/* ---- Auto-print once everything has settled ---- */
(function autoPrint() {
  let printed = false;
  function go() {
    if (printed) return;
    printed = true;
    setTimeout(() => window.print(), 500);
  }
  // Wait for fonts + a couple of paint ticks so React + images settle.
  function waitAndPrint() {
    const fontsReady = (document.fonts && document.fonts.ready) || Promise.resolve();
    Promise.all([fontsReady]).then(() => {
      // a few rAFs to let React commit & images decode
      requestAnimationFrame(() => requestAnimationFrame(() => {
        // give images another beat
        setTimeout(go, 800);
      }));
    });
  }
  if (document.readyState === "complete") waitAndPrint();
  else window.addEventListener("load", waitAndPrint);
})();
