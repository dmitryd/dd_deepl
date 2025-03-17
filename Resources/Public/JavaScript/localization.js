/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
import DocumentService from"@typo3/core/document-service.js";import $ from"jquery";import{SeverityEnum}from"@typo3/backend/enum/severity.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import Icons from"@typo3/backend/icons.js";import Modal from"@typo3/backend/modal.js";import MultiStepWizard from"@typo3/backend/multi-step-wizard.js";import"@typo3/backend/element/icon-element.js";class Localization{constructor(){this.triggerButton=".t3js-localize",DocumentService.ready().then((()=>{this.initialize()}))}async initialize(){const e=await Icons.getIcon("actions-localize",Icons.sizes.large),t=await Icons.getIcon("actions-edit-copy",Icons.sizes.large);$(this.triggerButton).removeClass("disabled"),$(document).on("click",this.triggerButton,(async a=>{a.preventDefault();const o=$(a.currentTarget),l=[],n=[];if(0===o.data("allowTranslate")&&0===o.data("allowCopy")&&0===o.data("allowDeepl"))return void Modal.confirm(TYPO3.lang["window.localization.mixed_mode.title"],TYPO3.lang["window.localization.mixed_mode.message"],SeverityEnum.warning,[{text:TYPO3?.lang?.["button.ok"]||"OK",btnClass:"btn-warning",name:"ok",trigger:(e,t)=>t.hideModal()}]);const i=await(await this.loadAvailableLanguages(parseInt(o.data("pageId"),10),parseInt(o.data("languageId"),10))).resolve();if(o.data("allowTranslate")&&(l.push('<div class="row"><div class="col-sm-3"><input class="btn-check t3js-localization-option" type="radio" name="mode" id="mode_translate" value="localize"><label class="btn btn-default btn-block-vertical" for="mode_translate" data-action="localize">'+e+TYPO3.lang["localize.wizard.button.translate"]+'</label></div><div class="col-sm-9"><p class="text-body-secondary">'+TYPO3.lang["localize.educate.translate"]+"</p></div></div>"),n.push("localize")),o.data("allowDeepl")&&(l.push('<div class="row"><div class="col-sm-3 pb-3"><input class="btn-check t3js-localization-option" type="radio" name="mode" id="mode_deepl" value="deepl"><label class="btn btn-default btn-block-vertical" for="mode_deepl" data-action="deepl">'+e+"<br>"+TYPO3.lang["localize.wizard.button.deepl"]+'</label></div><div class="col-sm-9"><p class="text-body-secondary">'+TYPO3.lang["localize.educate.deepl"]+"</p></div></div>"),n.push("deepl")),o.data("allowCopy")&&(l.push('<div class="row"><div class="col-sm-3"><input class="btn-check t3js-localization-option" type="radio" name="mode" id="mode_copy" value="copyFromLanguage"><label class="btn btn-default btn-block-vertical" for="mode_copy" data-action="copy">'+t+TYPO3.lang["localize.wizard.button.copy"]+'</label></div><div class="col-sm-9"><p class="t3js-helptext t3js-helptext-copy text-body-secondary">'+TYPO3.lang["localize.educate.copy"]+"</p></div></div>"),n.push("copyFromLanguage")),1===n.length)MultiStepWizard.set("localizationMode",n[0]);else{const e=document.createElement("div");e.dataset.bsToggle="buttons",e.append(...l.map((e=>document.createRange().createContextualFragment(e)))),MultiStepWizard.addSlide("localize-choose-action",TYPO3.lang["localize.wizard.header_page"].replace("{0}",o.data("page")).replace("{1}",o.data("languageName")),e,SeverityEnum.notice,TYPO3.lang["localize.wizard.step.selectMode"],((e,t)=>{void 0!==t.localizationMode&&MultiStepWizard.unlockNextStep()}))}1===i.length?MultiStepWizard.set("sourceLanguage",i[0].uid):MultiStepWizard.addSlide("localize-choose-language",TYPO3.lang["localize.view.chooseLanguage"],"",SeverityEnum.notice,TYPO3.lang["localize.wizard.step.chooseLanguage"],(async(e,t)=>{void 0!==t.sourceLanguage&&MultiStepWizard.unlockNextStep(),e.html('<div class="text-center">'+await Icons.getIcon("spinner-circle",Icons.sizes.large)+"</div>"),MultiStepWizard.getComponent().on("change",".t3js-language-option",(e=>{MultiStepWizard.set("sourceLanguage",$(e.currentTarget).val()),MultiStepWizard.unlockNextStep()}));const a=$("<div />",{class:"row"});for(const e of i){const t="language"+e.uid,o=$("<input />",{type:"radio",name:"language",id:t,value:e.uid,class:"btn-check t3js-language-option"}),l=$("<label />",{class:"btn btn-default btn-block",for:t}).text(" "+e.title).prepend(e.flagIcon);a.append($("<div />",{class:"col-sm-4"}).append(o).append(l))}e.empty().append(a)})),MultiStepWizard.addSlide("localize-summary",TYPO3.lang["localize.view.summary"],"",SeverityEnum.notice,TYPO3.lang["localize.wizard.step.selectRecords"],(async(e,t)=>{e.empty().html('<div class="text-center">'+await Icons.getIcon("spinner-circle",Icons.sizes.large)+"</div>");const a=await(await this.getSummary(parseInt(o.data("pageId"),10),parseInt(o.data("languageId"),10),t.sourceLanguage)).resolve();e.empty(),MultiStepWizard.set("records",[]);const l=a.columns.columns;a.columns.columnList.forEach((o=>{if(void 0===a.records[o])return;const n=l[o],i=document.createElement("div");i.classList.add("row","gy-2"),a.records[o].forEach((e=>{const a=" ("+e.uid+") "+e.title;t.records.push(e.uid);const o=document.createElement("div");o.classList.add("col-sm-6");const l=document.createElement("div");l.classList.add("input-group");const n=document.createElement("span");n.classList.add("input-group-text");const c=document.createElement("span");c.classList.add("form-check","form-check-type-toggle");const d=document.createElement("input");d.type="checkbox",d.id="record-uid-"+e.uid,d.classList.add("form-check-input","t3js-localization-toggle-record"),d.checked=!0,d.dataset.uid=e.uid.toString(),d.ariaLabel=a;const s=document.createElement("label");s.classList.add("form-control"),s.htmlFor="record-uid-"+e.uid,s.innerHTML=e.icon,s.appendChild(document.createTextNode(a)),c.appendChild(d),n.appendChild(c),l.appendChild(n),l.appendChild(s),o.appendChild(l),i.appendChild(o)}));const c=document.createElement("fieldset");c.classList.add("localization-fieldset");const d=document.createElement("div");d.classList.add("form-check","form-check-type-toggle");const s=document.createElement("input");s.classList.add("form-check-input","t3js-localization-toggle-column"),s.id="records-column-"+o,s.type="checkbox",s.checked=!0;const r=document.createElement("label");r.classList.add("form-check-label"),r.htmlFor="records-column-"+o,r.textContent=n,d.appendChild(s),d.appendChild(r),c.appendChild(d),c.appendChild(i),e.append(c)})),MultiStepWizard.unlockNextStep(),MultiStepWizard.getComponent().on("change",".t3js-localization-toggle-record",(e=>{const a=$(e.currentTarget),o=a.data("uid"),l=a.closest("fieldset"),n=l.find(".t3js-localization-toggle-column");if(a.is(":checked"))t.records.push(o);else{const e=t.records.indexOf(o);e>-1&&t.records.splice(e,1)}const i=l.find(".t3js-localization-toggle-record"),c=l.find(".t3js-localization-toggle-record:checked");n.prop("checked",c.length>0),n.prop("__indeterminate",c.length>0&&c.length<i.length),t.records.length>0?MultiStepWizard.unlockNextStep():MultiStepWizard.lockNextStep()})).on("change",".t3js-localization-toggle-column",(e=>{const t=$(e.currentTarget),a=t.closest("fieldset").find(".t3js-localization-toggle-record");a.prop("checked",t.is(":checked")),a.trigger("change")}))})),MultiStepWizard.addFinalProcessingSlide((async(e,t)=>{await this.localizeRecords(parseInt(o.data("pageId"),10),parseInt(o.data("languageId"),10),t.sourceLanguage,t.localizationMode,t.records),MultiStepWizard.dismiss(),document.location.reload()})).then((()=>{MultiStepWizard.show(),MultiStepWizard.getComponent().on("change",".t3js-localization-option",(e=>{MultiStepWizard.set("localizationMode",$(e.currentTarget).val()),MultiStepWizard.unlockNextStep()}))}))}))}loadAvailableLanguages(e,t){return new AjaxRequest(TYPO3.settings.ajaxUrls.page_languages).withQueryArguments({pageId:e,languageId:t}).get()}getSummary(e,t,a){return new AjaxRequest(TYPO3.settings.ajaxUrls.records_localize_summary).withQueryArguments({pageId:e,destLanguageId:t,languageId:a}).get()}localizeRecords(e,t,a,o,l){return new AjaxRequest(TYPO3.settings.ajaxUrls.records_localize).withQueryArguments({pageId:e,srcLanguageId:a,destLanguageId:t,action:"deepl"===o?"localize":o,uidList:l,deepl:"deepl"===o?1:0}).get()}}export default new Localization;