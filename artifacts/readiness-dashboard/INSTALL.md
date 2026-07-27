# PMP Readiness Dashboard Installation

## 1. Deploy the score collector and private score reader

1. Open the score spreadsheet.
2. Select **Extensions → Apps Script**.
3. Replace the editor contents with `google-apps-script/Code.gs`.
4. Open **Project Settings → Script Properties**.
5. Add these Script Properties:
   - `PTA_DASHBOARD_TOKEN` with a long, random value.
   - `PTA_SCORE_ACCESS_KEY` with `pmp-diagnostic-2026-report`, matching the assessment files.
6. Select **Deploy → New deployment → Web app**.
7. Execute as the spreadsheet owner.
8. Set access to **Anyone**. Assessment submissions use `doPost`; dashboard reads use the private token through WordPress.
9. Copy the deployed `/exec` URL.

## 2. Install and configure the WordPress plugin

Zip the `pta-readiness-dashboard` directory, install it through **Plugins → Add New → Upload Plugin**, and activate it.

Open **Settings → PMP Readiness Dashboard** and enter:

- The deployed Google Apps Script `/exec` URL.
- The same private value used for the `PTA_DASHBOARD_TOKEN` Script Property.

The token is stored on the WordPress server and is never placed in lesson HTML or browser JavaScript.

Add this shortcode to the authenticated Assessment Center page:

```text
[pta_readiness_dashboard]
```

The shortcode is optional on the restored Assessment Center design. Plugin version 1.2 automatically finds that design's existing **My Score Snapshot** card and replaces:

- Completed Assessments
- Latest Score
- Readiness Trend
- the status message beneath the metrics

with results matched to the logged-in WordPress user's email.

## 3. Point assessments to the deployment

Update `GOOGLE_SCRIPT_URL` in each assessment HTML file to the deployed `/exec` URL. The current assessment payload already contains the fields expected by the new `Scores` tab.

## 4. Data behavior

- The service automatically finds the worksheet whose first row contains the required headers.
- Results are matched to the logged-in WordPress user's email.
- The latest valid row for each checkpoint is used in history.
- The newest checkpoint supplies the four summary cards.
- If no result exists, the learner sees an empty-state message rather than a zero readiness score.

## 5. Identity and deployment checks

- The email entered in the diagnostic and the learner's WordPress/LMS account email must match after trimming and lowercasing. This prevents one learner from seeing another learner's results.
- After replacing `Code.gs`, use **Deploy → Manage deployments**, edit the existing web-app deployment, select **New version**, and deploy it. Keep the existing `/exec` URL in WordPress.
- The current score-service code records answered and unanswered counts, classifies partial submissions as `Incomplete`, and excludes incomplete submissions from readiness metrics.
