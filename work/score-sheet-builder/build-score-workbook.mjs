import fs from "node:fs/promises";
import { SpreadsheetFile, Workbook } from "@oai/artifact-tool";

const outputDir = "/Users/charleneparks/Documents/ATP/outputs/019f9f84-fdca-7c62-9c9f-c95e54cc4d9b";
const outputPath = `${outputDir}/pmp-assessment-score-record.xlsx`;
const previewDir = `${outputDir}/score-sheet-previews`;

const workbook = Workbook.create();
const scores = workbook.worksheets.add("Scores");
const dashboard = workbook.worksheets.add("Dashboard");
const instructions = workbook.worksheets.add("Instructions");

scores.showGridLines = false;
dashboard.showGridLines = false;
instructions.showGridLines = false;

const headers = [
  "Timestamp",
  "Checkpoint",
  "Name",
  "Email",
  "Overall %",
  "Overall Score",
  "People %",
  "People Score",
  "Process %",
  "Process Score",
  "Business %",
  "Business Score",
  "Topic Details",
  "Submission Reference",
  "Assessment Version",
  "Completion Seconds",
  "Average Seconds/Question",
  "Flagged Count",
  "Answer Changes",
  "Completion Status",
  "Answered Count",
  "Unanswered Count",
];

scores.getRange("A1:V1").values = [headers];
scores.freezePanes.freezeRows(1);
scores.getRange("A1:V1").format = {
  fill: "#E8EBF0",
  font: { bold: true, color: "#111E3E" },
  wrapText: true,
  verticalAlignment: "center",
  borders: { bottom: { style: "medium", color: "#C49A27" } },
};
scores.getRange("A1:T1").format.rowHeightPx = 38;
scores.getRange("A2:A2001").setNumberFormat("yyyy-mm-dd hh:mm");
scores.getRange("E2:E2001").setNumberFormat("0.0");
scores.getRange("G2:G2001").setNumberFormat("0.0");
scores.getRange("I2:I2001").setNumberFormat("0.0");
scores.getRange("K2:K2001").setNumberFormat("0.0");
scores.getRange("P2:S2001").setNumberFormat("0");
scores.getRange("T2:T2001").dataValidation = {
  rule: { type: "list", values: ["Completed", "Incomplete", "Invalid", "Test"] },
};
scores.getRange("E2:E2001").conditionalFormats.add("cellIs", {
  operator: "lessThan",
  formula: 70,
  format: { fill: "#FDECEC", font: { color: "#B42318" } },
});
scores.getRange("E2:E2001").conditionalFormats.add("cellIs", {
  operator: "between",
  formula: [70, 84.999],
  format: { fill: "#FFF8E5", font: { color: "#785600" } },
});
scores.getRange("E2:E2001").conditionalFormats.add("cellIs", {
  operator: "greaterThanOrEqual",
  formula: 85,
  format: { fill: "#EAF6EE", font: { color: "#216E39" } },
});

const scoreWidths = {
  A: 150, B: 250, C: 165, D: 220, E: 90, F: 105, G: 90, H: 105,
  I: 90, J: 105, K: 100, L: 110, M: 340, N: 190, O: 210, P: 125,
  Q: 160, R: 100, S: 110, T: 130, U: 115, V: 125,
};
for (const [column, width] of Object.entries(scoreWidths)) {
  scores.getRange(`${column}:${column}`).format.columnWidthPx = width;
}
scores.getRange("M2:M2001").format.wrapText = true;

dashboard.getRange("A1:F1").merge();
dashboard.getRange("A1").values = [["PMP Training Academy — Assessment Score Dashboard"]];
dashboard.getRange("A1:F1").format = {
  fill: "#F5E6C4",
  font: { bold: true, color: "#1B2E5E", size: 18 },
  verticalAlignment: "center",
};
dashboard.getRange("A1:F1").format.rowHeightPx = 38;
dashboard.getRange("A2:F2").merge();
dashboard.getRange("A2").values = [[
  "Automatically summarizes validated assessment attempts recorded on the Scores tab."
]];
dashboard.getRange("A2:F2").format = {
  font: { color: "#667085", italic: true },
  wrapText: true,
};

