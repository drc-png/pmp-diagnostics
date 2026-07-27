const PTA_SPREADSHEET_ID = '1Sb87uyUY2hiuCCw8_XpzppW1LA1ATnrsPVHL8cifs38';
const PTA_REQUIRED_HEADERS = [
  'Timestamp',
  'Checkpoint',
  'Name',
  'Email',
  'Overall %',
  'People %',
  'Process %',
  'Business %'
];

function doPost(e) {
  try {
    if (!e || !e.postData || !e.postData.contents) {
      return ptaJson_({ ok: false, error: 'missing_request_body' });
    }

    const data = JSON.parse(e.postData.contents);
    const expectedAccessKey = PropertiesService.getScriptProperties().getProperty('PTA_SCORE_ACCESS_KEY');
    if (expectedAccessKey && String(data.accessKey || '') !== expectedAccessKey) {
      return ptaJson_({ ok: false, error: 'unauthorized' });
    }

    const email = String(data.email || '').trim().toLowerCase();
    const checkpoint = String(data.checkpoint || '').trim();
    if (!email || !checkpoint) {
      return ptaJson_({ ok: false, error: 'email_and_checkpoint_required' });
    }

    const sheet = ptaFindScoreSheet_();
    const lastColumn = sheet.getLastColumn();
    const headers = sheet.getRange(1, 1, 1, lastColumn).getDisplayValues()[0];
    const headerMap = ptaHeaderMap_(headers);
    const submissionReference = String(data.submissionReference || '').trim();

    if (submissionReference && headerMap[ptaNormalizeHeader_('Submission Reference')] !== undefined && sheet.getLastRow() > 1) {
      const referenceColumn = headerMap[ptaNormalizeHeader_('Submission Reference')] + 1;
      const existingReferences = sheet.getRange(2, referenceColumn, sheet.getLastRow() - 1, 1).getDisplayValues();
      const duplicate = existingReferences.some(function(row) {
        return String(row[0] || '').trim() === submissionReference;
      });
      if (duplicate) return ptaJson_({ ok: true, duplicate: true, submissionReference: submissionReference });
    }

    const submittedAt = new Date(String(data.submittedAt || ''));
    const timestamp = isNaN(submittedAt.getTime()) ? new Date() : submittedAt;
    const topicDetails = data.topicDetails === undefined || data.topicDetails === null
      ? ''
      : (typeof data.topicDetails === 'string' ? data.topicDetails : JSON.stringify(data.topicDetails));

    const valuesByHeader = {
      'Timestamp': timestamp,
      'Checkpoint': checkpoint,
      'Name': String(data.studentName || data.name || '').trim(),
      'Email': email,
      'Overall %': ptaNumberOrBlank_(data.overallPct),
      'Overall Score': String(data.overallScore || '').trim(),
      'People %': ptaNumberOrBlank_(data.peoplePct),
      'People Score': String(data.peopleScore || '').trim(),
      'Process %': ptaNumberOrBlank_(data.processPct),
      'Process Score': String(data.processScore || '').trim(),
      'Business %': ptaNumberOrBlank_(data.businessPct),
      'Business Score': String(data.businessScore || '').trim(),
      'Topic Details': topicDetails,
      'Submission Reference': submissionReference,
      'Assessment Version': String(data.assessmentVersion || data.version || '').trim(),
      'Completion Seconds': ptaNumberOrBlank_(data.completedSeconds),
      'Average Seconds/Question': ptaNumberOrBlank_(data.averageSecondsPerQuestion),
      'Flagged Count': ptaNumberOrBlank_(data.flaggedCount),
      'Answer Changes': ptaNumberOrBlank_(
        data.answerChangeCount === undefined ? data.changedQuestionCount : data.answerChangeCount
      ),
      'Completion Status': String(data.completionStatus || '').trim() === 'Completed'
        ? 'Completed'
        : 'Incomplete',
      'Answered Count': ptaNumberOrBlank_(data.answeredCount),
      'Unanswered Count': ptaNumberOrBlank_(data.unansweredCount)
    };

    const row = headers.map(function(header) {
      const canonical = Object.keys(valuesByHeader).find(function(key) {
        return ptaNormalizeHeader_(key) === ptaNormalizeHeader_(header);
      });
      return canonical ? valuesByHeader[canonical] : '';
    });

    const lock = LockService.getScriptLock();
    lock.waitLock(10000);
    try {
      sheet.getRange(sheet.getLastRow() + 1, 1, 1, row.length).setValues([row]);
    } finally {
      lock.releaseLock();
    }

    return ptaJson_({ ok: true, submissionReference: submissionReference });
  } catch (error) {
    console.error(error);
    return ptaJson_({ ok: false, error: 'score_submission_failed' });
  }
}

