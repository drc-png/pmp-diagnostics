import fs from "node:fs";
import path from "node:path";

const root = "/Users/charleneparks/Documents/ATP";
const artifactDir = path.join(root, "artifacts", "pdu-courses");
const docsDir = path.join(root, "docs");

const courses = [
  {
    slug: "enterprise-digital-transformation",
    title: "Enterprise Digital Transformation for Project Managers",
    area: "Ways of Working",
    pdus: 8,
    description: "Plan and lead large-scale transformation from discovery through stabilization, with disciplined governance, rollout, vendor, adoption, and value controls.",
    topics: [
      "Transformation lifecycle: discovery through hypercare and stabilization",
      "Building and managing cross-functional implementation teams",
      "Vendor and system-integrator management at enterprise scale",
      "Phased rollout strategies for multi-location deployments",
      "Change control and scope management during transformation",
      "Operational readiness, adoption, and transition",
      "Measuring transformation success beyond go-live",
      "Enterprise transformation capstone and lessons learned"
    ]
  },
  {
    slug: "agile-at-scale",
    title: "Agile at Scale: From Framework to Execution",
    area: "Ways of Working",
    pdus: 6,
    description: "Translate scaled-agile concepts into workable enterprise delivery across dependencies, governance, legacy systems, and distributed teams.",
    topics: [
      "Selecting SAFe, Scrum@Scale, hybrid, or locally tailored structures",
      "PI planning, release trains, objectives, and dependency visibility",
      "Integrating adaptive work into predictive and regulated environments",
      "Managing dependencies across multiple teams and suppliers",
      "Agile governance, metrics, and executive reporting",
      "Failure patterns, recovery actions, and execution capstone"
    ]
  },
  {
    slug: "ai-powered-project-management",
    title: "AI-Powered Project Management: A Practitioner's Guide",
    area: "Ways of Working",
    pdus: 6,
    description: "Use AI as a governed decision-support tool for planning, reporting, risk, communication, resource analysis, and project controls.",
    topics: [
      "AI foundations, use-case selection, and human accountability",
      "Planning support: WBS, estimation, sequencing, and assumptions",
      "Status reporting and stakeholder communication workflows",
      "Risk identification, response design, and evidence validation",
      "Resource and capacity analysis with privacy-aware data handling",
      "Prompt patterns, ethics, governance, and applied capstone"
    ]
  },
  {
    slug: "risk-management-complex-programs",
    title: "Risk Management in Complex Programs",
    area: "Ways of Working",
    pdus: 4,
    description: "Move beyond isolated risk registers to manage systemic exposure, interdependencies, third parties, escalation, and resilience.",
    topics: [
      "Enterprise and program risk architecture",
      "Quantitative analysis, decision trees, and scenario ranges",
      "Dependency, vendor, and third-party risk mapping",
      "Executive communication, escalation, resilience, and capstone"
    ]
  },
  {
    slug: "executive-presence-project-leaders",
    title: "Executive Presence for Project Leaders",
    area: "Power Skills",
    pdus: 6,
    description: "Communicate with credibility, influence without authority, navigate organizational dynamics ethically, and lead difficult executive conversations.",
    topics: [
      "Leadership brand, credibility, and professional identity",
      "Executive briefings and steering-committee communication",
      "Influencing without authority in matrixed organizations",
      "Navigating organizational politics with integrity",
      "Difficult conversations, bad news, and conflict",
      "Stakeholder trust, executive simulation, and action plan"
    ]
  },
  {
    slug: "leading-through-change",
    title: "Leading Through Change: The Human Side of Transformation",
    area: "Power Skills",
    pdus: 6,
    description: "Guide people through uncertainty using sponsorship, involvement, communication, learning, resistance evidence, and reinforcement.",
    topics: [
      "Change leadership and the project leader's role",
      "Coalitions, sponsorship, and distributed ownership",
      "Resistance evidence and root-cause response",
      "Communication and involvement across stakeholder groups",
      "Training, capability, adoption, and rollout readiness",
      "Sustainment, culture, reinforcement, and capstone"
    ]
  },
  {
    slug: "high-performance-team-building",
    title: "High-Performance Team Building for Project Managers",
    area: "Power Skills",
    pdus: 4,
    description: "Create team clarity, psychological safety, accountability, coaching, and conflict practices that work across colocated and distributed teams.",
    topics: [
      "Team formation, roles, norms, and operating agreements",
      "Psychological safety, inclusion, and productive disagreement",
      "Distributed-team leadership, coaching, and development",
      "Accountability, conflict resolution, and team capstone"
    ]
  },
  {
    slug: "strategic-alignment-business-value",
    title: "Strategic Alignment: Connecting Projects to Business Value",
    area: "Business Acumen",
    pdus: 6,
    description: "Connect project decisions to strategy, business cases, benefits, financial measures, stakeholder value, and outcome evidence.",
    topics: [
      "Organizational strategy, portfolios, and project alignment",
      "Business cases and executive decision logic",
      "Benefits realization from promise to proof",
      "Financial literacy: ROI, NPV, and payback",
      "Stakeholder value mapping and prioritization",
      "OKRs, KPIs, strategic metrics, and capstone"
    ]
  },
  {
    slug: "ai-strategy-business-impact",
    title: "AI Strategy and Business Impact for Project Leaders",
    area: "Business Acumen",
    pdus: 4,
    description: "Evaluate AI opportunities, readiness, governance, vendors, integration, and measurable business impact without requiring coding.",
    topics: [
      "AI landscape, opportunity framing, and strategic fit",
      "Use-case evaluation, readiness, and business-case design",
      "Data governance, ethics, vendors, and integration",
      "ROI, adoption measures, risk, and decision capstone"
    ]
  },
  {
    slug: "retail-enterprise-operations",
    title: "Industry Domain Deep Dive: Retail and Enterprise Operations",
    area: "Business Acumen",
    pdus: 4,
    description: "Understand the operating, technology, deployment, vendor, and governance context behind complex retail and enterprise initiatives.",
    topics: [
      "Retail technology ecosystem: POS, supply chain, HCM, and ERP",
      "Multi-location rollout and store-operations impact",
      "Shared services, process optimization, compliance, and governance",
      "Vendor ecosystems, transformation trends, and industry capstone"
    ]
  },
  {
    slug: "organizational-change-transformation-strategy",
    title: "Organizational Change and Transformation Strategy",
    area: "Business Acumen",
    pdus: 6,
    description: "Connect transformation vision to portfolio choices, funding, governance, roadmaps, value measurement, and PMO capability.",
    topics: [
      "Transformation vision and execution roadmaps",
      "Portfolio management and program prioritization",
      "Funding models: CapEx, OpEx, and value streams",
      "Governance for enterprise change programs",
      "Transformation ROI and organizational impact",
      "Building a transformation PMO and strategy capstone"
    ]
  }
];

