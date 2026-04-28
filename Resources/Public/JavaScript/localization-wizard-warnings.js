import { html } from 'lit';
import '@typo3/backend/element/alert-element.js';
import { SeverityEnum } from '@typo3/backend/enum/severity.js';
import labels from '~labels/dd_deepl.messages';

const localizationHandler = 'deepl';
const maximumRecommendedRecordCount = 5;

document.addEventListener('wizard-step-summary', (event) => {
  const wizard = event.target;
  const selectedRecordUids = wizard?.getStoreData?.('selectedRecordUids') ?? [];

  if (wizard?.getStoreData?.('localizationHandler') !== localizationHandler) {
    return;
  }
  if (!Array.isArray(selectedRecordUids) || selectedRecordUids.length <= maximumRecommendedRecordCount) {
    return;
  }
  if (!Array.isArray(event.detail?.summaryData)) {
    return;
  }

  event.detail.summaryData.splice(Math.min(3, event.detail.summaryData.length), 0, {
    label: labels.get('localization.warning.largeSelection.title'),
    value: html`<typo3-backend-alert
      severity=${SeverityEnum.warning}
      message=${labels.get('localization.warning.largeSelection.message', [selectedRecordUids.length])}
      show-icon
    ></typo3-backend-alert>`,
  });
});
