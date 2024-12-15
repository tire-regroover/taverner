(function($) {
    var dataview_id = 0;

    var methods = {
        "init": function(options) {
            if (options.url == undefined) {
                $.error("Missing required option: url");
            }

            return this.each(function() {
                $(this).ajaxError(function(e, jqxhr, ajaxSettings, exception) {
                    alert("Error! " + ( exception == undefined ? "" : "exception: " + exception)
                            + ( ajaxSettings.data == undefined ? "" : "\ndata: " + ajaxSettings.data ) );

                    enableInput(settings);
                });

                var settings = $.extend({
                    "url":                  undefined,
                    "method":               "get",
                    "searchFields":         "",
                    "searchTexts":          "",
                    "sortColumn1":           0,
                    "sortColumn2":           0,
                    "sortDir1":             0,
                    "sortDir2":             0,
                    "limit":                20,
                    "offset":               0,
                    "pagesEstimate":        "100000",
                    "width":                "auto",
                    "height":               "auto",
                    "data":                 undefined,
                    "caption":              undefined,
                    "limits":               [5, 10, 15, 20, 25, 30, 40, 50, 60, 70, 80, 90, 100],
                    "wrap":                 true,
                    "hide":                 false,
                    "offscreenInit":        true,
                    "rowHover":             false,
                    "columnPositions":      undefined,
                    "columnVisibilities":   undefined,
                    "columnWidths":         undefined,
                    "request": {
                        "sortColumn1":          "sort_column1",
                        "sortColumn2":          "sort_column2",
                        "sortDir1":             "sort_dir1",
                        "sortDir2":             "sort_dir2",
                        "limit":                "limit",
                        "offset":               "offset",
                        "searchFields":         "search_fields",
                        "searchTexts":          "search_texts"
                    },
                    "click":                undefined,
                    "ready":                undefined,
                    "received":             undefined
                }, options);

                var $this = $(this).empty();

                var $container      = $("<div class='ui-widget dataview-container' />");
                var $head_container = $("<div class='dataview-head-table-container ui-widget-header ui-corner-top' />");
                var $head_table     = $("<table class='dataview-head-table' />");
                var $head           = $("<tbody />");
                var $body_container = $("<div class='dataview-body-table-container ui-widget-content ui-corner-bottom' />");
                var $body_table     = $("<table class='dataview-body-table' />");
                var $body           = $("<tbody />");
                var $foot           = $("<div class='dataview-foot' />");
                var $config_dialog  = $("<div class='dataview-config-dialog' />");
                var $splash         = $("<div class='dataview-splash ui-widget'></div>");

                var params = new Object();
                params[settings.request.sortColumn1]   = settings.sortColumn1;
                params[settings.request.sortColumn2]   = settings.sortColumn2;
                params[settings.request.sortDir1]      = settings.sortDir1;
                params[settings.request.sortDir2]      = settings.sortDir2;
                params[settings.request.limit]         = settings.limit;
                params[settings.request.offset]        = settings.offset;
                params[settings.request.searchTexts]   = settings.searchTexts;
                params[settings.request.searchFields]  = settings.searchFields;
                $.extend(params, settings.data);

                settings.dataviewId = dataview_id;
                settings.limits = settings.limits.sort(function(a, b) { return a - b; });
                settings.params = params;
                settings.elements = {
                    "head": $head,
                    "body": $body,
                    "foot": $foot,
                    "configDialog": $config_dialog,
                    "parentElement": $this
                };

                $(this).data("dataview-settings", settings);

                $(this).addClass("dataview");

                if (settings.caption != undefined) {
                    $container.append("<h2>" + settings.caption + "</h2>");
                }

                if (!isNaN(settings.width)) {
                    $head_container.width(settings.width - 2);
                    $body_container.width(settings.width - 2);
                }

                $splash.insertBefore($this);

                if (settings.offscreenInit)
                    $this.addClass("dataview-offscreen");

                $.ajax({
                    "url":      settings.url,
                    "data":     settings.params,
                    "type":     settings.method,
                    "dataType": "json",
                    "success":  function(data) {
                        $this.data("dataview-data", data);

                        if (settings.columnPositions == undefined) {
                            settings.columnPositions = new Array();
                            for (var i = 0; i < data.fields.length; i++)
                                settings.columnPositions[i] = i;
                        }
                        if (settings.columnVisibilities == undefined) {
                            settings.columnVisibilities = new Array();
                            for (var i = 0; i < data.fields.length; i++)
                                settings.columnVisibilities[i] = true;
                        }

                        $this.append(
                            $container.append(
                                $head_container.append(
                                    $head_table.append(
                                        $head)),
                                $body_container.append(
                                    $body_table.append(
                                        $body)),
                                $foot).append(
                                $config_dialog));

                        initConfigDialog(settings, data);
                        buildHead(settings, data);
                        buildBody(settings, data);
                        wireHead(settings, data);

                        if (settings.click != undefined)
                            wireBody(settings);

                        if (settings.columnWidths == undefined) {
                            settings.columnWidths = new Array();
                            autoWidths(settings);
                        }
                        else
                            fixWidths(settings);

                        hideColumns(settings);
                        adjustBorders(settings);

                        initFoot(settings, data);

                        if (settings.searchFields == undefined) {
                            $("option", $("select.dataview-select-fields", $foot).eq(0)).eq($("th:visible", $head).eq(0).index())
                                .prop("selected", "selected");
                        }

                        $body_container.scroll(function(e) {
                            $head.parent().css("margin-left", -1 * $body_container.scrollLeft() + "px");
                        }).scroll();

                        $this.resizable({
                            "handles": "se",
                            "alsoResize": $body_container,
                            "minWidth": $(".dataview-search-controls", $foot).width() + 18,
                            "minHeight": 120,
                            "resize": function() {
                                $body_container.scroll();
                                $head_container.width($body_container.width());
                            }
                        });

                        if (!isNaN(settings.height))
                            $body_container.height(settings.height);// - $head_container.height() - $foot.height() - 6);
                        else
                            $body_container.height($body_container.height());

                        if (!isNaN(settings.width))
                            $this.width(settings.width);
                        else
                            $this.width(Math.max($body_table.width() + 2, $foot.width()));

                        $this.height($this.height());

                        $splash.remove();

                        if (settings.offscreenInit)
                            $this.removeClass("dataview-offscreen");

                        if (settings.hide)
                            $this.hide();

                        if (settings.received != undefined)
                            settings.received.apply($this);

                        if (settings.ready != undefined)
                            settings.ready.apply($this);
                    }
                });

                dataview_id++;
            });
        },
        "option": function(key, val) {
            if (arguments.length == 1)
                return $(this).first().data("dataview-settings")[key];
            $(this).first().data("dataview-settings")[key] = val;
        },
        "data": function() {
            return $(this).first().data("dataview-data");
        },
        "autoWidths": function() {
            return $(this).each(function() {
                var settings = $(this).data("dataview-settings");
                autoWidths(settings);
            });
        },
        "destroy": function() {
            return $(this).each(function() {
                $(this).resizable("destroy").empty();
                $(this).removeClass("dataview");
                $(this).removeData("dataview-settings");
                $(this).removeData("dataview-data");
            });
        }
    };

    function wireHead(settings, data) {
        var $head = settings.elements.head;
        var $body = settings.elements.body;
        var $foot = settings.elements.foot;
        var $config_dialog = settings.elements.configDialog;

        var $divs = $("th div.dataview-header-content", $head);
        var noclick = false;
        var old_width;

        $divs.resizable({
            "handles": "e",
            "minWidth": 0,
            "resize": function() {
                var $th_div = $(this);
                var width = $th_div.width() + 20;
                var index = $th_div.parent().index();
                $("tr", $body).each(function() {
                    var $td_div = $("td div", this).eq(index);
                    $td_div.width(width);
                    settings.columnWidths[Number(settings.columnPositions[index])] = width;
                });
                $body.parent().parent().scroll();
            }
        }).click(function(e) {
            if (!noclick && !$head.parent().hasClass("ui-state-disabled")) {
                var index = Number(settings.columnPositions[$(this).parent().index()]);

                if (settings.params[settings.request.sortColumn1] == index) {
                    settings.params[settings.request.sortDir1] = Math.abs(settings.params[settings.request.sortDir1] - 1);
                }
                else {
                    settings.params[settings.request.sortColumn2] = settings.params[settings.request.sortColumn1];
                    settings.params[settings.request.sortDir2] = settings.params[settings.request.sortDir1];
                    settings.params[settings.request.sortColumn1] = index;
                    settings.params[settings.request.sortDir1] = 0;
                }
                retrieve(settings);
            }
        }).draggable({
            "containment": $head.parent(),
            "zIndex": 10,
            "addClasses": false,
            "axis": "x",
            "start": function() {
                $(".dataview-indicator", $(this).parent()).hide();
                $(this).addClass("ui-widget-header dataview-header-drag");
                $(this).height($(this).height() - 2);
                old_width = $(this).width();
                $(this).width($(this).width() - 2);
                noclick = true;
            },
            "stop": function(e, ui) {
                $(".dataview-indicator", $(this).parent()).show();
                $(this).removeClass("ui-widget-header dataview-header-drag");
                $(this).css({ "left": "", "height": "" });
                $(this).width(old_width);
                setTimeout(function() {noclick = false;}, 1);
            }
        }).droppable({
            "accept": $divs,
            "tolerance": "pointer",
            "addClasses": false,
            "drop": function(e, ui) {
                var index_drag = ui.draggable.parent().index();
                var index_drop = $(this).parent().index();
                var after = e.pageX >  16 + $(this).offset().left + $(this).width() / 2;

                drop(settings, index_drag, index_drop, after);
            }
        });
    }

    function drop(settings, index_drag, index_drop, after) {
        var $head = settings.elements.head;
        var $body = settings.elements.body;
        var $foot = settings.elements.foot;
        var $config_dialog = settings.elements.configDialog;

        var pos = settings.columnPositions.splice(index_drag, 1);

        if (after) {
            settings.columnPositions.splice(index_drop + 1 - (index_drag > index_drop ? 0 : 1), 0, pos);

            $("th", $head).eq(index_drag).insertAfter($("th", $head).eq(index_drop));
            $("li", $config_dialog).eq(index_drag).insertAfter($("li", $config_dialog).eq(index_drop));

            $(".dataview-select-fields", $foot).each(function() {
                $("option", $(this)).eq(index_drag)
                        .insertAfter($("option", $(this)).eq(index_drop));
            });
        }
        else {
            settings.columnPositions.splice(index_drop - (index_drag > index_drop ? 0 : 1), 0, pos);

            $("th", $head).eq(index_drag).insertBefore($("th", $head).eq(index_drop));
            $("li", $config_dialog).eq(index_drag).insertBefore($("li", $config_dialog).eq(index_drop));

            $(".dataview-select-fields", $foot).each(function() {
                $("option", $(this)).eq(index_drag)
                        .insertBefore($("option", $(this)).eq(index_drop));
            });
        }

        $("tr", $body).each(function() {
            var $cell_drag = $("td", this).eq(index_drag);
            var $cell_drop  = $("td", this).eq(index_drop);

            if (after)
                $cell_drag.insertAfter($cell_drop);
            else
                $cell_drag.insertBefore($cell_drop);
        });

        adjustBorders(settings);
    }

    function buildHead(settings, data) {
        var $head = settings.elements.head.empty();

        var $tr = $("<tr />");
        var $th, $div, $indicator;

        $head.append($tr);

        for (var x = 0; x < data.fields.length; x++) {
            $th = $("<th class='ui-widget-header borderless-top borderless-bottom' />");

            $tr.append($th);

            $div = $("<div class='dataview-header-content' />");
            $div.text(data.fields[settings.columnPositions[x]]);

            $indicator = $("<div class='dataview-indicator ui-icon' />");

            if (settings.params[settings.request.sortColumn1] == settings.columnPositions[x]) {
                $indicator.removeClass("ui-icon-triangle-2-n-s ui-icon-triangle-1-n ui-icon-triangle-1-s ui-state-disabled");
                if (settings.params[settings.request.sortDir1] == 1)
                    $indicator.addClass("ui-icon-triangle-1-n ui-priority-secondary");
                else
                    $indicator.addClass("ui-icon-triangle-1-s ui-priority-secondary");
            }
            else if (settings.params[settings.request.sortColumn2] == settings.columnPositions[x]) {
                $indicator.removeClass("ui-icon-triangle-2-n-s ui-icon-triangle-1-n ui-icon-triangle-1-s ui-priority-secondary");
                if (settings.params[settings.request.sortDir2] == 1)
                    $indicator.addClass("ui-icon-triangle-1-n ui-state-disabled");
                else
                    $indicator.addClass("ui-icon-triangle-1-s ui-state-disabled");
            }
            else {
                $indicator.removeClass("ui-icon-triangle-1-n ui-icon-triangle-1-s ui-priority-secondary");
                $indicator.addClass("ui-icon-triangle-2-n-s ui-state-disabled");
            }

            $th.append($indicator);
            $th.append($div);
        }
    }

    function buildBody(settings, data) {
        var $body = settings.elements.body.empty();
        var $tr, $td, $div, $a;

        for (var y = 0; y < data.rows.length; y++) {
            row = data.rows[y];
            $tr = $("<tr />");

            for (var x = 0; x < row.length; x++) {
                $td = $("<td />");

                $tr.append($td);

                $td.addClass("ui-widget-content");

                if (y % 2 == 1)
                    $td.addClass("dataview-odd");

                if (y == 0)
                    $td.addClass("borderless-top");

                if (data.types[settings.columnPositions[x]] == "string")
                    $td.addClass("dataview-string");
                else if (data.types[settings.columnPositions[x]] == "number")
                    $td.addClass("dataview-number");

                if (!settings.wrap)
                    $td.addClass("dataview-singleline");

                $div = $("<div />");

                if (settings.click != undefined) {
                    $a = $("<a href='#' />");

                    if (row[settings.columnPositions[x]] != null)
                        $a.text(row[settings.columnPositions[x]]);

                    $div.append($a);
                }
                else if (row[settings.columnPositions[x]] != null) {
                    $div.text(row[settings.columnPositions[x]]);
                }

                $td.append($div);
            }

            $body.append($tr);
        }
    }

    function hideColumns(settings) {
        var $head = settings.elements.head;
        var $body = settings.elements.body;
        $("th", $head).each(function(index) {
            var $th = $(this);
            if (!settings.columnVisibilities[settings.columnPositions[index]])
                $th.hide();
            $("tr", $body).each(function() {
                var $td = $("td", this).eq(index);
                if (!settings.columnVisibilities[settings.columnPositions[index]])
                    $td.hide();
            });
        });
    }

    function wireBody(settings, data) {
        var $body = settings.elements.body;
        var was_odd;

        $("tr", $body).each(function(y) {
            $("td", $(this)).each(function(x) {
                $(this).click({ "x": settings.columnPositions[x], "y": y }, function(e) {
                    if (!$body.parent().hasClass("ui-state-disabled"))
                        settings.click.apply(this, [ e ]);
                    return false;
                }).css("cursor", "pointer");


                if (settings.rowHover) {
                    $(this).hover(
                        function() {
                            $("td", $(this).parent()).each(function(x) {
                                if (!$body.hasClass("ui-state-disabled") && !$(this).hasClass("ui-state-disabled")) {
                                    was_odd = $(this).hasClass("dataview-odd");
                                    $(this).removeClass("dataview-odd");
                                    $(this).addClass("ui-state-hover");
                                }
                            });
                        },
                        function() {
                            $("td", $(this).parent()).each(function(x) {
                                $(this).removeClass("ui-state-hover");
                                if (was_odd)
                                    $(this).addClass("dataview-odd");
                            });
                        }
                    ).mousedown(function() {
                        $("td", $(this).parent()).each(function(x) {
                            if (!$body.hasClass("ui-state-disabled") && !$(this).hasClass("ui-state-disabled"))
                                $(this).addClass("ui-state-active");
                        });
                    }).mouseup(function() {
                        $("td", $(this).parent()).each(function(x) {
                            $(this).removeClass("ui-state-active");
                        });
                    }).mouseout(function() {
                        $("td", $(this).parent()).each(function(x) {
                            $(this).removeClass("ui-state-active");
                        });
                    });
                }
                else {
                    $(this).hover(
                        function() {
                            if (!$body.hasClass("ui-state-disabled") && !$(this).hasClass("ui-state-disabled")) {
                                was_odd = $(this).hasClass("dataview-odd");
                                $(this).removeClass("dataview-odd");
                            }
                        },
                        function() {
                            if (was_odd)
                                $(this).addClass("dataview-odd");
                        }
                    );
                    wireButtonEffects(settings, $(this));
                }
            });
        });
    }

    function wireButtonEffects(settings, $link) {
        var $foot = settings.elements.foot;
        $link.hover(
            function() {
                if (!$foot.hasClass("ui-state-disabled") && !$link.hasClass("ui-state-disabled"))
                    $link.addClass("ui-state-hover");
            },
            function() {
                $link.removeClass("ui-state-hover");
            }
        ).mousedown(function() {
            if (!$foot.hasClass("ui-state-disabled") && !$link.hasClass("ui-state-disabled"))
                $link.addClass("ui-state-active");
        }).mouseup(function() {
            $link.removeClass("ui-state-active");
        }).mouseout(function() {
            $link.removeClass("ui-state-active");
        });
    }


    function initFoot(settings, data) {
        var $parent = settings.elements.parentElement;
        var $head = settings.elements.head;
        var $body = settings.elements.body;
        var $foot = settings.elements.foot;
        var $config_dialog = settings.elements.configDialog;
        var limit = settings.params[settings.request.limit];
        var offset = settings.params[settings.request.offset];

        var $search_controls, $page_controls;
        var $select_tmp, $textbox_tmp;
        var $textbox_search = $("<input type='text' class='dataview-textbox-search' />");
        var $select_fields  = $("<select class='dataview-select-fields' title='Field' />");
        var $search_reset   = $("<a title='Reset' href='#' class='ui-icon ui-icon-document ui-state-default ui-corner-all'>+</a>");
        var $search_more    = $("<a title='Add condition' href='#' class='ui-icon ui-icon-plus ui-state-default ui-corner-all'>+</a>");
        var $textbox_page   = $("<input type='text' class='dataview-textbox-page' />");
        var $of_pages       = $("<div class='dataview-pages'> of " + String(settings.pagesEstimate) + "</div>");
        var $search         = $("<a title='Search' href='#' class='dataview-search-button'></a>");
        var $refresh        = $("<a title='Refresh' href='#' class='dataview-refresh-button ui-icon ui-icon-refresh ui-state-default ui-corner-all'>Refresh</a>");
        var $first          = $("<a title='First' href='#' class='dataview-first-button ui-icon ui-icon-seek-start ui-state-default ui-corner-all'>First</a>");
        var $prev           = $("<a title='Previous' href='#' class='dataview-prev-button ui-icon ui-icon-seek-prev ui-state-default ui-corner-all'>Previous</a>");
        var $next           = $("<a title='Next' href='#' class='dataview-next-button ui-icon ui-icon-seek-next ui-state-default ui-corner-all'>Next</a>");
        var $end            = $("<a title='End' href='#' class='dataview-end-button ui-icon ui-icon-seek-end ui-state-default ui-corner-all'>End</a>");
        var $config         = $("<a title='Config' href='#' class='dataview-config-button ui-icon ui-icon-wrench ui-state-default ui-corner-all'>Config</a>");
        var search_texts  = settings.searchTexts.split(",");
        var search_fields = settings.searchFields.split(",");
        var pages, page, width;

        function update(settings, data) {
            var offset = settings.params[settings.request.offset];
            var limit = settings.params[settings.request.limit];

            var showing_bottom = data.rows.length == 0 ? 0 : offset + 1;
            var showing_top = offset + data.rows.length;
            var $option;

            pages = Math.ceil(data.total / limit);
            page = 1 + pages - Math.ceil((data.total - offset) / limit);

            if (pages == 0)
                pages = 1;

            if (page == 1) {
                $([$first, $prev]).each(function() {
                    this.removeClass("ui-state-hover");
                    this.addClass("ui-state-disabled");
                });
            }
            else {
                $([$first, $prev]).each(function() {
                    this.removeClass("ui-state-disabled");
                });
            }

            if (page == pages) {
                $([$next, $end]).each(function() {
                    this.removeClass("ui-state-hover");
                    this.addClass("ui-state-disabled");
                });
            }
            else {
                $([$next, $end]).each(function() {
                    this.removeClass("ui-state-disabled");
                });
            }

            $textbox_page.val(page).prop("title", "Records " + showing_bottom + " - " + showing_top);
            $of_pages.text(" of " + pages).prop("title", " of " + data.total + (data.total == 1 ? " record" : " records"));
        }

        function enterPressed() {
            var $textboxes = $("input.dataview-textbox-search", $foot);
            var $options = $("option:selected", $("select.dataview-select-fields", $foot));

            var texts = new Array();
            var fields = new Array();

            for (var i = 0; i < $options.length; i++) {
                texts.push($.trim($textboxes.eq(i).val()).toLowerCase());
                fields.push($options.eq(i).text());
                $textboxes.eq(i).val(texts[i]);
            }

            settings.params[settings.request.searchFields] = fields.join(",");
            settings.params[settings.request.searchTexts] = texts.join(",");
            settings.params[settings.request.offset] = 0;
            retrieve(settings, update);
        }

        function moreClick(grow) {
            var $more = $search_controls.clone();
            var $less_button = $("<a title='Remove condition' href='#' class='ui-icon ui-icon-minus ui-state-default ui-corner-all'>-</a>");

            $more.removeClass("ui-corner-top");
            $("input", $more).val("");
            $(".dataview-search-controls-buttons ul", $more).empty().append(
                $("<li />").append($less_button));

            $("option", $more).eq($("option:selected", $search_controls).eq(0).index())
                .prop("selected", "selected");

            $("input,select", $more).keydown(function(e) {
                if (e.which == 13 || e.which == 0) {
                    enterPressed();
                }
            });

            $less_button.click(function() {
                var $elem = $(this).parent().parent().parent().parent();
                $parent.height($parent.height() - $elem.outerHeight(true));
                $elem.remove();
                return false;
            });

            wireButtonEffects(settings, $less_button);

            $more.insertAfter($(".dataview-search-controls:last", $foot));

            if (grow)
                $parent.height($parent.height() + $more.outerHeight(true));

            return $more;
        }

        $([$textbox_search, $select_fields]).each(function() {
            $(this).keydown(function(e) {
                if (e.which == 13 || e.which == 0) {
                    enterPressed();
                }
            });
        });

        $search.click(function() {
            if (!$foot.hasClass("ui-state-disabled") && !$(this).hasClass("ui-state-disabled")) {
                enterPressed();
            }
            return false;
        });

        $search_reset.click(function() {
            if (!$foot.hasClass("ui-state-disabled") && !$(this).hasClass("ui-state-disabled")) {
                $textbox_search.val("");
                $(".dataview-search-controls", $foot).not(":eq(0)").remove();
                enterPressed();
            }
            return false;
        });

        $search_more.click(function() {
            if (!$foot.hasClass("ui-state-disabled") && !$(this).hasClass("ui-state-disabled")) {
                var $more = moreClick(true);
                $("input", $more).focus();
            }
            return false;
        });

        $([$search_reset, $search_more, $refresh, $prev, $next, $first, $end, $config]).each(function() {
            wireButtonEffects(settings, $(this));
        });

        $textbox_page.keydown(function(e) {
            if (e.which == 13 || e.which == 0) {
                $textbox_page.val($.trim($textbox_page.val()));
                if (/\d+/.test($textbox_page.val()) && $textbox_page.val() <= pages && $textbox_page.val() > 0) {
                    settings.params[settings.request.offset] = ($textbox_page.val() - 1) * settings.params[settings.request.limit];
                    retrieve(settings, update);
                }
            }
        });

        $refresh.click(function() {
            if (!$foot.hasClass("ui-state-disabled") && !$(this).hasClass("ui-state-disabled")) {
                retrieve(settings, update);
            }
            return false;
        });

        $first.click(function() {
            if (!$foot.hasClass("ui-state-disabled") && !$(this).hasClass("ui-state-disabled")) {
                settings.params[settings.request.offset] = 0;
                retrieve(settings, update);
            }
            return false;
        });

        $prev.click(function() {
            if (!$foot.hasClass("ui-state-disabled") && !$(this).hasClass("ui-state-disabled")) {
                settings.params[settings.request.offset] -= settings.params[settings.request.limit];
                if (settings.params[settings.request.offset] < 0)
                    settings.params[settings.request.offset] = 0;
                retrieve(settings, update);
            }
            return false;
        });

        $next.click(function() {
            if (!$foot.hasClass("ui-state-disabled") && !$(this).hasClass("ui-state-disabled")) {
                settings.params[settings.request.offset] += Number(settings.params[settings.request.limit]);
                retrieve(settings, update);
            }
            return false;
        });

        $end.click(function() {
            if (!$foot.hasClass("ui-state-disabled") && !$(this).hasClass("ui-state-disabled")) {
                settings.params[settings.request.offset] = (pages - 1) * settings.params[settings.request.limit];
                retrieve(settings, update);
            }
            return false;
        });

        $config.click(function() {
            if ($config_dialog.dialog("isOpen"))
                $config_dialog.dialog("close");
            else
                $config_dialog.dialog("open");
            return false;
        });


        /* ------------------------------------------------------------------------------------------------ */

        for (var i = 0; i < data.fields.length; i++) {
            $select_fields.append("<option>" + data.fields[settings.columnPositions[i]] + "</option>");
        }

        $search_controls =
            $("<div class='dataview-search-controls ui-widget-header ui-corner-top' />").append(
                $("<div class='dataview-search-controls-input' />").append(
//                     "Search: ").append(
                    $textbox_search).append(
                    $search).append(
                    $select_fields)).append(
                $("<div class='dataview-search-controls-buttons' />").append(
                        $("<ul />").append(
                            $("<li />").append($search_reset)).append(
                            $("<li />").append($search_more))));

        $page_controls =
                $("<div class='dataview-page-controls ui-widget-header ui-corner-bottom' />").append(
                    $("<div class='dataview-page-controls-input' />").append(
                        "Page: ").append(
                        $textbox_page).append(
                        $of_pages)).append(
                    $("<div class='dataview-page-controls-buttons' />").append(
                        $("<ul />").append(
                            $("<li />").append($refresh),
                            $("<li />").append($first),
                            $("<li />").append($prev),
                            $("<li />").append($next),
                            $("<li />").append($end),
                            $("<li />").append($config))));

        $foot.empty().append(
            $search_controls,
            $page_controls);

        if (settings.searchTexts == "")
            $of_pages.text("of " + Math.floor(data.total / settings.limits[0]));

        width = Math.max(
            $(".dataview-page-controls-input", $page_controls).width() + $(".dataview-page-controls-buttons", $page_controls).width() + 4,
//             $(".dataview-search-controls-input", $page_controls).width() + $(".dataview-search-controls-buttons", $page_controls).width());
//             $page_controls.width(),
            $search_controls.width());


//         $textbox_search.width($textbox_search.width() + (width - $search_controls.width()));

        $page_controls.width(width);
        $search_controls.width(width);

        $of_pages.text("of " + Math.floor(data.total / settings.limits[0]));

        $textbox_tmp = $textbox_search;
        $select_tmp = $select_fields;
        for (var i = 0; i < search_texts.length; i++) {
            $textbox_tmp.val(search_texts[i]);
            $select_tmp.val($.trim(search_fields[i]));

            if (i + 1 < search_texts.length) {
                moreClick(false);
                $textbox_tmp = $("input.dataview-textbox-search", $foot).last();
                $select_tmp = $("select.dataview-select-fields", $foot).last();
            }
        }

        update(settings, data);

        /* ------------------------------------------------------------------------------------------------ */
    }

    function initConfigDialog(settings, data) {
        var $head = settings.elements.head;
        var $body = settings.elements.body;
        var $foot = settings.elements.foot;
        var $config_dialog = settings.elements.configDialog;

        var $div = $("<div class='dataview-dialog-columns' />");
        var $ul = $("<ul />");
        var $select_limit   = $("<select class='dataview-select-limit' title='Records per page' />");
        var $autowidths_link = $("<a href='#'>Resize columns</a>");
        var $dialog_content = $("<div class='dataview-dialog-content ui-corner-all' />");
        var $li, $checkbox, $label, $limits_div, $resize_div, $items;
        var id;
        var widest = 0;

        $config_dialog.empty().append($dialog_content.append($div.append($ul)));

        for (var i = 0; i < settings.limits.length; i++) {
            $select_limit.append("<option>" + settings.limits[i] + "</option>");
        }

        for (var i = 0; i < data.fields.length; i++) {
            id = "dataview-" + settings.dataviewId + "-column-option-" + i;
            $li = $("<li />");
            $checkbox = $("<input type='checkbox' id='" + id + "' />");
            if (settings.columnVisibilities[Number(settings.columnPositions[i])])
                $checkbox.prop("checked", "checked");
            $label = $("<label for='" + id + "'>" + data.fields[Number(settings.columnPositions[i])] + "</label>");

            $li.append($checkbox, $label);
            $ul.append($li);

            if ($li.width() > widest)
                widest = $li.width();
        }

        $div.width(widest + 20);

        for (var i = 0; i < settings.limits.length; i++) {
            $option = $("option", $select_limit).eq(i);
            if (settings.limits[i] == settings.params[settings.request.limit])
                $option.prop("selected", "selected");
            else
                $option.removeProp("selected");
        }

        $limits_div = $("<div class='dataview-limits'/>").append("Records per page: ", $select_limit);
        $config_dialog.append($limits_div);
        $limits_div.width($limits_div.width());

        $resize_div = $("<div class='dataview-autowidths'/>").append($autowidths_link);
        $config_dialog.append($resize_div);
        $resize_div.width($autowidths_link.width());


        $config_dialog.dialog({
            "title": settings.caption,
            "autoOpen": false
        });

        $select_limit.change(function() {
            settings.params[settings.request.limit] = Number($select_limit.val());
            $("a.dataview-refresh-button", $foot).click();
        });

        $autowidths_link.click(function() {
            disableInput(settings);
            autoWidths(settings);
            enableInput(settings);
            return false;
        });

        for (var i = 0; i < settings.columnPositions.length; i++) {
            $checkbox = $("input[type='checkbox']", $config_dialog).eq(i);

            $checkbox.change(function(e) {
                disableInput(settings);

                var index = $(this).parent().index();

                $("th", $head).eq(index).toggle();
                $("tr", $body).each(function() {
                    $("td", $(this)).eq(index).toggle();
                });

                settings.columnVisibilities[settings.columnPositions[index]] = !settings.columnVisibilities[settings.columnPositions[index]];

                fixWidths(settings);
                adjustBorders(settings);

                if ($body.parent().parent().scrollLeft() == 0)
                    $head.parent().css("margin-left", "0");

                enableInput(settings);
            });
        }

        $items = $("li", $config_dialog);
        $items.each(function() {
            $(this).draggable({
                "containment": $config_dialog,
                "zIndex": 10,
                "addClasses": false,
                "axis": "y",
                "start": function() {
                    $(this).addClass("dataview-header-drag");
                },
                "stop": function() {
                    $(this).removeClass("dataview-header-drag");
                    $(this).css("top", "");
                }
            }).droppable({
                "accept": $items,
                "tolerance": "pointer",
                "addClasses": false,
                "drop": function(e, ui) {
                    var index_drag = ui.draggable.index();
                    var index_drop = $(this).index();

                    drop(settings, index_drag, index_drop, true);
                }
            });
        });
    }

    function retrieve(settings, callback) {
        var $head = settings.elements.head;
        var $body = settings.elements.body;

        disableInput(settings);

        $.ajax({
            "url":      settings.url,
            "data":     settings.params,
            "type":     settings.method,
            "dataType": "json",
            "success":  function(data) {
                settings.elements.parentElement.data("dataview-data", data);

                buildHead(settings, data);
                buildBody(settings, data);

                fixWidths(settings);
                hideColumns(settings);
                adjustBorders(settings);
                wireHead(settings, data);

                if (settings.click != undefined)
                    wireBody(settings);

                $head.parent().css("margin-left", -1 * $body.parent().parent().scrollLeft() + "px");

                enableInput(settings);

                if (callback != undefined) {
                    callback(settings, data);
                }

                if (settings.received != undefined)
                    settings.received.apply(settings.elements.parentElement);
            }
        });
    }

    function disableInput(settings) {
        var $head = settings.elements.head;
        var $body = settings.elements.body;
        var $foot = settings.elements.foot;
        var $config_dialog = settings.elements.configDialog;
        var id = settings.dataviewId;

        $([$(".dataview-" + id + "-input"), $head.parent(), $body.parent(), $foot, $config_dialog]).each(function() {
            $(this).addClass("ui-state-disabled");
        });
        $("input", $foot).prop("disabled", "disabled");
    }

    function enableInput(settings) {
        var $head = settings.elements.head;
        var $body = settings.elements.body;
        var $foot = settings.elements.foot;
        var $config_dialog = settings.elements.configDialog;
        var id = settings.dataviewId;

        $([$(".dataview-" + id + "-input"), $head.parent(), $body.parent(), $foot, $config_dialog]).each(function() {
            $(this).removeClass("ui-state-disabled");
        });
        $("input", $foot).removeProp("disabled");
    }

    function autoWidths(settings) {
        var $head = settings.elements.head;
        var $body = settings.elements.body;
        $("th div.dataview-header-content", $head).each(function(index) {
            var $th_div = $(this);
            $th_div.width("auto");
            $("tr", $body).each(function(row_index) {
                var $td_div = $("td div", this).eq(index);
                $td_div.width("auto");
            });
        });
        $("th div.dataview-header-content", $head).each(function(index) {
            var $th_div = $(this);
            $("tr", $body).each(function(row_index) {
                var $td_div = $("td div", this).eq(index);
                var width = Math.max($td_div.width(), $th_div.width() + 20, 30);
                $th_div.width(width - 20);
                $td_div.width(width);

                settings.columnWidths[settings.columnPositions[index]] = width;
            });
        });
    }

    function fixWidths(settings) {
        var $head = settings.elements.head;
        var $body = settings.elements.body;

        $("th div.dataview-header-content", $head).each(function(index) {
            var $th_div = $(this);
            var size = Number(settings.columnWidths[settings.columnPositions[index]]);
            $th_div.width(size - 20);
            $("tr", $body).each(function() {
                var $td_div = $("td div", this).eq(index);
                $td_div.width(size);
            });
        });
    }

    function adjustBorders(settings) {
        var $head = settings.elements.head;
        var $body = settings.elements.body;
        var overflow_y = $body.parent().parent().css("overflow-y");
        var overflow_x = $body.parent().parent().css("overflow-x");
        var scroll_left = $body.parent().parent().scrollLeft();

        $body.parent().parent().css({ "overflow-y": "hidden", "overflow-x": "hidden" });

        $("th", $head).removeClass("borderless-left").filter(":visible").eq(0).addClass("borderless-left");
        $("tr", $body).each(function() {
            $("td", this).removeClass("borderless-left").filter(":visible").eq(0).addClass("borderless-left");
        });

        $body.parent().parent().css({ "overflow-y": overflow_y, "overflow-x": overflow_x });
        $body.parent().parent().scrollLeft(scroll_left);
        $head.parent().css("margin-left", -1 * scroll_left + "px");
    }


    $.fn.dataview = function(arg1, arg2) {
        if (methods[arg1])
            return methods[arg1].apply(this, Array.prototype.slice.call(arguments, 1));
        if (typeof arg1 === "string" && typeof arg2 === "string")
            return methods.init.apply(this, [{ "url": arg1, "caption": arg2 }]);
        if (typeof arg1 === "object" || !arg1)
            return methods.init.apply(this, arguments);
        $.error("dataview invalid method/arguments");
    };

})(jQuery);


