/* ===================================================
   LEVANT — Product page (with gallery + order form)
   =================================================== */

const Lightbox = ({ images, index, setIndex, onClose }) => {
  useEffect(() => {
    const k = (e) => {
      if (e.key === "Escape") onClose();
      if (e.key === "ArrowLeft") setIndex((index - 1 + images.length) % images.length);
      if (e.key === "ArrowRight") setIndex((index + 1) % images.length);
    };
    document.addEventListener("keydown", k);
    return () => document.removeEventListener("keydown", k);
  }, [index]);
  return (
    <div className="lightbox" onClick={(e) => { if (e.target.classList.contains("lightbox")) onClose(); }}>
      <button className="close" onClick={onClose}><Icon name="x"/></button>
      <button className="nav-l" onClick={() => setIndex((index - 1 + images.length) % images.length)}><Icon name="chev-l"/></button>
      <button className="nav-r" onClick={() => setIndex((index + 1) % images.length)}><Icon name="chev-r"/></button>
      <img src={images[index]} alt=""/>
      <div className="counter">{String(index + 1).padStart(2, "0")} / {String(images.length).padStart(2, "0")}</div>
    </div>
  );
};

const OrderForm = ({ p, t, lang }) => {
  const [data, setData] = useState({ name: "", phone: "", email: "", city: "", np: "", comment: "" });
  const [sent, setSent] = useState(false);
  const set = (k) => (e) => setData({ ...data, [k]: e.target.value });
  if (sent) {
    return (
      <div className="order-thanks">
        <div className="ok"><Icon name="check" size={24} stroke={1.5}/></div>
        <h3>{t.order_thanks}</h3>
        <p style={{ marginTop: 14, color: "var(--ink-mute)", fontSize: 13, letterSpacing: "0.06em" }}>
          {lang === "uk" ? `Номер замовлення · LV-${Math.floor(Math.random()*9000 + 1000)}` : `Order number · LV-${Math.floor(Math.random()*9000 + 1000)}`}
        </p>
      </div>
    );
  }
  return (
    <div className="order-form">
      <div className="intro">
        <div className="eyebrow">{lang === "uk" ? "Замовлення" : "Order"}</div>
        <h2 style={{ marginTop: 14 }}>{t.order_title}</h2>
        <p>{t.order_sub}</p>
        <div className="summary">
          <div className="img"><img src={p.img} alt=""/></div>
          <div>
            <div className="title">{lang === "uk" ? p.name_uk : p.name_en}</div>
            <div style={{ fontSize: 11, color: "var(--ink-mute)", letterSpacing: "0.18em", textTransform: "uppercase", marginTop: 4 }}>{p.volume} ml · eau de parfum</div>
            <div className="price">{fmtPrice(p.price, t)}</div>
          </div>
        </div>
      </div>
      <form className="fields" onSubmit={(e) => { e.preventDefault(); setSent(true); }}>
        <div className="field full">
          <label>{t.order_name} *</label>
          <input required value={data.name} onChange={set("name")} placeholder={lang === "uk" ? "Іванов Іван Іванович" : "Ivan Ivanov"}/>
        </div>
        <div className="field">
          <label>{t.order_phone} *</label>
          <input required type="tel" value={data.phone} onChange={set("phone")} placeholder="+38 (097) 000 00 00"/>
        </div>
        <div className="field">
          <label>{t.order_email} *</label>
          <input required type="email" value={data.email} onChange={set("email")} placeholder="email@levant.parfum"/>
        </div>
        <div className="field">
          <label>{lang === "uk" ? "Місто" : "City"} *</label>
          <input required value={data.city} onChange={set("city")} placeholder={t.order_city_ph}/>
        </div>
        <div className="field">
          <label>{t.order_np} *</label>
          <input required value={data.np} onChange={set("np")} placeholder={t.order_np_ph}/>
        </div>
        <div className="field full">
          <label>{t.order_comment}</label>
          <textarea value={data.comment} onChange={set("comment")} placeholder={lang === "uk" ? "Якщо потрібно — додайте побажання щодо пакування або семплів" : "Optional — packaging notes or sample preferences"}></textarea>
        </div>
        <div className="actions">
          <span className="agree">{t.order_agree}</span>
          <button type="submit" className="btn">{t.order_submit} <Icon name="arrow-r" size={14}/></button>
        </div>
      </form>
    </div>
  );
};

