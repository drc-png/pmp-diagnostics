const fs = require("fs");
const path = require("path");

const [, , htmlPath, bankPath, outPath] = process.argv;

if (!htmlPath || !bankPath || !outPath) {
  console.error(
    "Usage: node work/update-diagnostic-from-final-bank.js input.html bank.json output.html"
  );
  process.exit(1);
}

const html = fs.readFileSync(htmlPath, "utf8");
const bankData = JSON.parse(fs.readFileSync(bankPath, "utf8"));
const bankQuestions = Array.isArray(bankData) ? bankData : bankData.questions;

if (!Array.isArray(bankQuestions) || bankQuestions.length !== 180) {
  throw new Error(`Expected 180 bank questions, found ${bankQuestions?.length || 0}`);
}

const formatMap = {
  "independent-scenario": "Scenario",
  "case": "Case",
  "case-study": "Case",
  "graphic": "Graphic",
  "graphic-based": "Graphic",
};

const difficultyMap = {
  Expert: "Hard",
  hard: "Hard",
  "moderate-hard": "Moderate-Hard",
  moderate: "Moderate",
};

function titleCaseDifficulty(value) {
  if (!value) return "";
  return difficultyMap[value] || difficultyMap[String(value).toLowerCase()] || value;
}

function toQuestion(q) {
  const options = q.options || q.o;
  if (!Array.isArray(options) || options.length < 4) {
    throw new Error(`Question ${q.id || q.topic || "unknown"} has fewer than 4 options`);
  }
  const answer = Number.isInteger(q.answer) ? q.answer : q.x;
  if (!Number.isInteger(answer) || answer < 0 || answer >= options.length) {
    throw new Error(`Question ${q.id || q.topic || "unknown"} has invalid answer`);
  }
  const structure = q.structure || q.format;
  return {
    id: q.id,
    d: q.domain || q.d,
    t: q.topic || q.t,
    a: q.approach || q.a,
    q: q.stem || q.q,
    o: options,
    x: answer,
    r: q.rationale || q.r,
    task: q.task,
    taskLabel: q.taskLabel,
    rule: q.rule || "",
    format: formatMap[structure] || q.format || structure || "Scenario",
    responseType: q.responseType || "single-select",
    difficulty: titleCaseDifficulty(q.difficulty),
    pattern: q.pattern || "",
    mapping_status: q.mapping_status,
    sourceAnchors: q.sourceAnchors || [],
  };
}

const questions = bankQuestions.map(toQuestion);

function replaceQuestionsArray(source, replacementQuestions) {
  const match = /var\s+questions\s*=\s*\[/.exec(source);
  if (!match) throw new Error("Could not find `var questions = [` in HTML");

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
        const before = source.slice(0, match.index);
        const after = source.slice(i + 1);
        return `${before}var questions = ${JSON.stringify(replacementQuestions, null, 2)}${after}`;
      }
    }
  }
  throw new Error("Could not find end of questions array");
}

const output = replaceQuestionsArray(html, questions);
fs.mkdirSync(path.dirname(outPath), { recursive: true });
fs.writeFileSync(outPath, output);

const counts = questions.reduce(
  (acc, q) => {
    acc.domains[q.d] = (acc.domains[q.d] || 0) + 1;
    acc.approaches[q.a] = (acc.approaches[q.a] || 0) + 1;
    acc.formats[q.format] = (acc.formats[q.format] || 0) + 1;
    acc.difficulties[q.difficulty] = (acc.difficulties[q.difficulty] || 0) + 1;
    return acc;
  },
  { domains: {}, approaches: {}, formats: {}, difficulties: {} }
);

console.log(JSON.stringify({ output: outPath, questionCount: questions.length, ...counts }, null, 2));
