<?php
define("MAX_ROWS", 100);

function connect() {
    $con = mysql_connect('fdb2.awardspace.com', 'taverner_rollo', '23peyote46');
    mysql_select_db('taverner_rollo') or die("1");
    return $con;
}

function my_escape($text) {
//    if (get_magic_quotes_gpc()) {
//        return mysql_real_escape_string(stripslashes($text));
//    }
//    return mysql_real_escape_string($text);
    return $text; //wtf, awardspace?
}

function process() {
    global $error;

    $sort_column1 = $_GET["sort_column1"] + 1;
    $sort_column2 = $_GET["sort_column2"] + 1;
    $sort_dir1 = $_GET["sort_dir1"] == 1 ? "desc" : "asc";
    $sort_dir2 = $_GET["sort_dir2"] == 1 ? "desc" : "asc";
    $limit = $_GET["limit"];
    $offset = $_GET["offset"];
    $search_fields = preg_split("/,/", $_GET["search_fields"]);
    $search_texts = preg_split("/,/", $_GET["search_texts"]);

    $fields = array();
    $types = array();
    $rows = array();

    $where = "";

    $first = true;

    if ($limit > MAX_ROWS) {
        $limit = MAX_ROWS;
    }

    if ($_GET["set"] == "countries") {
        $fields = array("number", "iso3", "p.continent_code as continent_code", "c.name as continent_name",
                "p.code as country_code", "p.name as country_name", "full_name");
        $from = "from continents c join countries p on c.code = p.continent_code";
    }
    elseif ($_GET["set"] == "words") {
        $fields = array("word", "substring(word from 1 for 1) as letter");
        $from = "from words";
    }
    else {
        $fields = array("id", "word");
        $from = "from words";
    }

    $select = sprintf("select %s", implode(",", $fields));

    for ($i = 0; $i < count($search_fields); $i++) {
        if ($search_fields[$i] != "" && !in_array($search_fields[$i], $fields)) {
            $found = false;
            foreach ($fields as $field) {
                if (preg_match("/\s+as\s+/", $field)) {
                    $splits = preg_split("/\s+as\s+/", $field);
                    if ($search_fields[$i] == $splits[1]) {
                        $search_fields[$i] = $splits[0];
                        $found = true;
                        break;
                    }
                }
            }
            if (!$found) {
                die("no field");
            }
        }

        if ($search_fields[$i] != "" && $search_texts[$i] != "") {
            if ($first) {
                $where = "where ";
                $first = false;
            }
            else {
                $where .= " and ";
            }

            $where .= sprintf("lower(cast(%s as char)) regexp '%s'",
                my_escape($search_fields[$i]),
                my_escape($search_texts[$i]));
        }
    }

    $params = sprintf("order by %d %s, %d %s limit %d offset %d",
            my_escape($sort_column1),
            my_escape($sort_dir1),
            my_escape($sort_column2),
            my_escape($sort_dir2),
            my_escape($limit),
            my_escape($offset));

    $sql = sprintf("select count(*) %s %s", $from, $where);

    $con = connect();

    $result = mysql_query($sql) or die("2");
    $total = mysql_result($result, 0);
    mysql_free_result($result);

    $sql = sprintf("%s %s %s %s", $select, $from, $where, $params);
    $result = mysql_query($sql) or die("3");

    $num_fields = mysql_num_fields($result);
    $fields = array();

    for ($i = 0; $i < $num_fields; $i++) {
        $fields[] = mysql_field_name($result, $i);
        $type = mysql_field_type($result, $i);
        if (preg_match("/^(?:int|float|numeric)/i", $type)) {
            $types[] = "number";
        }
        else {
            $types[] = "string";
        }
    }

    while ($row = mysql_fetch_row($result)) {
        $rows[] = $row;
    }

    mysql_free_result($result);

    echo json_encode(array("total" => $total, "types" => $types, "fields" => $fields, "rows" => $rows)); //, JSON_NUMERIC_CHECK

    mysql_close($con);
}

process();

?>