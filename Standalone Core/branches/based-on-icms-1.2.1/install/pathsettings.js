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

  $("input#trustpath").click(function() {
    $("#trustpathimg").fadeOut("slow")
  });
  $("input#trustpath").blur(function() {
    $("#trustpathimg").fadeIn("slow")
  });
  $("input#trustpath").change(function() {
    $.ajax({
      url: 'page_pathsettings.php',
      data: "action=checktrustpath&path=" + $('input#trustpath').val(),
      dataType: "html",
      success: function(data) {
        $("#trustpathimg").html(data).fadeIn("slow");
      }
    });
  });

  $("#createtrustpath").click(function() {
    $.ajax({
      url: 'page_pathsettings.php',
      data: "action=createtrustpath&path=" + $('input#trustpath').val(),
      dataType: "html",
      success: function(data) {
        $("input#trustpath").change();
        $("#trustpathimg").html(data).fadeIn("slow");
        $("#trustperms").html(data).fadeIn("slow");
      }
    });
  });
});