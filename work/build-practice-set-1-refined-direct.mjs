import fs from 'node:fs';
import path from 'node:path';

const root = '/Users/charleneparks/Documents/ATP';
const sourcePath = '/Users/charleneparks/.codex/attachments/9b497cf8-f049-4ed3-bcfb-e310fb86de20/pasted-text.txt';
const sessionPaths = [
  path.join(root, 'artifacts/week-2/session-3-question-bank.masterstudy.json'),
  path.join(root, 'artifacts/week-2/session-4-question-bank.masterstudy.json'),
];
const outputPaths = [
  path.join(root, 'artifacts/practice-set-1-refined-direct.html'),
  path.join(root, 'docs/practice-set-1-refined-direct.html'),
];

const source = fs.readFileSync(sourcePath, 'utf8');
const refinedQuestions = sessionPaths.flatMap((file) => {
  const bank = JSON.parse(fs.readFileSync(file, 'utf8'));
  return bank.questions.map((item) => {
    const correctIndex = item.answers.findIndex((answer) => Number(answer.isTrue) === 1);
    if (correctIndex < 0) throw new Error(`Missing answer key: ${item.external_id}`);
    const metadata = item.pmp_metadata || {};
    return {
      id: item.external_id,
      d: metadata.domain || 'people',
      t: metadata.topic || 'People',
      task: metadata.task || '',
      taskLabel: metadata.task_label || metadata.task || '2026 ECO',
      a: metadata.approach || '',
      pattern: metadata.governing_rule || '',
      rule: metadata.governing_rule || '',
      format: metadata.format || 'Scenario',
      difficulty: metadata.difficulty || '',
      q: item.question,
      o: item.answers.map((answer) => answer.text),
      x: correctIndex,
      r: item.explanation,
      sourceAnchors: metadata.source_anchors || [],
    };
  });
});

if (refinedQuestions.length !== 12) {
  throw new Error(`Expected 12 refined questions, found ${refinedQuestions.length}`);
}