function doGet(e) {
  try {
    const suppliedToken = String((e && e.parameter && e.parameter.token) || '');
    const expectedToken = PropertiesService.getScriptProperties().getProperty('PTA_DASHBOARD_TOKEN');
    if (!expectedToken || suppliedToken !== expectedToken) {
      return ptaJson_({ error: 'unauthorized' });
    }

    const email = String((e && e.parameter && e.parameter.email) || '').trim().toLowerCase();
    if (!email) return ptaJson_({ error: 'email_required' });

    const sheet = ptaFindScoreSheet_();
    const values = sheet.getDataRange().getDisplayValues();
    if (values.length < 2) return ptaJson_(ptaEmptyResponse_(email));

    const headerMap = ptaHeaderMap_(values[0]);
    const attempts = values.slice(1)
      .map(function(row, index) { return ptaAttemptFromRow_(row, index + 2, headerMap); })
      .filter(function(attempt) {
        return attempt.email === email &&
          attempt.checkpoint &&
          attempt.timestamp &&
          attempt.completion_status === 'Completed';
      })
      .sort(function(a, b) { return b.timestamp_ms - a.timestamp_ms; });

    if (!attempts.length) return ptaJson_(ptaEmptyResponse_(email));

    const latestByCheckpoint = {};
    attempts.forEach(function(attempt) {
      if (!latestByCheckpoint[attempt.checkpoint]) latestByCheckpoint[attempt.checkpoint] = attempt;
    });
    const latestAttempts = Object.keys(latestByCheckpoint)
      .map(function(key) { return latestByCheckpoint[key]; })
      .sort(function(a, b) { return b.timestamp_ms - a.timestamp_ms; });

    const latest = latestAttempts[0];
    const previous = latestAttempts.length > 1 ? latestAttempts[1] : null;

    return ptaJson_({
      student: {
        email: latest.email,
        name: latest.name
      },
      summary: {
        overall_readiness: latest.overall_percent,
        people: latest.people_percent,
        process: latest.process_percent,
        business_environment: latest.business_percent,
        completed_assessments: latestAttempts.length,
        latest_assessment: latest.checkpoint,
        latest_score: latest.overall_percent,
        trend_points: previous === null || latest.overall_percent === null || previous.overall_percent === null
          ? null
          : ptaRound_(latest.overall_percent - previous.overall_percent)
      },
      history: latestAttempts.map(function(attempt) {
        return {
          timestamp: attempt.timestamp,
          checkpoint: attempt.checkpoint,
          overall_percent: attempt.overall_percent,
          overall_score: attempt.overall_score,
          people_percent: attempt.people_percent,
          people_score: attempt.people_score,
          process_percent: attempt.process_percent,
          process_score: attempt.process_score,
          business_percent: attempt.business_percent,
          business_score: attempt.business_score,
          topic_details: attempt.topic_details
        };
      }),
      latest_valid_attempt_policy: 'latest row per checkpoint'
    });
  } catch (error) {
    console.error(error);
    return ptaJson_({ error: 'score_service_unavailable' });
  }
}

function ptaFindScoreSheet_() {
  const spreadsheet = SpreadsheetApp.openById(PTA_SPREADSHEET_ID);
  const sheets = spreadsheet.getSheets();
  for (let i = 0; i < sheets.length; i++) {
    const lastColumn = sheets[i].getLastColumn();
    if (!lastColumn) continue;
    const headers = sheets[i].getRange(1, 1, 1, lastColumn).getDisplayValues()[0];
    const normalized = headers.map(ptaNormalizeHeader_);
    const matches = PTA_REQUIRED_HEADERS.every(function(header) {
      return normalized.indexOf(ptaNormalizeHeader_(header)) !== -1;
    });
    if (matches) return sheets[i];
  }
  throw new Error('No worksheet contains the required score headers.');
}

function ptaHeaderMap_(headers) {
  const map = {};
  headers.forEach(function(header, index) {
    map[ptaNormalizeHeader_(header)] = index;
  });
  return map;
}

function ptaAttemptFromRow_(row, rowNumber, map) {
  const value = function(header) {
    const index = map[ptaNormalizeHeader_(header)];
    return index === undefined ? '' : row[index];
  };
  const timestamp = String(value('Timestamp')).trim();
  const parsedDate = new Date(timestamp);
  const topicText = String(value('Topic Details')).trim();
  let topicDetails = topicText;
  if (topicText) {
    try { topicDetails = JSON.parse(topicText); } catch (ignore) {}
  }

  return {
    attempt_id: [timestamp, value('Email'), value('Checkpoint'), rowNumber].join('|'),
    timestamp: timestamp,
    timestamp_ms: isNaN(parsedDate.getTime()) ? 0 : parsedDate.getTime(),
    checkpoint: String(value('Checkpoint')).trim(),
    name: String(value('Name')).trim(),
    email: String(value('Email')).trim().toLowerCase(),
    overall_percent: ptaPercent_(value('Overall %')),
    overall_score: String(value('Overall Score')).trim(),
    people_percent: ptaPercent_(value('People %')),
    people_score: String(value('People Score')).trim(),
    process_percent: ptaPercent_(value('Process %')),
    process_score: String(value('Process Score')).trim(),
    business_percent: ptaPercent_(value('Business %')),
    business_score: String(value('Business Score')).trim(),
    topic_details: topicDetails,
    completion_status: String(value('Completion Status')).trim()
  };
}

function ptaPercent_(value) {
  const text = String(value === null || value === undefined ? '' : value)
    .replace('%', '')
    .replace(',', '')
    .trim();
  if (!text) return null;
  const number = Number(text);
  if (!isFinite(number)) return null;
  return ptaRound_(number > 0 && number <= 1 ? number * 100 : number);
}

function ptaNumberOrBlank_(value) {
  if (value === null || value === undefined || value === '') return '';
  const number = Number(value);
  return isFinite(number) ? number : '';
}

function ptaRound_(number) {
  return Math.round(number * 10) / 10;
}

function ptaNormalizeHeader_(value) {
  return String(value || '').replace(/\s+/g, ' ').trim().toLowerCase();
}

function ptaEmptyResponse_(email) {
  return {
    student: { email: email, name: '' },
    summary: {
      overall_readiness: null,
      people: null,
      process: null,
      business_environment: null,
      completed_assessments: 0,
      latest_assessment: null,
      latest_score: null,
      trend_points: null
    },
    history: [],
    latest_valid_attempt_policy: 'latest row per checkpoint'
  };
}

function ptaJson_(payload) {
  return ContentService
    .createTextOutput(JSON.stringify(payload))
    .setMimeType(ContentService.MimeType.JSON);
}
