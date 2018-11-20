<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 *
*
* @package    blocks
* @subpackage paperattendance
* @copyright  2016 Hans Jeria (hansjeria@gmail.com)
* @copyright  2017 Mark Michaelsen (mmichaelsen678@gmail.com)
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

class block_paperattendance extends block_base {
	
	/** @var int This allows for multiple navigation trees */
    public static $navcount;
    /** @var string The name of the block */
    public $blockname = null;
    /** @var bool A switch to indicate whether content has been generated or not. */
    protected $contentgenerated = false;
    /** @var bool|null variable for checking if the block is docked*/
    protected $docked = null;
    
    function init() {
    	$this->blockname = get_class($this);
    	$this->title = get_string("pluginname", "local_paperattendance");
    }
    function has_config() {
    	return true;
    }
    
    function instance_allow_multiple() {
    	return false;
    }
    
    function applicable_formats() {
    	return array("all" => true);
    }
    
    function instance_allow_config() {
    	return true;
    }
    
    function  instance_can_be_hidden() {
    	return false;
    }
    
    function instance_can_be_docked() {
    	return (parent::instance_can_be_docked() && (empty($this->config->enabledock) || $this->config->enabledock=="yes"));
    }
    
    function get_required_javascript() {
    	parent::get_required_javascript();
    	$arguments = array(
    			"instanceid" => $this->instance->id
    	);
    	$this->page->requires->string_for_js("viewallcourses", "moodle");
    	//$this->page->requires->js_call_amd("block_navigation/navblock", "init", $arguments);
    	$this->page->requires->jquery();
    	$this->page->requires->jquery_plugin ( "ui" );
    	$this->page->requires->jquery_plugin ( "ui-css" );
    }
    
    protected function paperattendance() {
 		global $COURSE, $PAGE, $CFG, $DB, $USER;

 		
 		$categoryid = optional_param("categoryid", $CFG->block_paperattendance_categoryid, PARAM_INT);
 		$context = $PAGE->context;
 		
 		//new feature for the secretary to see printsearch and upload from everywhere
 		$sqlcategory = "SELECT cc.*
					FROM {course_categories} cc
					INNER JOIN {role_assignments} ra ON (ra.userid = ?)
					INNER JOIN {role} r ON (r.id = ra.roleid AND r.shortname = ?)
					INNER JOIN {context} co ON (co.id = ra.contextid  AND  co.instanceid = cc.id  )";
 		
 		$categoryparams = array($USER->id, "secrepaper");
 		
 		$categorys = $DB->get_records_sql($sqlcategory, $categoryparams);
 		$categoryscount = count($categorys);
 		$is_secretary = 0;
 		if($categoryscount > 0){
 			$is_secretary = 1;
 		}
 		
 		$root = array();
		
 		if(has_capability("local/paperattendance:upload", $context) || $is_secretary){
 			$root["upload"] = array();
			$root["upload"]["string"] = get_string("uploadpaperattendance", "block_paperattendance");
 			$root["upload"]["url"] = 	new moodle_url("/local/paperattendance/upload.php", array("courseid" => $COURSE->id,"categoryid" => $categoryid));
 			$root["upload"]["icon"] = 	"i/backup";
 		}
 		
 		if(has_capability("local/paperattendance:modules", $context)){
 			$root["modules"] = array();
 			$root["modules"]["string"] = get_string("modulespaperattendance", "block_paperattendance");
 			$root["modules"]["url"] =	 new moodle_url("/local/paperattendance/modules.php");
 			$root["modules"]["icon"] =	 "i/calendar";
 		}
 		
 		if(has_capability("local/paperattendance:printsearch", $context) || $is_secretary){
 			$root["search"] = array();
 			$root["search"]["string"] = get_string("printsearchpaperattendance", "block_paperattendance");
 			$root["search"]["url"] =	new moodle_url("/local/paperattendance/printsearch.php", array("courseid" => $COURSE->id,"categoryid" => $categoryid));
 			$root["search"]["icon"] =	"t/print";
 		}
 		
 		if(has_capability("local/paperattendance:missingpages", $context) || $is_secretary){
 			$root["missing"] = array();
 			$root["missing"]["string"] = get_string("missingpagespaperattendance", "block_paperattendance");
 			$root["missing"]["url"] =	 new moodle_url("/local/paperattendance/missingpages.php");
 			$root["missing"]["icon"] =	 "i/warning";
 		}
 			
 		if($COURSE->id > 1){
 			if(has_capability("local/paperattendance:print", $context) || has_capability("local/paperattendance:printsecre", $context)){
 				$root["print"] = array();
 				if($COURSE->idnumber != NULL){
     				$root["print"]["string"] = get_string("printpaperattendance", "block_paperattendance");
     				$root["print"]["url"] =	   new moodle_url("/local/paperattendance/print.php", array("courseid" => $COURSE->id, "categoryid"  => $categoryid));
 				}else{
					//$root["print"]["string"] = get_string("notomegacourse", "block_paperattendance");
					$root["print"]["string"] = get_string("printpaperattendance", "block_paperattendance");
					$root["print"]["url"] =	   new moodle_url("/local/paperattendance/print.php", array("courseid" => $COURSE->id, "categoryid"  => $categoryid));
 				}
 				$root["print"]["icon"] =   "e/print";
 			}
 			if(has_capability("local/paperattendance:history", $context)){
 				$root["history"] = array();
 				if($COURSE->idnumber != NULL){
     				$root["history"]["string"] = get_string("historypaperattendance", "block_paperattendance");
     				$root["history"]["url"] =	 new moodle_url("/local/paperattendance/history.php", array("courseid" => $COURSE->id));
 				}else{
					$root["history"]["url"] =	 new moodle_url("/local/paperattendance/history.php", array("courseid" => $COURSE->id));
					$root["history"]["string"] = get_string("historypaperattendance", "block_paperattendance");
 				    //$root["history"]["string"] = get_string("notomegacourse", "block_paperattendance");
 				}
 				$root["history"]["icon"] =	 "i/grades";
 				
 				$root["discussion"] = array();
 				if($COURSE->idnumber != NULL){
     				$root["discussion"]["string"] = get_string("discussionpaperattendance", "block_paperattendance");
    				$root["discussion"]["url"] =	new moodle_url("/local/paperattendance/discussion.php", array("courseid" => $COURSE->id));
 				}else{
					$root["discussion"]["url"] =	new moodle_url("/local/paperattendance/discussion.php", array("courseid" => $COURSE->id));
					$root["discussion"]["string"] = get_string("discussionpaperattendance", "block_paperattendance");
 				    //$root["discussion"]["string"] = get_string("notomegacourse", "block_paperattendance");
 				}
				$root["discussion"]["icon"] =	"i/cohort";
			}
		}
 		
 		if(empty($root)) {
 			return false;
 		}
 		$root["string"] = get_string("paperattendance", "block_paperattendance");
 		$root["icon"] =   "attendance.png";
 		
 		return $root;
 	}
    
