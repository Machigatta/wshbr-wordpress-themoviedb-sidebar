jQuery(document).ready(function(){
    if(typeof TMDB_DATA !== 'undefined'){
        $.ajax({
            method: "POST",
            url: "/wp-content/plugins/wshbr-wordpress-themoviedb-sidebar/public/sidebarloader.php",
            data: { id: TMDB_DATA.id, type: TMDB_DATA.type }
        })
        .done(function( msg ) {
            $(".loader").replaceWith(msg);
        });
    }
});