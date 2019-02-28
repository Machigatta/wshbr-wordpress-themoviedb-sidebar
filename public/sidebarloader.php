<?php
require (__DIR__."/CURLManager.php");


if(isset($_POST["id"])){

$tmdb->type = $_POST["type"];
$tmdb->id = $_POST["id"];
$tmdb->language = "de-DE";

$resObj = requestToTMDB($tmdb->type, $tmdb->id, "&append_to_response=credits&language=".$tmdb->language);
    ?>
    <div class="sidebar-block wtmds-container">
        <div class="intro-block">
			<?php
                $title = "";
                $type = "";
				if($tmdb->type == "tv"){
                    $title = $resObj->name;
                    $type = "serie";
				}else if($tmdb->type == "movie"){
                    $title = $resObj->title;
                    $type = "film";
                }
                
                //untertitel
                if(strpos($title, " – ")){
                    $titleAr = explode(" – ",$title);
                    $title = $titleAr[0] . "<p><small>".$titleAr[1]."</small></p>";
                }
                echo '<h3>'.$title.'</h3>';
			?>
            <img src="<?php echo('https://image.tmdb.org/t/p/w300_and_h450_bestv2/'.$resObj->poster_path); ?>" alt="">
        </div>
        <hr>
        <?php 
            if ($resObj->overview != "") {
            ?>
                <div class="content_bringer">
                    <h4>Handlung:</h4>
                    <p class="content_handlung">
                        <?php echo $resObj->overview;?>
                    </p>
                </div>
                <hr>
            <?php 
            }
        ?>
        <?php 
            if ( $tmdb->type == "tv" ) {
            ?>
                <div class="content_bringer">
                    <span class="sidebar-head">Staffeln:</span>
                    <?php
                    
                    foreach ( $resObj->seasons as $season ) {
                        if($season->season_number <= 0){
                            continue;
                        }
                        echo '<div class="seasonContainer"><span>Staffel '.$season->season_number.'</span>:
                        <table style="border-collapse: unset;"><tr>';
                        $seasonResObj = requestToTMDB($tmdb->type, $tmdb->id."/season/".$season->season_number, "&language=".$tmdb->language);
                        if(count($seasonResObj->episodes) <= 0){
                            echo "<td class='seasonEpisodeUnknown'>Noch keine Angaben</td>";
                        }
                        foreach ( $seasonResObj->episodes as $episodedata ) {
                            echo '<td data-tooltip="'.$episodedata->name.'" class="t-top t-xl ';
                            if(!$episodedata->air_date){
                                echo ' seasonEpisodeUnknown ';
                            }else if(time() > strtotime($episodedata->air_date) ){
                                echo ' seasonEpisodePublished ';
                            }else{
                                echo ' seasonEpisodePlanned ';
                            }
                            echo '">'.$episodedata->episode_number.'</td>';
                            if($episodedata->episode_number % 10 == 0){
                                echo '</tr><tr>';
                            }
                        }
                        echo '</tr></table></div>';
                    }

                    ?>
                </div>
                <hr>
            <?php 
            }
        ?>
        <?php 
            if (!count($resObj->genres) > 0) {
            
                echo '<div class="content_bringer">
                      <span class="sidebar-head">Genres:</span>
                            <p>';
                            foreach ($resObj->genres as $genre) {
                                echo "<span>" . $genre->name ."</span>";
                            }
                    echo '</p>
                    </div>
                    <hr>';
            }
        ?>
        
    </div>


    <?php  
}

function requestToTMDB($interface, $subinterface, $params = ""){
    $cm = new CURLManager();
    $url = ("https://api.themoviedb.org/3/".$interface."/".$subinterface."?api_key=1c0dd61d7bac5cb8312a0b5c4f2b72db".$params);
    $json = $cm->fetchJSON($url);
    $obj = json_decode($json);
    return $obj;
}

?>