const esc = (value) => String(value)
  .replaceAll("&", "&amp;")
  .replaceAll("<", "&lt;")
  .replaceAll(">", "&gt;")
  .replaceAll('"', "&quot;");

const moduleCard = (topic, index) => `
<article class="module">
  <div class="number">${String(index + 1).padStart(2, "0")}</div>
  <div>
    <h3>${esc(topic)}</h3>
    <p><strong>Learning sequence:</strong> 40-minute video lesson, 10-minute case application, 5-minute workbook reflection, and 5-minute knowledge check.</p>
    <ul>
      <li>Identify the current state, evidence, stakeholders, authority, and value implications.</li>
      <li>Apply the concept to a realistic enterprise scenario.</li>
      <li>Record one decision rule and one action that transfers to the learner's work.</li>
    </ul>
    <p class="placeholder">Video status: recording pending</p>
  </div>
</article>`;

function courseShell(course) {
  return `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>${esc(course.title)} | PMP Training Academy</title>
<style>
:root{--navy:#111e3e;--blue:#1b2e5e;--gold:#c49a27;--paper:#fdfcf8;--muted:#5b6678;--line:#dfe4ec}
*{box-sizing:border-box}body{margin:0;background:var(--paper);color:#202737;font:17px/1.6 Arial,Helvetica,sans-serif}
main{width:min(980px,calc(100% - 32px));margin:auto;padding:48px 0 72px}
.hero{border-top:7px solid var(--gold);border-radius:9px;background:var(--navy);color:#fff;padding:34px;box-shadow:0 18px 45px #111e3e22}
.kicker{color:#f0d275;font-weight:800;letter-spacing:.11em;text-transform:uppercase;font-size:.78rem}
h1,h2,h3{font-family:Georgia,"Times New Roman",serif}h1{font-size:clamp(2rem,5vw,3.5rem);line-height:1.05;margin:.35rem 0 1rem}
.hero p{max-width:780px;color:#e9edf6}.badges{display:flex;flex-wrap:wrap;gap:9px;margin-top:20px}.badge{border:1px solid #ffffff44;border-radius:999px;padding:7px 11px;font-weight:700;font-size:.85rem}
.notice{margin:24px 0;border-left:5px solid var(--gold);background:#fff8e4;padding:16px 19px;border-radius:6px}
.modules{display:grid;gap:16px}.module{display:grid;grid-template-columns:54px 1fr;gap:18px;background:#fff;border:1px solid var(--line);border-radius:8px;padding:22px}
.number{width:46px;height:46px;display:grid;place-items:center;border-radius:50%;background:var(--blue);color:#fff;font-weight:800}.module h3{margin:0 0 6px;color:var(--blue)}
.placeholder{width:fit-content;background:#eef3fb;color:var(--blue);padding:6px 9px;border-radius:5px;font-weight:800;font-size:.82rem}
.completion{margin-top:28px;background:#fff;border:1px solid var(--line);border-radius:8px;padding:24px}.completion h2{color:var(--blue)}
@media(max-width:600px){.hero{padding:25px}.module{grid-template-columns:1fr}.number{width:38px;height:38px}}
</style>
</head>
<body><main>
<section class="hero">
  <div class="kicker">${esc(course.area)} · Course in production</div>
  <h1>${esc(course.title)}</h1>
  <p>${esc(course.description)}</p>
  <div class="badges"><span class="badge">Designed for ${course.pdus} PDUs</span><span class="badge">${course.pdus} learning modules</span><span class="badge">On-demand</span><span class="badge">Self-paced</span></div>
</section>
<div class="notice"><strong>PDU publication status:</strong> This course is designed for ${course.pdus} education PDUs in ${esc(course.area)}. Advertise it as pre-approved only after its individual activity record is active in PMI's CCR system.</div>
<h2>Course modules</h2>
<section class="modules">${course.topics.map(moduleCard).join("")}</section>
<section class="completion">
  <h2>Completion requirements</h2>
  <ul>
    <li>Complete all ${course.pdus} module learning sequences.</li>
    <li>Submit the module workbook reflections and final workplace application plan.</li>
    <li>Earn at least 80% on the final knowledge check, with one permitted retake after review.</li>
    <li>Complete the course evaluation and certificate attestation.</li>
  </ul>
</section>
</main></body></html>`;
}

