/* ===================================================
   LEVANT — Home page
   =================================================== */

const HomeHero = ({ t, lang }) => {
  return (
    <section className="hero">
      <div className="container">
        <div className="floating">{lang === "uk" ? "Іспанія → Туреччина → Україна" : "Spain → Turkey → Ukraine"}</div>
        <div className="grid">
          <Reveal className="image-wrap">
            <img src="assets/levant-luxury-bottle.jpg" alt="Levant Luxury" />
          </Reveal>
          <Reveal className="copy" stagger>
            <div className="eyebrow">{t.hero_eyebrow}</div>
            <h1 style={{ marginTop: 22, fontSize: "70px" }}>
              {t.hero_title_a}<br />
              <span className="ital">{t.hero_title_b}</span>
            </h1>
            <p className="lead">{t.hero_sub}</p>
            <div className="ctas">
              <a href="#/catalog" className="btn"><span>{t.hero_cta1}</span> <Icon name="arrow-r" size={14} /></a>
              <a href="#/about" className="btn ghost"><span>{t.hero_cta2}</span></a>
            </div>
            <div className="meta">
              <div className="item"><div className="num">22</div><div className="lbl">{lang === "uk" ? "Композиції" : "Compositions"}</div></div>
              <div className="item"><div className="num">2</div><div className="lbl">{lang === "uk" ? "Серії" : "Series"}</div></div>
              <div className="item"><div className="num">3</div><div className="lbl">{lang === "uk" ? "Країни" : "Countries"}</div></div>
            </div>
          </Reveal>
        </div>
      </div>
    </section>);

};

const Manifesto = ({ t, lang }) =>
<section className="manifesto">
    <div className="container">
      <div className="grid">
        <Reveal>
          <div className="eyebrow">{t.manifesto_eyebrow}</div>
          <h2 style={{ marginTop: 16 }}>
            <span className="quote-open">“</span>
            {t.manifesto_title}
          </h2>
        </Reveal>
        <Reveal className="body">
          <p>{t.manifesto_body_p1}</p>
          <p>{t.manifesto_body_p2}</p>
          <p style={{ fontFamily: "var(--font-serif)", fontStyle: "italic", color: "var(--accent)", marginTop: 28 }}>
            {t.manifesto_sig}
          </p>
        </Reveal>
      </div>
    </div>
  </section>;


const ThreePoints = ({ t, lang }) =>
<section className="threepoints">
    <div className="container">
      <Reveal className="head">
        <DiamondRule label={t.threepoints_eyebrow} />
        <h2>{t.threepoints_title}</h2>
        <p>{t.threepoints_body}</p>
      </Reveal>
      <Reveal className="points" stagger>
        <div className="pt">
          <div className="gem"></div>
          <div className="l">{t.threepoints_es_l}</div>
          <div className="b">{t.threepoints_es_b}</div>
        </div>
        <div className="conn"></div>
        <div className="pt">
          <div className="gem"></div>
          <div className="l">{t.threepoints_tr_l}</div>
          <div className="b">{t.threepoints_tr_b}</div>
        </div>
        <div className="conn"></div>
        <div className="pt">
          <div className="gem"></div>
          <div className="l">{t.threepoints_ua_l}</div>
          <div className="b">{t.threepoints_ua_b}</div>
        </div>
      </Reveal>
    </div>
  </section>;


const Collections = ({ t, lang }) =>
<section className="collections">
    <div className="container">
      <Reveal className="section-head">
        <div>
          <div className="eyebrow">{t.collections_eyebrow}</div>
          <h2 style={{ marginTop: 12 }}>{t.collections_title}</h2>
        </div>
      </Reveal>
      <Reveal className="grid" stagger>
        <a href="#/catalog?series=luxury" className="collection-card">
          <img src="assets/levant-flacon-3.jpg" alt="Luxury" />
          <div className="overlay"></div>
          <div>
            <div className="lbl">{t.luxury_count}</div>
            <div className="name" style={{ marginTop: 8 }}>Luxury Series</div>
          </div>
          <div>
            <div className="desc">{t.luxury_desc}</div>
            <div className="arrow" style={{ marginTop: 24 }}>{t.collection_view} <Icon name="arrow-r" size={14} /></div>
          </div>
        </a>
        <a href="#/catalog?series=onyx" className="collection-card">
          <img src="assets/levant-flacon-4.jpg" alt="Onyx" />
          <div className="overlay"></div>
          <div>
            <div className="lbl">{t.onyx_count}</div>
            <div className="name" style={{ marginTop: 8 }}>Onyx Series</div>
          </div>
          <div>
            <div className="desc">{t.onyx_desc}</div>
            <div className="arrow" style={{ marginTop: 24 }}>{t.collection_view} <Icon name="arrow-r" size={14} /></div>
          </div>
        </a>
      </Reveal>
    </div>
  </section>;


