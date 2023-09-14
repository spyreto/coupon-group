jQuery(document).ready(function ($) {
  $("#wc_coupons, #customers").select2();
  $(".date-picker").datepicker({
    dateFormat: "dd-mm-yy",
  });
});