function workbook(course) {
  return `<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>${esc(course.title)} Workbook</title>
<style>
@page{size:letter;margin:.6in}*{box-sizing:border-box}body{margin:0;color:#202737;font:15px/1.45 Arial,Helvetica,sans-serif}header{border-top:7px solid #c49a27;background:#111e3e;color:#fff;padding:26px;margin-bottom:24px}h1,h2{font-family:Georgia,"Times New Roman",serif}h1{margin:4px 0;font-size:30px}h2{color:#1b2e5e;border-bottom:2px solid #c49a27;padding-bottom:6px}.page{break-after:page}.box{min-height:105px;border:1px solid #bfc7d4;border-radius:6px;margin:9px 0 18px;padding:10px}.prompt{font-weight:700;color:#1b2e5e}.meta{color:#f0d275;font-weight:700;text-transform:uppercase;letter-spacing:.08em}.footer{font-size:11px;color:#667085;margin-top:22px}
@media print{.page:last-child{break-after:auto}}
</style></head><body>
<header><div class="meta">${esc(course.area)} · ${course.pdus}-PDU course workbook</div><h1>${esc(course.title)}</h1><p>Use this workbook while viewing each module. Remove confidential information from all examples.</p></header>
${course.topics.map((topic, index) => `<section class="page"><h2>Module ${index + 1}: ${esc(topic)}</h2>
<p class="prompt">1. What is the most important principle from this module?</p><div class="box"></div>
<p class="prompt">2. Apply it to a real or realistic situation. What evidence, authority, risk, and value considerations matter?</p><div class="box"></div>
<p class="prompt">3. What decision or action would you recommend, and how would you verify the result?</p><div class="box"></div>
<p class="footer">PMP Training Academy · Participant workbook · ${esc(course.title)}</p></section>`).join("")}
<section><h2>Final workplace application plan</h2><p class="prompt">Practice or process I will improve:</p><div class="box"></div><p class="prompt">First action, owner, evidence, and review date:</p><div class="box"></div></section>
</body></html>`;
}

