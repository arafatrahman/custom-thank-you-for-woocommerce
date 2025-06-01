  jQuery(document).ready(function ($) {
   $(document).on("click", "#ctyw_notice button.notice-dismiss", function (e) {
      e.preventDefault();
      ctyw_dismiss_notice(0)
   })

   $(document).on("click", "a.ctyw-feedback", function (e) {
      e.preventDefault();
      ctyw_dismiss_notice(1)
      $("#ctyw_notice").slideUp();
      window, open($(this).attr("href"), "_blank");
   })

   function ctyw_dismiss_notice(is_final) {

    $.ajax({
      url: ajaxurl,
      data: { action: "ctyw_dismiss_notice", "ctyw_dismissed_final": is_final },
      type: "POST",
      dataType: "json",
      beforeSend: function (res) {
        console.log("Dismiss Final: ", is_final);
      },
      success: function (response) {
        console.log(response)
      }
    })
  }

  });
