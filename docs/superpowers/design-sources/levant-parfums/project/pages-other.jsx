/* ===================================================
   LEVANT — About / Contacts / Articles / Article
   =================================================== */

const AboutPage = ({ t, lang }) =>
<div>
    <section className="about-hero">
      <div className="container">
        <div className="crumbs" style={{ padding: "0 0 32px" }}>
          <a href="#/">{t.nav.home}</a><span className="sep">/</span>
          <span className="current">{t.nav.about}</span>
        </div>
        <div className="grid">
          <Reveal>
            <div className="eyebrow">{lang === "uk" ? "Про дім" : "About the house"}</div>
            <h1 style={{ marginTop: 18, fontStyle: "italic" }}>{t.about_title}</h1>
            <p className="lead" style={{ marginTop: 28 }}>{t.about_sub}</p>
            <p style={{ marginTop: 24, color: "var(--ink-soft)", maxWidth: "52ch", lineHeight: 1.7 }}>
              {lang === "uk" ?
              "Levant — давня назва регіону, де зустрічаються Схід і Захід, де торгівля, культура та аромати знаходили одне одного тисячоліттями. Наш дім — це продовження цієї історії: ідея народжується в Іспанії, флакон збирається у Туреччині, серце ринку — в Україні. Три точки. Один підпис." :
              "Levant is the ancient name of a region where East and West meet, where trade, culture and scent have found each other for millennia. Our house is the continuation of that story: the idea is born in Spain, the bottle is assembled in Turkey, the market and the soul are in Ukraine. Three points. One signature."
            }
            </p>
          </Reveal>
          <Reveal className="img">
            <img src="assets/levant-luxury-bottle.jpg" alt="atelier" />
          </Reveal>
        </div>
        <Reveal className="about-stats" stagger>
          {t.about_stats.map((s, i) =>
        <div key={i} className="stat">
              <div className="num">{s.n}</div>
              <div className="lbl">{s.l}</div>
            </div>
        )}
        </Reveal>
      </div>
    </section>

    <section className="manifesto">
      <div className="container">
        <div className="grid">
          <Reveal>
            <div className="eyebrow">{t.manifesto_eyebrow}</div>
            <h2 style={{ marginTop: 16, fontStyle: "italic" }}>
              <span className="quote-open">“</span>
              {t.manifesto_title}
            </h2>
          </Reveal>
          <Reveal className="body">
            <p>{t.manifesto_body_p1}</p>
            <p>{t.manifesto_body_p2}</p>
            <p style={{ marginTop: 20 }}>
              {lang === "uk" ?
              "Дорога коробка не робить аромат кращим. Ми вкладаємо у склянку те, у що інші вкладають у логотип. Кінцевий результат — нішевий характер за чесну ціну." :
              "An expensive box does not make a better scent. We invest in the bottle what others invest in the logo. The result — niche character at an honest price."}
            </p>
            <p style={{ fontFamily: "var(--font-serif)", fontStyle: "italic", color: "var(--accent)", marginTop: 28 }}>
              {t.manifesto_sig}
            </p>
          </Reveal>
        </div>
      </div>
    </section>

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
    </section>

    <section className="tight">
      <div className="container">
        <Reveal className="section-head">
          <div>
            <div className="eyebrow">{lang === "uk" ? "Команда" : "Team"}</div>
            <h2 style={{ marginTop: 12 }}>{lang === "uk" ? "Хто стоїть за ароматом" : "Who is behind the scent"}</h2>
          </div>
        </Reveal>
        <div className="blog" style={{ padding: 0 }}>
          <Reveal className="grid" stagger style={{ display: "grid", gridTemplateColumns: "repeat(3,1fr)", gap: 24 }}>
            {[
          { name: "Carlos Mendoza", role_uk: "Парфумер, Барселона", role_en: "Perfumer, Barcelona", img: "assets/levant-luxury-1-marble.jpg" },
          { name: "Ayşe Demir", role_uk: "Технологія виробництва, Стамбул", role_en: "Production, Istanbul", img: "assets/levant-onyx-luxury-pair.jpg" },
          { name: "Олеся Коваленко", role_uk: "Креативна директорка, Київ", role_en: "Creative director, Kyiv", img: "assets/levant-luxury-box.jpg" }].
          map((m) =>
          <div key={m.name} className="article-card">
                <div className="cover" style={{ aspectRatio: "1" }}><img src={m.img} alt="" /></div>
                <h3 style={{ fontStyle: "italic", marginTop: 4 }}>{m.name}</h3>
                <div className="meta"><span className="tag">{lang === "uk" ? m.role_uk : m.role_en}</span></div>
              </div>
          )}
          </Reveal>
        </div>
      </div>
    </section>
  </div>;