function recordingPlan(course) {
  const modules = course.topics.map((topic, index) => `### Video ${index + 1}: ${topic}

- **Target run time:** 38-42 minutes
- **Opening hook:** Start with a realistic enterprise decision or failure pattern connected to this topic.
- **Teaching sequence:** Define the concept; show why it matters; compare weak and strong practice; demonstrate the decision logic; connect it to value.
- **Case application:** Present one situation with incomplete evidence, competing stakeholders, and a clear authority boundary.
- **Learner action:** Complete Module ${index + 1} in the workbook and record one reusable decision rule.
- **Knowledge check:** Three scenario questions plus rationale review.
- **Production assets:** 6-10 teaching slides, one process visual, one case slide, captions, transcript, and downloadable job aid.
`).join("\n");
  return `# ${course.title} - Video Production Plan

**Talent Triangle area:** ${course.area}  
**Designed learning time:** ${course.pdus} hours / ${course.pdus} modules  
**Delivery:** On-demand, self-paced  
**Status:** Course shell and learner workbook ready; video production pending.

## Course outcome

${course.description}

## Standard module timing

| Component | Minutes |
|---|---:|
| Recorded lesson | 40 |
| Case application | 10 |
| Workbook reflection | 5 |
| Knowledge check and rationale review | 5 |
| **Total per module** | **60** |

${modules}

## Course-level production checklist

- Confirm the individual PMI CCR activity record is active before using “pre-approved” in marketing.
- Validate total learning time from actual edited video duration and required activities.
- Add captions, accessible transcripts, alt text, keyboard support, and downloadable resources.
- Require completion tracking, assessment evidence, course evaluation, and certificate issuance.
- Retain learner completion records and the published course version used for each certificate.
`;
}