/* ------------------------------------------------------------------------------------------------ */
/* ------------------------------------------------------------------------------------------------ */


(function($) {
    var methods = {
        "init": function(options) {
            if (options.titleColumn == undefined)
                $.error("datviewBrowser('init') is missing required option: titleColumn");
            if (options.contentColumn == undefined)
                $.error("datviewBrowser('init') is missing required option: contentColumn");

            return this.each(function() {
                var settings = $.extend({
                    "titleColumn":      undefined,
                    "contentColumn":    undefined
                }, options);

                var $this = $(this);

                var $next_or_prev;

                var pending_page_change = false;
                var showing = false;
                var index;

                var id = $this.dataview("option", "dataviewId");
                var $dialog = $("<div class='dataview dataview-browser'/>");
                var $content = $("<pre class='dataview-browser-content dataview-dialog-content ui-corner-all' />");
                var $prev = $("<a title='Previous' href='#' class='ui-icon ui-icon-seek-prev ui-state-default ui-corner-all'>Previous</a>");
                var $next = $("<a title='Next' href='#' class='ui-icon ui-icon-seek-next ui-state-default ui-corner-all'>Next</a>");

                function updateDialog() {
                    var data = $this.dataview("data");
                    var offset = $this.dataview("option", "params").offset;
                    var row = data.rows[index];
                    $content.text(row[settings.contentColumn]);
                    $dialog.dialog("option", "title", row[settings.titleColumn]);

                    if (index + offset == 0)
                        $prev.addClass("ui-state-disabled");
                    else
                        $prev.removeClass("ui-state-disabled");

                    if (index + offset == data.total - 1)
                        $next.addClass("ui-state-disabled");
                    else
                        $next.removeClass("ui-state-disabled");

                    $("td", $("table.dataview-body-table tbody tr", $this).eq(index)).each(function() {
                        $(this).addClass("dataview-highlight");
                    });

                    $content.scrollTop(0);
                }

                function showDialog(e) {
                    var data = $this.dataview("data");
                    var row = data.rows[e.data.y];
                    var height_diff, width_diff;

                    index = e.data.y;

                    $([$prev, $next]).each(function() {
                        $(this).addClass("dataview-" + id + "-input");

                        $(this).hover(
                            function() {
                                if (!$(this).hasClass("ui-state-disabled"))
                                    $(this).addClass("ui-state-hover");
                            },
                            function() {
                                $(this).removeClass("ui-state-hover");
                            }
                        ).mousedown(function() {
                            if (!$(this).hasClass("ui-state-disabled"))
                                $(this).addClass("ui-state-active");
                        }).mouseup(function() {
                            $(this).removeClass("ui-state-active");
                        }).mouseout(function() {
                            $(this).removeClass("ui-state-active");
                        });
                    });

                    $prev.unbind("click").click(function() {
                        if (!$(this).hasClass("ui-state-disabled")) {
                            var $prev_page = $(".dataview-foot a.dataview-prev-button", $this);
                            var limit = $this.dataview("option", "params").limit;
                            $("td", $("table.dataview-body-table tbody tr", $this).eq(index)).each(function() {
                                $(this).removeClass("dataview-highlight");
                            });
                            index--;
                            if (index < 0) {
                                index = limit;
                                $content.empty();
                                $dialog.dialog("option", "title", "...");
                                $prev_page.click();
                                $next_or_prev = $prev;
                                pending_page_change = true;
                            }
                            else
                                updateDialog();
                        }
                        return false;
                    });

                    $next.unbind("click").click(function() {
                        if (!$(this).hasClass("ui-state-disabled")) {
                            var $next_page = $(".dataview-foot a.dataview-next-button", $this);
                            $("td", $("table.dataview-body-table tbody tr", $this).eq(index)).each(function() {
                                $(this).removeClass("dataview-highlight");
                            });
                            index++;
                            if (index >= $this.dataview("data").rows.length) {
                                index = -1;
                                $content.empty();
                                $dialog.dialog("option", "title", "...");
                                $next_page.click();
                                $next_or_prev = $next;
                                pending_page_change = true;
                            }
                            else
                                updateDialog();
                        }
                        return false;
                    });

                    updateDialog();

                    $dialog.append(
                        $content).append(
                        $("<ul class='dataview-browser-buttons' />").append(
                            $("<li />").append($prev)).append(
                            $("<li />").append($next)));

                    $dialog.dialog({
                        "title": row[settings.titleColumn],
                        "width": 540,
                        "close": function() {
                            $("td", $("table.dataview-body-table tbody tr", $this).eq(index)).each(function() {
                                $(this).removeClass("dataview-highlight");
                            });
                            $content.removeProp("style");
                            $(this).dialog("destroy");
                            showing = false;
                        },
                        "open": function() {
                            height_diff = $(this).height() - $content.height() + 1;
                            width_diff = $(this).width() - $content.width() + 1;
                            $next.focus();
                        },
                        "resize": function() {
                            $content.height($(this).height() - height_diff);
                            $content.width($(this).width() - width_diff);
                        }
                    });

                }

                $this.data("originalReceived", $this.dataview("option", "received"));
                $this.dataview("option", "received", function(e) {
                    var original_received = $this.dataview("option", "originalReceived");
                    var data = $this.dataview("data");
                    if (pending_page_change)
                        $next_or_prev.click();
                    pending_page_change = false;
                    if (showing) {
                        if (index >= data.rows.length)
                            index = data.rows.length - 1;
                        updateDialog();
                    }
                    if (original_received != undefined)
                        original_received.apply($this);
                });

                $this.data("originalClick", $this.dataview("option", "click"));
                $this.dataview("option", "click", function(e) {
                    var original_click = $this.data("originalClick");
                    if (showing) {
                        $("td", $("table.dataview-body-table tbody tr", $this).eq(index)).each(function() {
                            $(this).removeClass("dataview-highlight");
                        });
                        index = e.data.y;
                        updateDialog();
                    }
                    else {
                        showDialog(e);
                        showing = true;
                    }
                    if (original_click != undefined) {
                        var $cell = $("td", $("table.dataview-body-table tbody tr", $this).eq(e.data.y)).eq(e.data.x);
                        original_click.apply($cell, [ e ]);
                    }
                });
            });
        },
        "destroy": function() {
            return this.each(function() {
                $(this).dataview("option", "received", $(this).data("originalReceived"));
                $(this).removeData("originalReceived");
                $(this).dataview("option", "click", $(this).data("originalClick"));
                $(this).removeData("originalClick");
            });
        }
    };

    $.fn.dataviewBrowser = function(arg1) {
        if (methods[arg1])
            return methods[arg1].apply(this, Array.prototype.slice.call(arguments, 1));
        if (typeof arg1 === "object" || !arg1)
            return methods.init.apply(this, arguments);
        $.error("dataviewBrowser invalid method/arguments");
    };

})(jQuery);