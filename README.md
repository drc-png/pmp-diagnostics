# PMP Training Academy Diagnostics

Static deployment package for PMP Training Academy diagnostic exams and reusable question-bank files.

## Current Public Entry

- `docs/index.html` - Diagnostic 1, including the Meridian opening case-study section and July 2026 break flow.
- `docs/diagnostic-1.html` - Named copy of the same Diagnostic 1 file.
- `docs/question-banks/diagnostic-1.questions.json` - Extracted question data for the live diagnostic.
- `docs/question-banks/diagnostic-1-question-bank.csv` - Spreadsheet-ready question bank.

## GitHub Pages Setup

In the GitHub repository:

1. Open **Settings**.
2. Open **Pages**.
3. Set **Source** to **Deploy from a branch**.
4. Set branch to `main`.
5. Set folder to `/docs`.
6. Save.

The site will publish at:

```text
https://YOUR-GITHUB-USERNAME.github.io/YOUR-REPO-NAME/
```

## Elementor Embed

Add an HTML widget in Elementor and use:

```html
<iframe
  src="https://YOUR-GITHUB-USERNAME.github.io/YOUR-REPO-NAME/"
  style="width:100%; min-height:900px; border:0;"
  loading="lazy"
></iframe>
```

Use the final GitHub Pages URL after the repo is published.

## Updating Diagnostic 1

After rebuilding the latest diagnostic in `outputs/diagnostic-1-FINAL-180-with-meridian-case.html`, run:

```bash
node work/publish-current-diagnostic.js
```

Then commit and push:

```bash
git add docs outputs work README.md .gitignore
git commit -m "Update Diagnostic 1"
git push
```

## Content Privacy Note

GitHub Pages is simple, but anything served through a public Pages site can be viewed by visitors. If the full question bank should stay private, use a private repository plus a WordPress-hosted deployment or an authenticated delivery method instead.
