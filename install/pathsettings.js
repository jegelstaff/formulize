$(function() {
  $("#rootperms").css("display", "none").slideDown("slow");
  $("input#rootpath").click(function() {
    $("#rootpathimg").fadeOut("slow")
  });
  $("input#rootpath").blur(function() {
    $("#rootpathimg").fadeIn("slow")
  });
  $("input#rootpath").change(function() {
    $.ajax({
      url: 'page_pathsettings.php',
      data: "action=checkrootpath&path=" + $('input#rootpath').val(),
      dataType: "html",
      success: function(data) {
        $("#rootpathimg").html(data).fadeIn("slow");
      }
    });
  });

  $("#permrefresh").click(function() {
	this.form.submit();
  });
});
