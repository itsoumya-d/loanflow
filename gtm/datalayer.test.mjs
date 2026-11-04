import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';

import { LOANFLOW_EVENTS, createDataLayerEvent, pushDataLayerEvent } from './datalayer.mjs';

test('denied analytics consent produces no event', () => {
  assert.equal(
    createDataLayerEvent(LOANFLOW_EVENTS.STARTED, { step: 'details' }, { analytics_storage: 'denied' }),
    null,
  );
});

test('granted consent produces a schema-versioned event without PII', () => {
  const event = createDataLayerEvent(
    LOANFLOW_EVENTS.SUBMITTED,
    { step: 'complete', application_reference: 'LF-2026-ABCD1234' },
    { analytics_storage: 'granted' },
  );

  assert.equal(event.event, 'loan_application_submitted');
  assert.equal(event.loanflow.schema_version, 1);
  assert.equal(event.loanflow.application_reference, 'LF-2026-ABCD1234');
  assert.equal(event.loanflow.email, undefined);
});

test('unknown events are rejected', () => {
  assert.throws(() => createDataLayerEvent('purchase', {}, { analytics_storage: 'granted' }), /Unknown Loanflow event/);
});

test('invalid parameter keys and values are rejected', () => {
  assert.throws(
    () => createDataLayerEvent(LOANFLOW_EVENTS.STARTED, { 'Bad-Key': 'x' }, { analytics_storage: 'granted' }),
    /Invalid dataLayer key/,
  );
  assert.throws(
    () => createDataLayerEvent(LOANFLOW_EVENTS.STARTED, { nested: { nope: true } }, { analytics_storage: 'granted' }),
    /Invalid dataLayer value/,
  );
});

test('pushDataLayerEvent only pushes consented events', () => {
  const dataLayer = [];

  pushDataLayerEvent(dataLayer, LOANFLOW_EVENTS.VIEWED, { step: 'form' }, { analytics_storage: 'denied' });
  assert.equal(dataLayer.length, 0);

  pushDataLayerEvent(dataLayer, LOANFLOW_EVENTS.VIEWED, { step: 'form' }, { analytics_storage: 'granted' });
  assert.equal(dataLayer.length, 1);
  assert.equal(dataLayer[0].event, 'loan_application_viewed');
});

test('the GTM custom template contains the required sections and valid JSON', () => {
  const template = readFileSync(new URL('./templates/loanflow_event.tpl', import.meta.url), 'utf8');

  for (const section of ['___INFO___', '___TEMPLATE_PARAMETERS___', '___SANDBOXED_JS_FOR_WEB_TEMPLATE___', '___WEB_PERMISSIONS___']) {
    assert.ok(template.includes(section), `missing ${section}`);
  }

  const info = template.split('___INFO___')[1].split('___TEMPLATE_PARAMETERS___')[0].trim();
  const parameters = template.split('___TEMPLATE_PARAMETERS___')[1].split('___SANDBOXED_JS_FOR_WEB_TEMPLATE___')[0].trim();
  const sandbox = template.split('___SANDBOXED_JS_FOR_WEB_TEMPLATE___')[1].split('___WEB_PERMISSIONS___')[0].trim();

  assert.equal(JSON.parse(info).type, 'TAG');
  assert.ok(Array.isArray(JSON.parse(parameters)));
  assert.doesNotThrow(() => new Function(sandbox));
});
