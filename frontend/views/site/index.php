<?php

/* @var $this yii\web\View */
use yii\helpers\Url;

$this->title = 'The Havoc';

$grid_width = Yii::$app->params['grid_width'];
$grid_height = Yii::$app->params['grid_height'];
$row = Yii::$app->params['row'];
$column = Yii::$app->params['column'];
$map_width = Yii::$app->params['map_width'];
$map_height = Yii::$app->params['map_height'];
$scene_width = Yii::$app->params['scene_width'];
$scene_height = Yii::$app->params['scene_height'];
$offset_x = Yii::$app->params['offset_x'];
$offset_y = Yii::$app->params['offset_y'];
$mark_full = Yii::$app->params['mark_full'];
$mark_empty = Yii::$app->params['mark_empty'];
$bomb_timer = Yii::$app->params['bomb_timer'];
$bomb_delay = Yii::$app->params['bomb_delay'];
$bomb = Yii::$app->params['bomb'];
$resource = Yii::$app->params['resource'];
?>
<!doctype html>
<head>
    <meta charset="utf-8">
    <title>Hello World</title>
    <link rel="stylesheet" href="http://www.w3schools.com/lib/w3.css">
    <script src="https://code.createjs.com/easeljs-0.8.2.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
    // canvas
    var main;
    var text;
    var background;
    var my_towers;
    var other_towers;
    var mech_track;
    var walls;
    var mask;
    var start;
    var end;
    var bomb;

    // drawing components
    var rect; // shape component
    var core; // image component
    var score; // text component
    var resource; // text component
    var bomb; // text component
    var bomb_timer; // text component
    var bomb_delay; // text component
    var enter;
    var wait;

    // map components
    var grids;
    var my_grids;
    var other_grids;
    var wall_grids;
    var track;
    var other_bombs

    // preloaded images
    var my_tower_image;
    var other_tower_image;
    var wall_image;

    // ready controller
    var ready_my_towers;
    var ready_other_towers;
    var ready_wall;

    // interval
    var interval;
    var text_anim;
    var check_status;

    function init() {

        $('#start').hide();
        $('#end').hide();
        $("#restart-button").hide();
        $("#enter-button").hide();
        $('.loader').hide();
        $('.loader-mask').hide();

        clearInterval(check_status);
        clearInterval(text_anim);
        clearInterval(interval);

        drawLoading();

        // chekc if player is in game
        $.ajax({
            // method: "POST",
            url: "<?= '../../api/web/index.php?r=round/check'; ?>",
            data: {
                key: getCookie('key'),
            },
            dataType : 'json',
            success: function(response) {
                if (response.success) {
                    setCookie("key", response.data.player.key, 7);
                    setCookie("resource", response.data.player.current_resource);
                    setCookie("bomb", response.data.player.current_bomb);
                    setCookie("game_start", 1);
                    setCookie("game_end", 0);
                    setCookie("battle_start", 0);
                } else {
                    setCookie("game_start", 0);
                    setCookie("game_end", 0);
                    setCookie("battle_start", 0);
                }
            }
        });

        if (getCookie("game_start") == 0) {
            startGame();
            return;
        }

        // state controller
        ready_my_towers = false;
        ready_other_towers = false;

        // load stage
        text = new createjs.Stage("text");
        main = new createjs.Stage("main");
        background = new createjs.Stage("background");
        bombs = new createjs.Stage("bombs");
        my_towers = new createjs.Stage("my_towers");
        other_towers = new createjs.Stage("other_towers");
        mech_track = new createjs.Stage("mech_track");
        walls = new createjs.Stage("walls");

        // preload background
        if (main.getChildByName("bg") == null) {
            var rect = new createjs.Shape();
            rect.graphics.beginFill("<?=Yii::$app->params['canvas_background_color']?>").drawRect(0, 0, <?=$scene_width?>, <?=$scene_height?>);
            rect.name = "bg";
            main.addChild(rect);
            main.update();
        }

        if (main.getChildByName("left_border") == null) {
            var rect = new createjs.Shape();
            rect.graphics.beginFill("<?=Yii::$app->params['main_color']?>").drawRect(<?=$offset_x?>, <?=$offset_y?>, 5, <?=$map_height?>);
            rect.name = "left_border";
            main.addChild(rect);
            main.update();
        }

        if (main.getChildByName("right_border") == null) {
            var rect = new createjs.Shape();
            rect.graphics.beginFill("<?=Yii::$app->params['main_color']?>").drawRect(<?=$map_width-5+$offset_x?>, <?=$offset_y?>, 5, <?=$map_height?>);
            rect.name = "right_border";
            main.addChild(rect);
            main.update();
        }

        if (main.getChildByName("gradient") == null) {
            var gradient = new createjs.Shape();
            gradient.graphics.beginLinearGradientFill(["rgba(255,255,255,0)","rgba(255,211,150,125)"], [0, 1], <?=$scene_width?>, <?=$offset_y?>, 0, <?=$offset_y?>).drawRect(<?=$offset_x+5?>, <?=$offset_y?>, <?=$map_width-5?>, <?=$map_height?>);
            gradient.name = "gradient";
            main.addChild(gradient);
            // gradient.graphics.beginLinearGradientFill(["rgba(255,255,255,0)","rgba(255,185,89,125)"], [0, 1], <?=5*$scene_width/6?>, <?=$offset_y?>, <?=$scene_width?>, <?=$offset_y?>).drawRect(<?=$offset_x?>, <?=$offset_y?>, <?=$map_width?>, <?=$map_height?>);
            // main.addChild(gradient);
            main.update();
        }

        // // preload background image
        // if (main.getChildByName("bg_image") == null) {
        //     var image = new Image();
        //     image.src = 'images/scenes/2dmap.jpg';
        //     image.onload = function() {
        //         var bitmap = new createjs.Bitmap(image);
        //         bitmap.name = "bg_image";
        //         main.addChild(bitmap);
        //         main.update();
        //     };
        // }

        // preload core image
        if (background.getChildByName("core") == null) {
            var image = new Image();
            image.src = 'images/towers/icon_coretower.png';
            image.onload = function() {
                var bitmap = new createjs.Bitmap(image);
                var x = (<?=Yii::$app->params['grid_core_topleft']-1?>-1)%<?=$column?>;
                var y = Math.floor((<?=Yii::$app->params['grid_core_topleft']-1?>-1)/<?=$column?>);
                bitmap.x = (x+2)*<?=$grid_width?> - image.width/2 + <?=$offset_x?>;
                bitmap.y = (y+1)*<?=$grid_height?> - image.height/2 + <?=$offset_y?>;
                bitmap.name = "core";
                background.addChild(bitmap);
                background.update();
            };
        }

        drawText();

        // preload tower image
        my_tower_image = new Image();
        my_tower_image.src = 'images/towers/icon_redtower_new_glow.png';
        other_tower_image = new Image();
        other_tower_image.src = 'images/towers/icon_redtower_new.png';
        wall_image = new Image();
        wall_image.src = 'images/towers/org_wall_2.png';

        my_tower_image.onload = function() {
            ready_my_towers = true;
        }
        other_tower_image.onload = function() {
            ready_other_towers = true;
        }
        wall_image.onload = function() {
            ready_wall = true;
        }

        if (getCookie("game_end") == 1) {
            endGame();
        } else {
            interval = setInterval(function(){ updateMap(); }, <?=Yii::$app->params['refresh_rate']?>);
        }
    }
    function drawLoading() {
        mask = new createjs.Stage("mask");
        var rect = new createjs.Shape();
        rect.graphics.beginFill("<?=Yii::$app->params['background_color']?>").drawRect(0, 0, <?=$scene_width?>, <?=$scene_height?>);
        mask.addChild(rect);
        mask.update();

        $('#mask').show();
        $('.loader').show();
        setTimeout(function(){ $('#mask').hide(); $('.loader').hide();}, 3000);
    }
    function clickMap(id) {
        if (getCookie("game_start") == 1 && getCookie("battle_start") == 0 && getCookie("resource") > 0) {
            $('.loader').show();
            $('.loader-mask').show();
            $.ajax({
                // method: "POST",
                url: "<?= '../../api/web/index.php?r=map/mark'; ?>",
                data: {
                    id: id,
                    key: getCookie('key'),
                },
                dataType : 'json',
                success: function(response) {
                    $('.loader').hide();
                    $('.loader-mask').hide();
                    if (response.success) {
                        setCookie("key", response.data.player.key, 7);
                        setCookie("resource", response.data.player.current_resource);
                        markMyTower(id);
                        drawText();
                    }
                }
            });
        } else if (getCookie("game_start") == 1 && getCookie("battle_start") == 1 && getCookie("bomb") > 0 && getCookie("bomb_timer") == 0) {
            $.ajax({
                // method: "POST",
                url: "<?= '../../api/web/index.php?r=bomb/place'; ?>",
                data: {
                    id: id,
                    key: getCookie('key'),
                },
                dataType : 'json',
                success: function(response) {
                    $('.loader').hide();
                    $('.loader-mask').hide();
                    if (response.success) {
                        setCookie("key", response.data.player.key, 7);
                        if (getCookie("bomb") > 0) {
                            setCookie("bomb", response.data.player.current_bomb);
                            if (getCookie("bomb") > 0) {
                                setCookie("bomb_timer", <?=$bomb_timer?>);
                            } else {
                                setCookie("bomb_timer", '--');
                            }
                            setCookie("bomb_delay", <?=$bomb_delay?>);
                            markBomb(id);
                            drawText();
                        }
                    }
                }
            });
        }
    }
    function updateMap() {
        if (getCookie("battle_start") == 1 && getCookie("bomb") > 0 && getCookie("bomb_timer") <= <?=$bomb_timer?> && getCookie("bomb_timer") >= <?=Yii::$app->params['timer_step']?>) {
            setCookie("bomb_timer", Math.round((getCookie("bomb_timer")-<?=Yii::$app->params['timer_step']?>)*10)/10);
        } else if (getCookie("battle_start") == 1 && getCookie("bomb") > 0 && getCookie("bomb_timer") < <?=Yii::$app->params['timer_step']?>) {
            setCookie("bomb_timer", 0);
        }
        if (getCookie("bomb_delay") <= <?=$bomb_delay?> && getCookie("bomb_delay") >= <?=Yii::$app->params['timer_step']?>) {
            setCookie("bomb_delay", Math.round((getCookie("bomb_delay")-<?=Yii::$app->params['timer_step']?>)*10)/10);
        } else if (getCookie("bomb_delay") < <?=Yii::$app->params['timer_step']?>) {
            setCookie("bomb_delay", 0);
        }
        drawText();
        $.ajax({
            // method: "POST",
            url: "<?= '../../api/web/index.php?r=map/get-map-and-track'; ?>",
            data: {
                key: getCookie('key'),
            },
            dataType : 'json',
            success: function(response) {
                if (response.success) {
                    setCookie("key", response.data.player.key, 7);
                    setCookie("game_end", response.data.round.game_end);
                    setCookie("battle_start", response.data.round.battle_start);
                    setCookie("score", response.data.player.score);
                    setCookie("round_score", response.data.player.current_score);
                    setCookie("resource", response.data.player.current_resource);
                    setCookie("bomb", response.data.player.current_bomb);
                    setCookie("is_win", response.data.is_win);
                    grids = response.data.grids;
                    var new_my_grids = response.data.my_grids;
                    var new_other_grids = response.data.other_grids;
                    var new_wall_grids = response.data.wall_grids;
                    var new_track = response.data.track;
                    var new_other_bombs = response.data.other_bombs;
                    if (new_my_grids.equals(my_grids) === false) {
                        my_towers.removeAllChildren();
                        my_grids = new_my_grids;
                        for (var i=0; i<my_grids.length; i++) {
                            markMyTower(my_grids[i].id);
                        }
                        my_towers.update();
                    }
                    if (new_other_grids.equals(other_grids) === false) {
                        other_towers.removeAllChildren();
                        other_grids = new_other_grids;
                        for (var i=0; i<other_grids.length; i++) {
                            markOtherTower(other_grids[i].id);
                        }
                        other_towers.update();
                    }
                    if (new_wall_grids.equals(wall_grids) === false) {
                        walls.removeAllChildren();
                        wall_grids = new_wall_grids;
                        for (var i=0; i<wall_grids.length; i++) {
                            markWall(wall_grids[i].id);
                        }
                        walls.update();
                    }
                    //if (new_other_bombs.equals(other_bombs) === false) {
                    if (true){
                        bombs.removeAllChildren();
                        other_bombs = new_other_bombs;
                        for (var i=0; i<other_bombs.length; i++) {
                            markOtherBomb(other_bombs[i].grid_id);
                        }
                        bombs.update();
                    }
                    // console.log(new_track);
                    if (true){
                        mech_track.removeAllChildren();
                        track = new_track.substr(1, new_track.length);
                        if (track.length) {
                            track = track.split(",");
                            var min = track.length - 5;
                            if (min < 0) {
                                min = 0;
                            }
                            for (var i=min; i<track.length; i++) {
                                if (i == track.length-1) {
                                    drawStep(track[i], 1);
                                } else {
                                    drawStep(track[i], 0);
                                }
                            }
                            mech_track.update();
                        }
                    }
                    // if (new_track != track) {
                    //     track = new_track;
                    //     track = track.split(",");
                    //     drawStep(track[track.length-1]);
                    //     drawCurrentStep(track[track.length-1]);
                    //     mech_track.update();
                    //     // mech_track.removeAllChildren();
                    //     // track = new_track;
                    //     // for (var i=0; i<track.length; i++) {
                    //     //     drawStep(track[i].grid_id);
                    //     // }
                    //     // mech_track.update();
                    // }
                    if (getCookie("game_end") == 1) {
                        endGame();
                    }
                    if (getCookie("battle_start") == 1 && getCookie("bomb_timer") == '--' && getCookie("bomb") > 0) {
                        setCookie("bomb_timer", <?=$bomb_timer?>);
                    }
                    if (getCookie("bomb") <= 0) {
                        setCookie("bomb_timer", '--');
                    }
                }
            }
        });
    }
    function enterGame() {
        clearInterval(interval);
        clearInterval(text_anim);
        clearInterval(check_status);

        setCookie("game_start", 0);

        $.ajax({
            // method: "POST",
            url: "<?= '../../api/web/index.php?r=round/start'; ?>",
            data: {
                key: getCookie('key'),
            },
            dataType : 'json',
            success: function(response) {
                if (response.success) {
                    setCookie("key", response.data.player.key, 7);
                    setCookie("game_start", 1);
                    setCookie("game_end", 0);
                    setCookie("battle_start", 0);
                    setCookie("resource", response.data.player.current_resource);
                    setCookie("bomb", response.data.player.current_bomb);
                    // setCookie("bomb_timer", response.data.bomb_timer);
                    // setCookie("bomb_delay", response.data.bomb_timer);
                    // force refresh
                    location.reload();
                }
            }
        });
    }
    function drawText() {
        // text.removeAllChildren();

        if (text.getChildByName("hint") == null && getCookie("battle_start") == 0) {
            var string = new createjs.Text('Place your defense tower!', '48px <?=Yii::$app->params['font']?>', '<?=Yii::$app->params['main_color']?>');
            string.textAlign = 'center';
            string.name = 'hint';
            string.x = <?=$map_width/2?> + <?=$offset_x?>;
            string.y = 220;
            text.addChild(string);
        } else if (text.getChildByName("hint") && getCookie("battle_start") == 0) {
            text.getChildByName("hint").text = 'Place your defense tower!';
        } else if (text.getChildByName("hint") == null && getCookie("battle_start") == 1) {
            var string = new createjs.Text('Drop bomb to stop Mech!', '48px <?=Yii::$app->params['font']?>', '<?=Yii::$app->params['main_color']?>');
            string.textAlign = 'center';
            string.name = 'hint';
            string.x = <?=$map_width/2?> + <?=$offset_x?>;
            string.y = 220;
            text.addChild(string);
        } else {
            text.getChildByName("hint").text = 'Drop bomb to stop Mech!';
        }

        // score
        if (text.getChildByName("score") == null) {
            var string = new createjs.Text('Score: ' + getCookie("score"), '84px <?=Yii::$app->params['font']?>', '<?=Yii::$app->params['main_text_color']?>');
            string.textAlign = 'center';
            string.name = 'score';
            string.x = <?=$map_width/2?> + <?=$offset_x?>;
            string.y = 60;
            text.addChild(string);
        } else {
            text.getChildByName("score").text = 'Score: ' + getCookie("score");
        }

        // round_score
        if (text.getChildByName("round_score") == null) {
            var string = new createjs.Text('this round: ' + getCookie("round_score"), '48px <?=Yii::$app->params['font']?>', '<?=Yii::$app->params['main_text_color']?>');
            string.textAlign = 'center';
            string.name = 'round_score';
            string.x = <?=$map_width/2?> + <?=$offset_x?>;
            string.y = 160;
            text.addChild(string);
        } else {
            text.getChildByName("round_score").text = 'this round: ' + getCookie("round_score");
        }

        // resource
        if (getCookie("battle_start") == 0 && text.getChildByName("resource") == null) {
            var string = new createjs.Text('Tower: ' + getCookie("resource"), '48px <?=Yii::$app->params['font']?>', '<?=Yii::$app->params['main_text_color']?>');
            string.textAlign = 'left';
            string.name = "resource";
            string.x = <?=$map_width?> - 260 + <?=$offset_x?>;
            string.y = 20;
            text.addChild(string);
        } else if (getCookie("battle_start") == 0) {
            text.getChildByName("resource").text = 'Tower: ' + getCookie("resource");
        } else if (getCookie("battle_start") == 1 && text.getChildByName("resource") == null) {
            //
        } else {
            text.removeChild(text.getChildByName("resource"));
        }

        // bomb
        if (getCookie("battle_start") == 1 && text.getChildByName("bomb") == null) {
            string = new createjs.Text('Bomb: ' + getCookie("bomb"), '48px <?=Yii::$app->params['font']?>', '<?=Yii::$app->params['main_text_color']?>');
            string.textAlign = 'left';
            string.name = "bomb";
            string.x = <?=$map_width?> - 260 + <?=$offset_x?>;
            string.y = 80;
            text.addChild(string);
        } else if (getCookie("battle_start") == 1) {
            text.getChildByName("bomb").text = 'Bomb: ' + getCookie("bomb");
        }

        // bomb_timer
        if (getCookie("battle_start") == 1 && text.getChildByName("bomb_timer") == null) {
            stringstring = new createjs.Text('CD: ' + getCookie("bomb_timer"), '48px <?=Yii::$app->params['font']?>', '<?=Yii::$app->params['main_text_color']?>');
            string.textAlign = 'left';
            string.name = "bomb_timer";
            string.x = <?=$map_width?> - 260 + <?=$offset_x?>;
            string.y = 140;
            text.addChild(string);
        } else if (getCookie("battle_start") == 1) {
            text.getChildByName("bomb_timer").text = 'CD: ' + getCookie("bomb_timer");
        }

        // bomb_delay
        if (getCookie("battle_start") == 1 && text.getChildByName("bomb_delay") == null) {
            string = new createjs.Text('Delay: ' + getCookie("bomb_delay"), '48px <?=Yii::$app->params['font']?>', '<?=Yii::$app->params['main_text_color']?>');
            string.textAlign = 'left';
            string.name = "bomb_delay";
            string.x = <?=$map_width?> - 260 + <?=$offset_x?>;
            string.y = 200;
            text.addChild(string);
        } else if (getCookie("battle_start") == 1) {
            text.getChildByName("bomb_delay").text = 'Delay: ' + getCookie("bomb_delay");
        }

        text.update();
    }
    function drawStep(id, is_last) {
        var x = (id-1)%<?=$column?>;
        var y = Math.floor((id-1)/<?=$column?>);
        var circle = new createjs.Shape();
        circle.x = x*<?=$grid_width?> + <?=$grid_width/2?> + <?=$offset_x?>;
        circle.y = y*<?=$grid_height?> + <?=$grid_height/2?> + <?=$offset_y?>;
        if (is_last == 0) {
            circle.graphics.beginFill("pink").drawCircle(0, 0, <?=$grid_width/4?>);
        } else {
            circle.graphics.beginFill("<?=Yii::$app->params['dark_pink_color']?>").drawCircle(0, 0, <?=$grid_width/4?>);
        }
        mech_track.addChild(circle);
    }
    function drawCurrentStep(id) {
        var x = (id-1)%<?=$column?>;
        var y = Math.floor((id-1)/<?=$column?>);
        if (mech_track.getChildByName("current") == null) {
            var circle = new createjs.Shape();
            circle.x = x*<?=$grid_width?> + <?=$grid_width/2?> + <?=$offset_x?>;
            circle.y = y*<?=$grid_height?> + <?=$grid_height/2?> + <?=$offset_y?>;
            circle.graphics.beginFill("<?=Yii::$app->params['dark_pink_color']?>").drawCircle(0, 0, <?=$grid_width/4?>);
            circle.name = "current";
            mech_track.addChild(circle);
        } else {
            var circle = mech_track.getChildByName("current");
            circle.x = x*<?=$grid_width?> + <?=$grid_width/2?> + <?=$offset_x?>;
            circle.y = y*<?=$grid_height?> + <?=$grid_height/2?> + <?=$offset_y?>;
            // mech_track.update();
        }
    }
    function markOtherBomb(id) {
        var x = (id-1)%<?=$column?>;
        var y = Math.floor((id-1)/<?=$column?>);
        var circle = new createjs.Shape();
        circle.x = x*<?=$grid_width?> + <?=$grid_width/2?> + <?=$offset_x?>;
        circle.y = y*<?=$grid_height?> + <?=$grid_height/2?> + <?=$offset_y?>;
        circle.graphics.beginFill("<?=Yii::$app->params['light_yellow_color']?>").drawCircle(0, 0, <?=3*$grid_width/4?>);
        background.addChild(circle);
        background.update();
        setTimeout(function () {
            background.removeChild(circle);
            background.update();
        }, <?=$bomb_delay * 1000 / 2?>);
    }
    function markBomb(id) {
        var x = (id-1)%<?=$column?>;
        var y = Math.floor((id-1)/<?=$column?>);
        var circle = new createjs.Shape();
        circle.x = x*<?=$grid_width?> + <?=$grid_width/2?> + <?=$offset_x?>;
        circle.y = y*<?=$grid_height?> + <?=$grid_height/2?> + <?=$offset_y?>;
        circle.graphics.beginFill("<?=Yii::$app->params['main_color']?>").drawCircle(0, 0, <?=3*$grid_width/4?>);
        background.addChild(circle);
        background.update();
        var bomb_anim = setInterval(function(){
            circle.graphics.clear().beginFill("<?=Yii::$app->params['main_color']?>").drawCircle(0, 0, <?=3*$grid_width/4?>).endFill();
            background.update();
            setTimeout(function() {
                circle.graphics.clear().beginFill("<?=Yii::$app->params['light_purple_color']?>").drawCircle(0, 0, <?=3*$grid_width/4?>).endFill();
                background.update();
            }, <?=Yii::$app->params['refresh_rate']/2?>);
        }, <?=Yii::$app->params['refresh_rate']?>);
        setTimeout(function () {
            background.removeChild(circle);
            background.update();
        }, <?=$bomb_delay * 1000?>);
    }
    function markMyTower(id) {
        var x = (id-1)%<?=$column?>;
        var y = Math.floor((id-1)/<?=$column?>);
        var tower = new createjs.Bitmap(my_tower_image);
        tower.x = x*<?=$grid_width?> + <?=$grid_width/2?> - my_tower_image.width/2 + <?=$offset_x?>;
        tower.y = y*<?=$grid_height?> + <?=$grid_height/2?> - my_tower_image.height/2 + <?=$offset_y?>;
        my_towers.addChild(tower);
        // my_towers.update();
    }
    function markOtherTower(id) {
        var x = (id-1)%<?=$column?>;
        var y = Math.floor((id-1)/<?=$column?>);
        var tower = new createjs.Bitmap(other_tower_image);
        tower.x = x*<?=$grid_width?> + <?=$grid_width/2?> - other_tower_image.width/2 + <?=$offset_x?>;
        tower.y = y*<?=$grid_height?> + <?=$grid_height/2?> - other_tower_image.height/2 + <?=$offset_y?>;
        other_towers.addChild(tower);
        // other_towers.update();
    }
    function markWall(id) {
        var x = (id-1)%<?=$column?>;
        var y = Math.floor((id-1)/<?=$column?>);
        var tower = new createjs.Bitmap(wall_image);
        tower.x = x*<?=$grid_width?> + <?=$grid_width/2?> - wall_image.width/2 + <?=$offset_x?>;
        tower.y = y*<?=$grid_height?> + <?=$grid_height/2?> - wall_image.height/2 + <?=$offset_y?>;
        walls.addChild(tower);
        // other_towers.update();
    }
    function startGame() {
        clearInterval(interval);
        clearInterval(text_anim);
        clearInterval(check_status);

        if (start == null) {
            start = new createjs.Stage("start");
        }

        // start.removeAllChildren();

        if (start.getChildByName("bg") == null) {
            var rect = new createjs.Shape();
            rect.graphics.beginFill("<?=Yii::$app->params['background_color']?>").drawRect(0, 0, <?=$scene_width?>, <?=$scene_height?>);
            rect.name = "bg";
            start.addChild(rect);
        }

        if (start.getChildByName("title") == null) {
            var text = new createjs.Text('The Havoc', '120px <?=Yii::$app->params['font']?>', '<?=Yii::$app->params['main_text_color']?>');
            text.textAlign = 'center';
            text.name = "title";
            text.x = <?=$scene_width/2?>;
            text.y = <?=$scene_height/2?> - 160;
            start.addChild(text);
        }

        if (getCookie('key') == null || getCookie('key') == undefined || getCookie('key') == 'undefined') {
            setCookie('key', '');
        }

        check_status = setInterval(function(){
            $.ajax({
                // method: "POST",
                url: "<?= '../../api/web/index.php?r=round/ready'; ?>",
                data: {
                    key: getCookie('key'),
                },
                dataType : 'json',
                success: function(response) {
                    if (response.success) {
                        setCookie("key", response.data.player.key, 7);
                        setCookie("game_start", 0);
                        setCookie("game_end", 0);
                        setCookie("battle_start", 0);
                        if (start.getChildByName("enter") == null) {
                            var text = new createjs.Text('Enter', '84px <?=Yii::$app->params['font']?>', '<?=Yii::$app->params['main_color']?>');
                            text.textAlign = 'center';
                            text.name = "enter";
                            text.x = <?=$scene_width/2?>;
                            text.y = <?=$scene_height/2?> + 120;
                            start.addChild(text);
                        } else {
                            start.getChildByName("enter").text = "Enter";
                        }
                        $('#enter-button').show();
                    } else {
                        setCookie("key", response.data.player.key, 7);
                        setCookie("game_start", 0);
                        setCookie("game_end", 0);
                        setCookie("battle_start", 0);
                        if (start.getChildByName("enter") == null) {
                            var text = new createjs.Text('Waiting for Mech ...', '64px <?=Yii::$app->params['font']?>', '<?=Yii::$app->params['main_color']?>');
                            text.textAlign = 'center';
                            text.name = "enter";
                            text.x = <?=$scene_width/2?>;
                            text.y = <?=$scene_height/2?> + 120;
                            start.addChild(text);
                        } else {
                            start.getChildByName("enter").text = "Waiting for Mech ...";
                        }
                    }
                }
            });
            start.update();
        }, <?=Yii::$app->params['refresh_rate']?>);
        start.update();

        $('#end').hide();
        $('#start').show();
    }
    function endGame() {
        clearInterval(interval);
        clearInterval(text_anim);
        clearInterval(check_status);

        if (end == null) {
            end = new createjs.Stage("end");
        }

        if (end.getChildByName("bg") == null) {
            var rect = new createjs.Shape();
            rect.graphics.beginFill("<?=Yii::$app->params['background_color']?>").drawRect(0, 0, <?=$scene_width?>, <?=$scene_height?>);
            rect.name = "bg";
            end.addChild(rect);
        }

        if (end.getChildByName("the_end") == null) {
            var text = new createjs.Text('The End', '84px <?=Yii::$app->params['font']?>', '<?=Yii::$app->params['main_text_color']?>');
            text.textAlign = 'center';
            text.name = "the_end";
            text.x = <?=$scene_width/2?>;
            text.y = <?=$scene_height/2?> - 360;
            end.addChild(text);
        }

        if (end.getChildByName("is_win") == null) {
            if (getCookie("is_win") == 1) {
                var text = new createjs.Text('We won!', '84px <?=Yii::$app->params['font']?>', '<?=Yii::$app->params['main_color']?>');
            } else {
                var text = new createjs.Text('We lost...', '84px <?=Yii::$app->params['font']?>', '<?=Yii::$app->params['main_color']?>');
            }
            text.textAlign = 'center';
            text.name = "is_win";
            text.x = <?=$scene_width/2?>;
            text.y = <?=$scene_height/2?> - 220;
            end.addChild(text);
        } else {
            if (getCookie("is_win") == 1) {
                end.getChildByName("is_win").text = 'We won!';
            } else {
                end.getChildByName("is_win").text = 'We lost...';
            }
        }

        if (end.getChildByName("score") == null) {
            var text = new createjs.Text('Score: ' + getCookie("score"), '128px <?=Yii::$app->params['font']?>', '<?=Yii::$app->params['main_text_color']?>');
            text.textAlign = 'center';
            text.name = "score";
            text.x = <?=$scene_width/2?>;
            text.y = <?=$scene_height/2?> - 140;
            end.addChild(text);
        } else {
            end.getChildByName("score").text = 'Score: ' + getCookie("score");
        }

        if (end.getChildByName("round_score") == null) {
            var text = new createjs.Text('this round: ' + getCookie("round_score"), '84px <?=Yii::$app->params['font']?>', '<?=Yii::$app->params['main_color']?>');
            text.textAlign = 'center';
            text.name = "round_score";
            text.x = <?=$scene_width/2?>;
            text.y = <?=$scene_height/2?>;
            end.addChild(text);
        } else {
            end.getChildByName("round_score").text = 'this round: ' + getCookie("round_score");
        }

        if (end.getChildByName("start") == null) {
            var text = new createjs.Text('Restart', '84px <?=Yii::$app->params['font']?>', '<?=Yii::$app->params['white_color']?>');
            text.textAlign = 'center';
            text.name = "start";
            text.x = <?=$scene_width/2?>;
            text.y = <?=$scene_height/2?> + 200;
            end.addChild(text);
            text_anim = setInterval(function(){
                text.color = "<?=Yii::$app->params['hint_text_color']?>";
                end.update();
                setTimeout(function() {
                    text.color = "<?=Yii::$app->params['white_color']?>";
                    end.update();
                }, <?=Yii::$app->params['refresh_rate']/2?>);
            }, <?=Yii::$app->params['refresh_rate']?>);
        } else {
            var text = end.getChildByName("start");
            text_anim = setInterval(function(){
                text.color = "<?=Yii::$app->params['hint_text_color']?>";
                end.update();
                setTimeout(function() {
                    text.color = "<?=Yii::$app->params['white_color']?>";
                    end.update();
                }, <?=Yii::$app->params['refresh_rate']/2?>);
            }, <?=Yii::$app->params['refresh_rate']?>);
        }

        end.update();
        $('#end').show();
        $('#restart-button').show();
    }

    (createjs.Graphics.Polygon = function(x, y, points) {
        this.x = x;
        this.y = y;
        this.points = points;
    }).prototype.exec = function(ctx) {
        // Start at the end to simplify loop
        var end = this.points[this.points.length - 1];
        ctx.moveTo(end.x, end.y);
        this.points.forEach(function(point) {
            ctx.lineTo(point.x, point.y);
        });
    };
    createjs.Graphics.prototype.drawPolygon = function(x, y, args) {
        var points = [];
        if (Array.isArray(args)) {
            args.forEach(function(point) {
                point = Array.isArray(point) ? {x:point[0], y:point[1]} : point;
                points.push(point);
            });
        } else {
            args = Array.prototype.slice.call(arguments).slice(2);
            var px = null;
            args.forEach(function(val) {
                if (px === null) {
                    px = val;
                } else {
                    points.push({x: px, y: val});
                    px = null;
                }
            });
        }
        return this.append(new createjs.Graphics.Polygon(x, y, points));
    };
    // Warn if overriding existing method
    if(Array.prototype.equals)
        console.warn("Overriding existing Array.prototype.equals. Possible causes: New API defines the method, there's a framework conflict or you've got double inclusions in your code.");
    // attach the .equals method to Array's prototype to call it on any array
    Array.prototype.equals = function (array) {
        // if the other array is a falsy value, return
        if (!array)
            return false;

        // compare lengths - can save a lot of time
        if (this.length != array.length)
            return false;

        for (var i = 0, l=this.length; i < l; i++) {
            // Check if we have nested arrays
            if (this[i] instanceof Array && array[i] instanceof Array) {
                // recurse into the nested arrays
                if (!this[i].equals(array[i]))
                    return false;
            }
            else if (this[i] != array[i]) {
                // Warning - two different object instances will never be equal: {x:20} != {x:20}
                return false;
            }
        }
        return true;
    }
    // Hide method from for-in loops
    Object.defineProperty(Array.prototype, "equals", {enumerable: false});
    function setCookie(cname, cvalue, exdays) {
        var d = new Date();
        d.setTime(d.getTime() + (exdays*24*60*60*1000));
        var expires = "expires="+ d.toUTCString();
        document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
    }
    function getCookie(cname) {
        var name = cname + "=";
        var ca = document.cookie.split(';');
        for(var i = 0; i <ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0)==' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                return c.substring(name.length,c.length);
            }
        }
        return "";
    }
    </script>
