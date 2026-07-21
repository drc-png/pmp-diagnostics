const fs = require("fs");

const inputPath = process.argv[2];
const outputPath = process.argv[3];

if (!inputPath || !outputPath) {
  console.error("Usage: node work/add-meridian-case-block.js input.html output.html");
  process.exit(1);
}

const html = fs.readFileSync(inputPath, "utf8");

function extractQuestions(source) {
  const match = /var\s+questions\s*=\s*\[/.exec(source);
  if (!match) throw new Error("Could not find `var questions = [`");
  const open = source.indexOf("[", match.index);
  let depth = 0;
  let inString = false;
  let quote = "";
  let escaped = false;

  for (let i = open; i < source.length; i += 1) {
    const ch = source[i];
    if (inString) {
      if (escaped) escaped = false;
      else if (ch === "\\") escaped = true;
      else if (ch === quote) inString = false;
      continue;
    }
    if (ch === '"' || ch === "'" || ch === "`") {
      inString = true;
      quote = ch;
    } else if (ch === "[") {
      depth += 1;
    } else if (ch === "]") {
      depth -= 1;
      if (depth === 0) {
        return {
          start: match.index,
          end: i + 1,
          questions: JSON.parse(source.slice(open, i + 1)),
        };
      }
    }
  }
  throw new Error("Could not find end of questions array");
}

const meridianCaseHtml = `
<h3>Case Study: Meridian Health Patient Portal Modernization</h3>
<p>Meridian Health is modernizing its patient portal. Regulatory evidence gates and vendor integration milestones are managed through formal governance, while patient-facing workflow features are refined through short feedback cycles with clinical and operations stakeholders.</p>
<p><strong>Exhibit A - Project Controls and Business Objectives</strong></p>
<ul>
  <li>Approved launch target: 30 September, tied to a payer-contract commitment.</li>
  <li>Approved integration-workstream budget: $800,000.</li>
  <li>Project manager change authority: up to $50,000 and no more than one week of schedule impact.</li>
  <li>Change Control Board authority: required for changes above the project manager's delegated threshold.</li>
  <li>Approved benefit: reduce call-center volume through patient self-service, including appointment self-scheduling.</li>
</ul>
<p><strong>Exhibit B - Regulator Clarification and Stakeholder Messages</strong></p>
<ul>
  <li>The regulator clarified that biometric-consent evidence is required before external users can access the affected flow.</li>
  <li>The sponsor is pressing the team to protect the 30 September date.</li>
  <li>The operations director states that support staff are not trained for the revised launch workflow and that operations was not consulted early enough.</li>
</ul>
<p><strong>Exhibit C - Risk Register Excerpt</strong></p>
<ul>
  <li>R1: Regulator may require additional biometric-consent evidence. Trigger: regulator clarification requiring evidence before external access. Planned response: analyze impact, route required change through governance, and hold only the affected flow until disposition.</li>
  <li>R2: Integration vendor may miss the interim delivery needed for cutover sequencing. Trigger: missed interim delivery. Planned response: invoke the service-level agreement, resequence unaffected integration work, and update the delivery forecast.</li>
</ul>
<p><strong>Exhibit D - Integration Workstream Performance</strong></p>
<ul>
  <li>BAC: $800,000.</li>
  <li>EV: $300,000.</li>
  <li>AC: $375,000.</li>
  <li>Current cost performance is expected to continue unless corrective action changes the trend.</li>
</ul>
<p><strong>Exhibit E - Stakeholder Engagement Snapshot</strong></p>
<ul>
  <li>Operations director: high influence, currently resistant, desired state supportive.</li>
  <li>Support-readiness concern: training and workflow impact have not been made decision-ready for operations.</li>
  <li>Product owner: accountable for patient-facing backlog ordering within approved value and governance constraints.</li>
</ul>
<p><strong>Exhibit F - Change and Release Log</strong></p>
<ul>
  <li>CR-014: biometric-consent scope addition. Estimated impact: $120,000 and three weeks.</li>
  <li>Appointment self-scheduling is in the launch scope and is a key contributor to the call-center-reduction benefit.</li>
  <li>Unaffected feature and integration work can continue if governance controls are maintained.</li>
</ul>`.trim();

const extracted = extractQuestions(html);
let updatedCount = 0;
const questions = extracted.questions.map((q) => {
  if (String(q.id || "").startsWith("CS-MER-")) {
    updatedCount += 1;
    return {
      ...q,
      caseHtml: meridianCaseHtml,
      caseSection: true,
      format: "Case",
    };
  }
  return q;
});

if (updatedCount !== 6) {
  throw new Error(`Expected to update 6 Meridian questions, updated ${updatedCount}`);
}

const updatedHtml =
  html.slice(0, extracted.start) +
  `var questions = ${JSON.stringify(questions, null, 2)}` +
  html.slice(extracted.end);

fs.writeFileSync(outputPath, updatedHtml);
console.log(JSON.stringify({ outputPath, updatedCount, questionCount: questions.length }, null, 2));
