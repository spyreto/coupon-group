jQuery(document).ready(function ($) {
  $("#wc_coupons, #customers, #custom_coupons").select2();
  $(".date-picker").datepicker({
    dateFormat: "dd-mm-yy",
  });
});