dashboard.getRange("A4:B4").values = [["Metric", "Value"]];
dashboard.getRange("D4:E4").values = [["Data Quality Check", "Count"]];
dashboard.getRange("A4:B4").format = dashboard.getRange("D4:E4").format = {
  fill: "#E8EBF0",
  font: { bold: true, color: "#111E3E" },
  borders: { bottom: { style: "thin", color: "#AEB7C6" } },
};
dashboard.getRange("A5:A9").values = [
  ["Recorded Attempts"],
  ["Average Overall %"],
  ["Average People %"],
  ["Average Process %"],
  ["Average Business %"],
];
dashboard.getRange("B5:B9").formulas = [
  ["=COUNTA('Scores'!$B$2:$B$2001)"],
  ["=IFERROR(AVERAGE('Scores'!$E$2:$E$2001),\"\")"],
  ["=IFERROR(AVERAGE('Scores'!$G$2:$G$2001),\"\")"],
  ["=IFERROR(AVERAGE('Scores'!$I$2:$I$2001),\"\")"],
  ["=IFERROR(AVERAGE('Scores'!$K$2:$K$2001),\"\")"],
];
dashboard.getRange("B6:B9").setNumberFormat("0.0");
dashboard.getRange("D5:D7").values = [
  ["Attempts missing email"],
  ["Attempts missing checkpoint"],
  ["Incomplete attempts"],
];
dashboard.getRange("E5:E7").formulas = [
  ["=COUNTIFS('Scores'!$B$2:$B$2001,\"<>\",'Scores'!$D$2:$D$2001,\"\")"],
  ["=COUNTIFS('Scores'!$A$2:$A$2001,\"<>\",'Scores'!$B$2:$B$2001,\"\")"],
  ["=COUNTIF('Scores'!$T$2:$T$2001,\"Incomplete\")"],
];
dashboard.getRange("A5:B9").format.borders = {
  insideHorizontal: { style: "thin", color: "#E2E7F0" },
};
dashboard.getRange("D5:E7").format.borders = {
  insideHorizontal: { style: "thin", color: "#E2E7F0" },
};
dashboard.getRange("B5:B9").format = {
  fill: "#FDFCF8",
  font: { bold: true, color: "#1B2E5E" },
  horizontalAlignment: "right",
};
dashboard.getRange("E5:E7").format = {
  fill: "#FDFCF8",
  font: { bold: true, color: "#1B2E5E" },
  horizontalAlignment: "right",
};
dashboard.getRange("A11:F11").merge();
dashboard.getRange("A11").values = [[
  "Readiness scores use the current 2026 domain structure. Session Knowledge Checks should remain separate from formal readiness calculations unless an approved weighting rule is added."
]];
dashboard.getRange("A11:F11").format = {
  fill: "#FFF8E5",
  font: { color: "#785600" },
  wrapText: true,
  borders: { left: { style: "medium", color: "#C49A27" } },
};
dashboard.getRange("A11:F11").format.rowHeightPx = 52;
dashboard.getRange("A:A").format.columnWidthPx = 205;
dashboard.getRange("B:B").format.columnWidthPx = 120;
dashboard.getRange("C:C").format.columnWidthPx = 32;
dashboard.getRange("D:D").format.columnWidthPx = 225;
dashboard.getRange("E:E").format.columnWidthPx = 100;
dashboard.getRange("F:F").format.columnWidthPx = 30;