const Pyramid = ({ p, t, lang }) => {
  const top = lang === "uk" ? p.top_uk : p.top_uk;
  // EN notes use UA fallback (data is UA-localised for notes)
  return (
    <div className="pyramid">
      <div>
        <div className="eyebrow">{t.pyramid_title}</div>
        <h2 style={{ marginTop: 16 }}>{lang === "uk" ? "Як аромат розкривається на шкірі" : "How the scent unfolds on skin"}</h2>
        <p className="muted" style={{ marginTop: 18, fontSize: 14, maxWidth: "32ch", lineHeight: 1.7 }}>
          {lang === "uk" ? "Кожна композиція побудована з трьох рівнів. Верхні ноти живуть перші пів години, серцеві — наступні 2–3, базові — до десяти годин." : "Every composition is built in three layers. Top notes for the first half hour, heart for two to three hours, base up to ten."}
        </p>
      </div>
      <div className="levels">
        <div className="level">
          <div className="lbl">{t.pyramid_top}</div>
          <div className="notes">
            {p.top_uk.map(n => <span key={n} className="note">{n}</span>)}
          </div>
        </div>
        <div className="level">
          <div className="lbl">{t.pyramid_mid}</div>
          <div className="notes">
            {p.mid_uk.map(n => <span key={n} className="note">{n}</span>)}
          </div>
        </div>
        <div className="level">
          <div className="lbl">{t.pyramid_base}</div>
          <div className="notes">
            {p.base_uk.map(n => <span key={n} className="note">{n}</span>)}
          </div>
        </div>
      </div>
    </div>
  );
};

const Character = ({ p, t, lang }) => {
  return (
    <div className="character">
      <div className="bar-row">
        <div className="top">
          <span className="l">{t.char_sillage}</span>
          <span className="v">{t.sillage_word[Math.min(p.sillage - 1, t.sillage_word.length - 1)]}</span>
        </div>
        <div className="bar"><div className="fill" style={{ width: `${(p.sillage / 5) * 100}%` }}></div></div>
        <div className="ticks">
          {t.sillage_word.map((w, i) => <span key={i}>{i + 1}</span>)}
        </div>
      </div>
      <div className="bar-row">
        <div className="top">
          <span className="l">{t.char_longevity}</span>
          <span className="v">{p.longevity}+ {t.longevity_word_h}</span>
        </div>
        <div className="bar"><div className="fill" style={{ width: `${(p.longevity / 12) * 100}%` }}></div></div>
        <div className="ticks">
          <span>2h</span><span>4h</span><span>6h</span><span>8h</span><span>10h</span><span>12h</span>
        </div>
      </div>
    </div>
  );
};

