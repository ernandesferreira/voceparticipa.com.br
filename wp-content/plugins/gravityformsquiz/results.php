<?php

if (!class_exists("GFResults")) {

    require_once(GFCommon::get_base_path() . "/tooltips.php");

    add_filter('gform_tooltips', array('GFResults', 'add_tooltips'));

    class GFResults {
        public static function results_page($form_id, $field_types, $page_title, $gf_page, $gf_view, $filters = null) {
            if (empty($form_id)) {

                $forms = RGFormsModel::get_forms();
                if (!empty($forms)) {
                    $form_id = $forms[0]->id;
                }
            }
            $form = GFFormsModel::get_form_meta($form_id);
            $form = apply_filters("gform_form_pre_results_$form_id", apply_filters("gform_form_pre_results", $form));
            wp_print_scripts(array("jquery"));


            //set up filter vars
            $start_date    = rgget("start");
            $end_date      = rgget("end");
            $fields        = GFCommon::get_fields_by_type($form, $field_types);
            $exclude_types = array("rank");

            for ($i = 0; $i < count($fields); $i++) {
                $field_type = GFFormsmodel::get_input_type($fields[$i]);
                if (in_array($field_type, $exclude_types))
                    unset($fields[$i]);
            }
            $fields = array_values($fields);

            $field_filters = array();

            foreach ($fields as $field) {
                $operators   = array();
                $field_type  = GFFormsmodel::get_input_type($field);
                $operators[] = array(
                    "value" => "=",
                    "text"  => "is"
                );
                if ($field_type != "checkbox")
                    $operators[] = array(
                        "value" => "<>",
                        "text"  => "is not"
                    );
                $field_filter = array();
                $key          = $field["id"];
                if ($field_type == "likert" && rgar($field, "gsurveyLikertEnableMultipleRows")) {
                    $field_filter["key"]  = $key;
                    $field_filter["type"] = "group";
                    $field_filter["text"] = rgar($field, "label");
                    $sub_filters          = array();
                    $rows                 = rgar($field, "gsurveyLikertRows");
                    foreach ($rows as $row) {
                        $sub_filter                    = array();
                        $sub_filter["key"]             = $key . "|" . rgar($row, "value");
                        $sub_filter["text"]            = rgar($row, "text");
                        $sub_filter["type"]            = "field";
                        $sub_filter["preventMultiple"] = false;
                        $sub_filter["operators"]       = $operators;
                        $sub_filter["values"]          = $field["choices"];
                        $sub_filters[]                 = $sub_filter;
                    }
                    $field_filter["filters"] = $sub_filters;
                } else {
                    $field_filter["key"]             = $key;
                    $field_filter["type"]            = "field";
                    $field_filter["preventMultiple"] = false;
                    $field_filter["text"]            = rgar($field, "label");
                    $field_filter["operators"]       = $operators;
                    if (isset($field["choices"]))
                        $field_filter["values"] = $field["choices"];

                }
                $field_filters[] = $field_filter;

            }
            if ($filters)
                $field_filters = array_merge($field_filters, $filters);

            ?>
        <script type="text/javascript">
            var gresultsFields = <?php echo json_encode($fields); ?>;
            var gresultsFieldTypes = <?php echo json_encode($field_types); ?>;
            var gresultsFilters = <?php echo json_encode($field_filters); ?>;
        </script>
        <?php if (version_compare(GFCommon::$version, "1.6.999", '<')) { ?>
            <script type='text/javascript'
                    src='<?php echo GFCommon::get_base_url()?>/js/jquery-ui/ui.datepicker.js?ver=<?php echo GFCommon::$version ?>'></script>
            <?php } ?>
        <link rel="stylesheet"
              href="<?php echo GFCommon::get_base_url() ?>/css/admin.css?ver=<?php echo GFCommon::$version ?>"
              type="text/css"/>
        <div class="wrap gforms_edit_form <?php echo GFCommon::get_browser_class() ?>">

            <div class="icon32" id="gravity-entry-icon"><br></div>

            <h2><?php echo empty($form_id) ? $page_title : $page_title . " : " . esc_html($form["title"]) ?></h2>

            <?php RGForms::top_toolbar(); ?>
            <?php if (false === empty($fields)) : ?>
            <div class="gresults-filter-loading" style="display:none;float:left;margin-right:5px;">
                <img style="vertical-align:middle;" src="<?php echo GFCommon::get_base_url() ?>/images/spinner.gif"
                     alt="loading..."/>&nbsp;
                <a href="javascript:void(0);" onclick="javascript:gresultsAjaxRequest.abort()">Cancel</a>
            </div>
            <div id="poststuff" class="metabox-holder has-right-sidebar">
                <div id="gresults-results-wrapper">
                    <div id="gresults-results">&nbsp;
                    </div>
                </div>

                <div id="gresults-results-filter" class="postbox">
                    <h3 style="cursor: default;"><?php _e("Results Filters", "gravityforms"); ?></h3>


                    <div id="gresults-results-filter-content">
                        <form id="gresults-results-filter-form" action="" method="GET">
                            <input type="hidden" id="gresults-page-slug" name="page"
                                   value="<?php echo esc_attr($gf_page); ?>">
                            <input type="hidden" id="gresults-view-slug" name="view"
                                   value="<?php echo esc_attr($gf_view); ?>">
                            <input type="hidden" id="gresults-form-id" name="id"
                                   value="<?php echo esc_attr($form_id); ?>">
                            <?php foreach ($field_types as $field_type) { ?>
                            <input type="hidden" name="field_types[]" value="<?php echo esc_attr($field_type); ?>">
                            <?php } ?>

                            <div class='gresults-results-filter-section-label'>
                                <?php _e("Filters", "gravityforms"); ?>&nbsp;<?php gform_tooltip("gresults_filters", "tooltip_bottomleft")?></div>
                            <div id="gresults-results-field-filters-container">
                                <div id="gresults-results-field-filters">
                                    <!-- placeholder populated by js -->
                                </div>
                            </div>
                            <div class='gresults-results-filter-section-label'>
                                <?php _e("Date Range", "gravityforms");?>&nbsp;<?php gform_tooltip("gresults_date_range", "tooltip_left")?>
                            </div>
                            <div style="width:90px; float:left; ">

                                <label for="gresults-results-filter-date-start"><?php _e("Start", "gravityforms"); ?></label>
                                <input type="text" id="gresults-results-filter-date-start" name="start"
                                       style="width:80px"
                                       class="gresults-datepicker"
                                       value="<?php echo $start_date; ?>"/>
                            </div>
                            <div style="width:90px; float:left; ">
                                <label for="gresults-results-filter-date-end"><?php _e("End", "gravityforms"); ?></label>
                                <input type="text" id="gresults-results-filter-date-end" name="end" style="width:80px"
                                       class="gresults-datepicker"
                                       value="<?php echo $end_date; ?>"/>
                            </div>
                            <br style="clear:both"/>

                            <div id="gresults-results-filter-buttons">
                                <input type="submit" id="gresults-results-filter-submit-button"
                                       class="button button-primary button-large" value="Apply filters">
                                <input type="button" id="gresults-results-filter-clear-button"
                                       class="button button-secondary button-large" value="Clear"
                                       onclick="gresults.clearFilterForm();">

                                <div class="gresults-filter-loading" style="display:none; float:right; margin-top:5px;">
                                    <img
                                            src="<?php echo GFCommon::get_base_url() ?>/images/spinner.gif"
                                            alt="loading..."/>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php else :
            _e("This form does not have any fields that can be used for results", "gravityforms");
        endif ?>
        </div>


        <?php
        }

        public static function add_tooltips($tooltips){
            $tooltips["gresults_total_score"] = "<h6>" . __("Total Score", "gravityforms") . "</h6>" . __("Scores are weighted calculations. Items ranked higher are given a greater score than items that are ranked lower. The total score for each item is the sum of the weighted scores.", "gravityforms");
            $tooltips["gresults_agg_rank"] = "<h6>" . __("Aggregate Rank", "gravityforms") . "</h6>" . __("The aggregate rank is the overall rank for all entries based on the weighted scores for each item.", "gravityforms");
            $tooltips["gresults_date_range"] = "<h6>" . __("Date Range", "gravityforms") . "</h6>" . __("Date Range is optional, if no date range is specified it will be ignored.", "gravityforms");
            $tooltips["gresults_filters"] = "<h6>" . __("Filters", "gravityforms") . "</h6>" . __("Narrow the results by adding filters. Note that some field types support more options than others.", "gravityforms");

            return $tooltips;
        }

        public static function ajax_get_results() {
            $output          = array();
            $html            = "";
            $form_id         = rgpost("id");
            $field_types     = rgpost("field_types");
            $view_slug       = rgpost("view");
            $form            = GFFormsModel::get_form_meta($form_id);
            $form = apply_filters("gform_form_pre_results_$form_id", apply_filters("gform_form_pre_results", $form));
            $search_criteria = array();
            $fields          = GFCommon::get_fields_by_type($form, $field_types);
            $total_entries   = self::count_search_leads($form_id, $search_criteria);
            if ($total_entries == 0) {
                $html = __("No results.", "gravityforms");
            } else {

                $filter_fields = rgpost("f");
                if (is_array($filter_fields)) {
                    $filter_types     = rgpost("t");
                    $filter_operators = rgpost("o");
                    $filter_values    = rgpost("v");
                    for ($i = 0; $i < count($filter_fields); $i++) {
                        $field_filter         = array();
                        $field_filter["type"] = "field";
                        $key                  = $filter_fields[$i];
                        $filter_type          = $filter_types[$i];
                        $operator             = $filter_operators[$i];
                        $val                  = $filter_values[$i];
                        $strpos_row_key       = strpos($key, "|");
                        if ($strpos_row_key !== false) { //multi-row
                            $key_array = explode("|", $key);
                            $key       = $key_array[0];
                            $val       = $key_array[1] . ":" . $val;
                        }
                        $field_filter["key"]      = $key;
                        $field_filter["type"]     = $filter_type;
                        $field_filter["operator"] = $operator;
                        $field_filter["value"]    = $val;
                        $search_criteria[]        = $field_filter;
                    }
                }

                $start_date = rgpost("start");
                $end_date   = rgpost("end");
                if ($start_date)
                    $search_criteria[] = array(
                        'key'      => 'date_created',
                        'type'     => 'info',
                        'operator' => '>=',
                        'value'    => $start_date
                    );
                if ($end_date)
                    $search_criteria[] = array(
                        'key'      => 'date_created',
                        'type'     => 'info',
                        'operator' => '<=',
                        'value'    => $end_date
                    );

                $search_criteria[] = array("type" => "info", "key" => "status", "value" => "active");

                $state_array = null;
                if (isset($_POST["state"])) {
                    $state_array = isset($_POST["state"]) ? json_decode(rgpost("state"), true) : null;
                    $check_sum   = rgpost("checkSum");
                    if (self::generate_checksum($state_array) !== $check_sum) {
                        $output["status"] = "complete";
                        $output["html"]   = sprintf(__('There was an error while processing the entries. Please contact support.'));
                        echo json_encode($output);
                        die();
                    }
                }
                $data        = self::get_entries_data($form, $fields, $search_criteria, $state_array);
                $entry_count = $data["entry_count"];

                if ("incomplete" === rgar($data, "status")) {
                    $output["status"]      = "incomplete";
                    $output["stateObject"] = $data;
                    $output["checkSum"]    = self::generate_checksum($data);
                    $output["html"]        = sprintf(__('Entries processed: %1$d of %2$d'), rgar($data, "offset"), $entry_count);
                    echo json_encode($output);
                    die();
                }

                if ($total_entries > 0) {
                    $html = apply_filters("gresults_markup_" . $view_slug, $html, $data, $form, $fields);
                    if (empty($html)) {
                        foreach ($fields as $field) {
                            $field_id = $field['id'];
                            $html .= "<div class='gresults-results-field' id='gresults-results-field-{$field_id}'>";
                            $html .= "<div class='gresults-results-field-label'>" . esc_html(GFCommon::get_label($field)) . "</div>";
                            $html .= "<div>" . self::get_field_results($form_id, $data, $field, $search_criteria) . "</div>";
                            $html .= "</div>";
                        }
                    }

                } else {
                    $html .= __("No results", "gravityforms");
                }
            }

            $output["html"]           = $html;
            $output["status"]         = "complete";
            $output["searchCriteria"] = $search_criteria;
            echo json_encode($output);
            die();
        }

        public static function ajax_get_more_results() {
            $form_id         = rgpost("form_id");
            $field_id        = rgpost("field_id");
            $offset          = rgpost("offset");
            $search_criteria = rgpost("search_criteria");

            if (empty($search_criteria))
                $search_criteria = array();
            $page_size = 10;

            $form    = RGFormsModel::get_form_meta($form_id);
            $form_id = $form["id"];
            $field   = RGFormsModel::get_field($form, $field_id);

            $totals                = RGFormsModel::get_form_counts($form_id);
            $entry_count           = $totals["total"];
            $html                  = self::get_default_field_results($form_id, $field, $search_criteria, $offset, $page_size);
            $remaining             = $entry_count - $page_size;
            $remaining             = $remaining < 0 ? 0 : $remaining;
            $response              = array();
            $response["remaining"] = $remaining;
            $response['html']      = $html;

            echo json_encode($response);
            die();
        }

        private static function generate_checksum($data) {
            return wp_hash(crc32(base64_encode(serialize($data))));
        }


        public static function get_total_entries($form) {
            $totals = RGFormsModel::get_form_counts($form["id"]);

            return $totals["total"];
        }

        public static function get_field_results($form_id, $data, $field, $search_criteria) {
            $field_data    = $data["field_data"];
            $entry_count   = $data["entry_count"];
            $field_results = "";
            if (empty($field_data[$field["id"]])) {
                $field_results .= __("No entries for this field", "gravityforms");

                return $field_results;
            }
            $field_type = GFFormsModel::get_input_type($field);
            switch ($field_type) {
                case "radio" :
                case "checkbox" :
                case "select" :
                case "rating" :
                case "multiselect" :
                    $results          = $field_data[$field["id"]];
                    $non_zero_results = is_array($results) ? array_filter($results) : $results;
                    if (empty($non_zero_results)) {
                        $field_results .= __("No entries for this field", "gravityforms");

                        return $field_results;
                    }
                    $choices = $field["choices"];

                    $data_table    = array();
                    $data_table [] = array(__('Choice', "gravityforms"), __('Frequency', "gravityforms"));

                    foreach ($choices as $choice) {
                        $text          = htmlspecialchars($choice["text"], ENT_QUOTES);
                        $val           = $results[$choice['value']];
                        $data_table [] = array($text, $val);
                    }

                    $bar_height        = 40;
                    $chart_area_height = (count($choices) * $bar_height);

                    $chart_options = array(
                        'isStacked' => true,
                        'height'    => ($chart_area_height + $bar_height),
                        'chartArea' => array(
                            'top'    => 0,
                            'left'   => 200,
                            'height' => $chart_area_height,
                            'width'  => '100%'
                        ),
                        'series'    => array(
                            '0' => array(
                                'color'           => 'silver',
                                'visibleInLegend' => 'false'
                            )
                        ),
                        'hAxis'     => array(
                            'viewWindowMode' => 'explicit',
                            'viewWindow'     => array('min' => 0),
                            'title'          => __('Frequency', "gravityforms")
                        )

                    );

                    $data_table_json = json_encode($data_table);
                    $options_json    = json_encode($chart_options);
                    $div_id          = "gresults-results-chart-field-" . $field["id"];
                    $height          = ""; //             = sprintf("height:%dpx", (count($choices) * $bar_height));

                    $field_results .= sprintf('<div class="gresults-chart-wrapper" style="width: 100%%;%s" id=%s data-datatable=\'%s\' data-options=\'%s\' data-charttype="bar" ></div>', $height, $div_id, $data_table_json, $options_json);


                    break;
                case "likert" :
                    $results       = $field_data[$field["id"]];
                    $multiple_rows = rgar($field, "gsurveyLikertEnableMultipleRows");

                    $n = 100;

                    $xr = 255;
                    $xg = 255;
                    $xb = 255;

                    $yr = 100;
                    $yg = 250;
                    $yb = 100;

                    $field_results .= "<div class='gsurvey-likert-field-results'>";
                    $field_results .= "<table class='gsurvey-likert'>";
                    $field_results .= "<tr>";
                    if ($multiple_rows)
                        $field_results .= "<td></td>";

                    foreach ($field["choices"] as $choice) {
                        $field_results .= "<td class='gsurvey-likert-choice-label'>" . $choice['text'] . "</td>";
                    }
                    $field_results .= "</tr>";

                    foreach ($field["gsurveyLikertRows"] as $row) {
                        $row_text  = $row["text"];
                        $row_value = $row["value"];
                        $max       = 0;
                        foreach ($field["choices"] as $choice) {
                            if ($multiple_rows) {
                                $choice_value       = rgar($choice, "value");
                                $results_row        = rgar($results, $row_value);
                                $results_for_choice = rgar($results_row, $choice_value);
                                $max                = max(array($max, $results_for_choice));
                            } else {
                                $max = max(array($max, $results[$choice['value']]));
                            }

                        }

                        $field_results .= "<tr>";

                        if ($multiple_rows)
                            $field_results .= "<td class='gsurvey-likert-row-label'>" . $row_text . "</td>";

                        foreach ($field["choices"] as $choice) {
                            $val     = $multiple_rows ? $results[$row_value][$choice['value']] : $results[$choice['value']];
                            $percent = $max > 0 ? round($val / $max * 100, 0) : 0;
                            $red     = (int)(($xr + (($percent * ($yr - $xr)) / ($n - 1))));
                            $green   = (int)(($xg + (($percent * ($yg - $xg)) / ($n - 1))));
                            $blue    = (int)(($xb + (($percent * ($yb - $xb)) / ($n - 1))));
                            $clr     = 'rgb(' . $red . ',' . $green . ',' . $blue . ')';
                            $field_results .= "<td class='gsurvey-likert-results' style='background-color:{$clr}'>" . $val . "</td>";
                        }
                        $field_results .= "</tr>";

                        if (false === $multiple_rows)
                            break;

                    }
                    $field_results .= "</table>";
                    $field_results .= "</div>";

                    break;
                case "rank" :
                    $results = $field_data[$field["id"]];
                    arsort($results);
                    $field_results .= "<div class='gsurvey-rank-field-results'>";
                    $field_results .= " <table>";
                    $field_results .= "     <tr class='gresults-results-field-table-header'>";
                    $field_results .= "         <td class='gresults-rank-field-label'>";
                    $field_results .= __("Item", "gravityforms");
                    $field_results .= "         </td>";
                    $field_results .= "         <td class='gresults-rank-field-score'>";
                    $field_results .= __("Total Score", "gravityforms") . "&nbsp;" . gform_tooltip("gresults_total_score", null, true);
                    $field_results .= "         </td>";
                    $field_results .= "         <td class='gresults-rank-field-rank'>";
                    $field_results .= __("Aggregate Rank", "gravityforms") . "&nbsp;" . gform_tooltip("gresults_agg_rank", null, true);
                    $field_results .= "         </td>";
                    $field_results .= "     </tr>";

                    $agg_rank = 1;
                    foreach ($results as $choice_val => $score) {
                        $field_results .= "<tr>";
                        $field_results .= "      <td class='gresults-rank-field-label' style='text-align:left;'>";
                        $field_results .= RGFormsModel::get_choice_text($field, $choice_val);
                        $field_results .= "      </td>";
                        $field_results .= "      <td class='gresults-rank-field-score'>";
                        $field_results .= $score;
                        $field_results .= "      </td>";
                        $field_results .= "      <td class='gresults-rank-field-rank'>";
                        $field_results .= $agg_rank;
                        $field_results .= "      </td>";
                        $field_results .= "</tr>";
                        $agg_rank++;
                    }
                    $field_results .= "</table>";
                    $field_results .= "</div>";

                    break;
                default :
                    $page_size = 5;
                    $offset    = 0;
                    $field_id  = $field["id"];

                    $field_results .= "<div class='gresults-results-field-sub-label'>" . __("Latest entries:", "gravityforms") . "</div>";

                    $field_results .= "<ul id='gresults-results-field-content-{$field_id}' class='gresults-results-field-content' data-offset='{$page_size}'>";
                    $field_results .= self::get_default_field_results($form_id, $field, $search_criteria, $offset, $page_size);
                    $field_results .= "</ul>";

                    if ($entry_count > 5) {
                        $field_results .= "<a id='gresults-results-field-more-link-{$field_id}' class='gresults-results-field-more-link' href='javascript:void(0)' onclick='gresults.getMoreResults({$form_id},{$field_id})'>Show more</a>";
                    }
                    break;
            }

            return $field_results;

        }

        public static function get_entries_data($form, $fields, $search_criteria, $state_array) {

            $view_slug = rgpost("view");
            //todo: add hooks to modify $max_execution_time and $page_size
            $max_execution_time = 25; //seconds
            $page_size          = 100;

            $time_start = microtime(true);

            $data        = array();
            $offset      = 0;
            $entry_count = 0;
            $field_data  = array();
            $form_id     = $form['id'];

            if ($state_array) {
                //get counts from state
                $data        = $state_array;
                $offset      = (int)rgar($data, "offset");
                $entry_count = $offset;
                $field_data  = rgar($data, "field_data");
            } else {
                //initialize counts
                foreach ($fields as $field) {
                    $field_type = GFFormsModel::get_input_type($field);
                    if (false === isset($field["choices"])) {
                        $field_data[$field["id"]] = 0;
                        continue;
                    }
                    $choices = $field["choices"];

                    if ($field_type == "likert" && rgar($field, "gsurveyLikertEnableMultipleRows")) {
                        foreach ($field["gsurveyLikertRows"] as $row) {
                            foreach ($choices as $choice) {
                                $field_data[$field["id"]][$row["value"]][$choice['value']] = 0;
                            }
                        }
                    } else {
                        foreach ($choices as $choice) {
                            $field_data[$field["id"]][$choice['value']] = 0;
                        }
                    }

                }

            }

            $count_search_leads  = self::count_search_leads($form_id, $search_criteria);
            $data["entry_count"] = $count_search_leads;

            $entries_left = $count_search_leads - $offset;

            while ($entries_left >= 0) {

                $paging = array(
                    'offset'    => $offset,
                    'page_size' => $page_size
                );

                $search_leads_time_start = microtime(true);
                $leads                   = self::search_leads($form_id, $search_criteria, null, $paging);
                $search_leads_time_end   = microtime(true);
                $search_leads_time       = $search_leads_time_end - $search_leads_time_start;

                $leads_in_search = count($leads);

                $entry_count += $leads_in_search;

                foreach ($leads as $lead) {
                    foreach ($fields as $field) {
                        $field_type = GFFormsModel::get_input_type($field);
                        $field_id   = $field["id"];
                        $value      = RGFormsModel::get_lead_field_value($lead, $field);

                        if ($field_type == "likert" && rgar($field, "gsurveyLikertEnableMultipleRows")) {

                            if (empty($value))
                                continue;
                            foreach ($value as $value_vector) {
                                if (empty($value_vector))
                                    continue;
                                list($row_val, $col_val) = explode(":", $value_vector, 2);
                                if (isset($field_data[$field["id"]][$row_val]) && isset($field_data[$field["id"]][$row_val][$col_val])) {
                                    $field_data[$field["id"]][$row_val][$col_val]++;
                                }
                            }
                        } elseif ($field_type == "rank") {
                            $score  = count(rgar($field, "choices"));
                            $values = explode(",", $value);
                            foreach ($values as $ranked_value) {
                                $field_data[$field["id"]][$ranked_value] += $score;
                                $score--;
                            }
                        } else {

                            if (false === isset($field["choices"])) {
                                if (false === empty($value))
                                    $field_data[$field_id]++;
                                continue;
                            }
                            $choices = $field["choices"];
                            foreach ($choices as $choice) {
                                $choice_is_selected = false;
                                if (is_array($value)) {
                                    $choice_value = rgar($choice, "value");
                                    if (in_array($choice_value, $value))
                                        $choice_is_selected = true;
                                } else {
                                    if (RGFormsModel::choice_value_match($field, $choice, $value))
                                        $choice_is_selected = true;
                                }
                                if ($choice_is_selected) {
                                    $field_data[$field_id][$choice['value']]++;
                                }
                            }
                        }
                    }

                }
                $data["field_data"] = $field_data;
                $data               = apply_filters("gresults_entries_data_" . $view_slug, $data, $form, $fields, $leads);
                if ($leads_in_search < $page_size) {
                    $data["status"] = "complete";
                    break;
                }

                $offset += $page_size;
                $entries_left -= $page_size;

                $time_end       = microtime(true);
                $execution_time = ($time_end - $time_start);

                if ($execution_time + $search_leads_time > $max_execution_time) {
                    $data["status"] = "incomplete";
                    $data["offset"] = $offset;
                    break;
                }

            }


            return $data;
        }


        public static function get_default_field_results($form_id, $field, $search_criteria, $offset, $page_size) {
            $field_results = "";

            $paging = array('offset' => $offset, 'page_size' => $page_size);

            $sorting = array('key' => "date_created", 'direction' => "DESC");

            $leads = self::search_leads($form_id, $search_criteria, $sorting, $paging);

            foreach ($leads as $lead) {

                $value   = RGFormsModel::get_lead_field_value($lead, $field);
                $content = apply_filters("gform_entries_field_value", $value, $form_id, $field["id"], $lead);

                $field_results .= "<li>{$content}</li>";
            }

            return $field_results;
        }


        // ---------  search functions  -----------
        //todo: use core functions in 1.7

        public static function search_leads($form_id, $search_criteria, $sorting = null, $paging = null) {

            global $wpdb;
            $sort_field = isset($sorting["key"]) ? $sorting["key"] : "date_created"; // column, field or entry meta

            if (is_numeric($sort_field))
                $sql = self::sort_by_field_query($form_id, $search_criteria, $sorting, $paging);
            else
                $sql = self::sort_by_column_query($form_id, $search_criteria, $sorting, $paging);

            //initializing rownum
            $wpdb->query("select @rownum:=0");

            //getting results
            $results = $wpdb->get_results($sql);

            $leads = GFFormsModel::build_lead_array($results);

            return $leads;
        }


        private static function sort_by_field_query($form_id, $search_criteria, $sorting, $paging) {
            global $wpdb;
            $sort_field_number = rgar($sorting, "key");
            $sort_direction    = isset($sorting["direction"]) ? $sorting["direction"] : "DESC";

            $is_numeric_sort = isset($sorting["is_numeric"]) ? $sorting["is_numeric"] : false;
            $offset          = isset($paging["offset"]) ? $paging["offset"] : 0;
            $page_size       = isset($paging["page_size"]) ? $paging["page_size"] : 20;

            if (!is_numeric($form_id) || !is_numeric($sort_field_number) || !is_numeric($offset) || !is_numeric($page_size))
                return "";

            $lead_detail_table_name = GFFormsModel::get_lead_details_table_name();
            $lead_table_name        = GFFormsModel::get_lead_table_name();

            $orderby = $is_numeric_sort ? "ORDER BY query, (value+0) $sort_direction" : "ORDER BY query, value $sort_direction";

            $search_where      = self::get_search_where($form_id, $search_criteria);
            $info_search_where = self::get_info_search_where($search_criteria);
            if (!empty($search_where))
                $info_search_where = " AND " . $info_search_where;
            $where = empty($where) && empty($info_search_where) ? "" : "WHERE " . $search_where . $info_search_where;

            $form_id_where = $form_id > 0 ? $wpdb->prepare(" AND form_id=%d", $form_id) : "";

            $field_number_min = $sort_field_number - 0.001;
            $field_number_max = $sort_field_number + 0.001;

            $sql = "
            SELECT filtered.sort, l.*, d.field_number, d.value
            FROM $lead_table_name l
            INNER JOIN $lead_detail_table_name d ON d.lead_id = l.id
            INNER JOIN (
                SELECT distinct sorted.sort, l.id
                FROM $lead_table_name l
                INNER JOIN $lead_detail_table_name d ON d.lead_id = l.id
                INNER JOIN (
                    SELECT @rownum:=@rownum+1 as sort, id FROM (
                        SELECT 0 as query, lead_id as id, value
                        FROM $lead_detail_table_name
                        WHERE field_number between $field_number_min AND $field_number_max
                        $form_id_where

                        UNION ALL

                        SELECT 1 as query, l.id, d.value
                        FROM $lead_table_name l
                        LEFT OUTER JOIN $lead_detail_table_name d ON d.lead_id = l.id AND field_number between $field_number_min AND $field_number_max
                        WHERE d.lead_id IS NULL
                        $form_id_where

                    ) sorted1
                   $orderby
                ) sorted ON d.lead_id = sorted.id
                $where
                LIMIT $offset,$page_size
            ) filtered ON filtered.id = l.id

            ORDER BY filtered.sort";

            return $sql;
        }

        private static function sort_by_column_query($form_id, $search_criteria, $sorting, $paging) {
            global $wpdb;
            $sort_field      = isset($sorting["key"]) ? $sorting["key"] : "date_created";
            $sort_direction  = isset($sorting["direction"]) ? $sorting["direction"] : "DESC";
            $is_numeric_sort = isset($sorting["is_numeric"]) ? $sorting["is_numeric"] : false;
            $offset          = isset($paging["offset"]) ? $paging["offset"] : 0;
            $page_size       = isset($paging["page_size"]) ? $paging["page_size"] : 20;

            if (!is_numeric($form_id) || !is_numeric($offset) || !is_numeric($page_size)) {
                return "";
            }

            $lead_detail_table_name = GFFormsModel::get_lead_details_table_name();
            $lead_table_name        = GFFormsModel::get_lead_table_name();
            $lead_meta_table_name   = GFFormsModel::get_lead_meta_table_name();

            $entry_meta          = GFFormsModel::get_entry_meta($form_id);
            $entry_meta_sql_join = "";
            if (false === empty($entry_meta) && array_key_exists($sort_field, $entry_meta)) {
                $entry_meta_sql_join = $wpdb->prepare("INNER JOIN
                                                    (
                                                    SELECT
                                                         lead_id, meta_value as $sort_field
                                                         from $lead_meta_table_name
                                                         WHERE meta_key=%s
                                                    ) lead_meta_data ON lead_meta_data.lead_id = l.id
                                                    ", $sort_field);
                $is_numeric_sort     = $entry_meta[$sort_field]['is_numeric'];
            }

            $grid_columns = RGFormsModel::get_grid_columns($form_id);
            if ($sort_field != "date_created" && false === array_key_exists($sort_field, $grid_columns))
                $sort_field = "date_created";
            $orderby = $is_numeric_sort ? "ORDER BY ($sort_field+0) $sort_direction" : "ORDER BY $sort_field $sort_direction";

            $where_arr    = array();
            $search_where = self::get_search_where($form_id, $search_criteria);
            if (!empty($search_where))
                $where_arr[] = $search_where;
            $info_search_where = self::get_info_search_where($search_criteria);
            if (!empty($info_search_where))
                $where_arr[] = $info_search_where;
            $form_id_where = $form_id > 0 ? $wpdb->prepare("l.form_id=%d", $form_id) : "";
            if (!empty($form_id_where))
                $where_arr[] = $form_id_where;
            $where = empty($where_arr) ? "" : "WHERE " . join($where_arr, " AND ");

            $sql = "
            SELECT filtered.sort, l.*, d.field_number, d.value
            FROM $lead_table_name l
            INNER JOIN $lead_detail_table_name d ON d.lead_id = l.id
            INNER JOIN
            (
                SELECT @rownum:=@rownum + 1 as sort, id
                FROM
                (
                    SELECT distinct l.id
                    FROM $lead_table_name l
                    INNER JOIN $lead_detail_table_name d ON d.lead_id = l.id
                    $entry_meta_sql_join
					$where
                    $orderby
                    LIMIT $offset,$page_size
                ) page
            ) filtered ON filtered.id = l.id

            ORDER BY filtered.sort";

            return $sql;
        }

        private static function get_search_where($form_id, $search_criteria) {
            global $wpdb;
            $sql_array               = array();
            $lead_details_table_name = GFFormsModel::get_lead_details_table_name();
            $lead_meta_table_name    = GFFormsModel::get_lead_meta_table_name();
            $form_id_where           = $form_id > 0 ? $wpdb->prepare("WHERE form_id=%d", $form_id) : "";
            foreach ($search_criteria as $search) {
                $key = rgar($search, "key");
                $val = rgar($search, "value");

                switch (rgar($search, "type")) {

                    case "field":
                        $upper_field_number_limit = (string)(int)$key === $key ? (float)$key + 0.9999 : (float)$key + 0.0001;
                        $operator                 = isset($search["operator"]) ? $search["operator"] : "=";
                        $search_term              = "LIKE" == $operator ? "%$val%" : $val;
                        /* doesn't support "<>" for checkboxes */
                        $sql_array[] = $wpdb->prepare("l.id IN
									(
									SELECT
									lead_id
									from $lead_details_table_name
									$form_id_where
									AND (field_number BETWEEN %s AND %s AND value $operator %s)
									)
								", (float)$key - 0.0001, $upper_field_number_limit, $search_term);
                        /*
                        //supports "<>" for checkboxes but it doesn't scale
                        $sql_array[] = $wpdb->prepare("l.id IN
                                        (SELECT lead_id
                                        FROM
                                            (
                                                SELECT lead_id, value
                                                FROM $lead_details_table_name
                                                WHERE form_id = %d
                                                AND (field_number BETWEEN %s AND %s)
                                                GROUP BY lead_id
                                                HAVING value $operator %s
                                            ) ld
                                        )
                                        ", $form_id, (float)$key - 0.0001, $upper_field_number_limit, $val );
                        */
                        break;
                    case "meta":
                        /* doesn't support "<>" for multiple values of the same key */
                        $operator    = isset($search["operator"]) ? $search["operator"] : "=";
                        $search_term = "LIKE" == $operator ? "%$val%" : $val;
                        $sql_array[] = $wpdb->prepare("l.id IN
									(
									SELECT
									lead_id
									FROM $lead_meta_table_name
									WHERE meta_key=%s AND meta_value $operator %s
									)
								", $search["key"], $search_term);
                        break;

                }

            }

            $sql = empty($sql_array) ? "" : join(" AND ", $sql_array);

            return $sql;
        }

        private static function get_info_search_where($search_criteria) {
            global $wpdb;
            $where_array = array();
            foreach ($search_criteria as $search) {
                switch (rgar($search, "type")) {
                    case "free-form":
                        $val           = $search["value"];
                        $operator      = isset($search["operator"]) ? $search["operator"] : "LIKE";
                        $search_term   = "LIKE" == $operator ? "%$val%" : $val;
                        $where_array[] = $wpdb->prepare("value $operator %s", $search_term);
                        break;
                    case "info":
                        $col         = $search["key"];
                        $val         = $search["value"];
                        $operator    = isset($search["operator"]) ? $search["operator"] : "=";
                        $search_term = "LIKE" == $operator ? "%$val%" : $val;
                        if ("date_created" === $col)
                            $where_array[] = $wpdb->prepare("datediff(date_created, %s) $operator 0", $val);
                        else
                            $where_array[] = $wpdb->prepare("{$col} $operator %s", $search_term);
                        break;
                }
            }
            $sql = empty($where_array) ? "" : join(" AND ", $where_array);

            return $sql;
        }

        public static function count_search_leads($form_id, $search_criteria) {
            global $wpdb;

            if (!is_numeric($form_id))
                return "";

            $detail_table_name = GFFormsModel::get_lead_details_table_name();
            $lead_table_name   = GFFormsModel::get_lead_table_name();

            $where_arr = array();

            $search_where = self::get_search_where($form_id, $search_criteria);
            if (!empty($search_where))
                $where_arr[] = $search_where;
            $info_search_where = self::get_info_search_where($search_criteria);
            if (!empty($info_search_where))
                $where_arr[] = $info_search_where;
            $form_id_where = $form_id > 0 ? $wpdb->prepare("l.form_id=%d", $form_id) : "";
            if (!empty($form_id_where))
                $where_arr[] = $form_id_where;
            $where = empty($where_arr) ? "" : "WHERE " . join($where_arr, " AND ");

            $sql = "SELECT count(distinct l.id)
                FROM $lead_table_name l
                INNER JOIN $detail_table_name ld ON l.id = ld.lead_id
                $where
                ";

            return $wpdb->get_var($sql);
        }
    }
}