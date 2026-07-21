const fs = require("fs");
const path = require("path");

const root = path.resolve(__dirname, "..");
const latestHtml = path.join(root, "outputs", "diagnostic-1-FINAL-180-with-meridian-case.html");
const latestJson = path.join(root, "outputs", "diagnostic-1-FINAL-180-with-meridian-case.questions.json");
const bankCsv = path.join(root, "outputs", "diagnostic-1-question-bank.csv");

const docs = path.join(root, "docs");
const bankDir = path.join(docs, "question-banks");

fs.mkdirSync(bankDir, { recursive: true });

fs.copyFileSync(latestHtml, path.join(docs, "index.html"));
fs.copyFileSync(latestHtml, path.join(docs, "diagnostic-1.html"));
fs.copyFileSync(latestJson, path.join(bankDir, "diagnostic-1.questions.json"));
fs.copyFileSync(bankCsv, path.join(bankDir, "diagnostic-1-question-bank.csv"));

console.log("Published current Diagnostic 1 files to docs/ for GitHub Pages.");
