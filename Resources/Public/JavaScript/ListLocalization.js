define('TYPO3/CMS/DdDeepl/ListLocalization', ['jquery'], function ($) {
  $('.recordlist[id^="t3-table-tx_"] .col-localizationb .btn-group').each(function() {
    let that = $(this);
    let newElement = that.clone().insertAfter(that);
    let link = newElement.find('a.t3js-action-localize');
    link.attr('href', link.attr('href').replace('%5Blocalize%5D=', '%5Bdeepl%5D='));
    let img = newElement.find('img');
    img.attr('src', '/typo3conf/ext/dd_deepl/Resources/Public/Images/deepl-seeklogo.com.svg');
  });
});
