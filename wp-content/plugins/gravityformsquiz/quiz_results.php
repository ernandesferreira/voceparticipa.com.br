<?php

if (!class_exists("GFResults"))
    require_once(GFQuiz::get_base_path() . "/results.php");


class GFQuizResults {

    public static function results_entries_data($data, $form, $fields, $leads) {
        //$data is collected in loops of entries so check before initializing
        $sum          = (int)rgar($data, "sum");
        $count_passed = (int)rgar($data, "count_passed");
        if (isset($data["score_frequencies"])) {
            $score_frequencies = rgar($data, "score_frequencies");
        } else {
            //initialize counts
            $max_score = count($fields);
            for ($n = 0; $n <= $max_score; $n++) {
                $score_frequencies[$n] = 0;
            }
        }
        if (isset($data["grade_frequencies"])) {
            $grade_frequencies = rgar($data, "grade_frequencies");
        } else {
            //initialize counts
            $grades = $form["gquizGrades"];
            foreach ($grades as $grade) {
                $grade_frequencies[$grade["text"]] = 0;
            }
        }

        //$field_data already contains the counts for each choice so just add the totals
        $field_data = rgar($data, "field_data");
        foreach ($fields as $field) {
            if (false === isset($field_data[$field["id"]]["totals"])) {
                //initialize counts
                $field_data[$field["id"]]["totals"]["correct"] = 0;
            }

        }


        foreach ($leads as $lead) {
            $score = isset($lead["gquiz_score"]) ? $lead["gquiz_score"] : 0;
            $sum += $score;
            $score_frequencies[$score] = $score_frequencies[$score] + 1;
            $is_pass                   = rgar($lead, "gquiz_is_pass");
            if ($is_pass)
                $count_passed++;
            $entry_grade = isset($lead["gquiz_grade"]) ? $lead["gquiz_grade"] : 0;
            if (isset($grade_frequencies[$entry_grade]))
                $grade_frequencies[$entry_grade]++;

            foreach ($fields as $field) {
                if (self::is_response_correct($field, $lead))
                    $field_data[$field["id"]]["totals"]["correct"] += 1;
            }
        }

        $entry_count               = (int)rgar($data, "entry_count");
        $data["sum"]               = $sum;
        $data["pass_rate"]         = $entry_count > 0 ? round($count_passed / $entry_count * 100) : 0;
        $data["score_frequencies"] = $score_frequencies;
        $data["grade_frequencies"] = $grade_frequencies;
        $data["field_data"]        = $field_data;

        return $data;
    }

    public static function results_markup($html, $data, $form, $fields) {
        //completely override the default results markup

        $max_score       = count($fields);
        $entry_count     = $data["entry_count"];
        $sum             = $data["sum"];
        $pass_rate       = $data["pass_rate"];
        $average_score   = $entry_count > 0 ? $sum / $entry_count : 0;
        $average_score   = round($average_score, 2);
        $average_percent = $entry_count > 0 ? ($sum / ($max_score * $entry_count)) * 100 : 0;
        $average_percent = round($average_percent);
        $field_data      = $data["field_data"];

        $html .= "<table width='100%' id='gquiz-results-summary'>
                             <tr>
                                <td class='gquiz-results-summary-label'>" . __("Total Entries", "gravityformsquiz") . "</td>
                                <td class='gquiz-results-summary-label'>" . __("Average Score", "gravityformsquiz") . "</td>
                                <td class='gquiz-results-summary-label'>" . __("Average Percentage", "gravityformsquiz") . "</td>";

        if (rgar($form, "gquizGrading") == "passfail")
            $html .= "  <td class='gquiz-results-summary-label'>" . __("Pass Rate", "gravityformsquiz") . "</td>";

        $html .= "  </tr>
                            <tr>
                                <td class='gquiz-results-summary-data'><div class='gquiz-results-summary-data-box'>{$entry_count}</div></td>
                                <td class='gquiz-results-summary-data'><div class='gquiz-results-summary-data-box'>{$average_score}</div></td>
                                <td class='gquiz-results-summary-data'><div class='gquiz-results-summary-data-box'>{$average_percent}%</div></td>";
        if (rgar($form, "gquizGrading") == "passfail")
            $html .= "  <td class='gquiz-results-summary-data'><div class='gquiz-results-summary-data-box'>{$pass_rate}%</div></td>";

        $html .= "  </tr>
                  </table>";

        if ($entry_count > 0) {
            $html .= "<div class='gresults-results-field-label'>Score Frequencies</div>";
            $html .= self::get_score_frequencies_chart($data["score_frequencies"]);

            if (rgar($form, "gquizGrading") == "letter") {
                $html .= "<div class='gresults-results-field-label'>Grade Frequencies</div>";
                $html .= "<div class='gquiz-results-grades'>" . self::get_grade_frequencies_chart($data["grade_frequencies"]) . "</div>";
            }

            foreach ($fields as $field) {
                $field_id = $field['id'];
                $html .= "<div class='gresults-results-field' id='gresults-results-field-{$field_id}'>";
                $html .= "<div class='gresults-results-field-label'>" . esc_html(GFCommon::get_label($field)) . "</div>";
                $html .= "<div>" . self::get_field_score_results($field, $data["field_data"][$field_id]["totals"]["correct"], $entry_count) . "</div>";
                $html .= "<div>" . self::get_quiz_field_results($field_data, $field) . "</div>";
                $html .= "</div>";
            }
        }


        return $html;
    }

