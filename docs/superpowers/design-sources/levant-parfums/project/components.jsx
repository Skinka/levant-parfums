/* ===================================================
   LEVANT — shared UI components
   =================================================== */
const { useState, useEffect, useRef, useMemo } = React;

/* ---------- helpers ---------- */
function navigate(hash) {
  window.location.hash = hash;
  window.scrollTo({ top: 0, behavior: "smooth" });
}
function useHashRoute() {
  const [hash, setHash] = useState(window.location.hash || "#/");
  useEffect(() => {
    const onChange = () => setHash(window.location.hash || "#/");
    window.addEventListener("hashchange", onChange);
    return () => window.removeEventListener("hashchange", onChange);
  }, []);
  return hash;
}
function fmtPrice(p, t) {
  return new Intl.NumberFormat("uk-UA").format(p) + " " + t.currency;
}

/* ---------- Reveal on scroll (scroll-based, iframe-safe) ---------- */
// Global registry + single rAF-throttled scroll/resize listener.
// Fires `in` class when element top is within ~92% of viewport height.
(function setupRevealScroll() {
  if (window.__levantRevealReady) return;
  window.__levantRevealReady = true;
  let pending = false;
  function check() {
    pending = false;
    const vh = window.innerHeight || document.documentElement.clientHeight;
    const trigger = vh * 0.92;
    document.querySelectorAll('.reveal:not(.in), .reveal-stagger:not(.in)').forEach((el) => {
      const r = el.getBoundingClientRect();
      // visible if top is above trigger line AND bottom is below 0
      if (r.top < trigger && r.bottom > 0) el.classList.add('in');
    });
  }
  function schedule() {
    if (pending) return;
    pending = true;
    requestAnimationFrame(check);
  }
  window.addEventListener('scroll', schedule, { passive: true });
  window.addEventListener('resize', schedule);
  // run periodically while page loads (covers React mounts and image-resize)
  let ticks = 0;
  const interval = setInterval(() => {check();if (++ticks > 30) clearInterval(interval);}, 120);
  window.__levantRevealCheck = check;
})();

const Reveal = ({ children, as = "div", stagger = false, className = "", style }) => {
  const Tag = as;
  const ref = useRef(null);
  useEffect(() => {
    // immediate check + scheduled re-checks because layout may shift
    if (window.__levantRevealCheck) window.__levantRevealCheck();
    const t1 = setTimeout(() => window.__levantRevealCheck && window.__levantRevealCheck(), 80);
    const t2 = setTimeout(() => window.__levantRevealCheck && window.__levantRevealCheck(), 400);
    return () => {clearTimeout(t1);clearTimeout(t2);};
  }, []);
  const cls = (stagger ? "reveal-stagger" : "reveal") + (className ? " " + className : "");
  return <Tag ref={ref} className={cls} style={style}>{children}</Tag>;
};

/* ---------- Intro veil (one-time on load) ---------- */
const IntroVeil = () => {
  const [show, setShow] = useState(() => !sessionStorage.getItem("levant_intro_shown"));
  useEffect(() => {
    if (!show) return;
    const t = setTimeout(() => {
      sessionStorage.setItem("levant_intro_shown", "1");
      setShow(false);
    }, 1500);
    return () => clearTimeout(t);
  }, [show]);
  if (!show) return null;
  return (
    <div className="intro-veil">
      <div className="mark">LEVANT</div>
    </div>);
};

/* ---------- Diamond ornament (from brand book packaging) ---------- */
const DiamondRule = ({ label }) =>
<div className="diamond-rule">
    <span className="gem"></span>
    {label && <span>{label}</span>}
    <span className="gem"></span>
  </div>;


