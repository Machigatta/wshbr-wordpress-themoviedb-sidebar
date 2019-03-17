<?php
error_reporting(E_ALL);
require (__DIR__."/CURLManager.php");

class SBLoader
{
    private $tmdb;

    public function __construct() {
        if(isset($_POST["id"])){
            $this->tmdb->type = $_POST["type"];
            $this->tmdb->id = $_POST["id"];
            $this->tmdb->language = "de-DE";
        }
    }

    private function requestToTMDB($interface, $subinterface, $params = ""){
        $cm = new CURLManager();
        $url = ("https://api.themoviedb.org/3/".$interface."/".$subinterface."?api_key=1c0dd61d7bac5cb8312a0b5c4f2b72db".$params);
        $json = $cm->fetchJSON($url);
        $obj = json_decode($json);
        return $obj;
    }

    public function draw(){
        if($this->tmdb->type != null && $this->tmdb->id != null && $this->tmdb->language != null){
            $resObj = $this->requestToTMDB($this->tmdb->type, $this->tmdb->id, "&append_to_response=content_ratings,external_ids&language=".$this->tmdb->language);
            ?>
            <div class="sidebar-block wtmds-container">
                <div class="intro-block">
                    <?php
                        $title = "";
                        $type = "";
                        if($this->tmdb->type == "tv"){
                            $title = $resObj->name;
                            $type = "serie";
                        }else if($this->tmdb->type == "movie"){
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
                    if ( $this->tmdb->type == "tv" ) {
                    ?>
                        <div class="content_bringer">
                            <h4 class="sidebar-head">Staffeln:</h4>
                            <?php
                            
                            foreach ( $resObj->seasons as $season ) {
                                if($season->season_number <= 0){
                                    continue;
                                }
                                echo '<div class="seasonContainer"><span>Staffel '.$season->season_number.'</span>:
                                <table style="border-collapse: unset;"><tr>';
                                $seasonResObj = $this->requestToTMDB($this->tmdb->type, $this->tmdb->id."/season/".$season->season_number, "&language=".$this->tmdb->language);
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
                        if($resObj->first_air_date != ""){
                            $this->drawContentBasedOnObject("Original Sprache",$resObj);
                            $this->drawContentBasedOnObject("Altersfreigabe",$resObj);
                            $this->drawContentBasedOnObject("Laufzeit",$resObj);
                        }
                        $this->drawContentBasedOnObject("Genres",$resObj);
                        $this->drawContentBasedOnObject("Sender",$resObj);
                        $this->drawContentBasedOnObject("Links",$resObj);
                    }else if ($this->tmdb->type == "movie"){
                        if($resObj->status == "Released"){
                            $this->drawContentBasedOnObject("Veröffentlichung",$resObj);
                            $this->drawContentBasedOnObject("Budget",$resObj);
                            $this->drawContentBasedOnObject("Einspielergebnis",$resObj);
                            $this->drawContentBasedOnObject("Laufzeit",$resObj);
                        }
                        $this->drawContentBasedOnObject("Genres",$resObj);       
                        $this->drawContentBasedOnObject("Links",$resObj);                 
                    }
                ?>        
            </div>
        <?php  
        }
    }
    
    private function drawContentBasedOnObject($h, $resObj){
        ?>
        <div class="content_bringer">
            <h4 class="sidebar-head"><?php echo $h; ?>:</h4>
            <div>
            <?php
                switch ($h) {
                    case 'Genres':
                        foreach ($resObj->genres as $key => $value) {
                            echo '<span class="label label-default">'.$value->name.'</span> ';
                        }
                        break;
                    case 'Original Sprache':
                        echo '<span class="label label-default">'.$resObj->original_language.'</span> ';
                        break;
                    case 'Altersfreigabe':
                        foreach ($resObj->content_ratings->results as $key => $value) {
                            if($value->iso_3166_1 == "DE"){
                                $color = "default";
                                switch ($value->rating) {
                                    case 12:
                                        $color = "success";
                                        break;
                                    case 16:
                                        $color = "primary";
                                        break;
                                    case 18:
                                        $color = "danger";
                                        break;
                                    default:
                                        break;
                                }
                                echo '<span class="label label-'.$color.'">'.$value->rating.'</span> ';
                            }
                            
                        }
                        
                        break;
                    case 'Budget':
                        echo '<p><span class="label label-default">'.number_format($resObj->budget).' $</span></p>';
                        break;
                    case 'Einspielergebnis':
                        echo '<p><span class="label label-default">'.number_format($resObj->budget+$resObj->revenue).' $</span></p>';
                        break;
                    case 'Laufzeit':
                        if (isset($resObj->episode_run_time)) {
                            echo "<p>";
                            foreach ($resObj->episode_run_time as $value) {
                                echo '<span class="label label-default">'.$value.' min</span>&nbsp;';
                            }
                            echo "</p>";
                        }else{
                            echo '<p><span class="label label-default">'.$resObj->runtime.' min</span></p>';
                        }
                        
                        break;
                    case 'Veröffentlichung':
                        echo '<p><span class="label label-default">'.date("d.m.Y",strtotime($resObj->release_date)).'</span></p>';
                        break;
                    case 'Sender':
                        foreach ($resObj->networks as $obj) {
                            echo '<img src="https://image.tmdb.org/t/p/h30'.$obj->logo_path.'" alt="'.$obj->name.'" title='.$obj->name.'>';
                        }
                        break;
                    case 'Links':
                        echo "<p class='sidebar-links'>";
                        if ($resObj->external_ids->facebook_id != null) {
                            echo '<a href="https://www.facebook.com/'.$resObj->external_ids->facebook_id.'"><i class="fab fa-facebook fa-2x"></i></a>&nbsp;';
                        }
                        if ($resObj->external_ids->instagram_id != null) {
                            echo '<a href="https://instagram.com/'.$resObj->external_ids->instagram_id.'"><i class="fab fa-instagram fa-2x"></i></a>&nbsp;';
                        }
                        if ($resObj->external_ids->twitter_id != null) {
                            echo '<a href="https://twitter.com/'.$resObj->external_ids->twitter_id.'"><i class="fab fa-twitter fa-2x"></i></a>&nbsp;';
                        }
                        if ($resObj->external_ids->imdb_id != null) {
                            echo '<a href="https://www.imdb.com/'.$resObj->external_ids->imdb_id.'"><i class="fab fa-imdb fa-2x"></i></a>&nbsp;';
                        }
                        if ($resObj->homepage != null) {
                            echo '<a href="'.$resObj->homepage.'"><i class="fas fa-link fa-2x"></i></a>&nbsp;';
                        }
                        echo "</p>";
                        break;
                    default:
                        break;
                }
            ?>
            </div>
        </div>
        <?php
    } 
}

$SBLoader = new SBLoader();
$SBLoader->draw();
?>