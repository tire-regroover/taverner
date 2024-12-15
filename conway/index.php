<?php require("../../log.php"); ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="" xml:lang="">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
<title></title>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
<!--
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.0/jquery-ui.min.js"></script>
<link type="text/css" rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.0/themes/vader/jquery-ui.css" />
-->

<style type="text/css">
/* <![CDATA[ */
body { font:12pt sans-serif; background:#ffffff; text-align:center; }
#table { border-collapse:collapse; margin:10px auto; }
#table, #table th, #table td { border:1px solid black; padding:0; }
#table th, #table td { margin:0; padding:0; }
#table th, #table td { height:5px; width:5px; }
#table td.on { background-color:#111111; }
#buttons div { margin:4px; }
/* ]]> */
</style>

<script type="text/javascript">
/* <![CDATA[ */
/*
Any live cell with fewer than two live neighbours dies, as if caused by under-population.
Any live cell with two or three live neighbours lives on to the next generation.
Any live cell with more than three live neighbours dies, as if by overcrowding.
Any dead cell with exactly three live neighbours becomes a live cell, as if by reproduction.
*/
var conway = {
	steps: 0,
	timer: 0,
	delay: 500,

	grid: [],
	grid2: [],
	saved: [],

	$table: $('<table id="table"/>'),
	$counter: $('<div>0</div>'),

	init: function(rows, cols, $grid_container, $counter_container) {
		conway.rows = rows;
		conway.cols = cols;

		$grid_container.empty().append(conway.$table.empty());
		$counter_container.empty().append(conway.$counter);

		conway.grid.length = 0;
		conway.grid2.length = 0;
		conway.saved.length = 0;

		for (var y = 0; y < conway.rows; y++) {
			var $row = $('<tr />');
			conway.$table.append($row);

			conway.grid[y] = [];
			conway.grid2[y] = [];
			conway.saved[y] = [];

			for (var x = 0; x < conway.cols; x++) {
				var $cell = $('<td class="cell" id="cell_' + y + '_' + x + '" />"');

				conway.grid[y][x] = false;
				conway.grid2[y][x] = false;
				conway.saved[y][x] = false;

				$cell.click(function() {
					$(this).toggleClass('on');

					var x = $(this).parent().children().index(this);
					var y = $(this).parent().parent().children().index(this.parentNode);

					if (conway.grid[y][x])
						conway.grid[y][x] = false;
					else
						conway.grid[y][x] = true;
				});

				$row.append($cell);
			}
		}
	},

	draw: function() {
		for (var y = 0; y < conway.rows; y++) {
			for (var x = 0; x < conway.cols; x++) {
				var $cell = $('#cell_' + y + '_' + x);

				if (conway.grid[y][x])
					$cell.addClass('on');
				else
					$cell.removeClass('on');
			}
		}
	},

	clear: function() {
		conway.$counter.text(0);
		conway.steps = 0;

		for (var y = 0; y < conway.rows; y++) {
			for (var x = 0; x < conway.cols; x++) {
				var $cell = $('#cell_' + y + '_' + x);
				$cell.removeClass('on');

				conway.grid[y][x] = false;
				conway.grid2[y][x] = false;
			}
		}
	},

	save: function() {
		for (var y = 0; y < conway.rows; y++) {
			for (var x = 0; x < conway.cols; x++) {
				conway.saved[y][x] = conway.grid[y][x];
			}
		}
	},

	load: function() {
		conway.$counter.text(0);
		conway.steps = 0;

		for (var y = 0; y < conway.rows; y++) {
			for (var x = 0; x < conway.cols; x++) {
				conway.grid[y][x] = conway.saved[y][x];
			}
		}
		conway.draw();
	},

	randomize: function(sparsity) {
		for (var y = 0; y < conway.rows; y++) {
			for (var x = 0; x < conway.cols; x++) {
				var $cell = $('#cell_' + y + '_' + x);
				$cell.removeClass('on');
				conway.grid[y][x] = false;

				if (Math.floor(Math.random() * sparsity) == 0) {
					$cell.addClass('on');
					conway.grid[y][x] = true;
				}
			}
		}
	},

	go: function(delay) {
		if (delay != undefined)
			conway.delay = delay;
		clearTimeout(conway.timer);
		conway.step();
		conway.timer = setTimeout(conway.go, conway.delay);
	},

	stop: function() {
		clearTimeout(conway.timer);
	},

	step: function() {
		for (var y = 0; y < conway.rows; y++) {
			for (var x = 0; x < conway.cols; x++) {
				var neighbors = 0;

				for (var yy = -1; yy < 2; yy++) {
					for (var xx = -1; xx < 2; xx++) {
						if ((xx == 0 && yy == 0) || x + xx < 0 || y + yy < 0 || x + xx >= conway.cols || y + yy >= conway.rows)
							continue;

						if (conway.grid[y + yy][x + xx])
							neighbors++;
					}
				}

				conway.grid2[y][x] = conway.grid[y][x];

				if (conway.grid[y][x]) {
					if (neighbors < 2 || neighbors > 3) {
						conway.grid2[y][x] = false;
					}
				} else if (neighbors == 3) {
					conway.grid2[y][x] = true;
				}
			}
		}

		var tempgrid = conway.grid;
		conway.grid = conway.grid2;
		conway.grid2 = tempgrid;

		conway.draw();

		conway.$counter.text(++conway.steps);
	}
};


$(function() {
	$('#init').click(function() {
		conway.init(
			Number($('#rows').val()),
			Number($('#cols').val()),
			$('#grid'),
			$('#counter'));
	}).click();

	$('#clear').click(function() {
		conway.clear();
	});

	$('#save').click(function() {
		$('#load').removeAttr('disabled');
		conway.save();
	});

	$('#load').click(function() {
		conway.load();
	}).attr('disabled', 'disabled');

	$('#randomize').click(function() {
		conway.randomize(Number($('#sparsity').val()));
	});

	$('#step').click(function() {
		conway.step();
	});

	$('#go').click(function() {
		$('#go').attr('disabled', 'disabled');
		$('#stop').removeAttr('disabled');
		conway.go(Number($('#delay').val()));
	});

	$('#stop').click(function() {
		$('#go').removeAttr('disabled');
		$('#stop').attr('disabled', 'disabled');
		conway.stop();
	}).attr('disabled', 'disabled');
});
/* ]]> */
</script>

<body>

<div id="grid"></div>

<div id="buttons">
	<div>
		<input type="button" id="init" value="init" />
		<input type="text" id="rows" value="100" title="rows" />
		<input type="text" id="cols" value="100" title="cols" />
	</div>
	<div>
		<input type="button" id="clear" value="clear" />
		<input type="button" id="save" value="save" />
		<input type="button" id="load" value="load" />
	</div>
	<div>
		<input type="button" id="randomize" value="randomize" />
		<input type="text" id="sparsity" value="15" title="sparsity" />
	</div>
	<div>
		<input type="button" id="go" value="go" />
		<input type="text" id="delay" value="500" title="delay (ms)" />
		<input type="button" id="stop" value="stop" />
	</div>
	<div>
		<input type="button" id="step" value="step" />
	</div>
	<div id="counter"></div>
</div>

</body>

</html>