const ContactsPage = ({ t, lang }) => {
  const [data, setData] = useState({ name: "", email: "", msg: "" });
  const [sent, setSent] = useState(false);
  return (
    <section className="contacts" style={{ padding: "32px 0 120px" }}>
      <div className="container">
        <div className="crumbs" style={{ padding: "0 0 32px" }}>
          <a href="#/">{t.nav.home}</a><span className="sep">/</span>
          <span className="current">{t.nav.contacts}</span>
        </div>
        <Reveal className="section-head" style={{ marginBottom: 64 }}>
          <div>
            <div className="eyebrow">{lang === "uk" ? "Зв'язок" : "Contact"}</div>
            <h1 style={{ marginTop: 18, fontStyle: "italic" }}>{t.contacts_title}</h1>
            <p className="lead" style={{ marginTop: 24, maxWidth: "44ch" }}>{t.contacts_sub}</p>
          </div>
        </Reveal>
        <div className="grid">
          <Reveal className="info" stagger>
            <div className="item">
              <div className="l"><Icon name="pin" size={14} /> {t.contacts_addr_l}</div>
              <div className="v">{t.contacts_addr}</div>
            </div>
            <div className="item">
              <div className="l"><Icon name="phone" size={14} /> {t.contacts_phone_l}</div>
              <div className="v">{t.contacts_phone}</div>
            </div>
            <div className="item">
              <div className="l"><Icon name="mail" size={14} /> {t.contacts_mail_l}</div>
              <div className="v">{t.contacts_mail}</div>
            </div>
            <div className="item">
              <div className="l"><Icon name="clock" size={14} /> {t.contacts_hours_l}</div>
              <div className="v">{t.contacts_hours}</div>
            </div>
            <div className="item" style={{ paddingTop: 20 }}>
              <div className="l">{lang === "uk" ? "Соц мережі" : "Social"}</div>
              <div style={{ display: "flex", gap: 18, marginTop: 8 }}>
                <a href="#" className="lnk lnk-mute">Instagram</a>
                <a href="#" className="lnk lnk-mute">Telegram</a>
                <a href="#" className="lnk lnk-mute">TikTok</a>
              </div>
            </div>
          </Reveal>
          <Reveal className="form-card">
            <div className="eyebrow">{lang === "uk" ? "Форма" : "Form"}</div>
            <h2 style={{ marginTop: 12, fontStyle: "italic", marginBottom: 32 }}>{t.contacts_form_title}</h2>
            {sent ?
            <div style={{ padding: "40px 0" }}>
                <div className="ok" style={{ width: 56, height: 56, border: "1px solid var(--accent)", color: "var(--accent)", borderRadius: "50%", display: "inline-flex", alignItems: "center", justifyContent: "center", marginBottom: 24 }}><Icon name="check" size={24} /></div>
                <h3 style={{ fontStyle: "italic" }}>{t.form_thanks}</h3>
              </div> :

            <form className="fields" onSubmit={(e) => {e.preventDefault();setSent(true);}}>
                <div className="field"><label>{t.form_name} *</label><input required value={data.name} onChange={(e) => setData({ ...data, name: e.target.value })} /></div>
                <div className="field"><label>{t.form_email} *</label><input required type="email" value={data.email} onChange={(e) => setData({ ...data, email: e.target.value })} /></div>
                <div className="field full"><label>{t.form_msg} *</label><textarea required value={data.msg} onChange={(e) => setData({ ...data, msg: e.target.value })}></textarea></div>
                <div className="actions">
                  <span className="agree">{t.order_agree}</span>
                  <button type="submit" className="btn"><span>{t.form_submit}</span> <Icon name="arrow-r" size={14} /></button>
                </div>
              </form>
            }
          </Reveal>
        </div>
      </div>
    </section>);

};


const ArticlesPage = ({ t, lang }) => {
  const arts = window.LEVANT.ARTICLES;
  return (
    <section style={{ padding: "32px 0 120px" }}>
      <div className="container">
        <div className="crumbs" style={{ padding: "0 0 32px" }}>
          <a href="#/">{t.nav.home}</a><span className="sep">/</span>
          <span className="current">{t.nav.articles}</span>
        </div>
        <Reveal className="section-head" style={{ alignItems: "end" }}>
          <div>
            <div className="eyebrow">{lang === "uk" ? "Журнал · 2026" : "Journal · 2026"}</div>
            <h1 style={{ marginTop: 18, fontStyle: "italic" }}>{t.articles_title}</h1>
            <p className="lead" style={{ marginTop: 24, maxWidth: "44ch" }}>{t.articles_sub}</p>
          </div>
        </Reveal>
        <Reveal style={{ marginTop: 80 }} className="articles-grid" stagger>
          {arts.map((a) =>
          <a key={a.slug} href={`#/article/${a.slug}`} className="article-card">
              <div className="cover"><img src={a.cover} alt="" /></div>
              <div className="meta">
                <span className="tag">{lang === "uk" ? a.tag_uk : a.tag_en}</span>
                <span>{a.date}</span>
                <span>{a.read} {t.read_min}</span>
              </div>
              <h3 style={{ fontSize: 28, marginTop: 4 }}>{lang === "uk" ? a.title_uk : a.title_en}</h3>
              <p>{lang === "uk" ? a.excerpt_uk : a.excerpt_en}</p>
              <span className="lnk" style={{ marginTop: 12, width: "max-content" }}>{t.read_more} <Icon name="arrow-r" size={14} /></span>
            </a>
          )}
        </Reveal>
      </div>
    </section>);

};