/* ---------- Icons (inline minimal) ---------- */
const Icon = ({ name, size = 18, stroke = 1.25 }) => {
  const props = { width: size, height: size, viewBox: "0 0 24 24", fill: "none", stroke: "currentColor", strokeWidth: stroke, strokeLinecap: "round", strokeLinejoin: "round" };
  switch (name) {
    case "search":return <svg {...props}><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>;
    case "user":return <svg {...props}><circle cx="12" cy="8" r="4" /><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6" /></svg>;
    case "bag":return <svg {...props}><path d="M6 7h12l-1 13H7zM9 7a3 3 0 0 1 6 0" /></svg>;
    case "heart":return <svg {...props}><path d="M12 21s-7-4.5-9.5-9A4.5 4.5 0 0 1 12 7a4.5 4.5 0 0 1 9.5 5C19 16.5 12 21 12 21Z" /></svg>;
    case "chev-d":return <svg {...props}><path d="m6 9 6 6 6-6" /></svg>;
    case "chev-l":return <svg {...props}><path d="m15 6-6 6 6 6" /></svg>;
    case "chev-r":return <svg {...props}><path d="m9 6 6 6-6 6" /></svg>;
    case "x":return <svg {...props}><path d="M6 6l12 12M18 6 6 18" /></svg>;
    case "check":return <svg {...props}><path d="m5 12 5 5 9-12" /></svg>;
    case "arrow-r":return <svg {...props}><path d="M5 12h14m-6-6 6 6-6 6" /></svg>;
    case "shield":return <svg {...props}><path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6Z" /><path d="m9 12 2 2 4-4" /></svg>;
    case "truck":return <svg {...props}><path d="M3 7h11v9H3zm11 3h5l2 3v3h-7Z" /><circle cx="7" cy="18" r="2" /><circle cx="17" cy="18" r="2" /></svg>;
    case "refresh":return <svg {...props}><path d="M3 12a9 9 0 0 1 15-6.7L21 8M21 3v5h-5M21 12a9 9 0 0 1-15 6.7L3 16M3 21v-5h5" /></svg>;
    case "gift":return <svg {...props}><path d="M3 12h18v9H3zm0-5h18v5H3zM12 7v14M8 7c0-3 4-3 4 0 0-3 4-3 4 0" /></svg>;
    case "phone":return <svg {...props}><path d="M22 16.9v3a2 2 0 0 1-2.2 2A19.9 19.9 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.7a2 2 0 0 1-.5 2.1L8 9.7a16 16 0 0 0 6.3 6.3l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.7.6a2 2 0 0 1 1.7 2Z" /></svg>;
    case "mail":return <svg {...props}><rect x="3" y="5" width="18" height="14" rx="0" /><path d="m3 7 9 6 9-6" /></svg>;
    case "pin":return <svg {...props}><path d="M12 22s7-7 7-12a7 7 0 0 0-14 0c0 5 7 12 7 12Z" /><circle cx="12" cy="10" r="2.5" /></svg>;
    case "clock":return <svg {...props}><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></svg>;
    case "menu":return <svg {...props}><path d="M4 7h16M4 12h16M4 17h16" /></svg>;
    case "zoom":return <svg {...props}><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5M8 11h6M11 8v6" /></svg>;
    case "play":return <svg {...props}><path d="m6 4 14 8-14 8z" fill="currentColor" /></svg>;
    case "monogram":return (
        <svg width={size} height={size} viewBox="0 0 40 40" fill="none">
        <rect x="3" y="3" width="34" height="34" stroke="currentColor" strokeWidth="0.8" />
        <text x="20" y="25" textAnchor="middle" fontFamily="var(--font-serif)" fontSize="14" fill="currentColor" letterSpacing="0.1em">L</text>
      </svg>);

    default:return null;
  }
};

/* ---------- Brand wordmark with monogram ---------- */
const BrandMark = ({ size = "md" }) =>
<a href="#/" className="brand" style={{ fontSize: size === "lg" ? 36 : 26 }}>
    <span style={{ letterSpacing: "9px" }}>L E V A N T</span>
    <span className="sub">INTERSECTION OF THREE WORLDS</span>
  </a>;