const Guide = ({ t, lang }) =>
<section className="guide" id="guide">
    <div className="container">
      <Reveal className="section-head">
        <div>
          <div className="eyebrow">{t.guide_eyebrow}</div>
          <h2 style={{ marginTop: 12 }}>{t.guide_title}</h2>
        </div>
        <a href="#/articles" className="lnk">{t.guide_cta} <Icon name="arrow-r" size={14} /></a>
      </Reveal>
      <Reveal className="grid" stagger>
        <div className="step">
          <div className="deco"></div>
          <div className="num">01 · {lang === "uk" ? "Сімейство" : "Family"}</div>
          <h3>{t.guide_step1_t}</h3>
          <p>{t.guide_step1_b}</p>
        </div>
        <div className="step">
          <div className="deco"></div>
          <div className="num">02 · {lang === "uk" ? "Інтенсивність" : "Intensity"}</div>
          <h3>{t.guide_step2_t}</h3>
          <p>{t.guide_step2_b}</p>
        </div>
        <div className="step">
          <div className="deco"></div>
          <div className="num">03 · {lang === "uk" ? "Випадок" : "Occasion"}</div>
          <h3>{t.guide_step3_t}</h3>
          <p>{t.guide_step3_b}</p>
        </div>
      </Reveal>
    </div>
  </section>;


const Advantages = ({ t, lang }) =>
<section className="advantages tight">
    <div className="container">
      <Reveal className="section-head" style={{ marginBottom: 32 }}>
        <div>
          <div className="eyebrow">{t.advantages_eyebrow}</div>
          <h2 style={{ marginTop: 12, maxWidth: "16ch" }}>{lang === "uk" ? "Чотири причини обрати Levant" : "Four reasons to choose Levant"}</h2>
        </div>
      </Reveal>
      <Reveal className="grid" stagger>
        <div className="item">
          <div className="ico"><Icon name="shield" size={32} /></div>
          <h4>{t.adv1_t}</h4><p>{t.adv1_b}</p>
        </div>
        <div className="item">
          <div className="ico"><Icon name="truck" size={32} /></div>
          <h4>{t.adv2_t}</h4><p>{t.adv2_b}</p>
        </div>
        <div className="item">
          <div className="ico"><Icon name="refresh" size={32} /></div>
          <h4>{t.adv3_t}</h4><p>{t.adv3_b}</p>
        </div>
      </Reveal>
    </div>
  </section>;


const Reviews = ({ t, lang }) => {
  const reviews = window.LEVANT.REVIEWS;
  return (
    <section className="reviews">
      <div className="container">
        <Reveal className="section-head">
          <div>
            <div className="eyebrow">{t.reviews_eyebrow}</div>
            <h2 style={{ marginTop: 12 }}>{t.reviews_title}</h2>
          </div>
          <a href="#" className="lnk">{t.review_more} <Icon name="arrow-r" size={14} /></a>
        </Reveal>
        <Reveal className="grid" stagger>
          {reviews.map((r, i) =>
          <div key={i} className="review">
              <div className="quote-mark">“</div>
              <p className="text">{lang === "uk" ? r.text_uk : r.text_en}</p>
              <div className="meta">
                <span>{r.name} · {lang === "uk" ? r.city_uk : r.city_en}</span>
                <span className="stars">{"★".repeat(r.rating)}</span>
              </div>
            </div>
          )}
        </Reveal>
      </div>
    </section>);

};

const BlogPreview = ({ t, lang }) => {
  const articles = window.LEVANT.ARTICLES.slice(0, 3);
  return (
    <section className="blog">
      <div className="container">
        <Reveal className="section-head">
          <div>
            <div className="eyebrow">{t.blog_eyebrow}</div>
            <h2 style={{ marginTop: 12 }}>{t.blog_title}</h2>
          </div>
          <a href="#/articles" className="lnk">{t.blog_more} <Icon name="arrow-r" size={14} /></a>
        </Reveal>
        <Reveal className="grid" stagger>
          {articles.map((a) =>
          <a key={a.slug} href={`#/article/${a.slug}`} className="article-card">
              <div className="cover"><img src={a.cover} alt={lang === "uk" ? a.title_uk : a.title_en} /></div>
              <div className="meta">
                <span className="tag">{lang === "uk" ? a.tag_uk : a.tag_en}</span>
                <span>{a.date}</span>
                <span>{a.read} {t.read_min}</span>
              </div>
              <h3>{lang === "uk" ? a.title_uk : a.title_en}</h3>
              <p>{lang === "uk" ? a.excerpt_uk : a.excerpt_en}</p>
            </a>
          )}
        </Reveal>
      </div>
    </section>);

};

const HomePage = ({ t, lang }) => {
  const bestsellers = window.LEVANT.PRODUCTS.filter((p) => p.best);
  const newItems = window.LEVANT.PRODUCTS.filter((p) => p.new);
  return (
    <>
      <HomeHero t={t} lang={lang} />
      <Manifesto t={t} lang={lang} />
      <ProductSlider
        products={bestsellers}
        t={t}
        lang={lang}
        eyebrow={t.bestsellers_eyebrow}
        title={t.bestsellers_title}
        label={lang === "uk" ? "Усі бестселери" : "All bestsellers"} />
      
      <ThreePoints t={t} lang={lang} />
      <Collections t={t} lang={lang} />
      <Guide t={t} lang={lang} />
      <ProductSlider
        products={newItems}
        t={t}
        lang={lang}
        eyebrow={lang === "uk" ? "Новинки" : "New arrivals"}
        title={lang === "uk" ? "Свіже з лабораторії" : "Just out of the lab"}
        label={lang === "uk" ? "Усі новинки" : "All new"} />
      
      <Advantages t={t} lang={lang} />
      <Reviews t={t} lang={lang} />
      <BlogPreview t={t} lang={lang} />
    </>);

};

Object.assign(window, { HomePage });