instructions.getRange("A1:D1").merge();
instructions.getRange("A1").values = [["PMP Assessment Score Record — Setup and Data Dictionary"]];
instructions.getRange("A1:D1").format = {
  fill: "#F5E6C4",
  font: { bold: true, color: "#1B2E5E", size: 17 },
};
instructions.getRange("A2:D2").merge();
instructions.getRange("A2").values = [[
  "The assessment collector appends one row per completed attempt. Keep learner results private and restrict Sheet access to authorized instructors."
]];
instructions.getRange("A2:D2").format = {
  font: { color: "#667085", italic: true },
  wrapText: true,
};
instructions.getRange("A4:D4").values = [["Column", "Required", "Purpose", "Example"]];
instructions.getRange("A4:D4").format = {
  fill: "#E8EBF0",
  font: { bold: true, color: "#111E3E" },
  borders: { bottom: { style: "thin", color: "#AEB7C6" } },
};
const dictionaryRows = [
  ["Timestamp", "Yes", "Date and time the assessment was submitted", "2026-07-26 19:30"],
  ["Checkpoint", "Yes", "Stable assessment name used for attempt history", "Full-Length Readiness Diagnostic 1"],
  ["Name", "Yes", "Learner display name", "Student Name"],
  ["Email", "Yes", "Course email used to match the learner dashboard", "student@example.com"],
  ["Overall %", "Yes", "Whole-number percentage from 0 to 100", "78"],
  ["Overall Score", "Yes", "Correct answers divided by questions served", "140/180"],
  ["People % / Score", "Yes", "People-domain percentage and raw score", "81 and 48/59"],
  ["Process % / Score", "Yes", "Process-domain percentage and raw score", "74 and 55/74"],
  ["Business % / Score", "Yes", "Business Environment percentage and raw score", "79 and 37/47"],
  ["Topic Details", "Yes", "JSON or text containing topic-level results", "[{\"topic\":\"Risk\",\"pct\":72}]"],
  ["Submission Reference", "Recommended", "Unique attempt reference for reconciliation", "PMP-20260726-ABC123"],
  ["Assessment Version", "Recommended", "Stable released assessment version", "pmp-2026-diagnostic-1-v6"],
  ["Completion Seconds", "Optional", "Total completion time in seconds", "12600"],
  ["Average Seconds/Question", "Optional", "Average pacing per served question", "70"],
  ["Flagged Count", "Optional", "Number of questions flagged for review", "12"],
  ["Answer Changes", "Optional", "Total number of answer changes", "8"],
  ["Completion Status", "Recommended", "Completed, Incomplete, Invalid, or Test", "Completed"],
];
instructions.getRange(`A5:D${4 + dictionaryRows.length}`).values = dictionaryRows;
instructions.getRange(`A5:D${4 + dictionaryRows.length}`).format.borders = {
  insideHorizontal: { style: "thin", color: "#E2E7F0" },
};
instructions.getRange("A23:D23").merge();
instructions.getRange("A23").values = [[
  "Integration note: update the Google Apps Script spreadsheet ID to this Sheet's new ID after import, then deploy the script as a web app that executes as the Sheet owner."
]];
instructions.getRange("A23:D23").format = {
  fill: "#FFF8E5",
  font: { color: "#785600", bold: true },
  wrapText: true,
};
instructions.getRange("A23:D23").format.rowHeightPx = 54;
instructions.getRange("A:A").format.columnWidthPx = 190;
instructions.getRange("B:B").format.columnWidthPx = 125;
instructions.getRange("C:C").format.columnWidthPx = 360;
instructions.getRange("D:D").format.columnWidthPx = 220;
instructions.getRange("C5:D21").format.wrapText = true;
instructions.freezePanes.freezeRows(4);

await fs.mkdir(outputDir, { recursive: true });
await fs.mkdir(previewDir, { recursive: true });

const checks = await workbook.inspect({
  kind: "table",
  range: "Dashboard!A1:F11",
  include: "values,formulas",
  tableMaxRows: 20,
  tableMaxCols: 8,
});
console.log(checks.ndjson);

const errors = await workbook.inspect({
  kind: "match",
  searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A",
  options: { useRegex: true, maxResults: 100 },
  summary: "final formula error scan",
});
console.log(errors.ndjson);

for (const [sheetName, range] of [
  ["Scores", "A1:T8"],
  ["Dashboard", "A1:F11"],
  ["Instructions", "A1:D23"],
]) {
  const preview = await workbook.render({ sheetName, range, scale: 1.25, format: "png" });
  const bytes = new Uint8Array(await preview.arrayBuffer());
  await fs.writeFile(`${previewDir}/${sheetName.toLowerCase()}.png`, bytes);
}

const output = await SpreadsheetFile.exportXlsx(workbook);
await output.save(outputPath);
console.log(JSON.stringify({ outputPath, previewDir }));