</head>
<body onload="init();">
    <?php
    // echo "Hello World!";
    ?>
    <canvas id="text" width="<?=$scene_width?>" height="<?=$scene_height?>" style="position:absolute;top:0;left:0;"></canvas>
    <canvas id="main" width="<?=$scene_width?>" height="<?=$scene_height?>" style="position:absolute;top:0;left:0;"></canvas>
    <canvas id="start" width="<?=$scene_width?>" height="<?=$scene_height?>" style="position:absolute;top:0;left:0;"></canvas>
    <canvas id="end" width="<?=$scene_width?>" height="<?=$scene_height?>" style="position:absolute;top:0;left:0;"></canvas>
    <canvas id="background" width="<?=$scene_width?>" height="<?=$scene_height?>" style="position:absolute;top:0;left:0;"></canvas>
    <canvas id="bombs" width="<?=$scene_width?>" height="<?=$scene_height?>" style="position:absolute;top:0;left:0;"></canvas>
    <canvas id="mech_track" width="<?=$scene_width?>" height="<?=$scene_height?>" style="position:absolute;top:0;left:0;"></canvas>
    <canvas id="other_towers" width="<?=$scene_width?>" height="<?=$scene_height?>" style="position:absolute;top:0;left:0;"></canvas>
    <canvas id="my_towers" width="<?=$scene_width?>" height="<?=$scene_height?>" style="position:absolute;top:0;left:0;"></canvas>
    <canvas id="walls" width="<?=$scene_width?>" height="<?=$scene_height?>" style="position:absolute;top:0;left:0;"></canvas>
    <canvas id="mask" width="<?=$scene_width?>" height="<?=$scene_height?>" style="position:absolute;top:0;left:0;"></canvas>
    <div class="loader"></div>
    <div class="loader-mask"></div>
    <div class="map">
    <?php
    for ($y=0; $y<$column; $y++)
    {
        for ($x=0; $x<$row; $x++)
        {
            $id = $x*$column+$y+1;
            echo '<div id="grid_'.$id.'" class="grid" onclick="clickMap('.$id.');" style="position:absolute;top:'.($x*$grid_width).'px;left:'.($y*$grid_height).'px;width:'.$grid_width.'px;height:'.$grid_height.'px;"></div>';
        }
    }
    ?>
    </div>
    <div class="bomb-button round-button disabled hidden" onClick=""><span>Bomb</span></div>
    <div id="restart-button" onclick="startGame();"></div>
    <div id="enter-button" onclick="enterGame();"></div>