const ArticlePage = ({ slug, t, lang }) => {
  const a = window.LEVANT.ARTICLES.find((x) => x.slug === slug);
  if (!a) return <div className="container" style={{ padding: "120px 0" }}><h2>404</h2></div>;
  return (
    <article style={{ padding: "32px 0 80px" }}>
      <div className="container">
        <div className="crumbs">
          <a href="#/">{t.nav.home}</a><span className="sep">/</span>
          <a href="#/articles">{t.nav.articles}</a><span className="sep">/</span>
          <span className="current">{lang === "uk" ? a.title_uk : a.title_en}</span>
        </div>
        <Reveal style={{ maxWidth: 780, margin: "60px auto 0" }}>
          <div className="meta" style={{ display: "flex", gap: 18, fontSize: 11, letterSpacing: "0.18em", textTransform: "uppercase", color: "var(--ink-mute)" }}>
            <span style={{ color: "var(--accent)" }}>{lang === "uk" ? a.tag_uk : a.tag_en}</span>
            <span>{a.date}</span>
            <span>{a.read} {t.read_min}</span>
          </div>
          <h1 style={{ marginTop: 28, fontStyle: "italic", fontSize: "clamp(40px, 5vw, 80px)" }}>{lang === "uk" ? a.title_uk : a.title_en}</h1>
          <p className="lead" style={{ marginTop: 28 }}>{lang === "uk" ? a.excerpt_uk : a.excerpt_en}</p>
        </Reveal>
        <Reveal style={{ maxWidth: 1200, margin: "60px auto 0", aspectRatio: "16/9", overflow: "hidden" }}>
          <img src={a.cover} style={{ width: "100%", height: "100%", objectFit: "cover" }} alt="" />
        </Reveal>
        <div style={{ maxWidth: 720, margin: "60px auto 0", color: "var(--ink-soft)", fontSize: 18, lineHeight: 1.8 }}>
          <p style={{ fontFamily: "var(--font-serif)", fontSize: 28, lineHeight: 1.4, color: "var(--ink)", fontStyle: "italic" }}>
            {lang === "uk" ?
            "Парфум — це найшвидший вид мистецтва. Він не вимагає рами, експозиції, музею. Достатньо нанести краплю на зап'ясток — і виставка вже почалась." :
            "Perfume is the fastest form of art. It requires no frame, no exhibition, no museum. A drop on the wrist — and the show has begun."}
          </p>
          <p style={{ marginTop: 28 }}>
            {lang === "uk" ?
            "У наступних кількох абзацах ми поговоримо про найважливіше: як обрати свій аромат, не покладаючись на чужий смак, не довіряючи маркетингу і не намагаючись повторити чужий вибір." :
            "In the next few paragraphs we will talk about the most important thing: how to choose your scent without relying on someone else's taste, without trusting marketing and without trying to repeat another person's choice."}
          </p>
          <p style={{ marginTop: 20 }}>
            {lang === "uk" ?
            "По-перше, ніколи не вибирайте парфум на голодний шлунок. Запах сприймається мозком разом із сигналами голоду — і ви, ймовірно, перейдете до гурманських нот, які наступного дня здадуться занадто солодкими." :
            "First, never choose a perfume on an empty stomach. The brain reads scent together with hunger signals — you will gravitate to gourmand notes that will feel too sweet the next morning."}
          </p>
          <p style={{ marginTop: 20 }}>
            {lang === "uk" ?
            "По-друге, не використовуйте більше двох-трьох ароматів за один похід. Ніс втомлюється швидше, ніж очі. На четвертому тесті ви вже не відрізните троянду від тубероз." :
            "Second, do not test more than two or three scents in a single visit. The nose tires faster than the eyes. By the fourth test you will not tell rose from tuberose."}
          </p>
          <h2 style={{ marginTop: 56, fontSize: 36, fontStyle: "italic" }}>{lang === "uk" ? "І, нарешті, головне" : "And, finally, the main thing"}</h2>
          <p style={{ marginTop: 20 }}>
            {lang === "uk" ?
            "Парфум — це не аксесуар, а форма автобіографії. Він має пахнути не так, як ви хочете виглядати — а так, як ви себе пам'ятаєте. Найкращі флакони — ті, які ви відкриваєте через пів року і несподівано згадуєте, чому почали його носити." :
            "Perfume is not an accessory. It is a form of autobiography. It should smell like the way you remember yourself — not the way you want to look. The best bottles are those you open six months later and suddenly remember why you started wearing them."}
          </p>
        </div>
      </div>
    </article>);

};


Object.assign(window, { AboutPage, ContactsPage, ArticlesPage, ArticlePage });
