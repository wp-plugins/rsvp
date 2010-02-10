<?php
/**
 * @package rsvp
 * @author MDE Development, LLC
 * @version 1.0.0
 */
/*
Plugin Name: RSVP 
Plugin URI: http://wordpress.org/#
Description: This plugin allows guests to RSVP to an event.  It was made 
             initially for weddings but could be used for other things.  
Author: MDE Development, LLC
Version: 0.5.0
Author URI: http://mde-dev.com
License: GPL
*/
#
# INSTALLATION: see readme.txt
#
# USAGE: Once the RSVP plugin has been installed, you can set the custom text 
#        via Settings -> RSVP Options in the  admin area. 
#      
#        To add, edit, delete and see rsvp status there will be a new RSVP admin
#        area just go there.
# 
#        To allow people to rsvp create a new page and add "rsvp_pluginhere" to the text

	session_start();
	define("ATTENDEES_TABLE", $wpdb->prefix."attendees");
	define("ASSOCIATED_ATTENDEES_TABLE", $wpdb->prefix."associatedAttendees");
	define("EDIT_SESSION_KEY", "RsvpEditAttendeeID");
	define("FRONTEND_TEXT_CHECK", "rsvp-pluginhere");
	define("OPTION_GREETING", "rsvp_custom_greeting");
	define("OPTION_THANKYOU", "rsvp_custom_thankyou");
	define("OPTION_DEADLINE", "rsvp_deadline");
	define("OPTION_OPENDATE", 'rsvp_opendate');
	define("OPTION_YES_VERBIAGE", "rsvp_yes_verbiage");
	define("OPTION_NO_VERBIAGE", "rsvp_no_verbiage");
	define("OPTION_KIDS_MEAL_VERBIAGE", "rsvp_kids_meal_verbiage");
	define("OPTION_VEGGIE_MEAL_VERBIAGE", "rsvp_veggie_meal_verbiage");
	define("OPTION_NOTE_VERBIAGE", "rsvp_note_verbiage");
	define("OPTION_HIDE_VEGGIE", "rsvp_hide_veggie");
	define("OPTION_HIDE_KIDS_MEAL", "rsvp_hide_kids_meal");
	
	if(isset($_GET['page']) && (strToLower($_GET['page']) == 'rsvp-admin-export')) {
		add_action('init', 'rsvp_admin_export');
	}
	/*
	 * Description: Database setup for the rsvp plug-in.  
	 */
	function rsvp_database_setup() {
		global $wpdb;
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		
		$table = $wpdb->prefix."attendees";
		if($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
			$sql = "CREATE TABLE ".$table." (
			`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`firstName` VARCHAR( 100 ) NOT NULL ,
			`lastName` VARCHAR( 100 ) NOT NULL ,
			`rsvpDate` DATE NOT NULL ,
			`rsvpStatus` ENUM( 'Yes', 'No', 'NoResponse' ) NOT NULL DEFAULT 'NoResponse',
			`note` TEXT NOT NULL ,
			`kidsMeal` ENUM( 'Y', 'N' ) NOT NULL DEFAULT 'N',
			`additionalAttendee` ENUM( 'Y', 'N' ) NOT NULL DEFAULT 'N',
			`veggieMeal` ENUM( 'Y', 'N' ) NOT NULL DEFAULT 'N'
			);";
			dbDelta($sql);
		}
		$table = $wpdb->prefix."associatedAttendees";
		if($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
			$sql = "CREATE TABLE ".$table." (
			`attendeeID` INT NOT NULL ,
			`associatedAttendeeID` INT NOT NULL
			);";
			dbDelta($sql);
			$sql = "ALTER TABLE `".$table."` ADD INDEX ( `attendeeID` ) ";
			dbDelta($sql);
			$sql = "ALTER TABLE `".$table."` ADD INDEX ( `associatedAttendeeID` )";
			dbDelta($sql);
		}				
		add_option("rsvp_db_version", "1.0");
	}

	function rsvp_admin_guestlist_options() {
?>
		<link rel="stylesheet" href="<?php echo get_option("siteurl"); ?>/wp-content/plugins/rsvp/jquery-ui-1.7.2.custom/css/ui-lightness/jquery-ui-1.7.2.custom.css" type="text/css" media="all" />
		<script type="text/javascript" language="javascript" 
			src="<?php echo get_option("siteurl"); ?>/wp-content/plugins/rsvp/jquery-ui-1.7.2.custom/js/jquery-1.3.2.min.js"></script>
		<script type="text/javascript" language="javascript" 
			src="<?php echo get_option("siteurl"); ?>/wp-content/plugins/rsvp/jquery-ui-1.7.2.custom/js/jquery-ui-1.7.2.custom.min.js"></script>
		<script type="text/javascript" language="javascript">
			$(document).ready(function() {
				$("#rsvp_opendate").datepicker();
				$("#rsvp_deadline").datepicker();
			});
		</script>
		<div class="wrap">
			<h2>RSVP Guestlist Options</h2>
			<form method="post" action="options.php">
				<?php wp_nonce_field('update-options'); ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><label for="rsvp_opendate">RSVP Open Date:</label></th>
						<td align="left"><input type="text" name="rsvp_opendate" id="rsvp_opendate" value="<?php echo htmlspecialchars(get_option(OPTION_OPENDATE)); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="rsvp_deadline">RSVP Deadline:</label></th>
						<td align="left"><input type="text" name="rsvp_deadline" id="rsvp_deadline" value="<?php echo htmlspecialchars(get_option(OPTION_DEADLINE)); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="rsvp_custom_greeting">Custom Greeting:</label></th>
						<td align="left"><textarea name="rsvp_custom_greeting" id="rsvp_custom_greeting" rows="5" cols="60"><?php echo htmlspecialchars(get_option(OPTION_GREETING)); ?></textarea></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="rsvp_yes_verbiage">RSVP Yes Verbiage:</label></th>
						<td align="left"><input type="text" name="rsvp_yes_verbiage" id="rsvp_yes_verbiage" 
							value="<?php echo htmlspecialchars(get_option(OPTION_YES_VERBIAGE)); ?>" size="65" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="rsvp_no_verbiage">RSVP No Verbiage:</label></th>
						<td align="left"><input type="text" name="rsvp_no_verbiage" id="rsvp_no_verbiage" 
							value="<?php echo htmlspecialchars(get_option(OPTION_NO_VERBIAGE)); ?>" size="65" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="rsvp_kids_meal_verbiage">RSVP Kids Meal Verbiage:</label></th>
						<td align="left"><input type="text" name="rsvp_kids_meal_verbiage" id="rsvp_kids_meal_verbiage" 
							value="<?php echo htmlspecialchars(get_option(OPTION_KIDS_MEAL_VERBIAGE)); ?>" size="65" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="rsvp_hide_kids_meal">Hide Kids Meal Question:</label></th>
						<td align="left"><input type="checkbox" name="rsvp_hide_kids_meal" id="rsvp_hide_kids_meal" 
							value="Y" <?php echo ((get_option(OPTION_HIDE_KIDS_MEAL) == "Y") ? " checked=\"checked\"" : ""); ?> /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="rsvp_veggie_meal_verbiage">RSVP Vegetarian Meal Verbiage:</label></th>
						<td align="left"><input type="text" name="rsvp_veggie_meal_verbiage" id="rsvp_veggie_meal_verbiage" 
							value="<?php echo htmlspecialchars(get_option(OPTION_VEGGIE_MEAL_VERBIAGE)); ?>" size="65" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="rsvp_hide_veggie">Hide Vegetarian Meal Question:</label></th>
						<td align="left"><input type="checkbox" name="rsvp_hide_veggie" id="rsvp_hide_veggie" 
							value="Y" <?php echo ((get_option(OPTION_HIDE_VEGGIE) == "Y") ? " checked=\"checked\"" : ""); ?> /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="rsvp_note_verbiage">Note Verbiage:</label></th>
						<td align="left"><textarea name="rsvp_note_verbiage" id="rsvp_note_verbiage" rows="3" cols="60"><?php 
							echo htmlspecialchars(get_option(OPTION_NOTE_VERBIAGE)); ?></textarea></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="rsvp_custom_thankyou">Custom Thank You:</label></th>
						<td align="left"><textarea name="rsvp_custom_thankyou" id="rsvp_custom_thankyou" rows="5" cols="60"><?php echo htmlspecialchars(get_option(OPTION_THANKYOU)); ?></textarea></td>
					</tr>
				</table>
				<input type="hidden" name="action" value="update" />
				<p class="submit">
					<input type="hidden" name="page_options" 
						value="rsvp_opendate,rsvp_deadline,rsvp_custom_greeting,rsvp_custom_thankyou,rsvp_no_verbiage,rsvp_yes_verbiage,rsvp_kids_meal_verbiage,rsvp_veggie_meal_verbiage,rsvp_note_verbiage,rsvp_hide_kids_meal,rsvp_hide_veggie" />
					<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
				</p>
			</form>
		</div>
<?php
	}
	
	function rsvp_admin_guestlist() {
		global $wpdb;		
		if((count($_POST) > 0) && ($_POST['rsvp-bulk-action'] == "delete") && (is_array($_POST['attendee']) && (count($_POST['attendee']) > 0))) {
			foreach($_POST['attendee'] as $attendee) {
				if(is_numeric($attendee) && ($attendee > 0)) {
					$wpdb->query($wpdb->prepare("DELETE FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE attendeeID = %d OR associatedAttendeeID = %d", 
																			$attendee, 
																			$attendee));
					$wpdb->query($wpdb->prepare("DELETE FROM ".ATTENDEES_TABLE." WHERE id = %d", 
																			$attendee));
				}
			}
		}
		
		$sql = "SELECT id, firstName, lastName, rsvpStatus, note, kidsMeal, additionalAttendee, veggieMeal FROM ".ATTENDEES_TABLE;
		$orderBy = " lastName, firstName";
		if(isset($_GET['sort'])) {
			if(strToLower($_GET['sort']) == "rsvpstatus") {
				$orderBy = " rsvpStatus ".((strtolower($_GET['sortDirection']) == "desc") ? "DESC" : "ASC") .", ".$orderBy;
			}else if(strToLower($_GET['sort']) == "attendee") {
				$direction = ((strtolower($_GET['sortDirection']) == "desc") ? "DESC" : "ASC");
				$orderBy = " lastName $direction, firstName $direction";
			}	else if(strToLower($_GET['sort']) == "kidsmeal") {
				$orderBy = " kidsMeal ".((strtolower($_GET['sortDirection']) == "desc") ? "DESC" : "ASC") .", ".$orderBy;
			}	else if(strToLower($_GET['sort']) == "additional") {
				$orderBy = " additionalAttendee ".((strtolower($_GET['sortDirection']) == "desc") ? "DESC" : "ASC") .", ".$orderBy;
			}	else if(strToLower($_GET['sort']) == "vegetarian") {
				$orderBy = " veggieMeal ".((strtolower($_GET['sortDirection']) == "desc") ? "DESC" : "ASC") .", ".$orderBy;
			}			
		}
		$sql .= " ORDER BY ".$orderBy;
		$attendees = $wpdb->get_results($sql);
	?>
		<div class="wrap">	
			<div id="icon-edit" class="icon32"><br /></div>	
			<h2>List of current attendees</h2>
			<form method="post" id="rsvp-form" enctype="multipart/form-data">
				<input type="hidden" id="rsvp-bulk-action" name="rsvp-bulk-action" />
				<div class="tablenav">
					<div class="alignleft actions">
						<select id="rsvp-action-top" name="action">
							<option value="" selected="selected"><?php _e('Bulk Actions', 'rsvp'); ?></option>
							<option value="delete"><?php _e('Delete', 'rsvp'); ?></option>
						</select>
						<input type="submit" value="<?php _e('Apply', 'rsvp'); ?>" name="doaction" id="doaction" class="button-secondary action" onclick="document.getElementById('rsvp-bulk-action').value = document.getElementById('rsvp-action-top').value;" />
					</div>
					<?php
						$yesResults = $wpdb->get_results("SELECT COUNT(*) AS yesCount FROM ".ATTENDEES_TABLE." WHERE rsvpStatus = 'Yes'");
						$noResults = $wpdb->get_results("SELECT COUNT(*) AS noCount FROM ".ATTENDEES_TABLE." WHERE rsvpStatus = 'No'");
						$noResponseResults = $wpdb->get_results("SELECT COUNT(*) AS noResponseCount FROM ".ATTENDEES_TABLE." WHERE rsvpStatus = 'NoResponse'");
					?>
					<div class="alignright">RSVP Count -  
						Yes: <strong><?php echo $yesResults[0]->yesCount; ?></strong> &nbsp; &nbsp;  &nbsp; &nbsp; 
						No: <strong><?php echo $noResults[0]->noCount; ?></strong> &nbsp; &nbsp;  &nbsp; &nbsp; 
						No Response: <strong><?php echo $noResponseResults[0]->noResponseCount; ?></strong>
					</div>
					<div class="clear"></div>
				</div>
			<table class="widefat post fixed" cellspacing="0">
				<thead>
					<tr>
						<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
						<th scope="col" id="attendeeName" class="manage-column column-title" style="">Attendee</a> &nbsp;
							<a href="admin.php?page=rsvp-top-level&amp;sort=attendee&amp;sortDirection=asc">
								<img src="<?php echo get_option("siteurl"); ?>/wp-content/plugins/rsvp/uparrow<?php 
									echo ((($_GET['sort'] == "attendee") && ($_GET['sortDirection'] == "asc")) ? "_selected" : ""); ?>.gif" width="11" height="9" 
									alt="Sort Ascending Attendee Status" title="Sort Ascending Attendee Status" border="0"></a> &nbsp;
							<a href="admin.php?page=rsvp-top-level&amp;sort=attendee&amp;sortDirection=desc">
								<img src="<?php echo get_option("siteurl"); ?>/wp-content/plugins/rsvp/downarrow<?php 
									echo ((($_GET['sort'] == "attendee") && ($_GET['sortDirection'] == "desc")) ? "_selected" : ""); ?>.gif" width="11" height="9" 
									alt="Sort Descending Attendee Status" title="Sort Descending Attendee Status" border="0"></a>
						</th>			
						<th scope="col" id="rsvpStatus" class="manage-column column-title" style="">RSVP Status &nbsp;
							<a href="admin.php?page=rsvp-top-level&amp;sort=rsvpStatus&amp;sortDirection=asc">
								<img src="<?php echo get_option("siteurl"); ?>/wp-content/plugins/rsvp/uparrow<?php 
									echo ((($_GET['sort'] == "rsvpStatus") && ($_GET['sortDirection'] == "asc")) ? "_selected" : ""); ?>.gif" width="11" height="9" 
									alt="Sort Ascending RSVP Status" title="Sort Ascending RSVP Status" border="0"></a> &nbsp;
							<a href="admin.php?page=rsvp-top-level&amp;sort=rsvpStatus&amp;sortDirection=desc">
								<img src="<?php echo get_option("siteurl"); ?>/wp-content/plugins/rsvp/downarrow<?php 
									echo ((($_GET['sort'] == "rsvpStatus") && ($_GET['sortDirection'] == "desc")) ? "_selected" : ""); ?>.gif" width="11" height="9" 
									alt="Sort Descending RSVP Status" title="Sort Descending RSVP Status" border="0"></a>
						</th>
						<?php if(get_option(OPTION_HIDE_KIDS_MEAL) != "Y") {?>
						<th scope="col" id="kidsMeal" class="manage-column column-title" style="">Kids Meal	 &nbsp;
								<a href="admin.php?page=rsvp-top-level&amp;sort=kidsMeal&amp;sortDirection=asc">
									<img src="<?php echo get_option("siteurl"); ?>/wp-content/plugins/rsvp/uparrow<?php 
										echo ((($_GET['sort'] == "kidsMeal") && ($_GET['sortDirection'] == "asc")) ? "_selected" : ""); ?>.gif" width="11" height="9" 
										alt="Sort Ascending Kids Meal Status" title="Sort Ascending Kids Meal Status" border="0"></a> &nbsp;
								<a href="admin.php?page=rsvp-top-level&amp;sort=kidsMeal&amp;sortDirection=desc">
									<img src="<?php echo get_option("siteurl"); ?>/wp-content/plugins/rsvp/downarrow<?php 
										echo ((($_GET['sort'] == "kidsMeal") && ($_GET['sortDirection'] == "desc")) ? "_selected" : ""); ?>.gif" width="11" height="9" 
										alt="Sort Descending Kids Meal Status" title="Sort Descending Kids Meal Status" border="0"></a>
						</th>
						<?php } ?>
						<th scope="col" id="additionalAttendee" class="manage-column column-title" style="">Additional Attendee		 &nbsp;
									<a href="admin.php?page=rsvp-top-level&amp;sort=additional&amp;sortDirection=asc">
										<img src="<?php echo get_option("siteurl"); ?>/wp-content/plugins/rsvp/uparrow<?php 
											echo ((($_GET['sort'] == "additional") && ($_GET['sortDirection'] == "asc")) ? "_selected" : ""); ?>.gif" width="11" height="9" 
											alt="Sort Ascending Additional Attendees Status" title="Sort Ascending Additional Attendees Status" border="0"></a> &nbsp;
									<a href="admin.php?page=rsvp-top-level&amp;sort=additional&amp;sortDirection=desc">
										<img src="<?php echo get_option("siteurl"); ?>/wp-content/plugins/rsvp/downarrow<?php 
											echo ((($_GET['sort'] == "additional") && ($_GET['sortDirection'] == "desc")) ? "_selected" : ""); ?>.gif" width="11" height="9" 
											alt="Sort Descending Additional Attendees Status" title="Sort Descending Additional Atttendees Status" border="0"></a>
						</th>
						<?php if(get_option(OPTION_HIDE_VEGGIE) != "Y") {?>
						<th scope="col" id="veggieMeal" class="manage-column column-title" style="">Vegetarian			 &nbsp;
										<a href="admin.php?page=rsvp-top-level&amp;sort=vegetarian&amp;sortDirection=asc">
											<img src="<?php echo get_option("siteurl"); ?>/wp-content/plugins/rsvp/uparrow<?php 
												echo ((($_GET['sort'] == "vegetarian") && ($_GET['sortDirection'] == "asc")) ? "_selected" : ""); ?>.gif" width="11" height="9" 
												alt="Sort Ascending Vegetarian Status" title="Sort Ascending Vegetarian Status" border="0"></a> &nbsp;
										<a href="admin.php?page=rsvp-top-level&amp;sort=vegetarian&amp;sortDirection=desc">
											<img src="<?php echo get_option("siteurl"); ?>/wp-content/plugins/rsvp/downarrow<?php 
												echo ((($_GET['sort'] == "vegetarian") && ($_GET['sortDirection'] == "desc")) ? "_selected" : ""); ?>.gif" width="11" height="9" 
												alt="Sort Descending Vegetarian Status" title="Sort Descending Vegetarian Status" border="0"></a>
						</th>
						<?php } ?>
						<th scope="col" id="note" class="manage-column column-title" style="">Note</th>
						<th scope="col" id="associatedAttendees" class="manage-column column-title" style="">Associated Attendees</th>
					</tr>
				</thead>
			</table>
			<div style="overflow: auto;height: 450px;">
				<table class="widefat post fixed" cellspacing="0">
				<?php
					$i = 0;
					foreach($attendees as $attendee) {
					?>
						<tr class="<?php echo (($i % 2 == 0) ? "alternate" : ""); ?> author-self">
							<th scope="row" class="check-column"><input type="checkbox" name="attendee[]" value="<?php echo $attendee->id; ?>" /></th>						
							<td>
								<a href="<?php echo get_option("siteurl"); ?>/wp-admin/admin.php?page=rsvp-admin-guest&amp;id=<?php echo $attendee->id; ?>"><?php echo htmlentities(stripslashes($attendee->firstName)." ".stripslashes($attendee->lastName)); ?></a>
							</td>
							<td><?php echo $attendee->rsvpStatus; ?></td>
							<?php if(get_option(OPTION_HIDE_KIDS_MEAL) != "Y") {?>
							<td><?php 
								if($attendee->rsvpStatus == "NoResponse") {
									echo "--";
								} else {
									echo (($attendee->kidsMeal == "Y") ? "Yes" : "No"); 
								}?></td>
								<?php } ?>
							<td><?php 
								if($attendee->rsvpStatus == "NoResponse") {
									echo "--";
								} else {
									echo (($attendee->additionalAttendee == "Y") ? "Yes" : "No"); 
								}
							?></td>
							<?php if(get_option(OPTION_HIDE_VEGGIE) != "Y") {?>
							<td><?php 
								if($attendee->rsvpStatus == "NoResponse") {
									echo "--";
								} else {
									echo (($attendee->veggieMeal == "Y") ? "Yes" : "No"); 
								}	
									?></td>
							<?php } ?>
							<td><?php
								echo nl2br(stripslashes(trim($attendee->note)));
							?></td>
							<td>
							<?php
								$sql = "SELECT firstName, lastName FROM ".ATTENDEES_TABLE." 
								 	WHERE id IN (SELECT attendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE associatedAttendeeID = %d) 
										OR id in (SELECT associatedAttendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE attendeeID = %d)";
							
								$associations = $wpdb->get_results($wpdb->prepare($sql, $attendee->id, $attendee->id));
								foreach($associations as $a) {
									echo htmlentities($a->firstName." ".$a->lastName)."<br />";
								}
							?>
							</td>
						</tr>
					<?php
						$i++;
					}
				?>
				</table>
			</div>
			</form>
		</div>
	<?php
	}
	
	function rsvp_admin_export() {
		global $wpdb;
			$sql = "SELECT id, firstName, lastName, rsvpStatus, note, kidsMeal, additionalAttendee, veggieMeal 
							FROM ".ATTENDEES_TABLE.
							" ORDER BY lastName, firstName";
			$attendees = $wpdb->get_results($sql);
			$csv = "\"Attendee\",\"RSVP Status\",";
			
			if(get_option(OPTION_HIDE_KIDS_MEAL) != "Y") {
				$csv .= "\"Kids Meal\",";
			}
			$csv .= "\"Additional Attendee\",";
			
			if(get_option(OPTION_HIDE_VEGGIE) != "Y") {
				$csv .= "\"Vegatarian\",";
			}
			$csv .= "\"Note\",\"Associated Attendees\"\r\n";
			
			foreach($attendees as $a) {
				$csv .= "\"".stripslashes($a->firstName." ".$a->lastName)."\",\"".($a->rsvpStatus)."\",";
				
				if(get_option(OPTION_HIDE_KIDS_MEAL) != "Y") {
					$csv .= "\"".(($a->kidsMeal == "Y") ? "Yes" : "No")."\",";
				}
				
				$csv .= "\"".(($a->additionalAttendee == "Y") ? "Yes" : "No")."\",";
				
				if(get_option(OPTION_HIDE_VEGGIE) != "Y") {
					$csv .= "\"".(($a->veggieMeal == "Y") ? "Yes" : "No")."\",";
				}
				
				$csv .= "\"".(str_replace("\"", "\"\"", stripslashes($a->note)))."\",\"";
			
				$sql = "SELECT firstName, lastName FROM ".ATTENDEES_TABLE." 
				 	WHERE id IN (SELECT attendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE associatedAttendeeID = %d) 
						OR id in (SELECT associatedAttendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE attendeeID = %d)";
		
				$associations = $wpdb->get_results($wpdb->prepare($sql, $a->id, $a->id));
				foreach($associations as $a) {
					$csv .= stripslashes($a->firstName." ".$a->lastName)."\r\n";
				}
				$csv .= "\"\r\n";
			}
			if(isset($_SERVER['HTTP_USER_AGENT']) && preg_match("/MSIE/", $_SERVER['HTTP_USER_AGENT'])) {
				// IE Bug in download name workaround
				ini_set( 'zlib.output_compression','Off' );
			}
			header('Content-Description: RSVP Export');
			header("Content-Type: application/vnd.ms-excel", true);
			header('Content-Disposition: attachment; filename="rsvpEntries.csv"'); 
			echo $csv;
			exit();
	}
	
	function rsvp_admin_import() {
		global $wpdb;
		if(count($_FILES) > 0) {
			check_admin_referer('rsvp-import');
			require_once("Excel/reader.php");
			$data = new Spreadsheet_Excel_Reader();
			$data->read($_FILES['importFile']['tmp_name']);
			if($data->sheets[0]['numCols'] >= 2) {
				$count = 0;
				for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) {
					$fName = trim($data->sheets[0]['cells'][$i][1]);
					$lName = trim($data->sheets[0]['cells'][$i][2]);
					if(!empty($fName) && !empty($lName)) {
						$wpdb->insert(ATTENDEES_TABLE, array("firstName" => $fName, "lastName" => $lName), array('%s', '%s'));
						$count++;
					}
				}
			?>
			<p><strong><?php echo $count; ?></strong> total records were imported.</p>
			<p>Continue to the RSVP <a href="admin.php?page=rsvp-top-level">list</a></p>
			<?php
			}
		} else {
		?>
			<form name="rsvp_import" method="post" enctype="multipart/form-data">
				<?php wp_nonce_field('rsvp-import'); ?>
				<p>Select an excel file (only xls please, xlsx is not supported....yet) with the first name in <strong>column A</strong> and the last name in <strong>column B</strong>. A header row is not expected</p>
				<p><input type="file" name="importFile" id="importFile" /></p>
				<p><input type="submit" value="Import File" name="goRsvp" /></p>
			</form>
		<?php
		}
	}
	
	function rsvp_admin_guest() {
		global $wpdb;
		if((count($_POST) > 0) && !empty($_POST['firstName']) && !empty($_POST['lastName'])) {
			check_admin_referer('rsvp_add_guest');
			if(isset($_SESSION[EDIT_SESSION_KEY]) && is_numeric($_SESSION[EDIT_SESSION_KEY])) {
				$wpdb->update(ATTENDEES_TABLE, 
											array("firstName" => trim($_POST['firstName']), "lastName" => trim($_POST['lastName'])), 
											array("id" => $_SESSION[EDIT_SESSION_KEY]), 
											array("%s", "%s"), 
											array("%d"));
				$attendeeId = $_SESSION[EDIT_SESSION_KEY];
				$wpdb->query($wpdb->prepare("DELETE FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE attendeeId = %d", $attendeeId));
			} else {
				$wpdb->insert(ATTENDEES_TABLE, array("firstName" => trim($_POST['firstName']), "lastName" => trim($_POST['lastName'])), array('%s', '%s'));
				$attendeeId = $wpdb->insert_id;
			}
			foreach($_POST['associatedAttendees'] as $aid) {
				if(is_numeric($aid) && ($aid > 0)) {
					$wpdb->insert(ASSOCIATED_ATTENDEES_TABLE, array("attendeeID"=>$attendeeId, "associatedAttendeeID"=>$aid), array("%d", "%d"));
				}
			}
		?>
			<p>Attendee <?php echo htmlentities($_POST['firstName']." ".$_POST['lastName']);?> has been successfully saved</p>
			<p>
				<a href="<?php echo get_option('siteurl'); ?>/wp-admin/admin.php?page=rsvp-top-level">Continue to Attendee List</a> | 
				<a href="<?php echo get_option('siteurl'); ?>/wp-admin/admin.php?page=rsvp-admin-guest">Add a Guest</a> 
			</p>
	<?php
		} else {
			$attendee = null;
			session_unregister(EDIT_SESSION_KEY);
			$associatedAttendees = array();
			$firstName = "";
			$lastName = "";
			
			if(isset($_GET['id']) && is_numeric($_GET['id'])) {
				$attendee = $wpdb->get_row("SELECT id, firstName, lastName FROM ".ATTENDEES_TABLE." WHERE id = ".$_GET['id']);
				if($attendee != null) {
					$_SESSION[EDIT_SESSION_KEY] = $attendee->id;
					$firstName = stripslashes($attendee->firstName);
					$lastName = stripslashes($attendee->lastName);
					
					// Get the associated attendees and add them to an array
					$associations = $wpdb->get_results("SELECT associatedAttendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE attendeeId = ".$attendee->id.
																						 " UNION ".
																						 "SELECT attendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE associatedAttendeeID = ".$attendee->id);
					foreach($associations as $aId) {
						$associatedAttendees[] = $aId->associatedAttendeeID;
					}
				} 
			} 
	?>
			<form name="contact" action="admin.php?page=rsvp-admin-guest" method="post">
				<?php wp_nonce_field('rsvp_add_guest'); ?>
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e('Save'); ?>" />
				</p>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><label for="firstName">First Name:</label></th>
						<td align="left"><input type="text" name="firstName" id="firstName" size="30" value="<?php echo htmlentities($firstName); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="lastName">Last Name:</label></th>
						<td align="left"><input type="text" name="lastName" id="lastName" size="30" value="<?php echo htmlentities($lastName); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">Associated Attendees:</th>
						<td align="left">
							<select name="associatedAttendees[]" multiple="multiple" size="5" style="height: 2000px;">
								<?php
									$attendees = $wpdb->get_results("SELECT id, firstName, lastName FROM ".$wpdb->prefix."attendees ORDER BY lastName, firstName");
									foreach($attendees as $attendee) {
										if($attendee->id != $_SESSION[EDIT_SESSION_KEY]) {
								?>
											<option value="<?php echo $attendee->id; ?>" 
															<?php echo ((in_array($attendee->id, $associatedAttendees)) ? "selected=\"selected\"" : ""); ?>><?php echo htmlentities(stripslashes($attendee->firstName)." ".stripslashes($attendee->lastName)); ?></option>
								<?php
										}
									}
								?>
							</select>
						</td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e('Save'); ?>" />
				</p>
			</form>
<?php
		}
	}
	
	function rsvp_frontend_handler($text) {
		global $wpdb; 
		
		//QUIT if the replacement string doesn't exist
		if (!strstr($text,FRONTEND_TEXT_CHECK)) return $text;
		
		// See if we should allow people to RSVP, etc...
		$openDate = get_option(OPTION_OPENDATE);
		$closeDate = get_option(OPTION_DEADLINE);
		if((strtotime($openDate) !== false) && (strtotime($openDate) > time())) {
			return "<p>I am sorry but the ability to RSVP for our wedding won't open till <strong>".date("m/d/Y", strtotime($openDate))."</strong></p>";
		} 
		
		if((strtotime($closeDate) !== false) && (strtotime($closeDate) < time())) {
			return "<p>The deadline to RSVP for this wedding has passed, please contact the bride and groom to see if there is still a seat for you.</p>";
		}
		
		if(isset($_POST['rsvpStep'])) {
			switch(strtolower($_POST['rsvpStep'])) {
				case("handlersvp") :
					if(is_numeric($_POST['attendeeID']) && ($_POST['attendeeID'] > 0)) {
						// update their information and what not....
						if(strToUpper($_POST['mainRsvp']) == "Y") {
							$rsvpStatus = "Yes";
						} else {
							$rsvpStatus = "No";
						}
						$attendeeID = $_POST['attendeeID'];
						$wpdb->update(ATTENDEES_TABLE, array("rsvpDate" => date("Y-m-d"), 
																								 "rsvpStatus" => $rsvpStatus, 
																								 "note" => $_POST['note'], 
																								 "kidsMeal" => ((strToUpper($_POST['mainKidsMeal']) == "Y") ? "Y" : "N"), 
																								 "veggieMeal" => ((strToUpper($_POST['mainVeggieMeal']) == "Y") ? "Y" : "N")), 
																					array("id" => $attendeeID), 
																					array("%s", "%s", "%s", "%s", "%s"), 
																					array("%d"));
						$sql = "SELECT id FROM ".ATTENDEES_TABLE." 
						 	WHERE (id IN (SELECT attendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE associatedAttendeeID = %d) 
								OR id in (SELECT associatedAttendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE attendeeID = %d)) 
								 AND rsvpStatus = 'NoResponse'";
						$associations = $wpdb->get_results($wpdb->prepare($sql, $attendeeID, $attendeeID));
						foreach($associations as $a) {
							if($_POST['rsvpFor'.$a->id] == "Y") {
								if($_POST['attending'.$a->id] == "Y") {
									$rsvpStatus = "Yes";
								} else {
									$rsvpStatus = "No";
								}
								$wpdb->update(ATTENDEES_TABLE, array("rsvpDate" => date("Y-m-d"), 
																										 "rsvpStatus" => $rsvpStatus, 
																										  "kidsMeal" => ((strToUpper($_POST['attending'.$a->id.'KidsMeal']) == "Y") ? "Y" : "N"), 
																										  "veggieMeal" => ((strToUpper($_POST['attending'.$a->id.'VeggieMeal']) == "Y") ? "Y" : "N")),
																							 array("id" => $a->id), 
																							 array("%s", "%s", "%s", "%s"), 
																							 array("%d"));
							}
						}
						
						if(is_numeric($_POST['additionalRsvp']) && ($_POST['additionalRsvp'] > 0)) {
							for($i = 1; $i <= $_POST['additionalRsvp']; $i++) {
								if(($i <= 3) && 
								   !empty($_POST['newAttending'.$i.'FirstName']) && 
								   !empty($_POST['newAttending'.$i.'LastName'])) {									
									$wpdb->insert(ATTENDEES_TABLE, array("firstName" => trim($_POST['newAttending'.$i.'FirstName']), 
																											 "lastName" => trim($_POST['newAttending'.$i.'LastName']), 
																											 "rsvpDate" => date("Y-m-d"), 
																											 "rsvpStatus" => (($_POST['newAttending'.$i] == "Y") ? "Yes" : "No"), 
																											 "kidsMeal" => $_POST['newAttending'.$i.'KidsMeal'], 
																											 "veggieMeal" => $_POST['newAttending'.$i.'VeggieMeal'], 
																											 "additionalAttendee" => "Y"), 
																								array('%s', '%s', '%s', '%s', '%s', '%s'));
									$newAid = $wpdb->insert_id;
									// Add associations for this new user
									$wpdb->insert(ASSOCIATED_ATTENDEES_TABLE, array("attendeeID" => $newAid, 
																																	"associatedAttendeeID" => $attendeeID), 
																														array("%d", "%d"));
									$wpdb->query($wpdb->prepare("INSERT INTO ".ASSOCIATED_ATTENDEES_TABLE."(attendeeID, associatedAttendeeID)
																							 SELECT ".$newAid.", associatedAttendeeID 
																							 FROM ".ASSOCIATED_ATTENDEES_TABLE." 
																							 WHERE attendeeID = ".$attendeeID));
								}
							}
						}
						
						return frontend_rsvp_thankyou();
					} else {
						return rsvp_frontend_greeting();
					}
					break;
				case("editattendee") :
					if(is_numeric($_POST['attendeeID']) && ($_POST['attendeeID'] > 0)) {
						// Try to find the user.
						$attendee = $wpdb->get_row($wpdb->prepare("SELECT id, firstName, lastName, rsvpStatus 
																											 FROM ".ATTENDEES_TABLE." 
																											 WHERE id = %d", $_POST['attendeeID']));
						if($attendee != null) {
							$output .= "<div>\r\n";
							$output .= "<p>Welcome back ".htmlentities($attendee->firstName." ".$attendee->lastName)."!</p>";
							$output .= rsvp_frontend_main_form($attendee->id);
							return $output."</div>\r\n";
						}
					}
					break;
				case("foundattendee") :
					if(is_numeric($_POST['attendeeID']) && ($_POST['attendeeID'] > 0)) {
						// Try to find the user.
						$attendee = $wpdb->get_row($wpdb->prepare("SELECT id, firstName, lastName, rsvpStatus 
																											 FROM ".ATTENDEES_TABLE." 
																											 WHERE id = %d", $_POST['attendeeID']));
						if($attendee != null) {
							$output = "<div>\r\n";
							if(strtolower($attendee->rsvpStatus) == "noresponse") {
								$output .= "<p>Hi ".htmlentities($attendee->firstName." ".$attendee->lastName)."!</p>".
													"<p>There are a few more questions we need to ask you if you could please fill them out below to finish up the RSVP process.</p>";
								$output .= rsvp_frontend_main_form($attendee->id);
							} else {
								$output .= rsvp_frontend_prompt_to_edit($attendee);
							}
							return $output."</div>\r\n";
						} 
						
						return rsvp_frontend_greeting();
					} else {
						return rsvp_frontend_greeting();
					}
					break;
				case("find") :
					$_SESSION['rsvpFirstName'] = $_POST['firstName'];
					$_SESSION['rsvpLastName'] = $_POST['lastName'];
					$firstName = $_POST['firstName'];
					$lastName = $_POST['lastName'];
					
					if((strlen($_POST['firstName']) <= 1) || (strlen($_POST['lastName']) <= 1)) {
						$output = "<p style=\"color:red\">A first and last name must be specified</p>\r\n";
						$output .= rsvp_frontend_greeting();
						
						return $output;
					}
					
					// Try to find the user.
					$attendee = $wpdb->get_row($wpdb->prepare("SELECT id, firstName, lastName, rsvpStatus 
																										 FROM ".ATTENDEES_TABLE." 
																										 WHERE firstName = %s AND lastName = %s", $firstName, $lastName));
					if($attendee != null) {
						// hey we found something, we should move on and print out any associated users and let them rsvp
						$output = "<div>\r\n";
						if(strtolower($attendee->rsvpStatus) == "noresponse") {
							$output .= "<p>Hi ".htmlentities($attendee->firstName." ".$attendee->lastName)."!</p>".
												"<p>There are a few more questions we need to ask you if you could please fill them out below to finish up the RSVP process.</p>";
							$output .= rsvp_frontend_main_form($attendee->id);
						} else {
							$output .= rsvp_frontend_prompt_to_edit($attendee);
						}
						return $output."</div>\r\n";
					}
					
					// We did not find anyone let's try and do a rough search
					$attendees = null;
					for($i = 3; $i >= 1; $i--) {
						$truncFirstName = rsvp_chomp_name($firstName, $i);
						$attendees = $wpdb->get_results("SELECT id, firstName, lastName, rsvpStatus FROM ".ATTENDEES_TABLE." 
																						 WHERE lastName = '".mysql_real_escape_string($lastName)."' AND firstName LIKE '".mysql_real_escape_string($truncFirstName)."%'");
						if(count($attendees) > 0) {
							$output = "<p><strong>We could not find an exact match but could any of the below entries be you?</strong></p>";
							foreach($attendees as $a) {
								$output .= "<form method=\"post\">\r\n
												<input type=\"hidden\" name=\"rsvpStep\" value=\"foundattendee\" />\r\n
												<input type=\"hidden\" name=\"attendeeID\" value=\"".$a->id."\" />\r\n
												<p style=\"text-align:left;\">\r\n
										".htmlentities($a->firstName." ".$a->lastName)." 
										<input type=\"submit\" value=\"RSVP\" />\r\n
										</p>\r\n</form>\r\n";
							}
							
							return $output;
						} else {
							$i = strlen($truncFirstName);
						}
					}
					return "<p><strong>We were unable to find anyone with a name of ".htmlentities($firstName." ".$lastName)."</strong></p>\r\n".rsvp_frontend_greeting();
					break;
				case("newsearch"):
				default:
					return rsvp_frontend_greeting();
					break;
			}
		} else {
			return rsvp_frontend_greeting();
		}
	}
	
	function rsvp_frontend_prompt_to_edit($attendee) {
		$prompt = "<p>Hi ".htmlentities($attendee->firstName." ".$attendee->lastName)." it looks like you have already RSVP'd. 
									Would you like to edit your reservation?</p>";
		$prompt .= "<form method=\"post\">\r\n
									<input type=\"hidden\" name=\"attendeeID\" value=\"".$attendee->id."\" />
									<input type=\"hidden\" name=\"rsvpStep\" id=\"rsvpStep\" value=\"editattendee\" />
									<input type=\"submit\" value=\"Yes\" onclick=\"document.getElementById('rsvpStep').value='editattendee';\" />
									<input type=\"submit\" value=\"No\" onclick=\"document.getElementById('rsvpStep').value='newsearch';\"  />
								</form>\r\n";
		return $prompt;
	}
	
	function rsvp_frontend_main_form($attendeeID) {
		global $wpdb;
		$attendee = $wpdb->get_row($wpdb->prepare("SELECT id, firstName, lastName, rsvpStatus, note, kidsMeal, additionalAttendee, veggieMeal  
																							 FROM ".ATTENDEES_TABLE." 
																							 WHERE id = %d", $attendeeID));
		$sql = "SELECT id FROM ".ATTENDEES_TABLE." 
		 	WHERE (id IN (SELECT attendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE associatedAttendeeID = %d) 
				OR id in (SELECT associatedAttendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE attendeeID = %d)) 
				 AND additionalAttendee = 'Y'";
		$newRsvps = $wpdb->get_results($wpdb->prepare($sql, $attendeeID, $attendeeID));
		
		
		$form = "<script type=\"text/javascript\" language=\"javascript\" src=\"".get_option("siteurl")."/wp-content/plugins/rsvp/jquery.js\"></script>\r\n";
		$form .= "<script type=\"text/javascript\" language=\"javascript\" src=\"".get_option("siteurl")."/wp-content/plugins/rsvp/jquery-validate/jquery.validate.min.js\"></script>";
		$form .= "<script type=\"text/javascript\" language=\"javascript\">\r\n
								$(document).ready(function(){
									jQuery.validator.addMethod(\"customNote\", function(value, element) {
							      if(($(\"#additionalRsvp\").val() > 0) && ($(\"#note\").val() == \"\")) {
							        return false;
							      }

							      return true;
							    }, \"<br />Please enter an email address that we can use to contact you about the extra guest.  We have to keep a pretty close eye on the number of attendees.  Thanks!\");
							
									$(\"#rsvpForm\").validate({\r\n
										rules: {
											note: \"customNote\",
											newAttending1LastName:  \"required\",
											newAttending1FirstName: \"required\", 
											newAttending2LastName:  \"required\",
											newAttending2FirstName: \"required\",
											newAttending3LastName:  \"required\",
											newAttending3FirstName: \"required\"
										},
										messages: {
											note: \"<br />If you are adding additional RSVPs please enter your email address in case we have questions\",
											newAttending1LastName:  \"<br />Please enter a last name\",
											newAttending1FirstName: \"<br />Please enter a first name\", 
											newAttending2LastName:  \"<br />Please enter a last name\",
											newAttending2FirstName: \"<br />Please enter a first name\",
											newAttending3LastName:  \"<br />Please enter a last name\",
											newAttending3FirstName: \"<br />Please enter a first name\"
										}
									});
								});
						</script>\r\n";
		$form .= "<style text/css>\r\n".
							"	label.error { font-weight: bold; clear:both;}\r\n".
							"	input.error, textarea.error { border: 2px solid red; }\r\n".
							"</style>\r\n";
		$form .= "<form id=\"rsvpForm\" name=\"rsvpForm\" method=\"post\">\r\n";
		$form .= "	<input type=\"hidden\" name=\"attendeeID\" value=\"".$attendeeID."\" />\r\n";
		$form .= "	<input type=\"hidden\" name=\"rsvpStep\" value=\"handleRsvp\" />\r\n";
		$form .= "<table cellpadding=\"2\" cellspacing=\"0\" border=\"0\">\r\n";
		$yesVerbiage = ((trim(get_option(OPTION_YES_VERBIAGE)) != "") ? get_option(OPTION_YES_VERBIAGE) : 
			"Yes, of course I will be there! Who doesn't like family, friends, weddings, and a good time?");
		$noVerbiage = ((trim(get_option(OPTION_NO_VERBIAGE)) != "") ? get_option(OPTION_NO_VERBIAGE) : 
				"Um, unfortunately, there is a Star Trek marathon on that day that I just cannot miss.");
		$kidsVerbiage = ((trim(get_option(OPTION_KIDS_MEAL_VERBIAGE)) != "") ? get_option(OPTION_KIDS_MEAL_VERBIAGE) : 
						"We have the option of getting cheese pizza for the kids (and only kids).  Do you want pizza instead of \"adult food?\"");
		$veggieVerbiage = ((trim(get_option(OPTION_VEGGIE_MEAL_VERBIAGE)) != "") ? get_option(OPTION_VEGGIE_MEAL_VERBIAGE) : 
						"We also have the option of getting individual vegetarian meals instead of the fish or meat.  Would you like a vegetarian dinner?");
		$noteVerbiage = ((trim(get_option(OPTION_NOTE_VERBIAGE)) != "") ? get_option(OPTION_NOTE_VERBIAGE) : 
			"If you have any <strong style=\"color:red;\">food allergies</strong>, please indicate what they are in the &quot;notes&quot; section below.  Or, if you just want to send us a note, please feel free.  If you have any questions, please send us an email at <a href=\"mailto:rsvp@janaandmike.com\">rsvp@janaandmike.com</a>.");
		$form .= "  <tr>\r\n
									<td align=\"left\">So, how about it?</td>
								</tr>\r\n
								<tr>\r\n
									<td colspan=\"2\" align=\"left\"><input type=\"radio\" name=\"mainRsvp\" value=\"Y\" id=\"mainRsvpY\" ".
										(($attendee->rsvpStatus == "No") ? "" : "checked=\"checked\"")." /> - <label for=\"mainRsvpY\">".htmlentities($yesVerbiage)."</label></td>
								</tr>\r\n
								<tr>\r\n
									<td align=\"left\" colspan=\"2\"><input type=\"radio\" name=\"mainRsvp\" value=\"N\" id=\"mainRsvpN\" ".
												(($attendee->rsvpStatus == "No") ? "checked=\"checked\"" : "")." > - 
												<label for=\"mainRsvpN\">".htmlentities($noVerbiage)."</label></td>
								</tr>";		
		if(get_option(OPTION_HIDE_KIDS_MEAL) != "Y") {		
			$form .= "	<tr><td colspan=\"2\"><hr /></td></tr>\r\n
									<tr>\r\n
										<td colspan=\"2\" align=\"left\">".htmlentities($kidsVerbiage)."</td>
									</tr>\r\n
									<tr>\r\n
										<td align=\"center\" colspan=\"2\"><input type=\"radio\" name=\"mainKidsMeal\" value=\"Y\" id=\"mainKidsMealY\" 
										 	".(($attendee->kidsMeal == "Y") ? "checked=\"checked\"" : "")." /> <label for=\"mainKidsMealY\">Yes</label> 
												<input type=\"radio\" name=\"mainKidsMeal\" value=\"N\" id=\"mainKidsMealN\" 
												".(($attendee->kidsMeal == "Y") ? "" : "checked=\"checked\"")." /> <label for=\"mainKidsMealN\">No</label></td>
									</tr>";
		}
		
		if(get_option(OPTION_HIDE_VEGGIE) != "Y") {		
			$form .= "	<tr><td colspan=\"2\"><hr /></td></tr>\r\n
									<tr>\r\n
										<td align=\"left\" colspan=\"2\">".htmlentities($veggieVerbiage)."</td> 
									</tr>\r\n
									<tr>\r\n
										<td align=\"center\" colspan=\"2\"><input type=\"radio\" name=\"mainVeggieMeal\" value=\"Y\" id=\"mainVeggieMealY\"
										 		".(($attendee->veggieMeal == "Y") ? "checked=\"checked\"" : "")."/> <label for=\"mainVeggieMealY\">Yes</label> 
												<input type=\"radio\" name=\"mainVeggieMeal\" value=\"N\" id=\"mainVeggieMealN\" 
												".(($attendee->veggieMeal == "Y") ? "" : "checked=\"checked\"")." /> <label for=\"mainVeggieMealN\">No</label></td>
									</tr>\r\n";
		}
		
		$form .= " <tr><td><br /></td></tr>\r\n
							 <tr>
									<td valign=\"top\" align=\"left\" colspan=\"2\">".$noteVerbiage."</td>
								</tr>
								<tr>
									<td colspan=\"2\"><textarea name=\"note\" id=\"note\" rows=\"7\" cols=\"50\">".htmlentities($attendee->note)."</textarea></td>
							 </tr>";
		$form .= "</table>\r\n";
		
		$sql = "SELECT id, firstName, lastName FROM ".ATTENDEES_TABLE." 
		 	WHERE (id IN (SELECT attendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE associatedAttendeeID = %d) 
				OR id in (SELECT associatedAttendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE attendeeID = %d)) 
				 AND rsvpStatus <> 'NoResponse'";
		$rsvpd = $wpdb->get_results($wpdb->prepare($sql, $attendeeID, $attendeeID));
		if(count($rsvpd) > 0) {
			$form .= "<p>The following people associated with you have already registered: ";
			foreach($rsvpd as $r) {
				$form .= "<br />".htmlentities($r->firstName." ".$r->lastName);
			}
			$form .= "</p>\r\n";
		}
		
		$sql = "SELECT id, firstName, lastName FROM ".ATTENDEES_TABLE." 
		 	WHERE (id IN (SELECT attendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE associatedAttendeeID = %d) 
				OR id in (SELECT associatedAttendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE attendeeID = %d)) 
				 AND rsvpStatus = 'NoResponse'";
		
		$associations = $wpdb->get_results($wpdb->prepare($sql, $attendeeID, $attendeeID));
		if(count($associations) > 0) {
			$form .= "<h3>The following people are associated with you.  At this time you can RSVP for them as well.</h3>";
			foreach($associations as $a) {
				$form .= "<div style=\"text-align:left;border-top: 1px solid;\">\r\n
								<p><label for=\"rsvpFor".$a->id."\">RSVP for ".htmlentities($a->firstName." ".$a->lastName)."?</label> 
										<input type=\"checkbox\" name=\"rsvpFor".$a->id."\" id=\"rsvpFor".$a->id."\" value=\"Y\" /></p>";
				
				$form .= "<table cellpadding=\"2\" cellspacing=\"0\" border=\"0\">\r\n";
				$form .= "  <tr>\r\n
											<td align=\"left\">Will ".htmlentities($a->firstName)." be attending?</td>\r\n
											<td align=\"left\"><input type=\"radio\" name=\"attending".$a->id."\" value=\"Y\" id=\"attending".$a->id."Y\" checked=\"checked\" /> 
																				<label for=\"attending".$a->id."Y\">Yes</label> 
													<input type=\"radio\" name=\"attending".$a->id."\" value=\"N\" id=\"attending".$a->id."N\"> <label for=\"attending".$a->id."N\">No</label></td>
										</tr>";
				
				if(get_option(OPTION_HIDE_KIDS_MEAL) != "Y") {		
					$form .= "	<tr>
												<td align=\"left\">Does ".htmlentities($a->firstName)." need a kids meal?&nbsp;</td> 
												<td align=\"left\"><input type=\"radio\" name=\"attending".$a->id."KidsMeal\" value=\"Y\" id=\"attending".$a->id."KidsMealY\" /> 
																					<label for=\"attending".$a->id."KidsMealY\">Yes</label> 
														<input type=\"radio\" name=\"attending".$a->id."KidsMeal\" value=\"N\" id=\"attending".$a->id."KidsMealN\" checked=\"checked\" /> 
														<label for=\"attending".$a->id."KidsMealN\">No</label></td>
											</tr>";
				}
				
				if(get_option(OPTION_HIDE_VEGGIE) != "Y") {		
					$form .= "	<tr>
												<td align=\"left\">Does ".htmlentities($a->firstName)." need a vegetarian meal?&nbsp;</td> 
												<td align=\"left\"><input type=\"radio\" name=\"attending".$a->id."VeggieMeal\" value=\"Y\" id=\"attending".$a->id."VeggieMealY\" /> 
																					<label for=\"attending".$a->id."VeggieMealY\">Yes</label> 
														<input type=\"radio\" name=\"attending".$a->id."VeggieMeal\" value=\"N\" id=\"attending".$a->id."VeggieMealN\" checked=\"checked\" /> 
														<label for=\"attending".$a->id."VeggieMealN\">No</label></td>
											</tr>";
				}
				$form .= "</table>\r\n";
				$form .= "</div>\r\n";
			}
		}
		$form .= "<h3>Did we slip up and forget to invite someone? If so, please add him or her here:</h3>\r\n";
		$form .= "<div id=\"additionalRsvpContainer\">\r\n
								<input type=\"hidden\" name=\"additionalRsvp\" id=\"additionalRsvp\" value=\"".count($newRsvps)."\" />
								<div style=\"text-align:right\"><img 
									src=\"".get_option("siteurl")."/wp-content/plugins/rsvp/plus.png\" width=\"24\" height=\"24\" border=\"0\" id=\"addRsvp\" /></div>
		
							</div>";
							
		$form .= "<p><input type=\"submit\" value=\"RSVP\" /></p>\r\n";
		$form .= "<script type=\"text/javascript\" language=\"javascript\">\r\n
								$(document).ready(function() {
									$(\"#addRsvp\").click(function() {
										handleAddRsvpClick();
									});
								});
								
								function handleAddRsvpClick() {
									var numAdditional = $(\"#additionalRsvp\").val();
									numAdditional++;
									if(numAdditional > 3) {
										alert('You have already added 3 additional rsvp\'s you can add no more.');
									} else {
										$(\"#additionalRsvpContainer\").append(\"<div style=\\\"text-align:left;border-top: 1px solid;\\\">\" + \r\n
												\"<table cellpadding=\\\"2\\\" cellspacing=\\\"0\\\" border=\\\"0\\\">\" + \r\n
													\"<tr>\" + \r\n
													\"	<td align=\\\"left\\\">Person's first name&nbsp;</td>\" + \r\n 
													\"  <td align=\\\"left\\\"><input type=\\\"text\\\" name=\\\"newAttending\" + numAdditional + \"FirstName\\\" id=\\\"newAttending\" + numAdditional + \"FirstName\\\" /></td>\" + \r\n
										  		\"</tr>\" + \r\n
													\"<tr>\" + \r\n
													\"	<td align=\\\"left\\\">Person's last name</td>\" + \r\n 
													\"  <td align=\\\"left\\\"><input type=\\\"text\\\" name=\\\"newAttending\" + numAdditional + \"LastName\\\" id=\\\"newAttending\" + numAdditional + \"LastName\\\" /></td>\" + \r\n
													\"</tr>\" + \r\n
										  		\"<tr>\" + \r\n
														\"<td align=\\\"left\\\">Will this person be attending?&nbsp;</td>\" + \r\n
														\"<td align=\\\"left\\\">\" + 
															\"<input type=\\\"radio\\\" name=\\\"newAttending\" + numAdditional + \"\\\" value=\\\"Y\\\" id=\\\"newAttending\" + numAdditional + \"Y\\\" checked=\\\"checked\\\" /> \" + 
																							\"<label for=\\\"newAttending\" + numAdditional + \"Y\\\">Yes</label> \" + 
																\"<input type=\\\"radio\\\" name=\\\"newAttending\" + numAdditional + \"\\\" value=\\\"N\\\" id=\\\"newAttending\" + numAdditional + \"N\\\"> <label for=\\\"newAttending\" + numAdditional + \"N\\\">No</label></td>\" + 
													\"</tr>\" + 
													\"<tr>\" + 
														\"<td align=\\\"left\\\">Does this person need a kids meal?&nbsp;</td> \" + 
														\"<td align=\\\"left\\\"><input type=\\\"radio\\\" name=\\\"newAttending\" + numAdditional + \"KidsMeal\\\" value=\\\"Y\\\" id=\\\"newAttending\" + numAdditional + \"KidsMealY\\\" /> \" + 
																	\"<label for=\\\"newAttending\" + numAdditional + \"KidsMealY\\\">Yes</label> \" + 
																\"<input type=\\\"radio\\\" name=\\\"newAttending\" + numAdditional + \"KidsMeal\\\" value=\\\"N\\\" id=\\\"newAttending\" + numAdditional + \"KidsMealN\\\" checked=\\\"checked\\\" /> \" + 
																\"<label for=\\\"newAttending\" + numAdditional + \"KidsMealN\\\">No</label></td>\" + 
													\"</tr>\" + 
													\"<tr>\" + 
														\"<td align=\\\"left\\\">Does this person need a vegetarian meal?&nbsp;</td> \" + 
														\"<td align=\\\"left\\\"><input type=\\\"radio\\\" name=\\\"newAttending\" + numAdditional + \"VeggieMeal\\\" value=\\\"Y\\\" id=\\\"newAttending\" + numAdditional + \"VeggieMealY\\\" /> \" + 
																							\"<label for=\\\"newAttending\" + numAdditional + \"VeggieMealY\\\">Yes</label> \" + 
																\"<input type=\\\"radio\\\" name=\\\"newAttending\" + numAdditional + \"VeggieMeal\\\" value=\\\"N\\\" id=\\\"newAttending\" + numAdditional + \"VeggieMealN\\\" checked=\\\"checked\\\" /> \" + 
																\"<label for=\\\"newAttending\" + numAdditional + \"VeggieMealN\\\">No</label></td>\" + 
													\"</tr>\" + 
												\"</table>\" + 
												\"<br />\" + 
											\"</div>\");
										$(\"#additionalRsvp\").val(numAdditional);
									}
								}
							</script>\r\n";
		$form .= "</form>\r\n";
		
		return $form;
	}
	
	function frontend_rsvp_thankyou() {
		$customTy = get_option(OPTION_THANKYOU);
		if(!empty($customTy)) {
			return nl2br($customTy);
		} else {
			return "<p>Thank you for RSVPing</p>";
		}
	}
	
	function rsvp_chomp_name($name, $maxLength) {
		for($i = $maxLength; $maxLength >= 1; $i--) {
			if(strlen($name) >= $i) {
				return substr($name, 0, $i);
			}
		}
	}
	
	function rsvp_frontend_greeting() {
		$customGreeting = get_option(OPTION_GREETING);
		$output = "<p>Please enter your first and last name to RSVP.</p>";
		if(!empty($customGreeting)) {
			$output = nl2br($customGreeting);
		} 
		$output .= "<script type=\"text/javascript\" language=\"javascript\" src=\"".get_option("siteurl")."/wp-content/plugins/rsvp/jquery.js\"></script>";
		$output .= "<script type=\"text/javascript\" language=\"javascript\" src=\"".get_option("siteurl")."/wp-content/plugins/rsvp/jquery-validate/jquery.validate.min.js\"></script>";
		$output .= "<script type=\"text/javascript\">$(document).ready(function(){ $(\"#rsvp\").validate({rules: {firstName: \"required\",lastName: \"required\"}, messages: {firstName: \"<br />Please enter your first name\", lastName: \"<br />Please enter your last name\"}});});</script>";
		$output .= "<style text/css>\r\n".
			"	label.error { font-weight: bold; clear:both;}\r\n".
			"	input.error { border: 2px solid red; }\r\n".
			"</style>\r\n";
		$output .= "<form name=\"rsvp\" method=\"post\" id=\"rsvp\">\r\n";
		$output .= "	<input type=\"hidden\" name=\"rsvpStep\" value=\"find\" />";
		$output .= "<p><label for=\"firstName\">First Name:</label> 
									 <input type=\"text\" name=\"firstName\" id=\"firstName\" size=\"30\" value=\"".htmlentities($_SESSION['rsvpFirstName'])."\" class=\"required\" /></p>\r\n";
		$output .= "<p><label for=\"lastName\">Last Name:</label> 
									 <input type=\"text\" name=\"lastName\" id=\"lastName\" size=\"30\" value=\"".htmlentities($_SESSION['rsvpLastName'])."\" class=\"required\" /></p>\r\n";
		$output .= "<p><input type=\"submit\" value=\"Register\" /></p>";
		$output .= "</form>\r\n";
		
		return $output;
	}

	function rsvp_modify_menu() {
		
		add_options_page('RSVP Options',	//page title
	                   'RSVP Options',	//subpage title
	                   'manage_options',	//access
	                   'rsvp-options',		//current file
	                   'rsvp_admin_guestlist_options'	//options function above
	                   );
		add_menu_page("RSVP Plugin", 
									"RSVP Plugin", 
									"publish_posts", 
									"rsvp-top-level", 
									"rsvp_admin_guestlist");
		add_submenu_page("rsvp-top-level", 
										 "Add Guest",
										 "Add Guest",
										 "publish_posts", 
										 "rsvp-admin-guest",
										 "rsvp_admin_guest");
		add_submenu_page("rsvp-top-level", 
										 "RSVP Export",
										 "RSVP Export",
										 "publish_posts", 
										 "rsvp-admin-export",
										 "rsvp_admin_export");
		add_submenu_page("rsvp-top-level", 
										 "RSVP Import",
										 "RSVP Import",
										 "publish_posts", 
										 "rsvp-admin-import",
										 "rsvp_admin_import");
	}
	
	add_action('admin_menu', 'rsvp_modify_menu');
	add_filter('the_content', 'rsvp_frontend_handler');
	register_activation_hook(__FILE__,'rsvp_database_setup');
?>