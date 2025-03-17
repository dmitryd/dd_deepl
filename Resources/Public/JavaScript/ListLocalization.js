import $ from "jquery";

$('.recordlist[id^="t3-table-t"] .col-localizationb .btn-group').each(function() {
  let that = $(this);
  let newElement = that.clone().insertAfter(that);
  let link = newElement.find('a.t3js-action-localize');
  link.attr('href', link.attr('href') + '&deepl=1');
  let img = newElement.find('img');
  $('<span class="deepl-icon-overlay"></span>').insertAfter(img.parent().parent());
  img.css('opacity', 0.65);
});