const ProductPage = ({ slug, t, lang }) => {
  const p = window.LEVANT.PRODUCTS.find(x => x.slug === slug);
  if (!p) {
    return (
      <div className="container" style={{ padding: "120px 0", textAlign: "center" }}>
        <h2>404</h2>
        <p style={{ marginTop: 16 }}><a href="#/catalog" className="lnk">{t.crumb_catalog}</a></p>
      </div>
    );
  }
  const [active, setActive] = useState(0);
  const [lightboxOn, setLightboxOn] = useState(false);
  useEffect(() => { setActive(0); }, [slug]);

  const related = window.LEVANT.PRODUCTS.filter(x => x.slug !== p.slug && x.series === p.series).slice(0, 6);
  if (related.length < 4) {
    window.LEVANT.PRODUCTS.filter(x => x.slug !== p.slug && x.series !== p.series).slice(0, 6 - related.length).forEach(x => related.push(x));
  }

  return (
    <div className="product-page">
      <div className="container">
        <div className="crumbs">
          <a href="#/">{t.nav.home}</a><span className="sep">/</span>
          <a href="#/catalog">{t.crumb_catalog}</a><span className="sep">/</span>
          <a href={`#/catalog?series=${p.series}`}>{p.series === "onyx" ? t.onyx_label : t.luxury_label}</a><span className="sep">/</span>
          <span className="current">{lang === "uk" ? p.name_uk : p.name_en}</span>
        </div>

        <div className="top">
          <div className="gallery">
            <div className="thumbs">
              {p.gallery.map((g, i) => (
                <button key={i} className={i === active ? "active" : ""} onClick={() => setActive(i)}>
                  <img src={g} alt=""/>
                </button>
              ))}
            </div>
            <div className="main-img" onClick={() => setLightboxOn(true)}>
              <img src={p.gallery[active]} alt={lang === "uk" ? p.name_uk : p.name_en}/>
              <div className="zoom-hint"><Icon name="zoom" size={12}/> {lang === "uk" ? "Натисніть, щоб збільшити" : "Click to enlarge"}</div>
            </div>
          </div>

          <div className="info">
            <div className="series">— {p.series === "onyx" ? t.onyx_label : t.luxury_label}</div>
            <h1 className="display-italic">{lang === "uk" ? p.name_uk : p.name_en}</h1>
            <div className="subtitle">{lang === "uk" ? p.subtitle_uk : p.subtitle_en}</div>
            <div className="character-line">
              <span className="accent">{lang === "uk" ? p.character_uk : p.character_en}</span>
              {p.occasion_uk ? <span> · {lang === "uk" ? p.occasion_uk : p.occasion_en}</span> : null}
            </div>
            <div style={{ display: "flex", gap: 12, marginTop: 18 }}>
              {p.new && <span className="card" style={{ display: "inline-block", padding: "6px 12px", fontSize: 10, letterSpacing: "0.22em", textTransform: "uppercase", border: "1px solid var(--accent)", background: "var(--accent)", color: "var(--accent-ink)" }}>{t.new_badge}</span>}
              {p.best && <span className="card" style={{ display: "inline-block", padding: "6px 12px", fontSize: 10, letterSpacing: "0.22em", textTransform: "uppercase", border: "1px solid var(--line)", background: "var(--card)", color: "var(--ink)" }}>{t.best_badge}</span>}
            </div>
            <div className="price-row">
              <div className="price">{fmtPrice(p.price, t)}</div>
              <div className="vol">{p.volume} ml · eau de parfum</div>
            </div>
            <p className="desc">{lang === "uk" ? p.desc_uk : p.desc_en}</p>

            {p.why_uk &&
              <div className="why-block">
                <div className="l">{t.why_label}</div>
                <p>{lang === "uk" ? p.why_uk : p.why_en}</p>
              </div>
            }

            <div className="specs">
              <div className="row"><span className="l">{t.sku}</span><span className="v">LV-{(p.series === "onyx" ? "OX" : "LX") + String(p.no).padStart(2,"0")}</span></div>
              <div className="row"><span className="l">{t.volume}</span><span className="v">{p.volume} ml</span></div>
              <div className="row"><span className="l">{t.family_label}</span><span className="v">{lang === "uk" ? p.family : p.family_en}</span></div>
              <div className="row"><span className="l">{lang === "uk" ? "Концентрація" : "Concentration"}</span><span className="v">eau de parfum</span></div>
              <div className="row"><span className="l">{lang === "uk" ? "Розроблено" : "Composed"}</span><span className="v">Іспанія / ES</span></div>
              <div className="row"><span className="l">{lang === "uk" ? "Серія" : "Series"}</span><span className="v">{p.series === "onyx" ? t.onyx_label : t.luxury_label}</span></div>
            </div>

            <div style={{ marginTop: 36, display: "flex", gap: 14, flexWrap: "wrap" }}>
              <a href="#order" className="btn">{t.order_submit} <Icon name="arrow-r" size={14}/></a>
            </div>
          </div>
        </div>

        <Pyramid p={p} t={t} lang={lang}/>
        <Character p={p} t={t} lang={lang}/>

        <div id="order">
          <OrderForm p={p} t={t} lang={lang}/>
        </div>
      </div>

      <ProductSlider
        products={related}
        t={t}
        lang={lang}
        eyebrow={t.related_title}
        title={lang === "uk" ? "Інші композиції з нашого дому" : "Other compositions from our house"}
        label={lang === "uk" ? "Усі парфуми" : "All perfumes"}
      />

      {lightboxOn && <Lightbox images={p.gallery} index={active} setIndex={setActive} onClose={() => setLightboxOn(false)}/>}
    </div>
  );
};

Object.assign(window, { ProductPage });