    public static function get_field_score_results($field, $total_correct, $entry_count) {
        $field_results         = "";
        $total_correct_percent = round($total_correct / $entry_count * 100);
        $total_wrong           = $entry_count - $total_correct;
        $total_wrong_percent   = 100 - $total_correct_percent;


        $data_table    = array();
        $data_table [] = array(__("Response", "gravityformsquiz"), __("Count", "gravityformsquiz"));
        $data_table [] = array(__("Correct", "gravityformsquiz"), $total_correct);
        $data_table [] = array(__("Incorrect", "gravityformsquiz"), $total_wrong);

        $chart_options = array(
            'legend'       => array(
                'position' => 'none'
            ),
            'tooltip'      => array(
                'trigger' => 'none',
            ),
            'pieSliceText' => 'none',

            'slices'       => array(
                '0' => array(
                    'color' => 'green'
                ),
                '1' => array(
                    'color' => 'red'
                )
            ));


        $data_table_json = json_encode($data_table);
        $options_json    = json_encode($chart_options);
        $div_id          = "gquiz-results-chart-field-scores" . $field["id"];

        $field_results .= "<div class='gquiz-field-precentages-correct'>" . __("Correct:", "gravityformsquiz") . " <span style='color:green'>{$total_correct} ({$total_correct_percent}%)</span> " . __("Incorrect:", "gravityformsquiz") . " <span style='color:red'>$total_wrong ({$total_wrong_percent}%)</span></div>";

        $field_results .= "<div class='gresults-chart-wrapper' style='width: 50px;height:50px' id='{$div_id}'></div>";
        $field_results .= " <script>
                                jQuery('#{$div_id}')
                                    .data('datatable',{$data_table_json})
                                    .data('options', {$options_json})
                                    .data('charttype', 'pie');
                            </script>";

        return $field_results;

    }

