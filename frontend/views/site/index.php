<?php

/* @var $this yii\web\View */
use yii\helpers\Url;

$this->title = 'Ice and Fire';

$grid_width = 100;
$grid_height = 100;
$grid_x = 10;
$grid_y = 10;
$map_width = 1500;
$map_height = 1000;
$offset_x = 250;
?>
<!doctype html>
<head>
    <meta charset="utf-8">
    <title>Hello World</title>
    <script src="https://code.createjs.com/easeljs-0.8.2.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
    <script>
    var stage;
    function init() {
        stage = new createjs.Stage("demoCanvas");
        // draw the basic map to fill the canvas
        var rect = new createjs.Shape();
        rect.graphics.beginFill("#EEEEEE").drawRect(0, 0, <?=$map_width?>, <?=$map_height?>);
        stage.addChild(rect);
        // draw the core at the center of canvas
        var circle = new createjs.Shape();
        circle.graphics.beginFill("DeepSkyBlue").drawCircle(0, 0, 50);
        circle.x = <?=$map_width/2?>;
        circle.y = <?=$map_height/2?>;
        stage.addChild(circle);
        stage.update();
        updateMap();
    }
    function clickMap(object, id) {
        // $(object).addClass("marked");
        var ids = [];
        ids.push (id);
        // up
        if (id - <?=$grid_x?> >= 0) {
            ids.push (id - <?=$grid_x?>);
        }
        // down
        if (id + <?=$grid_x?> < <?=$grid_x*$grid_y?>) {
            ids.push (id + <?=$grid_x?>);
        }
        // left
        if (id % <?=$grid_x?> != 0) {
            ids.push (id - 1);
        }
        // right
        if ((id + 1) % <?=$grid_x?> != 0) {
            ids.push (id + 1);
        }
        $.ajax({
            url: "<?= '../../api/web/index.php?r=map/marks'; ?>",
            data: {
                ids: ids
            },
            dataType : 'json',
            success: function(response) {
                if (response.success) {
                    markGrid(id);
                }
            }
        });
    }
    function updateMap() {
        $.ajax({
            url: "<?= '../../api/web/index.php?r=map/get-marked&mark=4'; ?>",
            dataType : 'json',
            success: function(response) {
                if (response.success) {
                    for (var i=0; i<response.data.grids.length; i++) {
                        markGrid(response.data.grids[i].id);
                    }
                }
            }
        });
    }
    function markGrid(id) {
        // $("#grid_"+id).addClass("marked");
        var circle = new createjs.Shape();
        var x = (id-1)%<?=$grid_x?>;
        var y = Math.floor((id-1)/<?=$grid_x?>);
        // circle.graphics.beginFill("#FFFF00").drawCircle(0, 0, <?=$grid_width?>);
        // circle.x = x*<?=$grid_width?> + <?=$grid_width/2?> + <?=$offset_x?>;
        // circle.y = y*<?=$grid_height?> + <?=$grid_height/2?>;
        // stage.addChild(circle);
        var polygon = new createjs.Shape();
        polygon.graphics.beginFill("Yellow").drawPolygon(0, 0, 0, <?=-1*$grid_height?>, <?=-1*$grid_width?>, 0, 0, <?=$grid_height?>, <?=$grid_width?>, 0);
        polygon.x = x*<?=$grid_width?> + <?=$grid_width/2?> + <?=$offset_x?>;
        polygon.y = y*<?=$grid_height?> + <?=$grid_height/2?>;
        stage.addChild(polygon);
        stage.update();
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
    </script>
</head>
<body onload="init();">
    <?php
    // echo "Hello World!";
    ?>
    <canvas id="demoCanvas" width="1500" height="1000" style="position:absolute;top:0;left:0;"></canvas>
    <div class="map">
    <?php
    for ($y=0; $y<$grid_y; $y++)
    {
        for ($x=0; $x<$grid_x; $x++)
        {
            $id = $x*$grid_x+$y+1;
            echo '<div id="grid_'.$id.'" class="grid" onclick="clickMap(this,'.$id.');" style="position:absolute;top:'.($x*$grid_width).'px;left:'.($y*$grid_height).'px;width:'.$grid_width.'px;height:'.$grid_height.'px;"></div>';
        }
    }
    ?>
    </div>
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
    background-color: yellow;
}
.map {
    position: absolute;
    left: <?=$offset_x?>px;
    width: <?=$grid_width*$grid_x?>px;
    height: <?=$grid_height*$grid_y?>px;
}
</style>
