<?php
	/*
	 * Class that generates statistics for ownleads access
	 */
	class wcp_stats_ownleads extends main_wcp {

		/*
		 * Generate some graphs for displaying information
		 */
		public function load_statistics() {
			global $wpdb;
			$this->load_db_options(); // load the current tables and options

			// no access to this page for non-owners
            $this->get_the_current_user();
            if (!$this->current_access == 'ownleads') {
                $content = '<p>' . __('You do not have access to this page', 'shwcp') . '</p>';
                return $content;
            }

			// Get all of the field translated names for the charts from the sort table
		    $field_names = $wpdb->get_results (
		    	"select * from $this->table_sort"  
	        );

			foreach ($field_names as $k => $v) {
				if ($v->orig_name == 'l_status') {
					$status_title = $v->translated_name;
				} elseif ($v->orig_name == 'l_type') {
					$type_title = $v->translated_name;
				} elseif ($v->orig_name == 'l_source') {
					$source_title = $v->translated_name;
				}
			}


			// monthly total leads - last 12 months
			$months = array(
				1  => __('January', 'shwcp'),
				2  => __('February', 'shwcp'),
				3  => __('March', 'shwcp'),
				4  => __('April', 'shwcp'),
				5  => __('May', 'shwcp'),
				6  => __('June', 'shwcp'),
				7  => __('July', 'shwcp'),
				8  => __('August', 'shwcp'),
				9  => __('September', 'shwcp'),
				10 => __('October', 'shwcp'),
				11 => __('November', 'shwcp'),
				12 => __('December', 'shwcp')
			);

			// Colors and highlights to rotate through
			$colors = array(
				1 => 'rgba(65,105,225,0.8)',
				2 => 'rgba(100,149,237,0.8)',
				3 => 'rgba(173, 216,230,0.8)',
				4 => 'rgba(240,230,140,0.8)',
				5 => 'rgba(189,183,107,0.8)',
				6 => 'rgba(143,188,143,0.8)',
				7 => 'rgba(60,179,113,0.8)',
				8 => 'rgba(255,165,0,0.8)',
				9 => 'rgba(205,92,92,0.8)',
				10 => 'rgba(160,82,45,0.8)',
				11 => 'rgba(244,67,54,0.8)',
				12 => 'rgba(255,205,210,0.8)',
                13 => 'rgba(231,67,99,0.8)',
                14 => 'rgba(207,59,96,0.8)',
                15 => 'rgba(149,117,205,0.8)',
                16 => 'rgba(126,87,194,0.8)',
                17 => 'rgba(102,187,106,0.8)',
                18 => 'rgba(67,160,71,0.8)',
                19 => 'rgba(97,97,97,0.8)',
                20 => 'rgba(66,66,66,0.8)'
			);
			
			$highlights = array(
				1 => 'rgba(65,105,225,1)',
				2 => 'rgba(100,149,237,1)',
                3 => 'rgba(173, 216,230,1)',
                4 => 'rgba(240,230,140,1)',
                5 => 'rgba(189,183,107,1)',
                6 => 'rgba(143,188,143,1)',
                7 => 'rgba(60,179,113,1)',
                8 => 'rgba(255,165,0,1)',
				9 => 'rgba(205,92,92,1)',
                10 => 'rgba(160,82,45,1)',
				11 => 'rgba(244,67,54,1)',
				12 => 'rgba(255,205,210,1)',
				13 => 'rgba(231,67,99,1)',
				14 => 'rgba(207,59,96,1)',
				15 => 'rgba(149,117,205,1)',
				16 => 'rgba(126,87,194,1)',
				17 => 'rgba(102,187,106,1)',
				18 => 'rgba(67,160,71,1)',
				19 => 'rgba(97,97,97,1)',
				20 => 'rgba(66,66,66,1)'
            );
			
			$user_pseudo = $this->current_user->user_login;
			$current_month = date('n');
			$lead_totals = $wpdb->get_results (
				"
				SELECT DATE_FORMAT(creation_date, '%Y') as 'year',
     			DATE_FORMAT(creation_date, '%m') as 'month',
     			COUNT(*) as 'total'
     			FROM $this->table_main WHERE owned_by = '$user_pseudo' AND (creation_date) >= CURDATE() - INTERVAL 1 YEAR
     			GROUP BY DATE_FORMAT(creation_date, '%Y%m')
				"
			);	
			// line up months with zero data to past months...ugggh
			$decr = $current_month;
			for ($i = 1; $i <= 12; $i++) {
				if ($decr == 0) { $decr = 12; }
				$labels1[] = $months[$decr];
				$set = false;
				foreach ($lead_totals as $k => $v) {
					if ($v->month == $decr) {
						$values1[] = $v->total;
						$set = true;
					}
				} 
				if (!$set) { $values1[] = 0;}

				$decr--;
			}
			$labels1 = array_reverse($labels1);  // flip them for the graph
			$values1 = array_reverse($values1);
			$labels1 = json_encode($labels1);
			$values1 = json_encode($values1);


			// Types distribution
			$type_names = $wpdb->get_results (
				"select * from $this->table_sst WHERE sst_type=3 order by sst_name asc"         // Get Types
			);

			$i = 1;
			foreach ($type_names as $row => $type) {
				$type_count = $wpdb->get_var(
    				"select count(*) as 'Total' from $this->table_main where owned_by = '$user_pseudo' and l_type='$type->sst_id'"
				);
				$type_entries[] = array(
					'value' => $type_count,
					'label' => $type->sst_name,
					'color' => $colors[$i],
					'highlight' => $highlights[$i]
				);
				$i++;
				if ($i > 20) {
					$i = 1;
					//break; //exit loop
				}
			}
			usort( $type_entries, array($this, 'sortByOrder') ); // sort them descending
			for ($i2=0;$i2 <= 9;$i2++) {             // Limit to 10 results
				if (!isset($type_entries[$i2])) {
					break; // exit
				}
                $type_entries_limited[] = $type_entries[$i2];
            }
			$type_entries = json_encode($type_entries_limited);

			// Source distribution

			$source_names = $wpdb->get_results (
                "select * from $this->table_sst WHERE sst_type=1 order by sst_name asc"         // Get Types
            );

            $i = 1;
            foreach ($source_names as $row => $source) {
                $source_count = $wpdb->get_var(
                    "select count(*) as 'Total' from $this->table_main where owned_by = '$user_pseudo' and l_source='$source->sst_id'"
                );
                $source_entries[] = array(
                    'value' => $source_count,
                    'label' => $source->sst_name,
                    'color' => $colors[$i],
                    'highlight' => $highlights[$i]
                );
                $i++;
                if ($i > 20) {
					$i = 1;
                }
            }
            usort( $source_entries, array($this, 'sortByOrder') ); // sort them descending
			for ($i2=0;$i2 <= 9;$i2++) {             // Limit to 10 results
				if (!isset($source_entries[$i2])) {
                    break; // exit
                }
				$source_entries_limited[] = $source_entries[$i2];
			}
            $source_entries = json_encode($source_entries_limited);


			// Status distribution


            $status_names = $wpdb->get_results (
                "select * from $this->table_sst WHERE sst_type=2 order by sst_name asc"         // Get Types
            );

            $i = 1;
            foreach ($status_names as $row => $status) {
                $status_count = $wpdb->get_var(
                    "select count(*) as 'Total' from $this->table_main where owned_by = '$user_pseudo' and l_status='$status->sst_id'"
                );
                $status_entries[] = array(
                    'value' => $status_count,
                    'label' => $status->sst_name,
                    'color' => $colors[$i],
                    'highlight' => $highlights[$i]
                );
                $i++;
                if ($i > 20) {
					$i = 1;
                }
            }
            usort( $status_entries, array($this, 'sortByOrder') ); // sort them descending
			for ($i2=0;$i2 <= 9;$i2++) {             // Limit to 10 results
				if (!isset($status_entries[$i2])) {
                    break; // exit
                }
                $status_entries_limited[] = $status_entries[$i2];
            }
            $status_entries = json_encode($status_entries_limited);



			// Titles
			//Chart 1 title
			$chart1_title = __('New Entries', 'shwcp');
			$chart1_daily = __('Daily (1 Month)', 'shwcp');
			$chart1_weekly = __('Weekly (6 Months)', 'shwcp');
			$chart1_monthly = __('Monthly (1 Year)', 'shwcp');
			$chart2_title = __('Top 10', 'shwcp') . ' ' . $type_title . ' ' . __('Distribution', 'shwcp');
			$chart3_title = __('Top 10', 'shwcp') . ' ' . $source_title . ' ' . __('Distribution', 'shwcp');
			$chart4_title = __('Top 10', 'shwcp') . ' ' . $status_title . ' ' . __('Distribution', 'shwcp');

			$data = <<<EOC
				<script>
					jQuery(function ($) {  // use $ for jQuery
					    "use strict";


						// Leads chart (line)
						var data = {
							labels: $labels1,
							datasets: [
								{
									label: "Monthly Leads",
									fillColor: "rgba(151,187,205,0.2)",
            						strokeColor: "rgba(151,187,205,1)",
            						pointColor: "rgba(151,187,205,1)",
            						pointStrokeColor: "#fff",
            						pointHighlightFill: "#fff",
            						pointHighlightStroke: "rgba(151,187,205,1)",
									data: $values1
								}
							]
						};

						// Types chart (doughnut)
						var data2 = $type_entries;

						// Source chart (doughnut)
						var data3 = $source_entries;

						// Status chart (doughnut)
						var data4 = $status_entries;

						// Total leads chart
				    	var ctx = $("#monthlies").get(0).getContext("2d");
						window.myNewChart = new Chart(ctx);
						new Chart(ctx).Line(data, {
							bezierCurve: true, 
							responsive: true
						});
						// Types chart
						var ctx2 = $("#type-totals").get(0).getContext("2d");
						var myNewChart2 = new Chart(ctx2);
						new Chart(ctx2).Doughnut(data2, {
							percentageInnerCutout : 10,
							animationSteps: 100,
							animationEasing : "easeOutBounce",
							animateRotate : true,
							animateScale : false,
							responsive: true
						});

						// Source chart
						var ctx3 = $("#source-totals").get(0).getContext("2d");
                        var myNewChart3 = new Chart(ctx3);
                        new Chart(ctx3).Doughnut(data3, {
                            percentageInnerCutout : 10,
                            animationSteps: 100,
                            animationEasing : "easeOutBounce",
                            animateRotate : true,
                            animateScale : false,
                            responsive: true
                        });

						// Status chart
						var ctx4 = $("#status-totals").get(0).getContext("2d");
                        var myNewChart4 = new Chart(ctx4);
                        new Chart(ctx4).Doughnut(data4, {
                            percentageInnerCutout : 10,
                            animationSteps: 100,
                            animationEasing : "easeOutBounce",
                            animateRotate : true,
                            animateScale : false,
                            responsive: true
                        });


					});

				</script>

				<div class="row">
					<div class="col-md-4 no-padding">
						<div class="chart-holder">
                        	<h3>$chart2_title</h3>
                        	<canvas id="type-totals" width="400" height="200"></canvas>
						</div>
                    </div>
					<div class="col-md-4 no-padding"> 
						<div class="chart-holder">
							<h3>$chart3_title</h3>
							<canvas id="source-totals" width="400" height="200"></canvas>
						</div>
					</div>
					<div class="col-md-4 no-padding">
						<div class="chart-holder">
							<h3>$chart4_title</h3>
							<canvas id="status-totals" width="400" height="200"></canvas>
						</div>
					</div>
                </div>

				<div class="row">
                    <div class="col-md-12 no-padding"> 
						<div class="chart-holder">
							<h3>$chart1_title</h3>
							<div class="new-lead-view">
								  <select class="lead-stats">
								    <option value="daily">$chart1_daily</option>
									<option value="weekly">$chart1_weekly</option>
									<option value="monthly" selected="selected">$chart1_monthly</option>
								  </select>
							</div>
							<div class="ownleads-user" style="display:none">$user_pseudo</div>
                        	<canvas id="monthlies" width="400" height="200"></canvas>
						</div>
                    </div>
				</div>
EOC;

			
			return $data;
		}

		public function sortByOrder($a, $b) {
		    return $b['value'] - $a['value'];  // Descending
		}
	}