</body>

<style>
.grid {
    background-color:Transparent;
    background-repeat:no-repeat;
    border:none;
    cursor:pointer;
    overflow:hidden;
    outline:none;
    border:1px silver dashed;
}
.marked {
    background-color: #fffccc;
}
.scene {
    position: absolute;;
    left: 0px;
    top: 0px;
    width: <?=$scene_width?>px;
    height: <?=$scene_height?>px;
}
.map {
    position: absolute;
    left: <?=$offset_x?>px;
    top: <?=$offset_y?>px;
    width: <?=$map_width?>px;
    height: <?=$map_height?>px;
}
.clear-button {
    position: absolute;
    left: <?=$offset_x?>px;
    top: <?=$offset_y + $map_height?>px;
    width: <?=$map_width?>px;
    background-color: #999999;
    text-align: center;
    font-size: 30px;
}
.round-button {
    display:block;
    width:50px;
    height:50px;
    line-height:50px;
    border: 5px solid #f5f5f5;
    border-radius: 50%;
    color:#f5f5f5;
    text-align:center;
    text-decoration:none;
    background: #464646;
    box-shadow: 0 0 3px gray;
    font-size:20px;
    font-weight:bold;
}
.round-button:hover {
    color: #515151;
}
#restart-button:hover {
    color: <?=Yii::$app->params['white_color']?>;
}
#restart-button {
    position: absolute;
    left: 0px;
    top: 0;
    color: #999999;
    width: <?=$scene_width?>px;
    height: <?=$scene_height?>px;
    z-index: 3000;
}
#enter-button:hover {
    color: <?=Yii::$app->params['white_color']?>;
}
#enter-button {
    position: absolute;
    left: 0px;
    top: 0;
    color: #999999;
    width: <?=$scene_width?>px;
    height: <?=$scene_height?>px;
    z-index: 3000;
}
#main {
    position: fixed;
    z-index: -5000;
}
#background {
    position: fixed;
    z-index: -4000;
}
#walls {
    position: fixed;
    z-index: -3000;
}
#other_towers {
    position: fixed;
    z-index: -2000;
}
#my_towers {
    position: fixed;
    z-index: -1000;
}
#mech_track {
    position: fixed;
    z-index: -500;
}
#text {
    position: fixed;
    z-index: -100;
}
#mask {
    position: fixed;
    z-index: 2000;
}
#start {
    position: fixed;
    z-index: 2000;
}
#end {
    position: fixed;
    z-index: 2000;
}
body{font-family:<?=Yii::$app->params['font']?>;}
.loader {
    border: 16px solid #f3f3f3; /* Light grey */
    border-top: 16px solid <?=Yii::$app->params['main_color']?>;
    border-radius: 50%;
    width: 120px;
    height: 120px;
    animation: spin 2s linear infinite;
    position: fixed;
    top: <?=$scene_height/2-60?>px;
    left: <?=$scene_width/2-60?>px;
    z-index: 3000;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.loader-mask {
    position: fixed;
    z-index: 2000;
    width: <?=$scene_width?>px;
    height: <?=$scene_height?>px;
    background-color: rgba(255, 255, 255, 0.5);
}
</style>
