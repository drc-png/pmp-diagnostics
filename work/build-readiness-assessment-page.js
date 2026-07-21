const fs = require("fs");
const path = require("path");

const root = path.resolve(__dirname, "..");
const sourcePath =
  process.argv[2] ||
  "/Users/charleneparks/Downloads/PMP_2026_Readiness_Assessment_Questions_and_Answers.md";
const outPath = path.join(root, "docs", "readiness-assessment.html");
const markdown = fs.readFileSync(sourcePath, "utf8");

function parseQuestions(md) {
  const blocks = md.split(/\n(?=##\s+\d+\.\s+)/).filter((block) => /^##\s+\d+\.\s+/.test(block));
  return blocks.map((block) => {
    const heading = block.match(/^##\s+(\d+)\.\s+([\s\S]*?)(?=\n[A-D]\.\s)/);
    if (!heading) throw new Error(`Could not parse question heading:\n${block.slice(0, 200)}`);
    const number = Number(heading[1]);
    const stem = heading[2].replace(/\s+/g, " ").trim();
    const options = [];
    for (const letter of ["A", "B", "C", "D"]) {
      const re = new RegExp(`\\n${letter}\\.\\s+([\\s\\S]*?)(?=\\n[A-D]\\.\\s|\\n\\*\\*Correct answer:)`);
      const match = block.match(re);
      if (!match) throw new Error(`Missing option ${letter} for question ${number}`);
      options.push(match[1].replace(/\s+/g, " ").trim());
    }
    const correct = block.match(/\*\*Correct answer:\s+([A-D])\.\s+([\s\S]*?)\*\*/);
    if (!correct) throw new Error(`Missing correct answer for question ${number}`);
    return {
      id: `RA-${String(number).padStart(3, "0")}`,
      number,
      stem,
      options,
      answer: "ABCD".indexOf(correct[1]),
      answerText: correct[2].replace(/\s+/g, " ").trim(),
    };
  });
}

const questions = parseQuestions(markdown);
if (questions.length !== 50) throw new Error(`Expected 50 questions, found ${questions.length}`);

const html = `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PMP 2026 Readiness Assessment</title>
<style>
:root{--navy:#1B2E5E;--deep:#111E3E;--gold:#C49A27;--cream:#FAF7F0;--paper:#FDFCF8;--ink:#202532;--muted:#667085;--line:#E2E7F0;--green:#216E39;--red:#B42318}
*{box-sizing:border-box}
html,body{margin:0;padding:0;background:var(--cream);color:var(--ink);font-family:Calibri,Arial,Helvetica,sans-serif;line-height:1.55}
.topbar{position:sticky;top:0;z-index:5;background:var(--deep);color:#fff;padding:14px 18px;box-shadow:0 8px 24px rgba(17,30,62,.16)}
.topbar-inner{max-width:1020px;margin:0 auto;display:flex;gap:14px;align-items:center;flex-wrap:wrap}
.progress{height:8px;flex:1;min-width:180px;background:rgba(255,255,255,.18);border-radius:999px;overflow:hidden}.fill{height:100%;width:0;background:var(--gold);transition:.2s}.counter{font-weight:900;color:#F0E4B8}
.wrap{max-width:1020px;margin:0 auto;padding:20px 16px 70px}
.hero{background:linear-gradient(135deg,var(--deep),var(--navy));color:#fff;border-radius:10px;padding:28px;border-top:5px solid var(--gold);box-shadow:0 16px 42px rgba(17,30,62,.14)}
.eyebrow{font-size:.76rem;text-transform:uppercase;letter-spacing:.12em;color:#F0E4B8;font-weight:900}.hero h1{font-family:Georgia,serif;font-size:clamp(2rem,5vw,3.25rem);line-height:1;margin:.35rem 0 .65rem}.hero p{max-width:760px;color:#E7ECF8;margin:.45rem 0}.panel,.question,.results{background:#fff;border:1px solid var(--line);border-radius:8px;padding:18px;margin-top:14px;box-shadow:0 8px 30px rgba(17,30,62,.06)}
.start-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:14px}.stat{background:#FBFAF7;border:1px solid var(--line);border-radius:8px;padding:12px}.stat strong{display:block;color:var(--navy);font-size:1.4rem}.stat span{color:var(--muted);font-size:.82rem;text-transform:uppercase;font-weight:900;letter-spacing:.05em}
button{border:0;border-radius:7px;padding:.8rem 1rem;font-weight:900;cursor:pointer}.primary{background:var(--navy);color:#fff}.secondary{background:#fff;color:var(--navy);border:1px solid var(--line)}
.question{display:none}.question.active{display:block}.qmeta{font-size:.78rem;color:var(--muted);font-weight:900;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px}.stem{font-family:Georgia,serif;color:var(--deep);font-size:1.35rem;line-height:1.28;margin-bottom:16px}.options{display:grid;gap:10px}.option{display:flex;gap:12px;align-items:flex-start;width:100%;text-align:left;background:#fff;border:1px solid #D8DFEA;color:#23304A;border-radius:8px;padding:13px}.option.selected{border-color:var(--gold);background:#FFFBEB}.letter{font-weight:900;color:var(--gold);min-width:22px}.actions{display:flex;gap:10px;align-items:center;margin-top:16px}.actions .primary{margin-left:auto}.results{display:none}.results.show{display:block}.score{font-family:Georgia,serif;font-size:2.8rem;color:var(--navy);margin:.2rem 0}.review{display:grid;gap:12px;margin-top:14px}.review-item{border-left:5px solid var(--line);background:#FBFAF7;border-radius:8px;padding:12px}.review-item.correct{border-left-color:var(--green)}.review-item.incorrect{border-left-color:var(--red)}.review-item strong{color:var(--navy)}.correct-text{color:var(--green);font-weight:900}.incorrect-text{color:var(--red);font-weight:900}
@media(max-width:760px){.start-grid{grid-template-columns:1fr}.hero{padding:22px}.stem{font-size:1.15rem}.actions{flex-wrap:wrap}.actions .primary{margin-left:0}.topbar-inner{align-items:flex-start}}
</style>
</head>
<body>
<div class="topbar">
  <div class="topbar-inner">
    <div class="counter" id="counter">0 / ${questions.length}</div>
    <div class="progress" aria-hidden="true"><div class="fill" id="fill"></div></div>
  </div>
</div>
<main class="wrap">
  <section class="hero" id="intro">
    <div class="eyebrow">PMP Training Academy</div>
    <h1>PMP 2026 Readiness Assessment</h1>
    <p>Use this 50-question assessment as a focused check of PMP-style decision judgment. Answers stay hidden until you submit so students practice committing under realistic uncertainty.</p>
    <p>This is original PMP-aligned practice content, not official PMI exam content.</p>
    <div class="start-grid">
      <div class="stat"><strong>50</strong><span>Questions</span></div>
      <div class="stat"><strong>75 sec</strong><span>Suggested Pace</span></div>
      <div class="stat"><strong>2026</strong><span>ECO Aligned</span></div>
    </div>
    <div class="actions"><button class="primary" id="startBtn">Begin Assessment</button></div>
  </section>
  <section id="quiz"></section>
  <section class="results" id="results"></section>
</main>
<script>
const questions = ${JSON.stringify(questions)};
let current = 0;
const answers = new Array(questions.length).fill(null);
const quiz = document.getElementById('quiz');
const results = document.getElementById('results');
const letters = ['A','B','C','D'];
function updateTop(){
  const answered = answers.filter(v => v !== null).length;
  document.getElementById('counter').textContent = answered + ' / ' + questions.length;
  document.getElementById('fill').style.width = (answered / questions.length * 100) + '%';
}
function renderQuestion(index){
  current = index;
  const q = questions[index];
  quiz.innerHTML = '<article class="question active"><div class="qmeta">Question ' + (index + 1) + ' of ' + questions.length + '</div><div class="stem">' + escapeHtml(q.stem) + '</div><div class="options">' + q.options.map((opt,i)=>'<button class="option ' + (answers[index] === i ? 'selected' : '') + '" data-choice="' + i + '"><span class="letter">' + letters[i] + '</span><span>' + escapeHtml(opt) + '</span></button>').join('') + '</div><div class="actions"><button class="secondary" id="prevBtn"' + (index === 0 ? ' disabled' : '') + '>Previous</button><button class="secondary" id="nextBtn">' + (index === questions.length - 1 ? 'Review' : 'Next') + '</button><button class="primary" id="submitBtn">Submit</button></div></article>';
  quiz.querySelectorAll('.option').forEach(btn => btn.addEventListener('click', () => { answers[index] = Number(btn.dataset.choice); renderQuestion(index); updateTop(); }));
  document.getElementById('prevBtn').addEventListener('click', () => renderQuestion(Math.max(0, index - 1)));
  document.getElementById('nextBtn').addEventListener('click', () => renderQuestion(Math.min(questions.length - 1, index + 1)));
  document.getElementById('submitBtn').addEventListener('click', submit);
}
function submit(){
  const correct = answers.reduce((sum, ans, i) => sum + (ans === questions[i].answer ? 1 : 0), 0);
  const pct = Math.round(correct / questions.length * 100);
  quiz.style.display = 'none';
  results.classList.add('show');
  results.innerHTML = '<h2>Assessment Results</h2><div class="score">' + pct + '%</div><p><strong>' + correct + ' of ' + questions.length + '</strong> correct. Review each item below and note where the best answer depended on authority, timing, governance, value, or stakeholder decision need.</p><div class="review">' + questions.map((q,i)=>reviewItem(q,i)).join('') + '</div>';
  window.scrollTo({top:0, behavior:'smooth'});
  updateTop();
}
function reviewItem(q,i){
  const ans = answers[i];
  const ok = ans === q.answer;
  return '<div class="review-item ' + (ok ? 'correct' : 'incorrect') + '"><strong>' + (i + 1) + '. ' + escapeHtml(q.stem) + '</strong><p>Your answer: ' + (ans === null ? '<span class="incorrect-text">Not answered</span>' : '<span class="' + (ok ? 'correct-text' : 'incorrect-text') + '">' + letters[ans] + '. ' + escapeHtml(q.options[ans]) + '</span>') + '</p>' + (!ok ? '<p>Correct answer: <span class="correct-text">' + letters[q.answer] + '. ' + escapeHtml(q.options[q.answer]) + '</span></p>' : '') + '</div>';
}
function escapeHtml(value){ return String(value).replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch])); }
document.getElementById('startBtn').addEventListener('click', () => { document.getElementById('intro').style.display='none'; quiz.style.display='block'; renderQuestion(0); updateTop(); });
updateTop();
</script>
</body>
</html>
`;

fs.writeFileSync(outPath, html);
console.log(`Wrote ${outPath} with ${questions.length} questions.`);
