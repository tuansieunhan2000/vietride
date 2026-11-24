# Info Hub content preservation

The content source is the Info Hub modal on https://vietride.vn/, captured on 2026-11-19. This restoration copies the published wording; it does not independently update, interpret or certify the original advice.

The original has 19 questions in this order:

- Riding by the Rules: 4 questions
- Scooter Essentials: 6 questions
- Rental Rundown: 9 questions

`inc/vietride/guides.json` contains the original question text, complete answer HTML, category names and ordering. `inc/vietride/info-hub.json` contains the original section title, headline and category order. Both the homepage and `/guides/` render from these same files. Each guide uses its existing slug and original question as its H1. Search titles describe the topic, and meta descriptions use excerpts or summaries of the source answer.

`docs/reference/info-hub-2026-11-19.html` preserves the original Info Hub section for comparisons; the adjacent JSON file records its source URL and SHA-256. The source licence, speed-limit and rental-agreement answers have been restored. The added accident/emergency and privacy/enquiries topics have been removed. The footer now links to a privacy policy only if an actual WordPress privacy policy page exists.

Do not paraphrase, shorten or add business, rental, legal or safety claims to this content during UI/SEO work. Future copy changes must come from supplied/verified VietRide content or an explicit user instruction. Formatting, accessibility and layout changes must preserve the source text and answer links.

## Presentation

`vr_format_guide_body()` formats the legacy line breaks as paragraphs and lists, and distinguishes existing note text. It preserves the original words, punctuation, list hyphens and link labels/destinations. Stored source content remains unchanged. The same formatter is used for accordion answers and individual guide pages.

The Info Hub index uses topic navigation beside the questions on desktop and wrapped topic links on mobile. Native details/summary controls keep answers available without JavaScript. JavaScript enhances topic navigation by opening a selected category and marking the current topic.

## Verify against the capture

```sh
python3 scripts/sync-info-hub.py docs/reference/info-hub-2026-11-19.html
python3 scripts/audit-info-hub.py http://tuantran.local
```

To compare against the current public source, save its HTML and pass it to the checks:

```sh
curl -fsSL https://vietride.vn/ -o /tmp/vietride-current-source.html
python3 scripts/sync-info-hub.py /tmp/vietride-current-source.html
python3 scripts/audit-info-hub.py http://tuantran.local --source /tmp/vietride-current-source.html
```

The first check compares the stored questions, full answer HTML, links, groups and ordering. The second compares the rendered homepage, Info Hub index and all 19 detail pages with the original text and links. It also verifies that the two added topics are absent from routing and the sitemap.

Do not run `--write` against the redesigned site after production deployment without reviewing the extraction: the extraction intentionally expects the original Info Hub modal structure.
