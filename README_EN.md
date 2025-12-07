## 🌍 Overview

IWS Multilang is an open‑source WordPress plugin that turns a single‑language blog into a lightweight multilingual site by connecting to external translation APIs such as Google Cloud Translation, Zhipu GLM‑4‑Flash, and Anthropic Claude‑3‑Haiku. It focuses on being self‑hosted, configurable, and affordable, so individual bloggers and small teams can enjoy high‑quality AI translation without vendor lock‑in.

## ✨ Features

- 🚀 **Automatic translation** of post titles, full content, and excerpts, driven by pluggable AI engines (Google / GLM / Claude).
- 🔁 **Language switching via URL** using a simple `?iws_lang=xx` parameter (e.g. `en`, `zh-TW`, `es`, `fr`), keeping the original permalink structure intact.
- 🧱 **DOM‑based block translation engine** that parses post HTML and translates it by paragraphs and headings instead of one giant string, improving readability on long articles.
- 🛡️ **Compliance‑aware fallback**: when a large language model refuses to translate a paragraph for safety or policy reasons, the plugin detects typical refusal patterns and automatically falls back to Google Translation for that block, so readers still see a complete article.
- 💾 **Database caching** of translation results to reduce repeated API calls and control costs.
- 🎛️ **Minimal but powerful admin UI**：provider selection, API keys, language list, cache strategy, plus a top‑bar language switcher and sidebar widget.

## 📦 Installation

1. Upload the plugin folder to `wp-content/plugins/` or install it via the WordPress plugin upload interface.
2. Activate **IWS Multilang** in the WordPress admin “Plugins” page.
3. Open the plugin settings and follow the setup wizard:
   - Choose your main translation provider (Google, GLM‑4‑Flash, or Claude‑3‑Haiku) and paste the API keys.
   - Select which languages (EN / 繁體中文 / Español / Français) should be available to visitors.
   - Enable caching and set a cache lifetime that matches your traffic and budget.
   - In WordPress Settings > Appearance > Widgets, add the IWS 语言切换 to your desired location (recommended: sidebar or top bar).
4. Visit any post and use the top language bar or sidebar buttons to switch between language views via the `?iws_lang=` parameter.

## 🛣️ Roadmap

- 🔌 Add more AI providers and open‑source model gateways, giving users a broader choice of translation engines.
- 🧩 Improve handling of special content such as code blocks, captions, and complex quotes while preserving formatting.
- 📝 Provide an optional “review & override” screen so site owners can manually polish key translated paragraphs and write them back into the cache.
- ⚙️ Further optimize block segmentation and timeouts to handle very long articles smoothly on shared hosting and behind CDNs.

## 💬 Afterword

IWS Multilang may be a light and seemingly small WordPress plugin, yet it quietly proves something: within a truly open ecosystem, individuals can shape “Multilingual × AI” into a tool they genuinely own, rather than a service pulled along by subscription plans. How far it can go, and what shape it may grow into, is not determined by someone’s pricing page, but by how much curiosity, creativity, and imagination you are willing to build upon this small foundational block.
