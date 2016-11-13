<?php

/* @var $this yii\web\View */
use yii\helpers\Url;

$this->title = 'Ice and Fire';

$grid_width = Yii::$app->params['grid_width'];
$grid_height = Yii::$app->params['grid_height'];
$grid_x = Yii::$app->params['grid_x'];
$grid_y = Yii::$app->params['grid_y'];
$map_width = Yii::$app->params['map_width'];
$map_height = Yii::$app->params['map_height'];
$offset_x = Yii::$app->params['offset_x'];
$mark_building = Yii::$app->params['mark_building'];
$mark_empty = Yii::$app->params['mark_empty'];
?>
<!doctype html>
<head>
    <meta charset="utf-8">
    <title>Hello World</title>
    <link rel="stylesheet" href="http://www.w3schools.com/lib/w3.css">
    <script src="https://code.createjs.com/easeljs-0.8.2.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
    <script>
    var background;
    var my_towers;
    var other_towers;
    var mech_track;
    var rect;
    var grids;
    var my_grids;
    var other_grids;
    var core;
    var core_image;
    var my_tower_image;
    var other_tower_image;
    var battle_start;
    var battle_end;
    var key;
    var ready_my_towers;
    var ready_other_towers;

    function init() {
        battle_start = false;
        ready_my_towers = false;
        ready_other_towers = false;

        // load stage
        background = new createjs.Stage("background");
        my_towers = new createjs.Stage("my_towers");
        other_towers = new createjs.Stage("other_towers");
        mech_track = new createjs.Stage("mech_track");

        // preload background
        rect = new createjs.Shape();
        rect.graphics.beginFill("#EEEEEE").drawRect(0, 0, <?=$map_width?>, <?=$map_height?>);
        background.addChild(rect);
        background.update();

        // preload core image
        core_image = new Image();
        core_image.src = 'images/towers/core.png';
        core_image.onload = function() {
            core = new createjs.Bitmap(core_image);
            core.x = <?=$map_width/2?> - core_image.width/2;
            core.y = <?=$map_height/2?> - core_image.height/2;
            background.addChild(core);
            background.update();
        };

        // preload tower image
        my_tower_image = new Image();
        my_tower_image.src = 'images/towers/my_tower.png';
        other_tower_image = new Image();
        other_tower_image.src = 'images/towers/other_tower.png';

        my_tower_image.onload = function() {
            ready_my_towers = true;
        }
        other_tower_image.onload = function() {
            ready_other_towers = true;
        }
        setInterval(function(){ update(); }, 300);
    }
    function update() {
        // if (ready_my_towers) {
        //     updateMyTowers();
        // }
        // if (ready_other_towers) {
        //     updateOtherTowers();
        // }

        // if (ready_my_towers && ready_other_towers) {
        //     updateTowers();
        // }

        updateMap();
        updateMechTrack();
    }
    function updateMyTowers() {
        my_towers.removeAllChildren();
        markMyTowers();
    }
    function updateOtherTowers() {
        other_towers.removeAllChildren();
        markOtherTowers();
    }
    function updateTowers() {
        markTowers();
    }
    function updateMechTrack() {
        mech_track.removeAllChildren();
        markMechTrack();
    }
    function clickMap(id) {
        $.ajax({
            url: "<?= '../../api/web/index.php?r=map/mark'; ?>",
            data: {
                id: id,
                key: getCookie('key'),
            },
            dataType : 'json',
            success: function(response) {
                if (response.success) {
                    setCookie("key", response.data.player.key, 7);
                    markMyTower(id);
                }
            }
        });
    }
    function updateMap() {
        $.ajax({
            url: "<?= '../../api/web/index.php?r=map/get-map'; ?>",
            data: {
                key: getCookie('key'),
            },
            dataType : 'json',
            success: function(response) {
                if (response.success) {
                    setCookie("key", response.data.player.key, 7);
                    grids = response.data.grids;
                    var new_my_grids = response.data.my_grids;
                    var new_other_grids = response.data.other_grids;
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
                }
            }
        });
    }
    function markTowers() {
        $.ajax({
            url: "<?= '../../api/web/index.php?r=map/get-towers'; ?>",
            data: {
                key: getCookie('key'),
            },
            dataType : 'json',
            success: function(response) {
                if (response.success) {
                    my_towers.removeAllChildren();
                    other_towers.removeAllChildren();
                    setCookie("key", response.data.player.key, 7);
                    var my_tower_grids = response.data.my_grids;
                    if (my_tower_grids) {
                        for (var i=0; i<my_tower_grids.length; i++) {
                            markMyTower(my_tower_grids[i].id);
                        }
                    }
                    var other_tower_grids = response.data.other_grids;
                    if (other_tower_grids) {
                        for (var i=0; i<other_tower_grids.length; i++) {
                            markOtherTower(other_tower_grids[i].id);
                        }
                    }
                }
            }
        });
    }
    function markMyTowers() {
        $.ajax({
            url: "<?= '../../api/web/index.php?r=map/get-my-towers'; ?>",
            data: {
                key: getCookie('key'),
            },
            dataType : 'json',
            success: function(response) {
                if (response.success) {
                    setCookie("key", response.data.player.key, 7);
                    var my_tower_grids = response.data.grids;
                    if (my_tower_grids) {
                        for (var i=0; i<my_tower_grids.length; i++) {
                            markMyTower(my_tower_grids[i].id);
                        }
                    }
                }
            }
        });
    }
    function markOtherTowers() {
        $.ajax({
            url: "<?= '../../api/web/index.php?r=map/get-other-towers'; ?>",
            data: {
                key: getCookie('key'),
            },
            dataType : 'json',
            success: function(response) {
                if (response.success) {
                    setCookie("key", response.data.player.key, 7);
                    var other_tower_grids = response.data.grids;
                    if (other_tower_grids) {
                        for (var i=0; i<other_tower_grids.length; i++) {
                            markOtherTower(other_tower_grids[i].id);
                        }
                    }
                }
            }
        });
    }
    function markMechTrack() {
        $.ajax({
            url: "<?= '../../api/web/index.php?r=map/get-mech-track'; ?>",
            data: {
                key: getCookie('key'),
            },
            dataType : 'json',
            success: function(response) {
                if (response.success) {
                    setCookie("key", response.data.player.key, 7);
                    var track = response.data.track;
                    if (track != '') {
                        track = track.split(",");
                        if (track) {
                            drawTrack(track);
                        }
                    }
                }
            }
        });
    }
    function drawTrack(track) {
        for (var i=0; i<track.length; i++) {
            drawStep(track[i]);
        }
    }
    function drawStep(id) {
        var x = (id-1)%<?=$grid_x?>;
        var y = Math.floor((id-1)/<?=$grid_x?>);
        var circle = new createjs.Shape();
        circle.x = x*<?=$grid_width?> + <?=$grid_width/2?> + <?=$offset_x?>;
        circle.y = y*<?=$grid_height?> + <?=$grid_height/2?>;
        circle.graphics.beginFill("pink").drawCircle(0, 0, <?=$grid_width/4?>);
        mech_track.addChild(circle);
        mech_track.update();
    }
    function markMyTower(id) {
        var x = (id-1)%<?=$grid_x?>;
        var y = Math.floor((id-1)/<?=$grid_x?>);
        var tower = new createjs.Bitmap(my_tower_image);
        tower.x = x*<?=$grid_width?> + <?=$grid_width/2?> + <?=$offset_x?> - my_tower_image.width/2;
        tower.y = y*<?=$grid_height?> + <?=$grid_height/2?> - my_tower_image.height/2;
        my_towers.addChild(tower);
        // my_towers.update();
    }
    function markOtherTower(id) {
        var x = (id-1)%<?=$grid_x?>;
        var y = Math.floor((id-1)/<?=$grid_x?>);
        var tower = new createjs.Bitmap(other_tower_image);
        tower.x = x*<?=$grid_width?> + <?=$grid_width/2?> + <?=$offset_x?> - other_tower_image.width/2;
        tower.y = y*<?=$grid_height?> + <?=$grid_height/2?> - other_tower_image.height/2;
        other_towers.addChild(tower);
        // other_towers.update();
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
    function clearMap() {
        $.ajax({
            url: "<?= '../../api/web/index.php?r=map/clear'; ?>",
            dataType : 'json',
            success: function(response) {
                if (response.success) {
                    // force refresh
                    location.reload();
                }
            }
        });
    }
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
    <canvas id="background" width="1500" height="1000" style="position:absolute;top:0;left:0;"></canvas>
    <canvas id="mech_track" width="1500" height="1000" style="position:absolute;top:0;left:0;"></canvas>
    <canvas id="other_towers" width="1500" height="1000" style="position:absolute;top:0;left:0;"></canvas>
    <canvas id="my_towers" width="1500" height="1000" style="position:absolute;top:0;left:0;"></canvas>
    <div class="map">
    <?php
    for ($y=0; $y<$grid_y; $y++)
    {
        for ($x=0; $x<$grid_x; $x++)
        {
            $id = $x*$grid_x+$y+1;
            echo '<div id="grid_'.$id.'" class="grid" onclick="clickMap('.$id.');" style="position:absolute;top:'.($x*$grid_width).'px;left:'.($y*$grid_height).'px;width:'.$grid_width.'px;height:'.$grid_height.'px;"></div>';
        }
    }
    ?>
    </div>
    <div class="bomb-button round-button disabled hidden" onClick=""><span>Bomb</span></div>
    <div class="clear-button" onclick="clearMap();">Clear All</div>
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
.map {
    position: absolute;
    left: <?=$offset_x?>px;
    width: <?=$grid_width*$grid_x?>px;
    height: <?=$grid_height*$grid_y?>px;
}
.clear-button {
    position: absolute;
    left: <?=$offset_x?>px;
    top: <?=$grid_height*$grid_y?>px;
    width: <?=$grid_width*$grid_x?>px;
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
    background: #262626;
}
.bomb-button {
    position: fixed;
    bottom: 0;
    right: 0;
    background-color: #ff9b9b;
    width: 300px;
    height: 300px;
    line-height: 280px;
    border: 10px solid #f5f5f5;
    text-align: center;
    font-size: 45px;
}
#background {
    position: fixed;
    z-index: -40;
}
#mech_track {
    position: fixed;
    z-index: -30;
}
#other_towers {
    position: fixed;
    z-index: -20;
}
#my_towers {
    position: fixed;
    z-index: -10;
}
</style>