/* ---------- Announcement bar (duplicated for seamless marquee) ---------- */
const Announcement = ({ t }) => {
  const items = t === "uk" ? [
  "Колекція 2026 · Luxury × Onyx — у наявності",
  "Розроблено в Іспанії · Розлито в Туреччині · Зібрано в Україні"] :
  [
  "Collection 2026 · Luxury × Onyx — in stock",
  "Composed in Spain · Bottled in Turkey · Assembled in Ukraine"];

  return (
    <div className="announcement">
      <span className="marquee-track">
        {[0, 1].map((k) =>
        <React.Fragment key={k}>
            {items.map((s, i) =>
          <span key={k + "-" + i} className="marquee-item">
                <span className="dot"></span> {s}
              </span>
          )}
          </React.Fragment>
        )}
      </span>
    </div>);

};


/* ---------- Header ---------- */
const Header = ({ lang, setLang, t, route }) => {
  const [open, setOpen] = useState(false);
  const ref = useRef(null);
  useEffect(() => {
    const off = (e) => {if (ref.current && !ref.current.contains(e.target)) setOpen(false);};
    document.addEventListener("mousedown", off);
    return () => document.removeEventListener("mousedown", off);
  }, []);
  const isActive = (h) => {
    if (h === "#/") return route === "#/" || route === "";
    return route.startsWith(h);
  };
  return (
    <>
      <Announcement t={lang} />
      <header className="header">
        <div className="container">
          <BrandMark />
          <nav className="nav">
            <a href="#/" className={isActive("#/") && !route.startsWith("#/catalog") && !route.startsWith("#/about") && !route.startsWith("#/contacts") && !route.startsWith("#/articles") && !route.startsWith("#/product") ? "active" : ""}>{t.nav.home}</a>
            <a href="#/catalog" className={isActive("#/catalog") || route.startsWith("#/product") ? "active" : ""}>{t.nav.catalog}</a>
            <a href="#/articles" className={isActive("#/articles") ? "active" : ""}>{t.nav.articles}</a>
            <a href="#/about" className={isActive("#/about") ? "active" : ""}>{t.nav.about}</a>
            <a href="#/contacts" className={isActive("#/contacts") ? "active" : ""}>{t.nav.contacts}</a>
          </nav>
          <div className="head-right">
            <div className="lang" ref={ref}>
              <button className="lang-btn" onClick={() => setOpen(!open)}>
                {lang === "uk" ? "UA" : "EN"} <Icon name="chev-d" size={14} />
              </button>
              {open &&
              <div className="lang-menu">
                  <button onClick={() => {setLang("uk");setOpen(false);}} className={lang === "uk" ? "active" : ""}>
                    Українська <span className="dot" />
                  </button>
                  <button onClick={() => {setLang("en");setOpen(false);}} className={lang === "en" ? "active" : ""}>
                    English <span className="dot" />
                  </button>
                </div>
              }
            </div>
          </div>
        </div>
      </header>
    </>);

};

/* ---------- Footer ---------- */
const Footer = ({ t, lang }) =>
<footer className="footer" style={{ padding: "0px 0px 32px" }}>
    <div className="container" style={{ padding: "80px 56px 0px" }}>
      <div className="diamond-band" style={{ marginBottom: 56 }}></div>
      <div className="grid">
        <div>
          <div className="brand" style={{ alignItems: "flex-start" }}>
            <span>L E V A N T</span>
            <span className="sub" style={{ letterSpacing: "1.2px", backgroundColor: "rgba(244, 237, 224, 0)" }}>INTERSECTION OF THREE WORLDS</span>
          </div>
          <p style={{ marginTop: 24, color: "var(--ink-soft)", fontSize: 14, lineHeight: 1.7, maxWidth: "36ch" }}>{t.footer_about}</p>
        </div>
        <div>
          <h4>{t.footer_nav}</h4>
          <ul>
            <li><a href="#/">{t.nav.home}</a></li>
            <li><a href="#/catalog">{t.nav.catalog}</a></li>
            <li><a href="#/articles">{t.nav.articles}</a></li>
            <li><a href="#/about">{t.nav.about}</a></li>
            <li><a href="#/contacts">{t.nav.contacts}</a></li>
          </ul>
        </div>
        <div>
          <h4>{t.footer_shop}</h4>
          <ul>
            <li><a href="#/catalog?series=onyx">{t.onyx_label}</a></li>
            <li><a href="#/catalog?series=luxury">{t.luxury_label}</a></li>
            <li><a href="#/catalog?sort=new">{lang === "uk" ? "Новинки" : "New"}</a></li>
            <li><a href="#/catalog">{lang === "uk" ? "Бестселери" : "Bestsellers"}</a></li>
          </ul>
        </div>
        <div>
          <h4>{t.footer_help}</h4>
          <ul>
            <li><a href="#">{t.footer_delivery}</a></li>
            <li><a href="#">{t.footer_return}</a></li>
            <li><a href="#">{t.footer_terms}</a></li>
            <li><a href="#">{t.footer_privacy}</a></li>
          </ul>
        </div>
        <div>
          <h4>{lang === "uk" ? "Зв'язок" : "Get in touch"}</h4>
          <ul>
            <li><a href="tel:+380974128819">+38 (097) 412 88 19</a></li>
            <li><a href="mailto:concierge@levant.parfum">concierge@levant.parfum</a></li>
            <li><a href="#">Instagram</a></li>
            <li><a href="#">Telegram</a></li>
          </ul>
        </div>
      </div>
      <div className="legal">
        <span>{t.footer_rights}</span>
        <span>{lang === "uk" ? "Розроблено в Іспанії · Розлито в Туреччині · Зібрано в Україні" : "Composed in Spain · Bottled in Turkey · Assembled in Ukraine"}</span>
      </div>
    </div>
  </footer>;