let html = source;
html = html.replace(
  '<title>PMP® Practice Set 1 — Team Formation &amp; Stakeholder Leadership</title>',
  '<title>PMP® Focused Practice — Teams, Stakeholders &amp; Conflict</title>',
);
html = html.replace(
  /<div class="start-screen" id="startScreen">[\s\S]*?<div id="quizContainer"><\/div>/,
  '<div class="start-screen" id="startScreen" hidden aria-hidden="true"></div>\n    <div id="quizContainer"></div>',
);
html = html.replace(
  '<div class="progress-bar-container" id="progressBarContainer" style="display:none;">',
  '<div class="progress-bar-container" id="progressBarContainer" style="display:flex;">',
);
html = html.replace('<div class="progress-text" id="progressText">0 / 15</div>', '<div class="progress-text" id="progressText">0 / 12</div>');
html = html.replace('<div class="score-fraction" id="totalFraction">0 / 15</div>', '<div class="score-fraction" id="totalFraction">0 / 12</div>');
html = html.replace(
  '<div class="score-sub">Practice Set 1 — Team Formation &amp; Stakeholder Leadership</div>',
  '<div class="score-sub">Focused Practice — Teams, Stakeholders &amp; Conflict</div>',
);
html = html.replace(
  '<p><strong>Practice set complete.</strong> Review every explanation and Exam Trap callout — especially questions you got right for the wrong reasons.</p>',
  '<p><strong>Focused practice complete.</strong> Review each explanation and revisit the decisions that were difficult to distinguish.</p>',
);
html = html.replace(
  '<p>Retake this set for a different random draw from the 150-question bank.</p>',
  '<p>Use the answer review to study this pilot set. Additional attempts will open after the expanded bank contains independently written questions.</p>',
);
html = html.replace(
  "var SERVE_COUNT=15,studentName='',studentEmail='',activeQuestions=[],answers=[],submitted=false,currentQ=0;",
  "var SERVE_COUNT=12,studentName='',studentEmail='',activeQuestions=[],answers=[],submitted=false,currentQ=0;",
);
html = html.replace(
  /var QUESTION_BANK=\[[\s\S]*?\];\n\nfunction shuffleAndSlice/,
  `var questions = ${JSON.stringify(refinedQuestions, null, 2)};\nvar QUESTION_BANK=questions.map(function(item){return{id:item.id,q:item.q,opts:item.o,ans:item.x,domain:item.d,topic:item.t+' | '+item.taskLabel,explanation:item.r,approach:item.a,difficulty:item.difficulty};});\n\nfunction shuffleAndSlice`,
);
html = html.replace(
  /document\.addEventListener\('DOMContentLoaded',function\(\)\{[\s\S]*?\n\}\);\n\nfunction validateForm/,
  `document.addEventListener('DOMContentLoaded',function(){\n  document.getElementById('reviewBtn').addEventListener('click',toggleReview);\n  startQuiz();\n});\n\nfunction validateForm`,
);
html = html.replace(
  /function startQuiz\(\)\{[\s\S]*?\}\n\nfunction retakeQuiz/,
  `function startQuiz(){activeQuestions=shuffleAndSlice(QUESTION_BANK,SERVE_COUNT);answers=new Array(activeQuestions.length).fill(-1);submitted=false;currentQ=0;var start=document.getElementById('startScreen');if(start)start.style.display='none';document.getElementById('progressBarContainer').style.display='flex';document.getElementById('progressText').textContent='0 / '+activeQuestions.length;renderQuestions();showQuestion(0);}\n\nfunction retakeQuiz`,
);
html = html.replace(
  /function retakeQuiz\(\)\{[\s\S]*?\}\n\nfunction renderQuestions/,
  `function retakeQuiz(){document.getElementById('quizContainer').innerHTML='';document.getElementById('reviewSection').innerHTML='';document.getElementById('breakdownContainer').innerHTML='';document.getElementById('focusList').innerHTML='';document.getElementById('domainResults').innerHTML='';document.getElementById('resultsScreen').classList.remove('active');submitted=false;currentQ=0;activeQuestions=shuffleAndSlice(QUESTION_BANK,SERVE_COUNT);answers=new Array(activeQuestions.length).fill(-1);document.getElementById('quizContainer').style.display='';document.getElementById('progressBarContainer').style.display='flex';document.getElementById('progressText').textContent='0 / '+activeQuestions.length;renderQuestions();showQuestion(0);window.scrollTo({top:0,behavior:'smooth'});}\n\nfunction renderQuestions`,
);
html = html.replace(
  /c\.addEventListener\('click',function\(e\)\{([\s\S]*?)\},\{once:true\}\);\}/,
  "c.onclick=function(e){$1};}",
);
html = html.replace(
  '.pmp-quiz .bank-note{font-size:.78rem;color:#2E7D32;background:#E8F5E9;padding:.4rem .8rem;border-radius:4px;display:inline-block;margin:.5rem 0;}',
  `.pmp-quiz .bank-note{font-size:.78rem;color:#2E7D32;background:#E8F5E9;padding:.4rem .8rem;border-radius:4px;display:inline-block;margin:.5rem 0;}
.pmp-quiz .score-save-status{max-width:760px;margin:1rem auto;padding:.85rem 1rem;border-left:4px solid var(--gold);background:#FAF7F0;color:var(--navy);font-weight:700;text-align:center;}
.pmp-quiz .score-save-status.success{border-left-color:#27AE60;background:#EAF6EE;color:#216E39;}
.pmp-quiz .score-save-status.error{border-left-color:#C0392B;background:#FDECEC;color:#B42318;}`,
);
html = html.replace(
  '<div style="text-align:center;"><button class="retake-btn"',
  '<div class="score-save-status" id="scoreSaveStatus" role="status" aria-live="polite">This score is shown on this page. Open the set through your signed-in course page to save it to your learner dashboard.</div>\n      <div style="text-align:center;"><button class="retake-btn"',
);
html = html.replace(
  /<div style="text-align:center;"><button class="retake-btn"[\s\S]*?<\/button><\/div>/,
  '',
);
html = html.replace(
  'renderDomainResults(domainScores);renderBreakdown(topicScores);renderReview();window.scrollTo',
  'renderDomainResults(domainScores);renderBreakdown(topicScores);renderReview();sendResultToCourse(pct,totalCorrect,domainScores,topicScores);window.scrollTo',
);
html = html.replace(
  '\nfunction renderDomainResults(domainScores)',
  `\nfunction sendResultToCourse(pct,totalCorrect,domainScores,topicScores){var answeredCount=answers.filter(function(answer){return answer!==-1;}).length;var domainPct=function(key){var score=domainScores[key];return score.total?Math.round(score.correct/score.total*100):0;};var domainRaw=function(key){var score=domainScores[key];return score.correct+'/'+score.total;};var details=Object.keys(topicScores).map(function(key){var item=topicScores[key];return{domain:item.domain,topic:item.topic,correct:item.correct,total:item.total,pct:Math.round(item.correct/item.total*100)};});var payload={checkpoint:'Practice Set 1 - Teams, Stakeholders and Conflict',overallPct:pct,overallScore:totalCorrect+'/'+activeQuestions.length,peoplePct:domainPct('people'),peopleScore:domainRaw('people'),processPct:domainPct('process'),processScore:domainRaw('process'),businessPct:domainPct('business'),businessScore:domainRaw('business'),answeredCount:answeredCount,unansweredCount:activeQuestions.length-answeredCount,topicDetails:details};if(window.parent!==window){window.parent.postMessage({type:'ptaPracticeResult',payload:payload},'https://pmptrainingacademy.com');}}

window.addEventListener('message',function(event){if(event.origin!=='https://pmptrainingacademy.com')return;var message=event.data||{};if(message.type!=='ptaPracticeSaveStatus')return;var status=document.getElementById('scoreSaveStatus');if(!status)return;if(message.ok){status.className='score-save-status success';status.textContent='Score saved to your learner dashboard. Reference: '+message.reference;}else{status.className='score-save-status error';status.textContent='Your score is visible, but it could not be saved. Save the PDF and contact support.';}});

function renderDomainResults(domainScores)`,
);

if (html.includes('Your Full Name') || html.includes('Your Email Address') || html.includes('Begin Practice Set')) {
  throw new Error('The intake screen was not fully removed.');
}
if (!html.includes('startQuiz();') || !html.includes('var SERVE_COUNT=12')) {
  throw new Error('Direct launch was not configured.');
}

outputPaths.forEach((outputPath) => fs.writeFileSync(outputPath, html));
console.log(outputPaths.join('\n'));