function catalogPage() {
  const cards = courses.map(course => `<article class="card">
    <div class="area">${esc(course.area)}</div>
    <h2>${esc(course.title)}</h2>
    <p>${esc(course.description)}</p>
    <div class="chips"><span>Designed for ${course.pdus} PDUs</span><span>${course.pdus} modules</span><span>On-demand</span></div>
    <a href="${course.slug}-course-shell.html">Preview course outline →</a>
  </article>`).join("");
  return `<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>PDU Course Library | PMP Training Academy</title>
<style>
:root{--navy:#111e3e;--blue:#1b2e5e;--gold:#c49a27;--paper:#fdfcf8;--muted:#5b6678;--line:#dfe4ec}*{box-sizing:border-box}body{margin:0;background:var(--paper);color:#202737;font:16px/1.55 Arial,Helvetica,sans-serif}header{background:linear-gradient(135deg,var(--navy),#263c74);color:#fff;border-top:8px solid var(--gold);padding:64px 20px}header>div,main{width:min(1160px,calc(100% - 32px));margin:auto}h1,h2{font-family:Georgia,"Times New Roman",serif}h1{font-size:clamp(2.5rem,6vw,4.8rem);line-height:1;margin:.3rem 0 1rem}.kicker{color:#f0d275;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.lede{max-width:820px;color:#e8edf7;font-size:1.12rem}.summary{display:flex;flex-wrap:wrap;gap:10px;margin-top:24px}.summary span,.chips span{border:1px solid #ffffff44;border-radius:999px;padding:7px 11px;font-weight:700}.notice{width:min(1160px,calc(100% - 32px));margin:26px auto 0;border-left:5px solid var(--gold);background:#fff7df;padding:17px 20px;border-radius:6px}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;padding:34px 0 70px}.card{display:flex;flex-direction:column;border:1px solid var(--line);border-radius:9px;background:#fff;padding:25px;box-shadow:0 12px 30px #111e3e0d}.area{color:#8d6a12;font-size:.76rem;font-weight:900;letter-spacing:.1em;text-transform:uppercase}.card h2{color:var(--blue);font-size:1.55rem;margin:.4rem 0}.card p{color:var(--muted)}.chips{display:flex;flex-wrap:wrap;gap:7px;margin:auto 0 18px}.chips span{border-color:var(--line);font-size:.78rem}.card a{width:fit-content;background:var(--blue);color:#fff;text-decoration:none;padding:10px 13px;border-radius:5px;font-weight:800}
@media(max-width:760px){.grid{grid-template-columns:1fr}}
</style></head><body>
<header><div><div class="kicker">PMP Training Academy · Continuing Education</div><h1>PDU Course Library</h1><p class="lede">Eleven practitioner-led courses designed around 60 hours of professional learning across Ways of Working, Power Skills, and Business Acumen. Video production is underway.</p><div class="summary"><span>60 designed PDUs</span><span>11 courses</span><span>3 Talent Triangle areas</span><span>On-demand</span></div></div></header>
<div class="notice"><strong>Publication note:</strong> PDU amounts shown are design targets. Each course must have an active individual activity record in PMI's CCR system before it is marketed as pre-approved.</div>
<main><section class="grid">${cards}</section></main>
</body></html>`;
}

fs.mkdirSync(artifactDir, { recursive: true });
fs.mkdirSync(docsDir, { recursive: true });
fs.writeFileSync(path.join(artifactDir, "course-library.json"), JSON.stringify(courses, null, 2) + "\n");
for (const course of courses) {
  fs.writeFileSync(path.join(artifactDir, `${course.slug}-course-shell.html`), courseShell(course));
  fs.writeFileSync(path.join(artifactDir, `${course.slug}-workbook.html`), workbook(course));
  fs.writeFileSync(path.join(artifactDir, `${course.slug}-recording-plan.md`), recordingPlan(course));
}
fs.writeFileSync(path.join(artifactDir, "index.html"), catalogPage());
fs.writeFileSync(path.join(docsDir, "pdu-course-catalog.html"), catalogPage());
for (const course of courses) {
  fs.copyFileSync(
    path.join(artifactDir, `${course.slug}-course-shell.html`),
    path.join(docsDir, `${course.slug}-course-shell.html`)
  );
}
fs.writeFileSync(path.join(artifactDir, "README.md"), `# PDU Course Production Library

This package contains 11 course shells, 11 printable learner workbooks, and 11 video-production plans.

## Course structure

Each designed PDU corresponds to one 60-minute learning module:

- 40-minute recorded lesson
- 10-minute case application
- 5-minute workbook reflection
- 5-minute knowledge check and rationale review

## Publication gate

Do not advertise an individual course as PMI pre-approved until its activity record is active in PMI's CCR system. Reconcile advertised PDUs to the final edited video duration and required learning activities.

## Files

- \`course-library.json\`: canonical course metadata
- \`*-course-shell.html\`: learner-facing course outline
- \`*-workbook.html\`: printable learner workbook
- \`*-recording-plan.md\`: video and asset production plan
- \`index.html\`: complete course-library landing page
`);

console.log(`Built ${courses.length} courses, ${courses.length} workbooks, and ${courses.length} recording plans.`);