    function get_content() {
    	global $CFG, $PAGE;
    	
    	// Check if content is already generated. If so, doesn't do it again
    	if ($this->content !== null) {
    		return $this->content;
    	}	
    	// Check if an user is logged in. Block doesn't render if not.
    	if (!isloggedin()) {
    		return false;
    	}
    	$this->content = new stdClass();
    	$menu = array();
    		 
    	if($paperattendance = $this->paperattendance()) {
    		$menu[] = $paperattendance;
    	}
    	$this->content->text = $this->block_paperattendance_renderer($menu);
    	// Set content generated to true so that we know it has been done
    	$this->contentgenerated = true;
    	return $this->content;
    }
    
    /*
     * Produces a list of collapsible lists for each plugin to be displayed
     * 
     * @param array $plugins containing data sub-arrays of every plugin
     * @return html string to be inserted directly into the block
     */
    protected function block_paperattendance_renderer($plugins) {
    	global $OUTPUT, $CFG;
    	$content = array();
    	// For each plugin to be shown, make a collapsible list
    	foreach($plugins as $plugin) {
    		$elementhtml = "";
    		
    		// For each element in the plugin, create a collapsable list element
    		foreach($plugin as $element => $values) {
				$elementhtml = "";
    			// The "string" element is the plugin's name
    			if($element != "string" && $element != "settings" && $element != "icon") {
					$elementhtml .= html_writer::tag("li", 
						html_writer::tag("a", 
							$OUTPUT->pix_icon($values["icon"], "")." ".$values["string"], 
							array("href" => $values["url"]
						)), array("style" => "min-width:100%;"));
				} 
				$content[] = $elementhtml;
			}
    	}
    	
    	return html_writer::tag("ul", implode("", $content), array("class" => "nav nav-list"));
    }
    
    public function get_aria_role() {
    	return "navigation";
    }
}
