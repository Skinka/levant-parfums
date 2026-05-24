/* ===================================================
   LEVANT — App shell + Tweaks + Router
   =================================================== */

const TWEAK_DEFAULTS = /*EDITMODE-BEGIN*/{
  "style": "cream",
  "fontPair": "fraunces-inter",
  "accent": "default",
  "lang": "uk"
}/*EDITMODE-END*/;

const FONT_PAIRS = {
  "fraunces-inter":      { label: "Fraunces · Inter",       serif: '"Fraunces", Georgia, serif',                   sans: '"Inter", system-ui, sans-serif' },
  "dmserif-manrope":     { label: "DM Serif · Manrope",      serif: '"DM Serif Display", Georgia, serif',           sans: '"Manrope", system-ui, sans-serif' },
  "cormorant-jost":      { label: "Cormorant · Jost",        serif: '"Cormorant Garamond", Georgia, serif',         sans: '"Jost", system-ui, sans-serif' },
  "playfair-poppins":    { label: "Playfair · Poppins",      serif: '"Playfair Display", Georgia, serif',           sans: '"Poppins", system-ui, sans-serif' },
};

const STYLE_ACCENTS = {
  cream: {
    default:    { "--accent": "#a07a2a", "--accent-2": "#c79a45", "--gold": "#b08a3e" },
    bronze:     { "--accent": "#8a5a2a", "--accent-2": "#b07840", "--gold": "#8a5a2a" },
    burgundy:   { "--accent": "#7a2434", "--accent-2": "#a83648", "--gold": "#7a2434" },
    sage:       { "--accent": "#4a6a48", "--accent-2": "#6a8a68", "--gold": "#4a6a48" },
  },
  onyx: {
    default:    { "--accent": "#d8b56a", "--accent-2": "#efce85" },
    champagne:  { "--accent": "#e8d4a0", "--accent-2": "#f4e3b5" },
    copper:     { "--accent": "#c5824a", "--accent-2": "#dba370" },
    rose:       { "--accent": "#c79388", "--accent-2": "#dca9a0" },
  },
  editorial: {
    default:    { "--accent": "#8a6a25", "--accent-2": "#c79a45" },
    ink:        { "--accent": "#1a1a1a", "--accent-2": "#3a3a37" },
    olive:      { "--accent": "#6a6228", "--accent-2": "#8a8240" },
    plum:       { "--accent": "#4a2438", "--accent-2": "#6a3858" },
  },
};


/* ---------- Tweaks panel ---------- */
const Tweaks = ({ tweak, setTweak }) => {
  const styleOpts = [
    { value: "cream",     label: "Cream" },
    { value: "onyx",      label: "Onyx" },
    { value: "editorial", label: "White" },
  ];
  const fontOpts = Object.entries(FONT_PAIRS).map(([k, v]) => ({ value: k, label: v.label }));
  const accentSet = STYLE_ACCENTS[tweak.style] || STYLE_ACCENTS.cream;
  const accentOptions = Object.keys(accentSet).map(k => accentSet[k]["--accent"]);
  const currentAccentHex = accentSet[tweak.accent] ? accentSet[tweak.accent]["--accent"] : accentSet.default["--accent"];

  return (
    <TweaksPanel title="Tweaks · Levant">
      <TweakSection label="Style direction">
        <TweakRadio
          label="Mood"
          options={styleOpts}
          value={tweak.style}
          onChange={(v) => setTweak({ style: v, accent: "default" })}
        />
      </TweakSection>
      <TweakSection label="Typography">
        <TweakSelect
          label="Serif + sans"
          options={fontOpts}
          value={tweak.fontPair}
          onChange={(v) => setTweak("fontPair", v)}
        />
      </TweakSection>
      <TweakSection label="Accent colour">
        <TweakColor
          label="Gold tone"
          value={currentAccentHex}
          options={accentOptions}
          onChange={(hex) => {
            const match = Object.entries(accentSet).find(([_, v]) => v["--accent"].toLowerCase() === String(hex).toLowerCase());
            if (match) setTweak("accent", match[0]);
          }}
        />
      </TweakSection>
      <TweakSection label="Language">
        <TweakRadio
          label="Default"
          options={[{ value: "uk", label: "UA" }, { value: "en", label: "EN" }]}
          value={tweak.lang}
          onChange={(v) => setTweak("lang", v)}
        />
      </TweakSection>
    </TweaksPanel>
  );
};


/* ---------- App ---------- */
function App() {
  const [tweak, setTweak] = useTweaks(TWEAK_DEFAULTS);
  const route = useHashRoute();

  // Apply CSS variables for fonts + accent + theme on <body>
  useEffect(() => {
    const root = document.documentElement;
    const pair = FONT_PAIRS[tweak.fontPair] || FONT_PAIRS["fraunces-inter"];
    root.style.setProperty("--font-serif", pair.serif);
    root.style.setProperty("--font-sans", pair.sans);

    // Accent vars
    const accentSet = (STYLE_ACCENTS[tweak.style] && STYLE_ACCENTS[tweak.style][tweak.accent]) || STYLE_ACCENTS[tweak.style].default;
    Object.entries(accentSet).forEach(([k, v]) => root.style.setProperty(k, v));

    // Theme class on body so body bg/text resolves
    document.body.classList.remove("theme-cream", "theme-onyx", "theme-editorial");
    document.body.classList.add(`theme-${tweak.style || "cream"}`);
  }, [tweak.fontPair, tweak.style, tweak.accent]);

  // language: header dropdown sets tweak; tweak.lang drives strings
  const lang = tweak.lang || "uk";
  const setLang = (l) => setTweak("lang", l);
  const t = window.LEVANT.I18N[lang];

  // Theme class is applied to body in the effect above

  // Route resolution
  let body;
  if (route === "" || route === "#/" || route === "#") {
    body = <HomePage t={t} lang={lang}/>;
  } else if (route.startsWith("#/catalog")) {
    body = <CatalogPage t={t} lang={lang} route={route}/>;
  } else if (route.startsWith("#/product/")) {
    const slug = route.split("#/product/")[1].split("?")[0];
    body = <ProductPage slug={slug} t={t} lang={lang}/>;
  } else if (route.startsWith("#/about")) {
    body = <AboutPage t={t} lang={lang}/>;
  } else if (route.startsWith("#/contacts")) {
    body = <ContactsPage t={t} lang={lang}/>;
  } else if (route === "#/articles" || route.startsWith("#/articles")) {
    body = <ArticlesPage t={t} lang={lang}/>;
  } else if (route.startsWith("#/article/")) {
    const slug = route.split("#/article/")[1].split("?")[0];
    body = <ArticlePage slug={slug} t={t} lang={lang}/>;
  } else {
    body = <HomePage t={t} lang={lang}/>;
  }

  // page-fade key — restart fade-in on each route change
  const routeKey = route.split("?")[0];

  return (
    <>
      <IntroVeil/>
      <Header lang={lang} setLang={setLang} t={t} route={route}/>
      <main key={routeKey} className="page-fade">{body}</main>
      <Footer t={t} lang={lang}/>
      <Tweaks tweak={tweak} setTweak={setTweak}/>
    </>
  );
}

const root = ReactDOM.createRoot(document.getElementById("root"));
root.render(<App/>);