/* ---------- Product card ---------- */
const ProductCard = ({ p, t, lang }) => {
  return (
    <a href={`#/product/${p.slug}`} className="card">
      <div className="img">
        <img src={p.img} alt={lang === "uk" ? p.name_uk : p.name_en} loading="lazy" />
        <div className="badges">
          {p.new && <span className="badge gold">{t.new_badge}</span>}
          {p.best && <span className="badge">{t.best_badge}</span>}
        </div>
      </div>
      <div className="body">
        <span className="series">{p.series === "onyx" ? t.onyx_label : t.luxury_label}</span>
        <span className="title">{lang === "uk" ? p.name_uk : p.name_en}</span>
        {p.subtitle_uk && <span className="subtitle">{lang === "uk" ? p.subtitle_uk : p.subtitle_en}</span>}
        <div className="fam-row"><span className="dot"></span><span>{lang === "uk" ? p.family : p.family_en} · eau de parfum</span></div>
        <div className="meta">
          <span className="price">{fmtPrice(p.price, t)}</span>
          <span className="vol">{p.volume} ml</span>
        </div>
      </div>
    </a>);

};

/* ---------- Horizontal slider with arrows ---------- */
const ProductSlider = ({ products, t, lang, label, title, eyebrow }) => {
  const trackRef = useRef(null);
  const scrollBy = (dx) => trackRef.current && trackRef.current.scrollBy({ left: dx, behavior: "smooth" });
  return (
    <section>
      <div className="container">
        {(title || eyebrow) &&
        <Reveal className="section-head">
            <div>
              {eyebrow && <div className="eyebrow">{eyebrow}</div>}
              {title && <h2 style={{ marginTop: 12 }}>{title}</h2>}
            </div>
            {label &&
          <a href="#/catalog" className="lnk">{label} <Icon name="arrow-r" size={14} /></a>
          }
          </Reveal>
        }
        <div className="product-row">
          <div className="nav-btns">
            <button onClick={() => scrollBy(-360)} aria-label="prev" style={{ width: "30px", height: "30px", padding: "0px", margin: "14px 0px 0px" }}><Icon name="chev-l" size={16} /></button>
            <button onClick={() => scrollBy(360)} aria-label="next" style={{ margin: "14px 0px 0px", width: "30px", height: "30px" }}><Icon name="chev-r" size={16} /></button>
          </div>
          <div className="product-track" ref={trackRef}>
            {products.map((p) => <ProductCard key={p.slug} p={p} t={t} lang={lang} />)}
          </div>
        </div>
      </div>
    </section>);

};

/* expose */
Object.assign(window, { Icon, BrandMark, Announcement, Header, Footer, ProductCard, ProductSlider, navigate, useHashRoute, fmtPrice, Reveal, IntroVeil, DiamondRule });