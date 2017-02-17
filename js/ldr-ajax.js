var clientID;
var $client_name;
jQuery(document).ready(function($){
  $("body").on("click",".select-users a",function(){
    clientID = jQuery(this).data('id');
    update_view();
  });

  function update_view() {
    $.ajax({
      type: "get",
      data: {
        'action': 'view_report',
        clientID: clientID
      },
      dataType: "html",
      url: ajaxurl,
      beforeSend : function(){

        //Slide to Top
        $("html, body").animate({ scrollTop: 0 }, "slow");

        // Empty the container and append loading gif
        $('#ajax-container').empty();
        $('#ajax-container').append('<img src="'+ pluginDir +'/img/ajax-loader.gif" class="preload-gif" alt="preloader">');

      },
      success:function(data) {

        //Append data and remove the loading gif
        $('#ajax-container .preload-gif').fadeOut(500).remove();

        if($('#ajax-container').is(':empty')){
          $('#ajax-container').append(data);
        }

        // console.log(data);
      },
      error: function(errorThrown){
        console.log(errorThrown);
      }
    });
  };

});