    public static function get_quiz_field_results($field_data, $field) {
        $field_results = "";

        if (empty($field_data[$field["id"]])) {
            $field_results .= __("No entries for this field", "gravityformsquiz");

            return $field_results;
        }
        $choices = $field["choices"];

        $data_table    = array();
        $data_table [] = array(__('Choice', "gravityformsquiz"), __('Frequency', "gravityformsquiz"), __('Frequency (Correct)', "gravityformsquiz"));

        foreach ($choices as $choice) {
            $text = htmlspecialchars($choice["text"], ENT_QUOTES);
            $val  = $field_data[$field["id"]][$choice['value']];
            if (rgar($choice, "gquizIsCorrect")) {
                $data_table [] = Array($text, 0, $val);
            } else {
                $data_table [] = Array($text, $val, 0);
            }
        }

        $bar_height        = 40;
        $chart_area_height = (count($choices) * $bar_height);
        $chart_height      = $chart_area_height + $bar_height;

        $chart_options = array(
            'isStacked' => true,
            'height'    => $chart_height,
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
                ),
                '1' => array(
                    'color'           => '#99FF99',
                    'visibleInLegend' => 'false'
                )

            ),
            'hAxis'     => array(
                'viewWindowMode' => 'explicit',
                'viewWindow'     => array('min' => 0),
                'title'          => __('Frequency', "gravityformsquiz")
            )
        );

        $data_table_json = json_encode($data_table);
        $options_json    = json_encode($chart_options);
        $div_id          = "gquiz-results-chart-field-" . $field["id"];


        $field_results .= sprintf('<div class="gresults-chart-wrapper" style="width: 100%%;" id=%s data-datatable=\'%s\' data-options=\'%s\' data-charttype="bar" ></div>', $div_id, $data_table_json, $options_json);

        return $field_results;

    }


    public static function is_response_correct($field, $lead) {
        $value = RGFormsModel::get_lead_field_value($lead, $field);

        $completely_correct = true;

        $choices = $field["choices"];
        foreach ($choices as $choice) {

            $is_choice_correct = isset($choice['gquizIsCorrect']) && $choice['gquizIsCorrect'] == "1" ? true : false;

            $response_matches_choice = false;

            $user_responded = true;
            if (is_array($value)) {
                foreach ($value as $item) {
                    if (RGFormsModel::choice_value_match($field, $choice, $item)) {
                        $response_matches_choice = true;
                        break;
                    }
                }
            } elseif (empty($value)) {
                $response_matches_choice = false;
                $user_responded          = false;
            } else {
                $response_matches_choice = RGFormsModel::choice_value_match($field, $choice, $value) ? true : false;
            }

            if ($field["inputType"] == "checkbox")
                $is_response_wrong = ((!$is_choice_correct) && $response_matches_choice) || ($is_choice_correct && (!$response_matches_choice)) || $is_choice_correct && !$user_responded;
            else
                $is_response_wrong = ((!$is_choice_correct) && $response_matches_choice) || $is_choice_correct && !$user_responded;

            if ($is_response_wrong)
                $completely_correct = false;

        }

        //end foreach choice
        return $completely_correct;
    }

    public static function get_score_frequencies_chart($score_frequencies) {
        $markup = "";

        $data_table    = array();
        $data_table [] = array(__("Score", "gravityformsquiz"), __("Frequency", "gravityformsquiz"));

        foreach ($score_frequencies as $key => $value) {
            $data_table [] = array((string)$key, $value);
        }

        $chart_options = array(
            'series' => array(
                '0' => array(
                    'color'           => '#66CCFF',
                    'visibleInLegend' => 'false'
                ),
            ),
            'hAxis'  => array(
                'title' => 'Score'
            ),
            'vAxis'  => array(
                'title' => 'Frequency'
            )
        );

        $data_table_json = json_encode($data_table);
        $options_json    = json_encode($chart_options);
        $div_id          = "gquiz-results-chart-field-score-frequencies";
        $markup .= "<div class='gresults-chart-wrapper' style='width:100%;height:250px' id='{$div_id}'></div>";
        $markup .= "<script>
                        jQuery('#{$div_id}')
                            .data('datatable',{$data_table_json})
                            .data('options', {$options_json})
                            .data('charttype', 'column');
                    </script>";

        return $markup;

    }

    public static function get_grade_frequencies_chart($grade_frequencies) {
        $markup = "";

        $data_table    = array();
        $data_table [] = array(__("Grade", "gravityformsquiz"), __("Frequency", "gravityformsquiz"));

        foreach ($grade_frequencies as $key => $value) {
            $data_table [] = array((string)$key, $value);
        }

        $chart_options = array(
            'series' => array(
                '0' => array(
                    'color'           => '#66CCFF',
                    'visibleInLegend' => 'false'
                ),
            ),
            'hAxis'  => array(
                'title' => 'Score'
            ),
            'vAxis'  => array(
                'title' => 'Frequency'
            )
        );

        $data_table_json = json_encode($data_table);
        $options_json    = json_encode($chart_options);
        $div_id          = "gquiz-results-chart-field-grade-frequencies";

        $markup .= "<div class='gresults-chart-wrapper' style='width:100%;height:250px' id='{$div_id}'></div>";
        $markup .= "<script>
                        jQuery('#{$div_id}')
                            .data('datatable',{$data_table_json})
                            .data('options', {$options_json})
                            .data('charttype', 'column');
                    </script>";

        return $markup;

    }

}

