<?php
	/*
	 * Class that generates statistics
	 */
	class wcp_stats extends main_wcp {

		/*
		 * Generate some graphs for displaying information
		 */
		public function load_statistics() {
			global $wpdb;
			$this->load_db_options(); // load the current tables and options

			// no access to this page for non-admins or custom roles without access
            $this->get_the_current_user();
			$custom_role = $this->get_custom_role();
			if (!$custom_role['access']) {
            	if ($this->current_access != 'full' && $this->current_access != 'ownleads' ) {
                	$content = '<span class="no-access">' . __('You do not have access to this page', 'shwcp') . '</span>';
                	return $content;
				}
            } elseif ($custom_role['access'] ) {
				if ($custom_role['perms']['access_statistics'] != 'all' && $custom_role['perms']['access_statistics'] != 'own') {
					$content = '<span class="no-access">' . __('You do not have access to this page', 'shwcp') . '</span>';
                    return $content;
                }
            }

			$own_entries = '';
			$current_user = $this->current_user->user_login;
			if ($this->current_access == 'ownleads') {
				$own_entries = "and owned_by='$current_user'";
			} elseif ($custom_role['access'] && $custom_role['perms']['access_statistics'] == 'own') {
				$own_entries = "and owned_by='$current_user'";
			}

			// Get all of the field translated names for the charts from the sort table for dropdowns
		    $dropdowns = $wpdb->get_results (
		    	"select * from $this->table_sort where field_type=10 order by sort_number asc"  
	        );

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

			$current_month = date('n');
			$lead_totals = $wpdb->get_results (
				"
				SELECT DATE_FORMAT(creation_date, '%Y') as 'year',
     			DATE_FORMAT(creation_date, '%m') as 'month',
     			COUNT(*) as 'total'
     			FROM $this->table_main WHERE (creation_date) >= CURDATE() - INTERVAL 1 YEAR
				$own_entries
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


			// dropdown array
			$dd_names = array();
			$dd_entries = array();
			$i = 1;
			foreach ($dropdowns as $k => $v) {
				$dd_names[$v->orig_name] = $wpdb->get_results (
					"select * from $this->table_sst WHERE sst_type_desc='$v->orig_name' order by sst_name asc"
				);
				foreach($dd_names[$v->orig_name] as $k2 => $v2) {
					$dd_count = $wpdb->get_var(
						"select count(*) as 'Total' 
						from $this->table_main 
						where $v->orig_name='$v2->sst_id' $own_entries"
					);
					$dd_entries[$v->orig_name][] = array(
						'value'     => $dd_count,
						'label'     => $v2->sst_name,
						'color'     => $colors[$i],
						'highlight' => $highlights[$i]
					);
					$i++;
					if ($i > 20) {
						$i = 1;
					}
				}
			}
			//print_r($dd_entries);

			// Ownership distribution
			$owners = $wpdb->get_results (
				"select count(*) as 'Total', owned_by as 'Owner' from $this->table_main group by owned_by"
			);
			$i = 1;
			foreach ($owners as $k => $owner) {
				$owner_entries[] = array(
					'value' => $owner->Total,
					'label' => $owner->Owner,
					'color' => $colors[$i],
					'highlight' => $highlights[$i]
				);
				$i++;
				if ($i > 25) {
					break; //exit loop
				}
			}
			usort( $owner_entries, array($this, 'sortByOrder') ); // sort descending
			$owner_entries = json_encode($owner_entries);



			// Titles
			$top_ten = __('Top 10', 'shwcp');
			$distribution = __('Distribution', 'shwcp');

			//Chart 1 title
			$chart1_title = __('New Entries', 'shwcp');
			$chart1_daily = __('Daily (1 Month)', 'shwcp');
			$chart1_weekly = __('Weekly (6 Months)', 'shwcp');
			$chart1_monthly = __('Monthly (1 Year)', 'shwcp');
			$chart2_title = __('Entry Ownership Distribution', 'shwcp');

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
						// Owner chart
                        var data2 = $owner_entries;

EOC;
			$inc = 3;
			foreach ($dd_entries as $k => $v) {
				$limited = array();
				usort( $v, array($this, 'sortByOrder') ); // sort them descending
            	for ($i2=0;$i2 <= 9;$i2++) {             // Limit to 10 results
                	if (!isset($v[$i2])) {
                    	break; // exit
                	}
                	$limited[] = $v[$i2];
            	}

				$encoded_array = json_encode($limited);
				$data .= <<<EOC

						// $k chart (doughnut)
						var data$inc = $encoded_array;
EOC;
				$inc++;
			};

			$data .= <<<EOC

						// Total leads chart
				    	var ctx = $("#monthlies").get(0).getContext("2d");
						window.myNewChart = new Chart(ctx);
						new Chart(ctx).Line(data, {
							bezierCurve: true, 
							responsive: true
						});
EOC;
			if ($this->current_access == 'full') {
                    $data .= <<<EOC

						// Owner chart
						var ctx2 = $("#owner-totals").get(0).getContext("2d");
						var myNewChart2 = new Chart(ctx2);
						new Chart(ctx2).PolarArea(data2, {
							scaleShowLabelBackdrop : true,
							scaleBackdropColor : "rgba(255,255,255,0.75)",
							scaleBeginAtZero : true,
							scaleShowLine : true,
							segmentShowStroke : true,
							segmentStrokeColor : "#fff",
							segmentStrokeWidth : 2,
							animationSteps : 100,
							animationEasing : "easeOutBounce",
							animateRotate : true,
							responsive: true
						});
EOC;
			}

			$inc = 3;
			foreach ($dd_entries as $k => $v) {
				$data .= <<<EOC
						// $k Chart
                        var ctx$inc = $("#$k").get(0).getContext("2d");
                        var myNewChart$inc = new Chart(ctx$inc);
                        new Chart(ctx$inc).Doughnut(data$inc, {
                            percentageInnerCutout : 10,
                            animationSteps: 100,
                            animationEasing : "easeOutBounce",
                            animateRotate : true,
                            animateScale : false,
                            responsive: true
                        });
EOC;
				$inc++;
			}


				$data .= <<<EOC

					});

				</script>


				<div class="row">
EOC;
				if ($this->current_access == 'full') {
                    $data .= <<<EOC

                    <div class="col-md-6 no-padding"> 
						<div class="chart-holder">
							<h3>$chart1_title</h3>
							<div class="new-lead-view">
								  <select class="lead-stats">
								    <option value="daily">$chart1_daily</option>
									<option value="weekly">$chart1_weekly</option>
									<option value="monthly" selected="selected">$chart1_monthly</option>
								  </select>
								 </div>
                        	<canvas id="monthlies" width="400" height="200"></canvas>
						</div>
                    </div>
                    <div class="col-md-6 no-padding">
						<div class="chart-holder">
							<h3>$chart2_title</h3>
                        	<canvas id="owner-totals" width="400" height="200"></canvas>
						</div>
                    </div>
EOC;
				} else {  // ownleads users don't see ownership distribution
					$data .= <<<EOC

                    <div class="col-md-4 no-padding">
                        <div class="chart-holder">
                            <h3>$chart1_title</h3>
                            <div class="new-lead-view">
                                  <select class="lead-stats">
                                    <option value="daily">$chart1_daily</option>
                                    <option value="weekly">$chart1_weekly</option>
                                    <option value="monthly" selected="selected">$chart1_monthly</option>
                                  </select>
                                 </div>
                            <canvas id="monthlies" width="400" height="200"></canvas>
                        </div>
                    </div>
EOC;
				}

				$data .= <<<EOC
EOC;

				foreach ($dropdowns as $k => $v) {

					$data .= <<<EOC
					<div class="col-md-4 no-padding">
						<div class="chart-holder">
							<h3>$top_ten $v->translated_name $distribution</h3>
							<canvas id="$v->orig_name" width="400" height="200"></canvas>
						</div>
					</div>
EOC;
				}

				$data .= <<<EOC
                </div>
EOC;

			
			return $data;
		}

		public function sortByOrder($a, $b) {
		    return $b['value'] - $a['value'];  // Descending
		}
	}
